<?php

namespace Modules\Flight\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuote extends BaseModel
{
    protected $table = 'bc_tsa_supplier_quotes';

    protected $fillable = [
        'quote_uuid',
        'offer_id',
        'offer_uuid',
        'selected_fare_id',
        'supplier_code',
        'confirmed_currency',
        'confirmed_total_amount',
        'price_changed',
        'requirements_json',
        'rules_json',
        'payload_json',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'requirements_json' => 'array',
        'rules_json' => 'array',
        'payload_json' => 'array',
        'price_changed' => 'boolean',
        'expires_at' => 'datetime',
        'confirmed_total_amount' => 'float',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(SupplierOffer::class, 'offer_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThanOrEqualTo($this->expires_at);
    }
}
