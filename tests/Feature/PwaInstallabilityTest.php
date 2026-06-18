<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaInstallabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_main_page_includes_pwa_metadata(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get('/')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(asset('manifest.webmanifest'), false)
            ->assertSee('<meta name="theme-color" content="#0f172a">', false)
            ->assertSee('<meta name="mobile-web-app-capable" content="yes">', false)
            ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false)
            ->assertSee('<meta name="apple-mobile-web-app-title" content="Property Manager">', false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee(asset('icons/apple-touch-icon-180x180.png'), false);
    }

    public function test_manifest_icons_and_network_only_service_worker_are_valid(): void
    {
        $manifestPath = public_path('manifest.webmanifest');

        $this->assertFileExists($manifestPath);

        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/', $manifest['id']);
        $this->assertSame('Property Manager', $manifest['name']);
        $this->assertSame('Property Manager', $manifest['short_name']);
        $this->assertNotEmpty($manifest['description']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#f8fafc', $manifest['background_color']);
        $this->assertSame('#0f172a', $manifest['theme_color']);
        $this->assertSame('auto', $manifest['dir']);
        $this->assertSame('en', $manifest['lang']);
        $this->assertFalse($manifest['prefer_related_applications']);

        $expectedIcons = [
            '/icons/pwa-192x192.png' => [192, 192, 'any'],
            '/icons/pwa-512x512.png' => [512, 512, 'any'],
            '/icons/pwa-maskable-512x512.png' => [512, 512, 'maskable'],
        ];

        foreach ($manifest['icons'] as $icon) {
            $this->assertArrayHasKey($icon['src'], $expectedIcons);

            [$width, $height, $purpose] = $expectedIcons[$icon['src']];
            $iconPath = public_path(ltrim($icon['src'], '/'));

            $this->assertSame("{$width}x{$height}", $icon['sizes']);
            $this->assertSame('image/png', $icon['type']);
            $this->assertSame($purpose, $icon['purpose']);
            $this->assertFileExists($iconPath);
            $this->assertGreaterThan(0, filesize($iconPath));

            $dimensions = getimagesize($iconPath);
            $this->assertNotFalse($dimensions);
            $this->assertSame($width, $dimensions[0]);
            $this->assertSame($height, $dimensions[1]);
            $this->assertSame(IMAGETYPE_PNG, $dimensions[2]);
        }

        $appleIconPath = public_path('icons/apple-touch-icon-180x180.png');
        $this->assertFileExists($appleIconPath);
        $this->assertGreaterThan(0, filesize($appleIconPath));

        $appleDimensions = getimagesize($appleIconPath);
        $this->assertNotFalse($appleDimensions);
        $this->assertSame(180, $appleDimensions[0]);
        $this->assertSame(180, $appleDimensions[1]);
        $this->assertSame(IMAGETYPE_PNG, $appleDimensions[2]);

        $serviceWorkerPath = public_path('service-worker.js');
        $this->assertFileExists($serviceWorkerPath);

        $serviceWorker = file_get_contents($serviceWorkerPath);
        $this->assertStringContainsString("addEventListener('install'", $serviceWorker);
        $this->assertStringContainsString("addEventListener('activate'", $serviceWorker);
        $this->assertStringContainsString("addEventListener('fetch'", $serviceWorker);
        $this->assertStringContainsString("event.request.method !== 'GET'", $serviceWorker);
        $this->assertStringContainsString('requestUrl.origin !== self.location.origin', $serviceWorker);
        $this->assertStringContainsString('fetch(event.request)', $serviceWorker);
        $this->assertStringNotContainsString('caches.open', $serviceWorker);
        $this->assertStringNotContainsString('caches.match', $serviceWorker);
        $this->assertStringNotContainsString('addAll', $serviceWorker);

        $frontend = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("'serviceWorker' in navigator", $frontend);
        $this->assertStringContainsString(".register('/service-worker.js', { scope: '/' })", $frontend);
    }
}
