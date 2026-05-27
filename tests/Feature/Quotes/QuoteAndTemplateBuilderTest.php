<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureWorkspaceSettingsOnboarded;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;

test('authenticated workspace user can create a quote with nested sections and line items', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    // Add user to workspace via role_user table
    $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id') ??
                   DB::table('roles')->insertGetId([
                       'name' => 'admin',
                       'display_name' => 'Admin',
                       'description' => 'Default administrator role.',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

    DB::table('role_user')->insertOrIgnore([
        'role_id' => $adminRoleId,
        'user_id' => $user->id,
        'user_type' => get_class($user),
        'workspace_id' => $workspace->id,
    ]);

    $client = Client::factory()->for($workspace, 'workspace')->create();
    $catalogItem = CatalogItem::factory()->for($workspace, 'workspace')->create();
    $tax = Tax::factory()->for($workspace, 'workspace')->create();

    app(WorkspaceSettingsService::class)->syncDefaults($workspace);
    app(WorkspaceSettingsService::class)->updateGroup($workspace, 'quotes', [
        'quote_prefix' => 'ACM',
        'quote_number_sequence' => 7,
        'quote_validity_days' => 45,
    ]);

    $response = $this->actingAs($user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post('/quotes', [
            'title' => 'Electrical Installation Proposal',
            'status' => 'draft',
            'client_id' => $client->id,
            'currency' => 'USD',
            'valid_until' => now()->addDays(30)->toDateString(),
            'sections' => [
                [
                    'title' => 'Services',
                    'line_items' => [
                        [
                            'catalog_item_id' => $catalogItem->id,
                            'name' => 'Wiring and installation',
                            'description' => 'Full wiring package',
                            'quantity' => 2,
                            'unit' => 'unit',
                            'unit_price' => 450,
                            'discount_percent' => 0,
                            'subtotal' => 900,
                            'tax_amount' => 67.5,
                            'total' => 967.5,
                            'taxes' => [
                                [
                                    'tax_id' => $tax->id,
                                    'tax_label' => $tax->name,
                                    'tax_rate' => $tax->rate,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    $quote = Quote::query()->latest('id')->first();

    expect($quote)->not->toBeNull();

    $response
        ->assertRedirect(route('quotes.edit', $quote));

    $this->assertDatabaseHas('quotes', [
        'id' => $quote?->id,
        'workspace_id' => $workspace->id,
        'title' => 'Electrical Installation Proposal',
        'client_id' => $client->id,
    ]);

    $this->assertDatabaseHas('quote_sections', [
        'quote_id' => $quote?->id,
        'title' => 'Services',
    ]);

    $this->assertDatabaseHas('quote_line_items', [
        'quote_id' => $quote?->id,
    ]);
});

test('quote creation uses workspace sequence and validity settings when number and valid until are omitted', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    // Add user to workspace via role_user table
    $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id') ??
                   DB::table('roles')->insertGetId([
                       'name' => 'admin',
                       'display_name' => 'Admin',
                       'description' => 'Default administrator role.',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

    DB::table('role_user')->insertOrIgnore([
        'role_id' => $adminRoleId,
        'user_id' => $user->id,
        'user_type' => get_class($user),
        'workspace_id' => $workspace->id,
    ]);

    $user->forceFill(['current_workspace_id' => $workspace->id])->save();
    $user->refresh();

    app(WorkspaceSettingsService::class)->syncDefaults($workspace);
    app(WorkspaceSettingsService::class)->updateGroup($workspace, 'quotes', [
        'quote_prefix' => 'ACM',
        'quote_number_sequence' => 7,
        'quote_validity_days' => 45,
    ]);

    $response = $this->actingAs($user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post('/quotes', [
            'title' => 'Test Quote',
            'status' => 'draft',
            'client_id' => Client::factory()->for($workspace, 'workspace')->create()->id,
            'currency' => 'USD',
            'sections' => [
                [
                    'title' => 'Services',
                    'line_items' => [
                        [
                            'catalog_item_id' => null,
                            'name' => 'Default service',
                            'description' => null,
                            'quantity' => 1,
                            'unit' => null,
                            'unit_price' => 100,
                            'discount_percent' => 0,
                            'subtotal' => 100,
                            'tax_amount' => 0,
                            'total' => 100,
                            'is_optional' => false,
                            'notes' => null,
                            'taxes' => [],
                        ],
                    ],
                ],
            ],
        ]);

    $quote = Quote::query()->latest('id')->first();

    expect($quote)->not->toBeNull();
    expect($quote?->number)->toBe(sprintf('ACM-%d-001', (int) now()->year));
    expect($quote?->valid_until?->toDateString())->toBe(now()->addDays(45)->toDateString());

    $response->assertRedirect(route('quotes.edit', $quote));
});

test('quote create persists layout_snapshot when request sends layout key', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    // Add user to workspace via role_user table
    $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id') ??
                   DB::table('roles')->insertGetId([
                       'name' => 'admin',
                       'display_name' => 'Admin',
                       'description' => 'Default administrator role.',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

    DB::table('role_user')->insertOrIgnore([
        'role_id' => $adminRoleId,
        'user_id' => $user->id,
        'user_type' => get_class($user),
        'workspace_id' => $workspace->id,
    ]);

    $user->forceFill(['current_workspace_id' => $workspace->id])->save();
    $user->refresh();

    $client = Client::factory()->for($workspace, 'workspace')->create();

    $layout = [
        'version' => 1,
        'theme' => [
            'primaryColor' => '#0EA5E9',
            'fontFamily' => 'inter',
        ],
        'blocks' => [
            [
                'id' => 'header-1',
                'type' => 'header',
                'visible' => true,
                'locked' => true,
                'label' => 'Header',
                'config' => [
                    'layout' => 'logo-left-details-right',
                    'showLogo' => true,
                    'showQuoteNumber' => true,
                    'showIssueDate' => true,
                    'showValidUntil' => true,
                    'showExpiryCountdown' => false,
                    'padding' => 'md',
                    'background' => null,
                    'border' => [
                        'style' => 'solid',
                        'color' => null,
                        'width' => 'thin',
                        'sides' => 'bottom',
                        'radius' => 'none',
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post('/quotes', [
            'title' => 'Layout key persistence',
            'status' => 'draft',
            'currency' => 'USD',
            'client_id' => $client->id,
            'layout' => $layout,
            'sections' => [
                [
                    'title' => 'Services',
                    'line_items' => [
                        [
                            'catalog_item_id' => null,
                            'name' => 'Service',
                            'description' => null,
                            'quantity' => 1,
                            'unit' => null,
                            'unit_price' => 100,
                            'discount_percent' => 0,
                            'subtotal' => 100,
                            'tax_amount' => 0,
                            'total' => 100,
                            'is_optional' => false,
                            'notes' => null,
                            'taxes' => [],
                        ],
                    ],
                ],
            ],
        ]);

    $quote = Quote::query()->latest('id')->first();

    expect($quote)->not->toBeNull();
    expect($quote?->layout_snapshot)->toBe($layout);

    $response->assertRedirect(route('quotes.edit', $quote));
});

test('authenticated workspace manager can create quote templates', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    // Add user to workspace via role_user table
    $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id') ??
                   DB::table('roles')->insertGetId([
                       'name' => 'admin',
                       'display_name' => 'Admin',
                       'description' => 'Default administrator role.',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

    DB::table('role_user')->insertOrIgnore([
        'role_id' => $adminRoleId,
        'user_id' => $user->id,
        'user_type' => get_class($user),
        'workspace_id' => $workspace->id,
    ]);

    $user->forceFill(['current_workspace_id' => $workspace->id])->save();
    $user->refresh();

    $catalogItem = CatalogItem::factory()->for($workspace, 'workspace')->create();
    $tax = Tax::factory()->for($workspace, 'workspace')->create();
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $response = $this->actingAs($user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post('/quote-templates', [
            'title' => 'Standard Electrical Template',
            'description' => 'Reusable quote skeleton for residential work',
            'industry' => 'Construction',
            'is_active' => true,
            'sections' => [
                [
                    'title' => 'Materials',
                    'line_items' => [
                        [
                            'catalog_item_id' => $catalogItem->id,
                            'name' => 'Material package',
                            'description' => 'Default material allocation',
                            'quantity' => 1,
                            'unit' => 'lot',
                            'unit_price' => 1200,
                            'discount_percent' => 0,
                            'is_optional' => false,
                            'taxes' => [
                                [
                                    'tax_id' => $tax->id,
                                    'tax_label' => $tax->name,
                                    'tax_rate' => $tax->rate,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    $template = QuoteTemplate::query()->latest('id')->first();

    expect($template)->not->toBeNull();

    $response
        ->assertRedirect(route('quote-templates.edit', $template));

    $this->assertDatabaseHas('quote_templates', [
        'id' => $template?->id,
        'workspace_id' => $workspace->id,
        'name' => 'Standard Electrical Template',
        'industry' => 'Construction',
    ]);

    $this->assertDatabaseHas('quote_template_sections', [
        'quote_template_id' => $template?->id,
        'title' => 'Materials',
    ]);

    $this->assertDatabaseHas('quote_template_line_items', [
        'quote_template_id' => $template?->id,
        'name' => 'Material package',
        'catalog_item_id' => $catalogItem->id,
    ]);
});

test('quote template stores layout json and quote copies it as layout snapshot when created from template', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    // Add user to workspace via role_user table
    $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id') ??
                   DB::table('roles')->insertGetId([
                       'name' => 'admin',
                       'display_name' => 'Admin',
                       'description' => 'Default administrator role.',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

    DB::table('role_user')->insertOrIgnore([
        'role_id' => $adminRoleId,
        'user_id' => $user->id,
        'user_type' => get_class($user),
        'workspace_id' => $workspace->id,
    ]);

    $user->forceFill(['current_workspace_id' => $workspace->id])->save();
    $user->refresh();

    $client = Client::factory()->for($workspace, 'workspace')->create();

    $layout = [
        'version' => 1,
        'theme' => [
            'primaryColor' => '#2563EB',
            'accentColor' => '#F59E0B',
            'backgroundColor' => '#FFFFFF',
            'fontFamily' => 'inter',
            'fontSize' => 'md',
            'borderRadius' => 'md',
            'headerStyle' => 'bordered',
        ],
        'blocks' => [],
    ];

    $templateResponse = $this->actingAs($user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post('/quote-templates', [
            'title' => 'Template With Layout',
            'description' => null,
            'industry' => null,
            'cover_message' => null,
            'notes' => null,
            'terms' => null,
            'layout' => $layout,
            'is_active' => true,
            'sections' => [
                [
                    'title' => 'Services',
                    'line_items' => [
                        [
                            'catalog_item_id' => null,
                            'name' => 'Base service',
                            'description' => null,
                            'quantity' => 1,
                            'unit' => null,
                            'unit_price' => 100,
                            'discount_percent' => 0,
                            'is_optional' => false,
                            'notes' => null,
                            'taxes' => [],
                        ],
                    ],
                ],
            ],
        ]);

    $template = QuoteTemplate::query()->latest('id')->first();

    expect($template)->not->toBeNull();
    expect($template?->layout)->toBe($layout);

    $templateResponse->assertRedirect(route('quote-templates.edit', $template));

    $quoteResponse = $this->actingAs($user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post('/quotes', [
            'title' => 'Quote using template layout',
            'status' => 'draft',
            'template_id' => $template?->id,
            'currency' => 'USD',
            'client_id' => $client->id,
            'sections' => [
                [
                    'title' => 'Services',
                    'line_items' => [
                        [
                            'catalog_item_id' => null,
                            'name' => 'Service line',
                            'description' => null,
                            'quantity' => 1,
                            'unit' => null,
                            'unit_price' => 100,
                            'discount_percent' => 0,
                            'subtotal' => 100,
                            'tax_amount' => 0,
                            'total' => 100,
                            'is_optional' => false,
                            'notes' => null,
                            'taxes' => [],
                        ],
                    ],
                ],
            ],
        ]);

    $quote = Quote::query()->latest('id')->first();

    expect($quote)->not->toBeNull();
    expect($quote?->layout_snapshot)->toBe($layout);

    $quoteResponse->assertRedirect(route('quotes.edit', $quote));

    $this->assertDatabaseHas('quote_templates', [
        'id' => $template?->id,
        'workspace_id' => $workspace->id,
        'name' => 'Template With Layout',
    ]);
});
