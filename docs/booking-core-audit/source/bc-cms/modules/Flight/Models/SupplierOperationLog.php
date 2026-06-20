<?php

namespace Modules\Flight\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Booking\Models\Booking;

class SupplierOperationLog extends BaseModel
{
    protected $table = 'bc_tsa_supplier_operation_logs';

    protected $fillable = [
        'booking_id',
        'quote_id',
        'quote_uuid',
        'supplier_code',
        'operation',
        'status',
        'normalized_error_code',
        'supplier_error_raw',
        'request_json',
        'response_json',
        'duration_ms',
        'correlation_id',
    ];

    protected $casts = [
        'request_json' => 'array',
        'response_json' => 'array',
        'duration_ms' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class, 'quote_id');
    }
}
