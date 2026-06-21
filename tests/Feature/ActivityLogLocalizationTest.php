<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_index_renders_english_system_text_and_translated_actions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = $this->activitySubject($owner);
        $log = $this->activityLog($owner, $building, [
            'action' => 'building.created',
            'description' => 'English activity description remains stored content.',
            'created_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('activity-logs.index'));

        $response->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('data-mobile-table', false)
            ->assertSee('Activity log')
            ->assertSee('English activity description remains stored content.')
            ->assertSee($owner->name)
            ->assertSeeHtml('<bdi dir="ltr">'.$log->fresh()->created_at.'</bdi>')
            ->assertDontSee('>building.created</td>', false);

        $this->assertActionCellContains($response, 'Building created');
        $this->assertSame('building.created', $log->fresh()->action);
        $this->assertSame('English activity description remains stored content.', $log->fresh()->description);
    }

    public function test_activity_log_index_renders_arabic_system_text_actions_and_ltr_timestamp(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = $this->activitySubject($owner);
        $created = $this->activityLog($owner, $building, [
            'action' => 'building.created',
            'description' => 'Arabic building created description remains stored.',
            'created_at' => now()->addDays(3),
        ]);
        $updated = $this->activityLog($owner, $building, [
            'action' => 'user.role_changed',
            'description' => 'Role changed from manager to accountant.',
            'created_at' => now()->addDays(2),
        ]);
        $recorded = $this->activityLog($owner, $building, [
            'action' => 'payment.recorded',
            'description' => 'Payment recorded description remains stored.',
            'created_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('activity-logs.index'));

        $response->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('data-mobile-table', false)
            ->assertSee('سجل النشاط')
            ->assertSee('Arabic building created description remains stored.')
            ->assertSee('Role changed from manager to accountant.')
            ->assertSee('Payment recorded description remains stored.')
            ->assertSee($owner->name)
            ->assertSeeHtml('<bdi dir="ltr">'.$created->fresh()->created_at.'</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">'.$updated->fresh()->created_at.'</bdi>')
            ->assertSeeHtml('<bdi dir="ltr">'.$recorded->fresh()->created_at.'</bdi>')
            ->assertDontSee('>building.created</td>', false)
            ->assertDontSee('>user.role_changed</td>', false)
            ->assertDontSee('>payment.recorded</td>', false);

        $this->assertActionCellContains($response, 'تم إنشاء مبنى');
        $this->assertActionCellContains($response, 'تم تغيير دور مستخدم');
        $this->assertActionCellContains($response, 'تم تسجيل دفعة');
        $this->assertSame('building.created', $created->fresh()->action);
        $this->assertSame('user.role_changed', $updated->fresh()->action);
        $this->assertSame('payment.recorded', $recorded->fresh()->action);
    }

    public function test_all_known_application_activity_actions_have_visible_translations_without_changing_storage(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = $this->activitySubject($owner);

        $actions = [
            'building.created' => 'تم إنشاء مبنى',
            'building.updated' => 'تم تحديث مبنى',
            'building.deleted' => 'تم حذف مبنى',
            'tenant.created' => 'تم إنشاء مستأجر',
            'tenant.updated' => 'تم تحديث مستأجر',
            'tenant.deleted' => 'تم حذف مستأجر',
            'contract.created' => 'تم إنشاء عقد',
            'contract.updated' => 'تم تحديث عقد',
            'expense.created' => 'تم إنشاء مصروف',
            'expense.updated' => 'تم تحديث مصروف',
            'payment.recorded' => 'تم تسجيل دفعة',
            'unit.created' => 'تم إنشاء وحدة',
            'unit.updated' => 'تم تحديث وحدة',
            'unit.status_changed' => 'تم تغيير حالة وحدة',
            'unit.deleted' => 'تم حذف وحدة',
            'user.role_changed' => 'تم تغيير دور مستخدم',
            'user.deactivated' => 'تم تعطيل مستخدم',
            'user.reactivated' => 'تمت إعادة تفعيل مستخدم',
        ];

        foreach (array_keys($actions) as $index => $action) {
            $this->activityLog($owner, $building, [
                'action' => $action,
                'description' => "Stored activity description {$index}.",
                'created_at' => now()->addDays($index + 1),
            ]);
        }

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('activity-logs.index'));

        $response->assertOk();

        foreach ($actions as $action => $arabicLabel) {
            $this->assertActionCellContains($response, $arabicLabel);
            $response->assertDontSee('>'.$action.'</td>', false);

            $this->assertDatabaseHas('activity_logs', [
                'organization_id' => $owner->organization_id,
                'action' => $action,
            ]);
        }
    }

    public function test_unknown_activity_action_falls_back_to_bdi_wrapped_stored_value(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = $this->activitySubject($owner);
        $log = $this->activityLog($owner, $building, [
            'action' => 'visible.log',
            'description' => 'Unknown action fallback description remains stored.',
            'created_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSeeHtml('<bdi dir="ltr">visible.log</bdi>')
            ->assertDontSee('activity_logs.actions.visible.log')
            ->assertSee('Unknown action fallback description remains stored.');

        $this->assertSame('visible.log', $log->fresh()->action);
    }

    public function test_activity_log_authorization_routes_and_organization_scope_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $building = $this->activitySubject($owner);
        $ownLog = $this->activityLog($owner, $building, [
            'action' => 'building.updated',
            'description' => 'Activity localization visible organization log.',
            'created_at' => now()->addDay(),
        ]);
        $otherLog = $this->otherOrganizationActivityLog();

        $this->assertSame('/activity-logs', route('activity-logs.index', absolute: false));

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('activity-logs.index'));

        $response->assertOk()
            ->assertSee('Activity localization visible organization log.')
            ->assertDontSee('Activity localization private organization log.')
            ->assertDontSee($otherLog->user->name)
            ->assertDontSee('private.log')
            ->assertDontSee('activity_logs.actions.private.log');

        $this->assertActionCellContains($response, 'Building updated');

        foreach ([$manager, $accountant, $caretaker] as $user) {
            $this->actingAs($user)->get(route('activity-logs.index'))->assertForbidden();
        }

        $this->assertDatabaseHas('activity_logs', [
            'id' => $ownLog->id,
            'organization_id' => $owner->organization_id,
            'action' => 'building.updated',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $otherLog->id,
            'organization_id' => $otherLog->organization_id,
            'action' => 'private.log',
        ]);
    }

    private function activitySubject(User $owner): Building
    {
        return Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Activity Localization Building',
            'location' => 'Abu Dhabi',
        ]);
    }

    private function activityLog(User $owner, Building $building, array $values): ActivityLog
    {
        $log = ActivityLog::create([
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'action' => $values['action'],
            'subject_type' => Building::class,
            'subject_id' => $building->id,
            'description' => $values['description'],
        ]);

        $createdAt = $values['created_at'];

        $log->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $log->refresh();
    }

    private function assertActionCellContains($response, string $label): void
    {
        $this->assertMatchesRegularExpression(
            '/<td class="p-4 whitespace-nowrap">\s*'.preg_quote($label, '/').'\s*<\/td>/u',
            $response->getContent()
        );
    }

    private function otherOrganizationActivityLog(): ActivityLog
    {
        $organization = Organization::create(['name' => 'Activity Localization Other Organization']);
        $otherOwner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Other Activity Localization Owner',
            'email' => 'other-activity-localization-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Other Activity Localization Building',
            'location' => 'Dubai',
        ]);

        return ActivityLog::create([
            'organization_id' => $organization->id,
            'user_id' => $otherOwner->id,
            'action' => 'private.log',
            'subject_type' => Building::class,
            'subject_id' => $building->id,
            'description' => 'Activity localization private organization log.',
        ]);
    }
}
