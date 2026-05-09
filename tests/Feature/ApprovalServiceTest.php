<?php

use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteStatus;
use App\Models\ApprovalRule;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ApprovalService::class);
});

it('returns index data for workspace owner', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $owner->update(['current_workspace_id' => $workspace->id]);

    $approver = User::factory()->create();

    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $owner->id,
        'status' => QuoteStatus::PendingApproval,
    ]);

    $approvalRule = ApprovalRule::factory()->create([
        'workspace_id' => $workspace->id,
        'approver_id' => $approver->id,
        'is_active' => true,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $approvalRule->id,
        'approver_id' => $approver->id,
        'status' => QuoteApprovalStatus::Pending,
    ]);

    $data = $this->service->index($workspace, $owner);

    expect($data)->toHaveKeys(['pendingApprovals', 'rules', 'approvers', 'clients', 'currency']);
    expect($data['pendingApprovals'])->toHaveCount(1);
    expect($data['rules'])->toHaveCount(1);
    expect($data['clients'])->toHaveCount(1);
});

it('returns index data for non-owner approver', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

    $approver = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $otherApprover = User::factory()->create();

    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $owner->id,
        'status' => QuoteStatus::PendingApproval,
    ]);

    $approverRule = ApprovalRule::factory()->create([
        'workspace_id' => $workspace->id,
        'approver_id' => $approver->id,
        'is_active' => true,
    ]);

    $otherApproverRule = ApprovalRule::factory()->create([
        'workspace_id' => $workspace->id,
        'approver_id' => $otherApprover->id,
        'is_active' => true,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $approverRule->id,
        'approver_id' => $approver->id,
        'status' => QuoteApprovalStatus::Pending,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $otherApproverRule->id,
        'approver_id' => $otherApprover->id,
        'status' => QuoteApprovalStatus::Pending,
    ]);

    $data = $this->service->index($workspace, $approver);

    expect($data['pendingApprovals'])->toHaveCount(1); // only approver's approval
    expect($data['rules'])->toHaveCount(1); // only approver's rule
});

it('counts pending approvals for workspace owner', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $owner->update(['current_workspace_id' => $workspace->id]);

    $approver = User::factory()->create();

    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::PendingApproval,
    ]);

    $approvalRule = ApprovalRule::factory()->create([
        'workspace_id' => $workspace->id,
        'approver_id' => $approver->id,
        'is_active' => true,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $approvalRule->id,
        'approver_id' => $approver->id,
        'status' => QuoteApprovalStatus::Pending,
    ]);

    $count = $this->service->count($workspace, $owner);

    expect($count)->toBe(1);
});

it('counts pending approvals for non-owner approver', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

    $approver = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $otherApprover = User::factory()->create();

    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::PendingApproval,
    ]);

    $approverRule = ApprovalRule::factory()->create([
        'workspace_id' => $workspace->id,
        'approver_id' => $approver->id,
        'is_active' => true,
    ]);

    $otherApproverRule = ApprovalRule::factory()->create([
        'workspace_id' => $workspace->id,
        'approver_id' => $otherApprover->id,
        'is_active' => true,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $approverRule->id,
        'approver_id' => $approver->id,
        'status' => QuoteApprovalStatus::Pending,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $otherApproverRule->id,
        'approver_id' => $otherApprover->id,
        'status' => QuoteApprovalStatus::Pending,
    ]);

    $approverCount = $this->service->count($workspace, $approver);
    $ownerCount = $this->service->count($workspace, $owner);

    expect($approverCount)->toBe(1); // only approver's approval
    expect($ownerCount)->toBe(2); // all approvals
});

it('does not count non-pending approvals', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $owner->update(['current_workspace_id' => $workspace->id]);

    $approver = User::factory()->create();

    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::PendingApproval,
    ]);

    $approvalRule = ApprovalRule::factory()->create([
        'workspace_id' => $workspace->id,
        'approver_id' => $approver->id,
        'is_active' => true,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $approvalRule->id,
        'approver_id' => $approver->id,
        'status' => QuoteApprovalStatus::Approved,
    ]);

    QuoteApproval::factory()->create([
        'quote_id' => $quote->id,
        'approval_rule_id' => $approvalRule->id,
        'approver_id' => $approver->id,
        'status' => QuoteApprovalStatus::Rejected,
    ]);

    $count = $this->service->count($workspace, $owner);

    expect($count)->toBe(0);
});
