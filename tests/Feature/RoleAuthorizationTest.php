<?php

namespace Tests\Feature;

use App\Filament\Resources\ItemCreationRequests\ItemCreationRequestResource;
use App\Models\ItemCreationRequest;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndUsersSeeder::class);
    }

    public function test_requester_is_blocked_from_administration_resources(): void
    {
        $this->actingAs(User::where('email', 'requester@example.com')->firstOrFail());

        foreach ([
            'filament.item-approval.resources.roles.index',
            'filament.item-approval.resources.users.index',
            'filament.item-approval.resources.number-sequences.index',
            'filament.item-approval.resources.d365-item-groups.index',
        ] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_staff_can_open_administration_resources(): void
    {
        $this->actingAs(User::where('email', 'accounting@example.com')->firstOrFail());

        $this->get(route('filament.item-approval.resources.roles.index'))->assertOk();
        $this->get(route('filament.item-approval.resources.users.index'))->assertOk();
    }

    public function test_requester_can_only_open_their_own_requests(): void
    {
        $requester = User::where('email', 'requester@example.com')->firstOrFail();
        $other = User::factory()->create();

        $mine = ItemCreationRequest::create([
            'item_name' => 'Mine',
            'unit' => 'pcs',
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);
        $theirs = ItemCreationRequest::create([
            'item_name' => 'Theirs',
            'unit' => 'pcs',
            'requested_by' => $other->id,
            'status' => 'pending',
        ]);

        $this->actingAs($requester);

        $this->get(route('filament.item-approval.resources.item-creation-requests.edit', $mine))->assertOk();
        // Scoped out of the requester's query — not visible at all.
        $this->get(route('filament.item-approval.resources.item-creation-requests.edit', $theirs))->assertNotFound();
    }

    public function test_delete_is_limited_to_drafts(): void
    {
        $requester = User::where('email', 'requester@example.com')->firstOrFail();
        $accounting = User::where('email', 'accounting@example.com')->firstOrFail();

        $draft = ItemCreationRequest::create([
            'item_name' => 'Draft', 'unit' => 'pcs',
            'requested_by' => $requester->id, 'status' => 'pending',
        ]);
        $classified = ItemCreationRequest::create([
            'item_name' => 'Classified', 'unit' => 'pcs',
            'requested_by' => $requester->id, 'status' => 'classified',
        ]);
        $created = ItemCreationRequest::create([
            'item_name' => 'Created', 'unit' => 'pcs',
            'requested_by' => $requester->id, 'status' => 'created',
        ]);

        // Owner: draft yes, post-classification never.
        $this->assertTrue(ItemCreationRequestResource::canBeDeletedBy($draft, $requester));
        $this->assertFalse(ItemCreationRequestResource::canBeDeletedBy($classified, $requester));
        $this->assertFalse(ItemCreationRequestResource::canBeDeletedBy($created, $requester));

        // Staff: any draft (incl. other people's) yes, post-classification never.
        $this->assertTrue(ItemCreationRequestResource::canBeDeletedBy($draft, $accounting));
        $this->assertFalse(ItemCreationRequestResource::canBeDeletedBy($created, $accounting));

        $this->assertFalse(ItemCreationRequestResource::canBeDeletedBy(null, $requester));
        $this->assertFalse(ItemCreationRequestResource::canBeDeletedBy($draft, null));
    }

    public function test_staff_helper_matches_seeded_roles(): void
    {
        $this->assertFalse(Roles::isStaff(User::where('email', 'requester@example.com')->firstOrFail()));
        $this->assertTrue(Roles::isStaff(User::where('email', 'accounting@example.com')->firstOrFail()));
        $this->assertTrue(Roles::isStaff(User::where('email', 'commercial@example.com')->firstOrFail()));
        $this->assertTrue(Roles::isStaff(User::where('email', 'admin@example.com')->firstOrFail()));
        $this->assertFalse(Roles::isStaff(null));
    }
}
