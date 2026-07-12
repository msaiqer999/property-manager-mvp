<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseCacheMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_cache_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasColumns('cache', [
            'key',
            'value',
            'expiration',
        ]));

        $this->assertTrue(Schema::hasTable('cache_locks'));
        $this->assertTrue(Schema::hasColumns('cache_locks', [
            'key',
            'owner',
            'expiration',
        ]));
    }
}
