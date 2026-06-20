<?php

namespace Modules\Flight\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Booking\Models\Booking;

class SupplierBooking extends BaseModel
{
    protected $table = 'bc_tsa_supplier_bookings';

    protected $fillable = [
        'booking_id',
        'quote_id',
        'quote_uuid',
        'supplier_code',
        'supplier_booking_reference',
        'pnr',
        'ticket_numbers_json',
        'payment_status',
        'fulfillment_status',
        'manual_review_required',
        'snapshot_json',
    ];

    protected $casts = [
        'ticket_numbers_json' => 'array',
        'snapshot_json' => 'array',
        'manual_review_required' => 'boolean',
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
