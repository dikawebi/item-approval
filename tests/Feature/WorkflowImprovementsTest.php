<?php

namespace Tests\Feature;

use App\Filament\Resources\ItemCreationRequests\ItemCreationRequestResource;
use App\Jobs\CreateReleasedProductInD365;
use App\Models\ItemCreationRequest;
use App\Models\User;
use App\Services\D365ODataClient;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Tests\TestCase;

class WorkflowImprovementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndUsersSeeder::class);
    }

    public function test_failed_job_moves_request_to_create_failed_and_notifies_trigger(): void
    {
        $requester = User::where('email', 'requester@example.com')->firstOrFail();
        $accounting = User::where('email', 'accounting@example.com')->firstOrFail();

        $request = ItemCreationRequest::create([
            'item_name' => 'Stuck Job Item',
            'unit' => 'pcs',
            'requested_by' => $requester->id,
            'creation_triggered_by' => $accounting->id,
            'status' => 'creating',
        ]);

        (new CreateReleasedProductInD365($request))->failed(new \RuntimeException('D365 exploded'));

        $this->assertSame('create_failed', $request->fresh()->status);
        $this->assertStringContainsString('D365 exploded', $request->fresh()->sync_error);
        $this->assertTrue(
            DatabaseNotification::where('notifiable_type', User::class)
                ->where('notifiable_id', $accounting->id)
                ->get()
                ->contains(fn ($n) => str_contains($n->data['title'] ?? '', 'D365 creation failed')),
            'Trigger user should get a failure notification'
        );
    }

    public function test_job_has_overlap_protection_per_request(): void
    {
        $request = ItemCreationRequest::create([
            'item_name' => 'Overlap Item',
            'unit' => 'pcs',
            'requested_by' => User::where('email', 'requester@example.com')->firstOrFail()->id,
            'status' => 'classified',
        ]);

        $middleware = (new CreateReleasedProductInD365($request))->middleware();

        $this->assertNotEmpty($middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
    }

    public function test_handle_returns_early_for_already_created_requests(): void
    {
        $request = ItemCreationRequest::create([
            'item_name' => 'Already Created',
            'unit' => 'pcs',
            'requested_by' => User::where('email', 'requester@example.com')->firstOrFail()->id,
            'status' => 'created',
            'assigned_item_number' => 'ITM-000001',
        ]);

        // Would throw / hit the network if it tried D365 — the guard must
        // return before any of that.
        (new CreateReleasedProductInD365($request))->handle(new D365ODataClient());

        $this->assertSame('created', $request->fresh()->status);
        $this->assertSame('ITM-000001', $request->fresh()->assigned_item_number);
    }

    public function test_audience_scope_limits_requesters_to_their_own(): void
    {
        $requester = User::where('email', 'requester@example.com')->firstOrFail();
        $other = User::factory()->create();
        $accounting = User::where('email', 'accounting@example.com')->firstOrFail();

        ItemCreationRequest::create(['item_name' => 'Mine', 'unit' => 'pcs', 'requested_by' => $requester->id, 'status' => 'pending']);
        ItemCreationRequest::create(['item_name' => 'Theirs', 'unit' => 'pcs', 'requested_by' => $other->id, 'status' => 'pending']);

        $this->assertSame(1, ItemCreationRequest::forAudience($requester)->count());
        $this->assertSame(2, ItemCreationRequest::forAudience($accounting)->count());
        $this->assertSame(2, ItemCreationRequest::forAudience(null)->count());
    }

    public function test_resource_query_eager_loads_requester(): void
    {
        $eagerLoads = ItemCreationRequestResource::getEloquentQuery()->getEagerLoads();

        $this->assertArrayHasKey('requestedBy', $eagerLoads);
    }

    public function test_similar_name_finder(): void
    {
        $requester = User::where('email', 'requester@example.com')->firstOrFail();
        $other = User::factory()->create();
        $accounting = User::where('email', 'accounting@example.com')->firstOrFail();

        $existing = ItemCreationRequest::create([
            'item_name' => 'Bearing Ball 6204', 'unit' => 'pcs',
            'requested_by' => $requester->id, 'status' => 'pending',
        ]);
        ItemCreationRequest::create([
            'item_name' => 'Bearing Ball 6305', 'unit' => 'pcs',
            'requested_by' => $other->id, 'status' => 'pending',
        ]);

        // Requester only matches against their own.
        $mine = ItemCreationRequestResource::findSimilarNames('bearing ball', $requester);
        $this->assertTrue($mine->contains('id', $existing->id));
        $this->assertSame(1, $mine->count());

        // Staff match globally.
        $this->assertSame(2, ItemCreationRequestResource::findSimilarNames('bearing ball', $accounting)->count());

        // Short input + self-exclusion.
        $this->assertTrue(ItemCreationRequestResource::findSimilarNames('abc', $accounting)->isEmpty());
        $this->assertTrue(
            ItemCreationRequestResource::findSimilarNames('Bearing Ball 6204', $accounting, $existing->id)
                ->doesntContain('id', $existing->id)
        );

        // No match at all.
        $this->assertTrue(ItemCreationRequestResource::findSimilarNames('Hydraulic Pump XYZ', $accounting)->isEmpty());
    }

    public function test_new_request_notifies_accounting_only(): void
    {
        $requester = User::where('email', 'requester@example.com')->firstOrFail();
        $commercial = User::where('email', 'commercial@example.com')->firstOrFail();
        $accounting = User::where('email', 'accounting@example.com')->firstOrFail();

        ItemCreationRequest::create([
            'item_name' => 'Quiet Ping Item', 'unit' => 'pcs',
            'requested_by' => $requester->id, 'status' => 'pending',
        ]);

        $titlesFor = fn (User $user) => DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->get()
            ->map(fn ($n) => $n->data['title'] ?? '');

        $this->assertTrue(
            $titlesFor($accounting)->contains(fn ($t) => str_starts_with($t, 'New Item Request')),
            'Accounting should be notified of new requests'
        );
        $this->assertTrue(
            $titlesFor($commercial)->doesntContain(fn ($t) => str_starts_with($t, 'New Item Request')),
            'Commercial should NOT be pinged until classification'
        );
    }
}
