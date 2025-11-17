<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarkupRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'category_id',
        'supplier_id',
        'min_cost',
        'max_cost',
        'markup_percentage',
        'min_markup',
        'max_markup',
        'priority',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_cost' => 'decimal:2',
        'max_cost' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'min_markup' => 'decimal:2',
        'max_markup' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query, $priority = 'high')
    {
        return $query->where('priority', $priority);
    }

    public function scopeUniversal($query)
    {
        return $query->where('rule_type', 'universal');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('rule_type', 'category')
                    ->where('category_id', $categoryId);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('rule_type', 'supplier')
                    ->where('supplier_id', $supplierId);
    }

    public function scopeByCostRange($query, $cost)
    {
        return $query->where('rule_type', 'cost_range')
                    ->where(function ($q) use ($cost) {
                        $q->whereNull('min_cost')
                          ->orWhere('min_cost', '<=', $cost);
                    })
                    ->where(function ($q) use ($cost) {
                        $q->whereNull('max_cost')
                          ->orWhere('max_cost', '>=', $cost);
                    });
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function isUniversal()
    {
        return $this->rule_type === 'universal';
    }

    public function isCategoryRule()
    {
        return $this->rule_type === 'category';
    }

    public function isSupplierRule()
    {
        return $this->rule_type === 'supplier';
    }

    public function isCostRangeRule()
    {
        return $this->rule_type === 'cost_range';
    }

    public function appliesToSparePart($sparePart)
    {
        if (!$this->is_active) {
            return false;
        }

        switch ($this->rule_type) {
            case 'universal':
                return true;
                
            case 'category':
                return $sparePart->category_id === $this->category_id;
                
            case 'supplier':
                // This would require tracking supplier per spare part
                // For now, we'll return false as we don't have this relationship
                return false;
                
            case 'cost_range':
                $cost = $sparePart->cost_price;
                return ($this->min_cost === null || $cost >= $this->min_cost) &&
                       ($this->max_cost === null || $cost <= $this->max_cost);
                
            default:
                return false;
        }
    }

    public function calculateSellingPrice($costPrice)
    {
        $sellingPrice = $costPrice * (1 + ($this->markup_percentage / 100));
        
        // Apply min/max markup constraints if set
        if ($this->min_markup !== null) {
            $minPrice = $costPrice + $this->min_markup;
            $sellingPrice = max($sellingPrice, $minPrice);
        }
        
        if ($this->max_markup !== null) {
            $maxPrice = $costPrice + $this->max_markup;
            $sellingPrice = min($sellingPrice, $maxPrice);
        }
        
        return $sellingPrice;
    }

    public static function getApplicableRules($sparePart)
    {
        return self::active()
            ->where(function ($query) use ($sparePart) {
                $query->where('rule_type', 'universal')
                      ->orWhere(function ($q) use ($sparePart) {
                          $q->where('rule_type', 'category')
                            ->where('category_id', $sparePart->category_id);
                      })
                      ->orWhere(function ($q) use ($sparePart) {
                          $q->where('rule_type', 'cost_range')
                            ->where(function ($q2) use ($sparePart) {
                                $q2->whereNull('min_cost')
                                   ->orWhere('min_cost', '<=', $sparePart->cost_price);
                            })
                            ->where(function ($q2) use ($sparePart) {
                                $q2->whereNull('max_cost')
                                   ->orWhere('max_cost', '>=', $sparePart->cost_price);
                            });
                      });
            })
            ->orderBy('sort_order')
            ->orderBy('priority', 'desc')
            ->get();
    }

    public static function calculateSellingPriceForSparePart($sparePart)
    {
        $rules = self::getApplicableRules($sparePart);
        
        if ($rules->isEmpty()) {
            return $sparePart->cost_price; // Return cost price if no rules apply
        }
        
        // Use the first (highest priority) rule
        $rule = $rules->first();
        
        return $rule->calculateSellingPrice($sparePart->cost_price);
    }

    public static function applyRulesToAllSpareParts()
    {
        $updatedCount = 0;
        
        $spareParts = SparePart::active()->get();
        
        foreach ($spareParts as $sparePart) {
            $newSellingPrice = self::calculateSellingPriceForSparePart($sparePart);
            
            if ($newSellingPrice !== $sparePart->selling_price) {
                $sparePart->selling_price = $newSellingPrice;
                $sparePart->save();
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    public function getRuleTypeLabelAttribute()
    {
        $labels = [
            'universal' => 'Universal',
            'category' => 'Kategori',
            'supplier' => 'Supplier',
            'cost_range' => 'Range Harga',
        ];
        
        return $labels[$this->rule_type] ?? $this->rule_type;
    }

    public function getPriorityLabelAttribute()
    {
        $labels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'highest' => 'Tertinggi',
        ];
        
        return $labels[$this->priority] ?? $this->priority;
    }
}