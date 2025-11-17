<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'vehicle_type',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_year',
        'license_plate',
        'engine_number',
        'chassis_number',
        'mileage',
        'service_date',
        'completion_date',
        'status',
        'priority',
        'problem_description',
        'work_description',
        'notes',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'total_amount',
        'payment_status',
        'payment_method',
        'location_id',
        'mechanic_id',
        'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'completion_date' => 'date',
        'mileage' => 'integer',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceJobParts()
    {
        return $this->hasMany(ServiceJobPart::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeWaitingParts($query)
    {
        return $query->where('status', 'waiting_parts');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isWaitingParts()
    {
        return $this->status === 'waiting_parts';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function canAddParts()
    {
        return in_array($this->status, ['pending', 'in_progress', 'waiting_parts']);
    }

    public function calculateCosts()
    {
        $this->parts_cost = $this->serviceJobParts()->sum('total_price');
        $this->total_cost = $this->labor_cost + $this->parts_cost;
        $this->total_amount = $this->total_cost;
        $this->save();
    }

    public function addPart($sparePartId, $locationId, $quantity, $unitPrice = null)
    {
        if (!$this->canAddParts()) {
            return false;
        }

        $sparePart = SparePart::find($sparePartId);
        
        if (!$sparePart) {
            return false;
        }

        $stock = Stock::where('spare_part_id', $sparePartId)
            ->where('location_id', $locationId)
            ->first();

        if (!$stock || $stock->available_quantity < $quantity) {
            return false;
        }

        // Reserve stock
        if (!$stock->reserveStock($quantity)) {
            return false;
        }

        $unitCost = $stock->average_cost;
        $unitPrice = $unitPrice ?? $sparePart->selling_price;
        $totalCost = $unitCost * $quantity;
        $totalPrice = $unitPrice * $quantity;

        $serviceJobPart = ServiceJobPart::create([
            'service_job_id' => $this->id,
            'spare_part_id' => $sparePartId,
            'location_id' => $locationId,
            'quantity_used' => $quantity,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'total_cost' => $totalCost,
            'total_price' => $totalPrice,
            'status' => 'allocated',
        ]);

        // Update job costs
        $this->calculateCosts();

        return $serviceJobPart;
    }

    public function usePart($serviceJobPartId)
    {
        $serviceJobPart = $this->serviceJobParts()->find($serviceJobPartId);
        
        if (!$serviceJobPart || $serviceJobPart->status !== 'allocated') {
            return false;
        }

        $serviceJobPart->status = 'used';
        $serviceJobPart->save();

        // Remove stock
        $stock = Stock::where('spare_part_id', $serviceJobPart->spare_part_id)
            ->where('location_id', $serviceJobPart->location_id)
            ->first();

        if ($stock) {
            $stock->removeStock($serviceJobPart->quantity_used, $serviceJobPart->unit_price, 'service_usage');
            $stock->releaseStock($serviceJobPart->quantity_used); // Release the reservation
        }

        return true;
    }

    public function returnPart($serviceJobPartId)
    {
        $serviceJobPart = $this->serviceJobParts()->find($serviceJobPartId);
        
        if (!$serviceJobPart || $serviceJobPart->status !== 'allocated') {
            return false;
        }

        $serviceJobPart->status = 'returned';
        $serviceJobPart->save();

        // Release stock reservation
        $stock = Stock::where('spare_part_id', $serviceJobPart->spare_part_id)
            ->where('location_id', $serviceJobPart->location_id)
            ->first();

        if ($stock) {
            $stock->releaseStock($serviceJobPart->quantity_used);
        }

        // Update job costs
        $this->calculateCosts();

        return true;
    }

    public function complete()
    {
        if ($this->status !== 'completed') {
            $this->status = 'completed';
            $this->completion_date = now();
            $this->save();
            
            // Use all allocated parts
            foreach ($this->serviceJobParts()->where('status', 'allocated')->get() as $part) {
                $this->usePart($part->id);
            }
            
            return true;
        }
        
        return false;
    }

    public function getVehicleInfoAttribute()
    {
        $info = [];
        
        if ($this->vehicle_brand) {
            $info[] = $this->vehicle_brand;
        }
        
        if ($this->vehicle_model) {
            $info[] = $this->vehicle_model;
        }
        
        if ($this->vehicle_year) {
            $info[] = $this->vehicle_year;
        }
        
        return implode(' ', $info);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'gray',
            'in_progress' => 'blue',
            'waiting_parts' => 'yellow',
            'completed' => 'green',
            'cancelled' => 'red',
        ];
        
        return $colors[$this->status] ?? 'gray';
    }
}