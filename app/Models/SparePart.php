<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SparePart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'description',
        'category_id',
        'brand',
        'model',
        'part_number',
        'barcode',
        'unit',
        'cost_price',
        'selling_price',
        'markup_percentage',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'weight',
        'dimensions',
        'material',
        'color',
        'warranty_period',
        'compatibility',
        'manufacturer',
        'country_of_origin',
        'hs_code',
        'tax_type',
        'tax_percentage',
        'is_active',
        'is_taxable',
        'is_discountable',
        'image',
        'gallery',
        'specifications',
        'view_count',
        'purchase_count',
        'sale_count',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'weight' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'is_discountable' => 'boolean',
        'gallery' => 'array',
        'specifications' => 'array',
        'view_count' => 'integer',
        'purchase_count' => 'integer',
        'sale_count' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function serviceJobParts()
    {
        return $this->hasMany(ServiceJobPart::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function lowStockAlerts()
    {
        return $this->hasMany(LowStockAlert::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithBarcode($query)
    {
        return $query->whereNotNull('barcode');
    }

    public function getTotalStockAttribute()
    {
        return $this->stock()->sum('quantity');
    }

    public function getTotalAvailableStockAttribute()
    {
        return $this->stock()->sum('available_quantity');
    }

    public function isLowStock()
    {
        return $this->getTotalAvailableStockAttribute() <= $this->reorder_point;
    }

    public function isOutOfStock()
    {
        return $this->getTotalAvailableStockAttribute() <= 0;
    }

    public function calculateSellingPrice()
    {
        if ($this->markup_percentage > 0) {
            return $this->cost_price * (1 + ($this->markup_percentage / 100));
        }
        
        return $this->selling_price;
    }

    public function updateSellingPriceFromMarkup()
    {
        if ($this->markup_percentage > 0) {
            $this->selling_price = $this->calculateSellingPrice();
            $this->save();
        }
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function incrementPurchaseCount($quantity = 1)
    {
        $this->increment('purchase_count', $quantity);
    }

    public function incrementSaleCount($quantity = 1)
    {
        $this->increment('sale_count', $quantity);
    }
}