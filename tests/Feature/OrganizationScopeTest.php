<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_organizations_building(): void
    {
        $first = Organization::create(['name' => 'First']);
        $second = Organization::create(['name' => 'Second']);

        $user = User::create([
            'organization_id' => $first->id,
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $building = Building::create([
            'organization_id' => $second->id,
            'name' => 'Private Building',
        ]);

        $this->actingAs($user)->get(route('buildings.show', $building))->assertForbidden();
    }
}
