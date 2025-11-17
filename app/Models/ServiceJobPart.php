<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceJobPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_job_id',
        'spare_part_id',
        'location_id',
        'quantity_used',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_price',
        'discount_percentage',
        'discount_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity_used' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function serviceJob()
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeAllocated($query)
    {
        return $query->where('status', 'allocated');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function isAllocated()
    {
        return $this->status === 'allocated';
    }

    public function isUsed()
    {
        return $this->status === 'used';
    }

    public function isReturned()
    {
        return $this->status === 'returned';
    }

    public function canBeUsed()
    {
        return $this->isAllocated();
    }

    public function canBeReturned()
    {
        return $this->isAllocated();
    }

    public function getProfitAttribute()
    {
        return $this->total_price - $this->total_cost;
    }

    public function getProfitMarginAttribute()
    {
        if ($this->total_cost <= 0) {
            return 0;
        }
        
        return ($this->profit / $this->total_cost) * 100;
    }

    public function calculateTotals()
    {
        $this->discount_amount = ($this->unit_price * $this->quantity_used) * ($this->discount_percentage / 100);
        $this->total_price = ($this->unit_price * $this->quantity_used) - $this->discount_amount;
        $this->total_cost = $this->unit_cost * $this->quantity_used;
        $this->save();
    }
}