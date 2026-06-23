<?php

namespace Modules\Flight\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierSearchLog extends Model
{
    protected $table = 'bc_tsa_supplier_search_logs';

    protected $fillable = [
        'search_uuid',
        'search_hash',
        'supplier_mode',
        'supplier_code',
        'origin',
        'destination',
        'departure_date',
        'return_date',
        'adult_count',
        'child_count',
        'infant_count',
        'cabin_class',
        'user_id',
        'ip_hash',
        'session_hash',
        'status',
        'source',
        'offers_count',
        'duration_ms',
        'criteria_json',
        'guard_context_json',
        'error_message',
        'booked_at',
        'booking_id',
    ];

    protected $casts = [
        'criteria_json' => 'array',
        'guard_context_json' => 'array',
        'departure_date' => 'date',
        'return_date' => 'date',
        'booked_at' => 'datetime',
    ];
}
