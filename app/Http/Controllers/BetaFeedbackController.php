<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\BetaFeedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BetaFeedbackController extends Controller
{
    use ScopesOrganization;

    public function index()
    {
        abort_unless(auth()->user()->role->can('manage-users'), 403);

        $feedback = BetaFeedback::with('user')
            ->where('organization_id', $this->organizationId())
            ->latest()
            ->paginate(30);

        return view('beta-feedback.index', compact('feedback'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['bug', 'confusing', 'suggestion', 'other'])],
            'message' => ['required', 'string', 'max:5000'],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        BetaFeedback::create([
            'organization_id' => $this->organizationId(),
            'user_id' => auth()->id(),
            'page_url' => $data['page_url'] ?: url()->previous(),
            'type' => $data['type'],
            'message' => $data['message'],
        ]);

        return back()->with('status', __('feedback.submitted'));
    }
}
