<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'organization_id', 'full_name', 'phone', 'email', 'id_number',
        'nationality', 'notes',
    ];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function contracts() { return $this->hasMany(Contract::class); }
}
