<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'location', 'description'];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function units() { return $this->hasMany(Unit::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
}
