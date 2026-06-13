<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name'];

    public function users() { return $this->hasMany(User::class); }
    public function buildings() { return $this->hasMany(Building::class); }
    public function tenants() { return $this->hasMany(Tenant::class); }
    public function contracts() { return $this->hasMany(Contract::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }
}
