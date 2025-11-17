<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'spare_part_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_pending',
        'unit_cost',
        'unit_price',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'total_amount',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'quantity_pending' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopePartiallyReceived($query)
    {
        return $query->where('status', 'partially_received');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isReceived()
    {
        return $this->status === 'received';
    }

    public function isPartiallyReceived()
    {
        return $this->status === 'partially_received';
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->unit_price * $this->quantity_ordered;
        $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        $this->tax_amount = ($this->subtotal - $this->discount_amount) * ($this->tax_percentage / 100);
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
        $this->save();
    }

    public function getReceivingProgressAttribute()
    {
        if ($this->quantity_ordered <= 0) {
            return 0;
        }
        
        return ($this->quantity_received / $this->quantity_ordered) * 100;
    }

    public function canReceiveMore()
    {
        return $this->quantity_pending > 0;
    }

    public function getRemainingQuantityAttribute()
    {
        return $this->quantity_pending;
    }
}