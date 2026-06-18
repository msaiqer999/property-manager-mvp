<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileTableSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_main_pages_render_with_mobile_safe_table_wrappers(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        foreach (['/', '/reports'] as $path) {
            $this->actingAs($owner)->get($path)->assertOk();
        }

        foreach ([
            '/buildings',
            '/units',
            '/tenants',
            '/contracts',
            '/payments',
            '/expenses',
            '/users',
            '/activity-logs',
        ] as $path) {
            $this->actingAs($owner)
                ->get($path)
                ->assertOk()
                ->assertSee('data-mobile-table', false)
                ->assertSee('overflow-x-auto', false);
        }
    }
}
