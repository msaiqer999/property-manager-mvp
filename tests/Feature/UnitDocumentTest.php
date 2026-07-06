<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\UnitDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnitDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_valid_pdf_to_own_unit(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();

        $response = $this->actingAs($owner)->post(route('unit-documents.store', $unit), $this->validPayload([
            'document' => UploadedFile::fake()->create('lease.pdf', 100, 'application/pdf'),
        ]));

        $response->assertRedirect(route('units.show', $unit));
        $response->assertSessionHas('success', __('unit_documents.messages.uploaded'));

        $document = UnitDocument::firstOrFail();
        $this->assertSame($owner->organization_id, $document->organization_id);
        $this->assertSame($unit->id, $document->unit_id);
        $this->assertSame($owner->id, $document->uploaded_by);
        $this->assertSame('local', $document->disk);
        $this->assertStringStartsWith("unit-documents/{$owner->organization_id}/{$unit->id}/", $document->path);
        $this->assertSame('lease.pdf', $document->original_name);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_configured_unit_documents_disk_is_used_on_upload(): void
    {
        config(['filesystems.unit_documents_disk' => 'unit-documents-test']);
        Storage::fake('local');
        Storage::fake('unit-documents-test');
        [$owner, , , , $unit] = $this->scenario();

        $this->actingAs($owner)
            ->post(route('unit-documents.store', $unit), $this->validPayload())
            ->assertRedirect(route('units.show', $unit));

        $document = UnitDocument::firstOrFail();
        $this->assertSame('unit-documents-test', $document->disk);
        $this->assertStringStartsWith("unit-documents/{$owner->organization_id}/{$unit->id}/", $document->path);
        Storage::disk('unit-documents-test')->assertExists($document->path);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_manager_can_upload_when_unit_policy_allows_unit_management(): void
    {
        Storage::fake('local');
        [, $manager, , , $unit] = $this->scenario();

        $this->actingAs($manager)
            ->post(route('unit-documents.store', $unit), $this->validPayload())
            ->assertRedirect(route('units.show', $unit));

        $this->assertDatabaseHas('unit_documents', [
            'organization_id' => $manager->organization_id,
            'unit_id' => $unit->id,
            'uploaded_by' => $manager->id,
        ]);
    }

    public function test_accountant_and_caretaker_cannot_upload_unit_documents(): void
    {
        [$owner, , $accountant, $caretaker, $unit] = $this->scenario();

        $this->actingAs($accountant)
            ->post(route('unit-documents.store', $unit), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($caretaker)
            ->post(route('unit-documents.store', $unit), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('units.show', $unit))
            ->assertOk();

        $this->assertDatabaseCount('unit_documents', 0);
    }

    public function test_unauthenticated_users_cannot_upload_or_download(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();
        $document = $this->storedDocument($owner, $unit);

        $this->post(route('unit-documents.store', $unit), $this->validPayload())->assertRedirect(route('login'));
        $this->get(route('unit-documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_owner_can_download_own_unit_document(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();
        $document = $this->storedDocument($owner, $unit, ['title' => 'Tenant ID Copy']);

        $response = $this->actingAs($owner)->get(route('unit-documents.download', $document));

        $response->assertOk();
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString("tenant-id-copy-{$document->id}.pdf", (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('private', (string) $response->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));
        $this->assertSame('nosniff', $response->headers->get('x-content-type-options'));
        $this->assertStringNotContainsString($document->path, (string) $response->headers->get('content-disposition'));
    }

    public function test_download_uses_the_disk_stored_on_the_document_record(): void
    {
        Storage::fake('archive-test');
        config(['filesystems.unit_documents_disk' => 'local']);
        [$owner, , , , $unit] = $this->scenario();
        $path = "unit-documents/{$owner->organization_id}/{$unit->id}/stored-disk-document.pdf";
        Storage::disk('archive-test')->put($path, 'document-bytes');

        $document = UnitDocument::create([
            'organization_id' => $owner->organization_id,
            'unit_id' => $unit->id,
            'uploaded_by' => $owner->id,
            'title' => 'Stored Disk Document',
            'category' => 'other',
            'disk' => 'archive-test',
            'path' => $path,
            'original_name' => 'stored-disk-document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $this->actingAs($owner)
            ->get(route('unit-documents.download', $document))
            ->assertOk();
    }

    public function test_owner_can_delete_own_unit_document_and_storage_object(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();
        $document = $this->storedDocument($owner, $unit);

        $this->actingAs($owner)
            ->delete(route('unit-documents.destroy', $document))
            ->assertRedirect(route('units.show', $unit))
            ->assertSessionHas('success', __('unit_documents.messages.deleted'));

        $this->assertDatabaseMissing('unit_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_manager_can_delete_unit_document_when_policy_allows_update(): void
    {
        Storage::fake('local');
        [$owner, $manager, , , $unit] = $this->scenario();
        $document = $this->storedDocument($owner, $unit);

        $this->actingAs($manager)
            ->delete(route('unit-documents.destroy', $document))
            ->assertRedirect(route('units.show', $unit));

        $this->assertDatabaseMissing('unit_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_accountant_and_caretaker_cannot_delete_unit_documents(): void
    {
        Storage::fake('local');
        [$owner, , $accountant, $caretaker, $unit] = $this->scenario();
        $accountantDocument = $this->storedDocument($owner, $unit, ['title' => 'Accountant blocked']);
        $caretakerDocument = $this->storedDocument($owner, $unit, ['title' => 'Caretaker blocked']);

        $this->actingAs($accountant)
            ->delete(route('unit-documents.destroy', $accountantDocument))
            ->assertForbidden();

        $this->actingAs($caretaker)
            ->delete(route('unit-documents.destroy', $caretakerDocument))
            ->assertForbidden();

        $this->assertDatabaseHas('unit_documents', ['id' => $accountantDocument->id]);
        $this->assertDatabaseHas('unit_documents', ['id' => $caretakerDocument->id]);
        Storage::disk('local')->assertExists($accountantDocument->path);
        Storage::disk('local')->assertExists($caretakerDocument->path);
    }

    public function test_unauthenticated_user_cannot_delete_unit_document(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();
        $document = $this->storedDocument($owner, $unit);

        $this->delete(route('unit-documents.destroy', $document))->assertRedirect(route('login'));

        $this->assertDatabaseHas('unit_documents', ['id' => $document->id]);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_cross_organization_delete_is_forbidden(): void
    {
        Storage::fake('local');
        [$ownerA, , , , , $ownerB, $unitB] = $this->scenarioWithSecondOrganization();
        $documentB = $this->storedDocument($ownerB, $unitB);

        $this->actingAs($ownerA)
            ->delete(route('unit-documents.destroy', $documentB))
            ->assertForbidden();

        $this->assertDatabaseHas('unit_documents', ['id' => $documentB->id]);
        Storage::disk('local')->assertExists($documentB->path);
    }

    public function test_delete_uses_stored_document_disk(): void
    {
        Storage::fake('local');
        Storage::fake('archive-test');
        [$owner, , , , $unit] = $this->scenario();
        $document = $this->storedDocument($owner, $unit, ['disk' => 'archive-test']);

        $this->actingAs($owner)
            ->delete(route('unit-documents.destroy', $document))
            ->assertRedirect(route('units.show', $unit));

        $this->assertDatabaseMissing('unit_documents', ['id' => $document->id]);
        Storage::disk('archive-test')->assertMissing($document->path);
    }

    public function test_missing_file_still_allows_authorized_database_cleanup(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();
        $path = "unit-documents/{$owner->organization_id}/{$unit->id}/missing-document.pdf";
        $document = UnitDocument::create([
            'organization_id' => $owner->organization_id,
            'unit_id' => $unit->id,
            'uploaded_by' => $owner->id,
            'title' => 'Missing document',
            'category' => 'other',
            'disk' => 'local',
            'path' => $path,
        ]);

        $this->actingAs($owner)
            ->delete(route('unit-documents.destroy', $document))
            ->assertRedirect(route('units.show', $unit));

        $this->assertDatabaseMissing('unit_documents', ['id' => $document->id]);
    }

    public function test_delete_with_invalid_path_is_not_removed_and_returns_not_found(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();
        $path = 'wrong-prefix/secret.pdf';
        Storage::disk('local')->put($path, 'secret-bytes');
        $document = UnitDocument::create([
            'organization_id' => $owner->organization_id,
            'unit_id' => $unit->id,
            'uploaded_by' => $owner->id,
            'title' => 'Invalid path',
            'category' => 'other',
            'disk' => 'local',
            'path' => $path,
        ]);

        $this->actingAs($owner)
            ->delete(route('unit-documents.destroy', $document))
            ->assertNotFound();

        $this->assertDatabaseHas('unit_documents', ['id' => $document->id]);
        Storage::disk('local')->assertExists($path);
    }

    public function test_cross_organization_upload_and_download_are_forbidden(): void
    {
        Storage::fake('local');
        [$ownerA, , , , $unitA, $ownerB, $unitB] = $this->scenarioWithSecondOrganization();
        $documentB = $this->storedDocument($ownerB, $unitB);

        $this->actingAs($ownerA)
            ->post(route('unit-documents.store', $unitB), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($ownerA)
            ->get(route('unit-documents.download', $documentB))
            ->assertForbidden();

        $this->actingAs($ownerB)
            ->get(route('unit-documents.download', $documentB))
            ->assertOk();

        $this->assertDatabaseMissing('unit_documents', [
            'organization_id' => $ownerA->organization_id,
            'unit_id' => $unitA->id,
            'path' => $documentB->path,
        ]);
    }

    public function test_request_cannot_forge_organization_or_uploader(): void
    {
        Storage::fake('local');
        [$ownerA, , , , $unitA, $ownerB] = $this->scenarioWithSecondOrganization();

        $this->actingAs($ownerA)
            ->post(route('unit-documents.store', $unitA), $this->validPayload([
                'organization_id' => $ownerB->organization_id,
                'uploaded_by' => $ownerB->id,
            ]))
            ->assertRedirect(route('units.show', $unitA));

        $document = UnitDocument::firstOrFail();
        $this->assertSame($ownerA->organization_id, $document->organization_id);
        $this->assertSame($ownerA->id, $document->uploaded_by);
    }

    public function test_unsafe_and_oversized_files_are_rejected(): void
    {
        [$owner, , , , $unit] = $this->scenario();

        $this->actingAs($owner)
            ->from(route('units.show', $unit))
            ->post(route('unit-documents.store', $unit), $this->validPayload([
                'document' => UploadedFile::fake()->create('unsafe.svg', 10, 'image/svg+xml'),
            ]))
            ->assertRedirect(route('units.show', $unit))
            ->assertSessionHasErrors('document');

        $this->actingAs($owner)
            ->from(route('units.show', $unit))
            ->post(route('unit-documents.store', $unit), $this->validPayload([
                'document' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
            ]))
            ->assertRedirect(route('units.show', $unit))
            ->assertSessionHasErrors('document');

        $this->assertDatabaseCount('unit_documents', 0);
    }

    public function test_pdf_jpg_png_and_webp_are_accepted(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();

        foreach ([
            ['file' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'), 'title' => 'PDF File'],
            ['file' => UploadedFile::fake()->create('photo.jpg', 20, 'image/jpeg'), 'title' => 'JPG File'],
            ['file' => UploadedFile::fake()->create('scan.png', 20, 'image/png'), 'title' => 'PNG File'],
            ['file' => UploadedFile::fake()->create('preview.webp', 20, 'image/webp'), 'title' => 'WEBP File'],
        ] as $case) {
            $this->actingAs($owner)
                ->post(route('unit-documents.store', $unit), $this->validPayload([
                    'title' => $case['title'],
                    'document' => $case['file'],
                ]))
                ->assertRedirect(route('units.show', $unit));
        }

        $this->assertDatabaseCount('unit_documents', 4);
    }

    public function test_unit_show_page_renders_document_section_without_private_path_or_raw_keys(): void
    {
        [$owner, , , , $unit] = $this->scenario();
        $document = $this->storedDocument($owner, $unit, [
            'title' => 'Handover Signed Copy',
            'category' => 'handover_document',
            'notes' => 'Signed by tenant.',
        ]);

        $this->actingAs($owner)
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee(__('unit_documents.title'))
            ->assertSee('Handover Signed Copy')
            ->assertSee(__('unit_documents.categories.handover_document'))
            ->assertSee('Signed by tenant.')
            ->assertSee(route('unit-documents.download', $document, absolute: false))
            ->assertSee(route('unit-documents.destroy', $document, absolute: false))
            ->assertSee(__('unit_documents.actions.upload'))
            ->assertSee(__('unit_documents.actions.delete'))
            ->assertDontSee($document->path)
            ->assertDontSee('unit_documents.');
    }

    public function test_missing_or_invalid_private_path_returns_not_found_after_authorization(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();

        foreach ([
            '',
            '../secret.pdf',
            '/absolute/secret.pdf',
            'C:/absolute/secret.pdf',
            'wrong-prefix/secret.pdf',
            "unit-documents/{$owner->organization_id}/999/missing.pdf",
            "unit-documents/{$owner->organization_id}/{$unit->id}/missing.pdf",
        ] as $path) {
            $document = UnitDocument::create([
                'organization_id' => $owner->organization_id,
                'unit_id' => $unit->id,
                'uploaded_by' => $owner->id,
                'title' => 'Missing file',
                'category' => 'other',
                'disk' => 'local',
                'path' => $path,
            ]);

            $this->actingAs($owner)
                ->get(route('unit-documents.download', $document))
                ->assertNotFound();
        }
    }

    public function test_arabic_and_english_labels_render(): void
    {
        Storage::fake('local');
        [$owner, , , , $unit] = $this->scenario();
        $this->storedDocument($owner, $unit);

        app()->setLocale('en');
        $this->actingAs($owner)
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee('Unit Documents')
            ->assertSee('Upload document')
            ->assertSee('Delete');

        app()->setLocale('ar');
        $this->actingAs($owner)
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee(__('unit_documents.title'))
            ->assertSee(__('unit_documents.upload_title'))
            ->assertSee(__('unit_documents.actions.delete'));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Tenant ID Copy',
            'category' => 'tenant_id_copy',
            'notes' => 'Private archive note.',
            'document' => UploadedFile::fake()->create('tenant-id.pdf', 100, 'application/pdf'),
        ], $overrides);
    }

    private function storedDocument(User $user, Unit $unit, array $overrides = []): UnitDocument
    {
        $path = $overrides['path'] ?? "unit-documents/{$user->organization_id}/{$unit->id}/private-document.pdf";
        $disk = $overrides['disk'] ?? 'local';
        Storage::disk($disk)->put($path, 'document-bytes');

        return UnitDocument::create(array_merge([
            'organization_id' => $user->organization_id,
            'unit_id' => $unit->id,
            'uploaded_by' => $user->id,
            'title' => 'Tenant ID Copy',
            'category' => 'tenant_id_copy',
            'notes' => null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => 'tenant-id.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ], $overrides));
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Unit Docs Org']);

        $owner = $this->user($organization, 'owner@example.com', 'owner');
        $manager = $this->user($organization, 'manager@example.com', 'manager');
        $accountant = $this->user($organization, 'accountant@example.com', 'accountant');
        $caretaker = $this->user($organization, 'caretaker@example.com', 'caretaker');
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Archive Tower',
            'location' => 'Dubai',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'A-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 50000,
        ]);

        return [$owner, $manager, $accountant, $caretaker, $unit];
    }

    private function scenarioWithSecondOrganization(): array
    {
        [$ownerA, $managerA, $accountantA, $caretakerA, $unitA] = $this->scenario();

        $organizationB = Organization::create(['name' => 'Unit Docs Org B']);
        $ownerB = $this->user($organizationB, 'owner-b@example.com', 'owner');
        $buildingB = Building::create([
            'organization_id' => $organizationB->id,
            'name' => 'Archive Tower B',
            'location' => 'Sharjah',
        ]);
        $unitB = Unit::create([
            'building_id' => $buildingB->id,
            'unit_number' => 'B-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 40000,
        ]);

        return [$ownerA, $managerA, $accountantA, $caretakerA, $unitA, $ownerB, $unitB];
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
