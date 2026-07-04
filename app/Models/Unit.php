<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'building_id', 'unit_number', 'type', 'size', 'rooms', 'status',
        'rent_amount', 'notes',
    ];

    public function building() { return $this->belongsTo(Building::class); }
    public function contracts() { return $this->hasMany(Contract::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
    public function unitDocuments() { return $this->hasMany(UnitDocument::class)->latest(); }
}
