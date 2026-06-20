<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaginationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_pagination_controls_accessibility_labels_and_urls_render_on_paginated_page(): void
    {
        [$owner] = $this->paginationScenario();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('buildings.index'));

        $response->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false);

        $nav = $this->paginationNav($response);
        $text = $this->readablePaginationText($nav);

        $this->assertStringContainsString('Showing 1 to 15 of 16 results', $text);
        $this->assertStringContainsString('Previous', $text);
        $this->assertStringContainsString('Next', $text);
        $this->assertStringContainsString('aria-label="Pagination Navigation"', $nav);
        $this->assertStringContainsString('aria-label="Go to page 2"', $nav);
        $this->assertStringContainsString('rel="next"', $nav);
        $this->assertStringContainsString('page=2', $nav);
        $this->assertStringContainsString('aria-disabled="true"', $nav);
        $this->assertStringContainsString('aria-current="page"', $nav);
        $this->assertStringContainsString('inline-flex items-center px-4 py-2', $nav);
        $this->assertStringNotContainsString('pagination.previous', $nav);
        $this->assertStringNotContainsString('pagination.next', $nav);

        $response->assertSee('Owner Pagination Building 16')
            ->assertDontSee('Owner Pagination Building 01')
            ->assertDontSee('Other Organization Pagination Building')
            ->assertDontSee('Other organization pagination record.');

        $pageTwoResponse = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('buildings.index', ['page' => 2]));

        $pageTwoResponse->assertOk()
            ->assertSee('Owner Pagination Building 01')
            ->assertDontSee('Owner Pagination Building 16')
            ->assertDontSee('Other Organization Pagination Building')
            ->assertDontSee('Other organization pagination record.');

        $pageTwoNav = $this->paginationNav($pageTwoResponse);
        $pageTwoText = $this->readablePaginationText($pageTwoNav);

        $this->assertStringContainsString('Showing 16 to 16 of 16 results', $pageTwoText);
        $this->assertStringContainsString('Previous', $pageTwoText);
        $this->assertStringContainsString('rel="prev"', $pageTwoNav);
        $this->assertStringContainsString('page=1', $pageTwoNav);
        $this->assertStringContainsString('aria-current="page"', $pageTwoNav);
        $this->assertStringContainsString('inline-flex items-center px-4 py-2', $pageTwoNav);
    }

    public function test_arabic_pagination_controls_accessibility_labels_and_page_numbers_render_without_english_words(): void
    {
        [$owner] = $this->paginationScenario();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('buildings.index'));

        $response->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false);

        $nav = $this->paginationNav($response);
        $text = $this->readablePaginationText($nav);

        $this->assertStringContainsString('عرض 1 إلى 15 من 16 نتيجة', $text);
        $this->assertStringContainsString('السابق', $text);
        $this->assertStringContainsString('التالي', $text);
        $this->assertStringContainsString('aria-label="التنقل بين الصفحات"', $nav);
        $this->assertStringContainsString('aria-label="الانتقال إلى الصفحة 2"', $nav);
        $this->assertStringContainsString('rel="next"', $nav);
        $this->assertStringContainsString('page=2', $nav);
        $this->assertStringContainsString('aria-disabled="true"', $nav);
        $this->assertStringContainsString('aria-current="page"', $nav);
        $this->assertStringContainsString('inline-flex items-center px-4 py-2', $nav);
        $this->assertStringNotContainsString('dir="ltr"', $nav);
        $this->assertStringNotContainsString('Pagination Navigation', $nav);
        $this->assertStringNotContainsString('Showing', $text);
        $this->assertStringNotContainsString('Go to page', $nav);
        $this->assertStringNotContainsString('Previous', $text);
        $this->assertStringNotContainsString('Next', $text);
        $this->assertStringNotContainsString('pagination.previous', $nav);
        $this->assertStringNotContainsString('pagination.next', $nav);

        $response->assertSee('Owner Pagination Building 16')
            ->assertDontSee('Owner Pagination Building 01')
            ->assertDontSee('Other Organization Pagination Building')
            ->assertDontSee('Other organization pagination record.');

        $pageTwoResponse = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('buildings.index', ['page' => 2]));

        $pageTwoResponse->assertOk()
            ->assertSee('Owner Pagination Building 01')
            ->assertDontSee('Owner Pagination Building 16')
            ->assertDontSee('Other Organization Pagination Building')
            ->assertDontSee('Other organization pagination record.');

        $pageTwoNav = $this->paginationNav($pageTwoResponse);
        $pageTwoText = $this->readablePaginationText($pageTwoNav);

        $this->assertStringContainsString('عرض 16 إلى 16 من 16 نتيجة', $pageTwoText);
        $this->assertStringContainsString('السابق', $pageTwoText);
        $this->assertStringContainsString('rel="prev"', $pageTwoNav);
        $this->assertStringContainsString('page=1', $pageTwoNav);
        $this->assertStringContainsString('aria-current="page"', $pageTwoNav);
        $this->assertStringContainsString('inline-flex items-center px-4 py-2', $pageTwoNav);
        $this->assertStringNotContainsString('dir="ltr"', $pageTwoNav);
    }

    public function test_pagination_keeps_authorization_and_organization_isolation_across_pages(): void
    {
        [$owner, $manager, $accountant, $caretaker] = $this->paginationScenario();

        $this->actingAs($owner)
            ->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('Owner Pagination Building 16')
            ->assertDontSee('Owner Pagination Building 01')
            ->assertDontSee('Other Organization Pagination Building')
            ->assertDontSee('Other organization pagination record.');

        $this->actingAs($owner)
            ->get(route('buildings.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Owner Pagination Building 01')
            ->assertDontSee('Owner Pagination Building 16')
            ->assertDontSee('Other Organization Pagination Building')
            ->assertDontSee('Other organization pagination record.');

        $this->actingAs($manager)
            ->get(route('buildings.index'))
            ->assertOk();

        $this->actingAs($accountant)
            ->get(route('buildings.index'))
            ->assertForbidden();

        $this->actingAs($caretaker)
            ->get(route('buildings.index'))
            ->assertForbidden();
    }

    private function paginationNav(TestResponse $response): string
    {
        $content = $response->getContent();
        $start = strpos($content, '<nav role="navigation"');

        $this->assertNotFalse($start, 'Expected rendered pagination <nav role="navigation"> markup to exist.');

        $end = strpos($content, '</nav>', $start);

        $this->assertNotFalse($end, 'Expected rendered pagination </nav> closing tag to exist.');

        return substr($content, $start, $end - $start + strlen('</nav>'));
    }

    private function readablePaginationText(string $html): string
    {
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function paginationScenario(): array
    {
        $organization = Organization::create(['name' => 'Pagination Localization Organization']);
        $otherOrganization = Organization::create(['name' => 'Other Pagination Localization Organization']);

        $owner = $this->user($organization, 'pagination-owner@example.com', 'owner');
        $manager = $this->user($organization, 'pagination-manager@example.com', 'manager');
        $accountant = $this->user($organization, 'pagination-accountant@example.com', 'accountant');
        $caretaker = $this->user($organization, 'pagination-caretaker@example.com', 'caretaker');

        for ($i = 1; $i <= 16; $i++) {
            $building = Building::create([
                'organization_id' => $organization->id,
                'name' => 'Owner Pagination Building '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'location' => 'Owner Pagination Location',
                'description' => 'Owner pagination localization record.',
            ]);

            $this->setBuildingTimestamp($building, now()->addMinutes($i));
        }

        for ($i = 1; $i <= 16; $i++) {
            $building = Building::create([
                'organization_id' => $otherOrganization->id,
                'name' => 'Other Organization Pagination Building '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'location' => 'Other Pagination Location',
                'description' => 'Other organization pagination record.',
            ]);

            $this->setBuildingTimestamp($building, now()->addMinutes($i + 100));
        }

        return [$owner, $manager, $accountant, $caretaker];
    }

    private function setBuildingTimestamp(Building $building, mixed $timestamp): void
    {
        $building->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveQuietly();

        $building->refresh();
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' Pagination User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
