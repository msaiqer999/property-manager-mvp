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
        abort_unless(in_array(auth()->user()->role->value, ['owner', 'manager'], true), 403);

        $feedback = BetaFeedback::with('user')
            ->where('organization_id', $this->organizationId())
            ->latest()
            ->paginate(30);

        return view('beta-feedback.index', compact('feedback'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['bug', 'confusion', 'confusing', 'suggestion', 'other'])],
            'message' => ['required', 'string', 'max:5000'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'screenshot_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $type = $data['type'] === 'confusing' ? 'confusion' : $data['type'];

        BetaFeedback::create([
            'organization_id' => $this->organizationId(),
            'user_id' => auth()->id(),
            'page_url' => $this->safePageUrl($data['page_url'] ?: url()->previous()),
            'type' => $type,
            'message' => $data['message'],
            'screenshot_note' => $data['screenshot_note'] ?? null,
        ]);

        return back()->with('status', __('feedback.submitted'));
    }

    private function safePageUrl(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return url()->previous();
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return url()->previous();
        }

        $safeUrl = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? request()->getHost());
        $safeUrl .= isset($parts['port']) ? ':'.$parts['port'] : '';
        $safeUrl .= $parts['path'] ?? '/';

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $blockedKeys = ['token', 'password', 'secret', 'cookie', 'session', 'api_key', 'apikey'];
            $query = collect($query)
                ->reject(fn ($value, $key) => in_array(strtolower((string) $key), $blockedKeys, true))
                ->all();

            if ($query !== []) {
                $safeUrl .= '?'.http_build_query($query);
            }
        }

        return $safeUrl;
    }
}
