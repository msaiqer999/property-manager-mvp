<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitDocument extends Model
{
    public const CATEGORIES = [
        'tenant_id_copy',
        'contract_attachment',
        'ownership_document',
        'utility_bill',
        'maintenance_photo',
        'external_invoice_archive',
        'inspection_photo',
        'handover_document',
        'other',
    ];

    protected $fillable = [
        'organization_id',
        'unit_id',
        'uploaded_by',
        'title',
        'category',
        'notes',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
