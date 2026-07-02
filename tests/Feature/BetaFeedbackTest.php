<?php

namespace Tests\Feature;

use App\Models\BetaFeedback;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetaFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_submit_feedback(): void
    {
        $user = $this->user('caretaker');

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('beta-feedback.store'), [
                'type' => 'confusing',
                'message' => 'The payment filter was hard to understand.',
                'page_url' => 'https://beta.example.test/payments?status=pending',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('beta_feedback', [
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'type' => 'confusing',
            'message' => 'The payment filter was hard to understand.',
            'page_url' => 'https://beta.example.test/payments?status=pending',
            'status' => 'new',
        ]);
    }

    public function test_unauthenticated_users_cannot_submit_feedback(): void
    {
        $this->post(route('beta-feedback.store'), [
            'type' => 'bug',
            'message' => 'Guest feedback should not be accepted.',
            'page_url' => 'https://beta.example.test/login',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('beta_feedback', 0);
    }

    public function test_owner_can_view_feedback_for_their_organization(): void
    {
        $owner = $this->user('owner');
        $feedback = BetaFeedback::create([
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'page_url' => 'https://beta.example.test/dashboard',
            'type' => 'suggestion',
            'message' => 'Please add a small rent collection reminder.',
        ]);

        $this->actingAs($owner)
            ->get(route('beta-feedback.index'))
            ->assertOk()
            ->assertSee(__('feedback.index_title'))
            ->assertSee(__('feedback.types.suggestion'))
            ->assertSee($feedback->message)
            ->assertSee($feedback->page_url);
    }

    public function test_non_owners_cannot_view_feedback_index(): void
    {
        foreach (['manager', 'accountant', 'caretaker'] as $role) {
            $this->actingAs($this->user($role, "{$role}-feedback@example.com"))
                ->get(route('beta-feedback.index'))
                ->assertForbidden();
        }
    }

    public function test_feedback_index_is_scoped_to_current_organization(): void
    {
        $owner = $this->user('owner');
        $otherOwner = $this->user('owner', 'other-feedback-owner@example.com', 'Other Feedback Organization');

        BetaFeedback::create([
            'organization_id' => $owner->organization_id,
            'user_id' => $owner->id,
            'page_url' => 'https://beta.example.test/payments',
            'type' => 'bug',
            'message' => 'Visible organization feedback.',
        ]);
        BetaFeedback::create([
            'organization_id' => $otherOwner->organization_id,
            'user_id' => $otherOwner->id,
            'page_url' => 'https://beta.example.test/reports',
            'type' => 'bug',
            'message' => 'Other organization feedback.',
        ]);

        $this->actingAs($owner)
            ->get(route('beta-feedback.index'))
            ->assertOk()
            ->assertSee('Visible organization feedback.')
            ->assertDontSee('Other organization feedback.');
    }

    private function user(string $role, string $email = 'feedback-user@example.com', string $organizationName = 'Feedback Organization'): User
    {
        $organization = Organization::create(['name' => $organizationName]);

        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' Feedback User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
