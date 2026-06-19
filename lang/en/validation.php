<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
        'numeric' => 'The :attribute field must be at least :min.',
    ],
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
    ],
    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'name' => 'name',
        'organization_name' => 'organization',
    ],
];
