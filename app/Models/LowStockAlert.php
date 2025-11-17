<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LowStockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'spare_part_id',
        'location_id',
        'current_quantity',
        'reorder_point',
        'min_stock_level',
        'alert_type',
        'severity',
        'is_resolved',
        'resolved_date',
        'resolution_notes',
        'resolved_by',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'reorder_point' => 'integer',
        'min_stock_level' => 'integer',
        'is_resolved' => 'boolean',
        'resolved_date' => 'date',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
    ];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeEmailNotSent($query)
    {
        return $query->where('email_sent', false);
    }

    public function scopeEmailSent($query)
    {
        return $query->where('email_sent', true);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeMedium($query)
    {
        return $query->where('severity', 'medium');
    }

    public function scopeLow($query)
    {
        return $query->where('severity', 'low');
    }

    public function isResolved()
    {
        return $this->is_resolved;
    }

    public function isUnresolved()
    {
        return !$this->is_resolved;
    }

    public function isEmailSent()
    {
        return $this->email_sent;
    }

    public function isCritical()
    {
        return $this->severity === 'critical';
    }

    public function isHigh()
    {
        return $this->severity === 'high';
    }

    public function isMedium()
    {
        return $this->severity === 'medium';
    }

    public function isLow()
    {
        return $this->severity === 'low';
    }

    public function resolve($resolutionNotes = null, $userId = null)
    {
        if ($this->is_resolved) {
            return false;
        }

        $this->is_resolved = true;
        $this->resolved_date = now();
        $this->resolution_notes = $resolutionNotes;
        $this->resolved_by = $userId ?? auth()->id();
        $this->save();

        return true;
    }

    public function markEmailSent()
    {
        $this->email_sent = true;
        $this->email_sent_at = now();
        $this->save();
    }

    public function getAlertTypeLabelAttribute()
    {
        $labels = [
            'low_stock' => 'Stok Rendah',
            'out_of_stock' => 'Stok Habis',
            'over_stock' => 'Stok Berlebih',
        ];
        
        return $labels[$this->alert_type] ?? $this->alert_type;
    }

    public function getSeverityLabelAttribute()
    {
        $labels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'critical' => 'Kritis',
        ];
        
        return $labels[$this->severity] ?? $this->severity;
    }

    public function getSeverityColorAttribute()
    {
        $colors = [
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
        ];
        
        return $colors[$this->severity] ?? 'gray';
    }

    public static function createAlert($sparePartId, $locationId, $alertType = 'low_stock')
    {
        $stock = Stock::where('spare_part_id', $sparePartId)
            ->where('location_id', $locationId)
            ->first();

        if (!$stock) {
            return null;
        }

        $currentQuantity = $stock->available_quantity;
        $reorderPoint = $stock->reorder_point;
        $minStockLevel = $stock->min_stock_level;

        // Determine severity
        if ($currentQuantity <= 0) {
            $severity = 'critical';
            $alertType = 'out_of_stock';
        } elseif ($currentQuantity <= ($reorderPoint * 0.5)) {
            $severity = 'critical';
        } elseif ($currentQuantity <= $reorderPoint) {
            $severity = 'high';
        } elseif ($currentQuantity <= ($reorderPoint * 1.5)) {
            $severity = 'medium';
        } else {
            $severity = 'low';
        }

        // Check if alert already exists
        $existingAlert = self::where('spare_part_id', $sparePartId)
            ->where('location_id', $locationId)
            ->where('alert_type', $alertType)
            ->where('is_resolved', false)
            ->first();

        if ($existingAlert) {
            // Update existing alert
            $existingAlert->current_quantity = $currentQuantity;
            $existingAlert->severity = $severity;
            $existingAlert->save();
            
            return $existingAlert;
        }

        return self::create([
            'spare_part_id' => $sparePartId,
            'location_id' => $locationId,
            'current_quantity' => $currentQuantity,
            'reorder_point' => $reorderPoint,
            'min_stock_level' => $minStockLevel,
            'alert_type' => $alertType,
            'severity' => $severity,
            'is_resolved' => false,
        ]);
    }

    public static function checkAndCreateAlerts()
    {
        $alertsCreated = 0;
        
        $stocks = Stock::where(function ($query) {
            $query->where('is_low_stock', true)
                  ->orWhere('is_out_of_stock', true);
        })->get();

        foreach ($stocks as $stock) {
            $alertType = $stock->is_out_of_stock ? 'out_of_stock' : 'low_stock';
            $alert = self::createAlert($stock->spare_part_id, $stock->location_id, $alertType);
            
            if ($alert) {
                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }
}