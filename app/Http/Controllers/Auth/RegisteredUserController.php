<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register', [
            'countries' => Country::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'country_id' => [
                'nullable',
                'integer',
                Rule::exists('countries', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $organizationName = trim((string) ($data['organization_name'] ?? ''));
        if ($organizationName === '') {
            $organizationName = app()->isLocale('ar')
                ? 'حساب عقارات '.$data['name']
                : $data['name']."'s Property Account";
        }

        $country = null;

        if (! empty($data['country_id'])) {
            $country = Country::active()->find($data['country_id']);
        }

        $organization = Organization::create([
            'name' => $organizationName,
            'country_id' => $country?->id,
            'currency_code' => $country?->default_currency_code,
            'locale' => $country?->default_locale,
            'timezone' => $country?->default_timezone,
        ]);

        $user = User::create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'owner',
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
