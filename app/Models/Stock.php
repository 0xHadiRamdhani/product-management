<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'spare_part_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'average_cost',
        'total_value',
        'last_stock_date',
        'last_purchase_date',
        'last_sale_date',
        'total_purchases',
        'total_sales',
        'is_low_stock',
        'is_out_of_stock',
        'is_over_stock',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'reorder_point' => 'integer',
        'average_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'total_purchases' => 'integer',
        'total_sales' => 'integer',
        'is_low_stock' => 'boolean',
        'is_out_of_stock' => 'boolean',
        'is_over_stock' => 'boolean',
        'last_stock_date' => 'date',
        'last_purchase_date' => 'date',
        'last_sale_date' => 'date',
    ];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeLowStock($query)
    {
        return $query->where('is_low_stock', true);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('is_out_of_stock', true);
    }

    public function scopeOverStock($query)
    {
        return $query->where('is_over_stock', true);
    }

    public function updateStockLevels()
    {
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
        
        $this->is_low_stock = $this->available_quantity <= $this->reorder_point;
        $this->is_out_of_stock = $this->available_quantity <= 0;
        $this->is_over_stock = $this->available_quantity > $this->max_stock_level;
        
        $this->total_value = $this->quantity * $this->average_cost;
        
        $this->save();
    }

    public function addStock($quantity, $unitCost = null, $movementType = 'purchase')
    {
        $previousQuantity = $this->quantity;
        
        if ($unitCost) {
            $this->average_cost = (($this->quantity * $this->average_cost) + ($quantity * $unitCost)) / ($this->quantity + $quantity);
        }
        
        $this->quantity += $quantity;
        $this->updateStockLevels();
        
        $this->createStockMovement($movementType, 'in', $quantity, $previousQuantity, $this->quantity, $unitCost);
    }

    public function removeStock($quantity, $unitPrice = null, $movementType = 'sale')
    {
        $previousQuantity = $this->quantity;
        
        $this->quantity -= $quantity;
        $this->updateStockLevels();
        
        $this->createStockMovement($movementType, 'out', $quantity, $previousQuantity, $this->quantity, null, $unitPrice);
    }

    public function reserveStock($quantity)
    {
        if ($this->available_quantity >= $quantity) {
            $this->reserved_quantity += $quantity;
            $this->updateStockLevels();
            return true;
        }
        
        return false;
    }

    public function releaseStock($quantity)
    {
        if ($this->reserved_quantity >= $quantity) {
            $this->reserved_quantity -= $quantity;
            $this->updateStockLevels();
            return true;
        }
        
        return false;
    }

    protected function createStockMovement($type, $direction, $quantity, $previousQuantity, $newQuantity, $unitCost = null, $unitPrice = null)
    {
        return StockMovement::create([
            'spare_part_id' => $this->spare_part_id,
            'location_id' => $this->location_id,
            'movement_type' => $type,
            'movement_direction' => $direction,
            'quantity' => $quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'total_cost' => $unitCost ? $quantity * $unitCost : null,
            'total_price' => $unitPrice ? $quantity * $unitPrice : null,
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    public function checkLowStockAlert()
    {
        if ($this->is_low_stock && !$this->sparePart->lowStockAlerts()->where('location_id', $this->location_id)->where('is_resolved', false)->exists()) {
            LowStockAlert::create([
                'spare_part_id' => $this->spare_part_id,
                'location_id' => $this->location_id,
                'current_quantity' => $this->available_quantity,
                'reorder_point' => $this->reorder_point,
                'min_stock_level' => $this->min_stock_level,
                'alert_type' => 'low_stock',
                'severity' => $this->available_quantity <= 0 ? 'critical' : 'high',
            ]);
        }
    }
}