<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Invitations\InvitationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private InvitationService $invitationService) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'invitation' => ['nullable', 'string', 'max:64'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $workspace = Workspace::query()->create([
                'name' => sprintf('%s Workspace #%d', $user->name, $user->id),
                'display_name' => sprintf('%s Workspace', $user->name),
            ]);

            $workspace->forceFill(['owner_id' => $user->id])->save();

            $adminRole = Role::query()->firstOrCreate(
                ['name' => 'admin', 'workspace_id' => null],
                [
                    'display_name' => 'Admin',
                    'description' => 'Default admin role for newly registered users.',
                ],
            );

            $user->addRole($adminRole, $workspace);
            $user->switchWorkspace($workspace);

            $invitationCode = $input['invitation'] ?? null;

            if ($invitationCode) {
                $invitation = Invitation::query()
                    ->where('code', $invitationCode)
                    ->first();

                if ($invitation) {
                    $this->invitationService->accept($invitation, $user);
                }
            }

            return $user;
        });
    }
}
