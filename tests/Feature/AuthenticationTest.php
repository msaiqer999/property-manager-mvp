<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_login(): void
    {
        $organization = Organization::create(['name' => 'Test Org']);
        $user = User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');
    }
}
