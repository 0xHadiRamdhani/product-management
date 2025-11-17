<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'spare_part_id',
        'location_id',
        'movement_type',
        'movement_direction',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_price',
        'reference_number',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePurchase($query)
    {
        return $query->where('movement_type', 'purchase');
    }

    public function scopeSale($query)
    {
        return $query->where('movement_type', 'sale');
    }

    public function scopeServiceUsage($query)
    {
        return $query->where('movement_type', 'service_usage');
    }

    public function scopeTransfer($query)
    {
        return $query->whereIn('movement_type', ['transfer_in', 'transfer_out']);
    }

    public function scopeAdjustment($query)
    {
        return $query->where('movement_type', 'adjustment');
    }

    public function scopeIn($query)
    {
        return $query->where('movement_direction', 'in');
    }

    public function scopeOut($query)
    {
        return $query->where('movement_direction', 'out');
    }

    public function isPurchase()
    {
        return $this->movement_type === 'purchase';
    }

    public function isSale()
    {
        return $this->movement_type === 'sale';
    }

    public function isServiceUsage()
    {
        return $this->movement_type === 'service_usage';
    }

    public function isTransfer()
    {
        return in_array($this->movement_type, ['transfer_in', 'transfer_out']);
    }

    public function isAdjustment()
    {
        return $this->movement_type === 'adjustment';
    }

    public function isIn()
    {
        return $this->movement_direction === 'in';
    }

    public function isOut()
    {
        return $this->movement_direction === 'out';
    }

    public function getMovementTypeLabelAttribute()
    {
        $labels = [
            'purchase' => 'Pembelian',
            'sale' => 'Penjualan',
            'service_usage' => 'Pemakaian Service',
            'transfer_in' => 'Transfer Masuk',
            'transfer_out' => 'Transfer Keluar',
            'adjustment' => 'Penyesuaian',
            'return' => 'Retur',
            'damage' => 'Kerusakan',
            'initial_stock' => 'Stok Awal',
        ];
        
        return $labels[$this->movement_type] ?? $this->movement_type;
    }

    public function getMovementDirectionLabelAttribute()
    {
        return $this->movement_direction === 'in' ? 'Masuk' : 'Keluar';
    }

    public function getReferenceModelAttribute()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }
        
        $modelMap = [
            'purchase_order' => PurchaseOrder::class,
            'purchase_order_item' => PurchaseOrderItem::class,
            'service_job' => ServiceJob::class,
            'service_job_part' => ServiceJobPart::class,
        ];
        
        $modelClass = $modelMap[$this->reference_type] ?? null;
        
        if ($modelClass && class_exists($modelClass)) {
            return $modelClass::find($this->reference_id);
        }
        
        return null;
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeBySparePart($query, $sparePartId)
    {
        return $query->where('spare_part_id', $sparePartId);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeByMovementType($query, $movementType)
    {
        return $query->where('movement_type', $movementType);
    }

    public function getProfitAttribute()
    {
        if ($this->isSale() && $this->total_price && $this->total_cost) {
            return $this->total_price - $this->total_cost;
        }
        
        return null;
    }

    public function getProfitMarginAttribute()
    {
        if ($this->isSale() && $this->profit && $this->total_cost) {
            return ($this->profit / $this->total_cost) * 100;
        }
        
        return null;
    }
}