<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_caretaker_cannot_open_profit_reports(): void
    {
        $organization = Organization::create(['name' => 'Test Org']);
        $caretaker = User::create([
            'organization_id' => $organization->id,
            'name' => 'Caretaker',
            'email' => 'caretaker@test.com',
            'password' => 'password',
            'role' => 'caretaker',
        ]);

        $this->actingAs($caretaker)->get(route('reports.index'))->assertForbidden();
    }
}
