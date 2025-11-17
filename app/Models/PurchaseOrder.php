<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'location_id',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'priority',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'other_costs',
        'total_amount',
        'currency',
        'exchange_rate',
        'payment_terms',
        'payment_status',
        'payment_method',
        'reference_number',
        'tracking_number',
        'notes',
        'terms_conditions',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'approved', 'ordered']);
    }

    public function scopeReceived($query)
    {
        return $query->whereIn('status', ['partially_received', 'received']);
    }

    public function isPending()
    {
        return in_array($this->status, ['pending', 'approved', 'ordered']);
    }

    public function isReceived()
    {
        return in_array($this->status, ['partially_received', 'received']);
    }

    public function isFullyReceived()
    {
        return $this->status === 'received';
    }

    public function getTotalItemsAttribute()
    {
        return $this->items()->sum('quantity_ordered');
    }

    public function getTotalReceivedAttribute()
    {
        return $this->items()->sum('quantity_received');
    }

    public function getTotalPendingAttribute()
    {
        return $this->items()->sum('quantity_pending');
    }

    public function calculateTotals()
    {
        $subtotal = $this->items()->sum('total_amount');
        $this->subtotal = $subtotal;
        $this->tax_amount = $subtotal * 0.11; // 11% tax
        $this->total_amount = $subtotal + $this->tax_amount + $this->shipping_cost + $this->other_costs - $this->discount_amount;
        $this->save();
    }

    public function approve($userId)
    {
        if ($this->status === 'pending') {
            $this->status = 'approved';
            $this->approved_by = $userId;
            $this->approved_at = now();
            $this->save();
            
            return true;
        }
        
        return false;
    }

    public function markAsOrdered()
    {
        if ($this->status === 'approved') {
            $this->status = 'ordered';
            $this->save();
            
            return true;
        }
        
        return false;
    }

    public function receiveItem($itemId, $quantity, $unitCost = null)
    {
        $item = $this->items()->find($itemId);
        
        if (!$item) {
            return false;
        }
        
        if ($item->quantity_pending >= $quantity) {
            $item->quantity_received += $quantity;
            $item->quantity_pending = $item->quantity_ordered - $item->quantity_received;
            
            if ($unitCost) {
                $item->unit_cost = $unitCost;
            }
            
            if ($item->quantity_pending <= 0) {
                $item->status = 'received';
            } else {
                $item->status = 'partially_received';
            }
            
            $item->actual_delivery_date = now();
            $item->save();
            
            // Update stock
            $stock = Stock::where('spare_part_id', $item->spare_part_id)
                ->where('location_id', $this->location_id)
                ->first();
            
            if (!$stock) {
                $stock = Stock::create([
                    'spare_part_id' => $item->spare_part_id,
                    'location_id' => $this->location_id,
                    'quantity' => 0,
                    'available_quantity' => 0,
                    'average_cost' => $unitCost ?? 0,
                ]);
            }
            
            $stock->addStock($quantity, $unitCost ?? $item->unit_cost, 'purchase');
            
            // Update PO status
            $this->updateStatusFromItems();
            
            return true;
        }
        
        return false;
    }

    protected function updateStatusFromItems()
    {
        $totalItems = $this->items()->count();
        $receivedItems = $this->items()->where('status', 'received')->count();
        $partiallyReceivedItems = $this->items()->where('status', 'partially_received')->count();
        
        if ($receivedItems === $totalItems) {
            $this->status = 'received';
            $this->actual_delivery_date = now();
        } elseif ($partiallyReceivedItems > 0 || $receivedItems > 0) {
            $this->status = 'partially_received';
        }
        
        $this->save();
    }
}