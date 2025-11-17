<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'changed_fields',
        'user_type',
        'user_id',
        'ip_address',
        'user_agent',
        'url',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeByRecord($query, $recordId)
    {
        return $query->where('record_id', $recordId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeCreateActions($query)
    {
        return $query->where('action', 'create');
    }

    public function scopeUpdateActions($query)
    {
        return $query->where('action', 'update');
    }

    public function scopeDeleteActions($query)
    {
        return $query->where('action', 'delete');
    }

    public function scopeRestoreActions($query)
    {
        return $query->where('action', 'restore');
    }

    public function isCreateAction()
    {
        return $this->action === 'create';
    }

    public function isUpdateAction()
    {
        return $this->action === 'update';
    }

    public function isDeleteAction()
    {
        return $this->action === 'delete';
    }

    public function isRestoreAction()
    {
        return $this->action === 'restore';
    }

    public function getActionLabelAttribute()
    {
        $labels = [
            'create' => 'Dibuat',
            'update' => 'Diperbarui',
            'delete' => 'Dihapus',
            'restore' => 'Dipulihkan',
        ];
        
        return $labels[$this->action] ?? $this->action;
    }

    public function getChangedFieldsListAttribute()
    {
        if (!$this->changed_fields || !is_array($this->changed_fields)) {
            return [];
        }
        
        return $this->changed_fields;
    }

    public function getOldValue($field)
    {
        return $this->old_values[$field] ?? null;
    }

    public function getNewValue($field)
    {
        return $this->new_values[$field] ?? null;
    }

    public function hasFieldChanged($field)
    {
        return in_array($field, $this->changed_fields ?? []);
    }

    public static function log($tableName, $recordId, $action, $oldValues = null, $newValues = null, $userId = null)
    {
        $changedFields = [];
        
        if ($action === 'update' && $oldValues && $newValues) {
            foreach ($newValues as $key => $value) {
                if (array_key_exists($key, $oldValues) && $oldValues[$key] !== $value) {
                    $changedFields[] = $key;
                }
            }
        }
        
        return self::create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'user_type' => 'App\\Models\\User',
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'created_at' => now(),
        ]);
    }

    public static function logModel($model, $action, $userId = null)
    {
        $tableName = $model->getTable();
        $recordId = $model->getKey();
        
        $oldValues = null;
        $newValues = null;
        
        if ($action === 'update') {
            $oldValues = $model->getOriginal();
            $newValues = $model->getAttributes();
        } elseif ($action === 'create') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'delete') {
            $oldValues = $model->getAttributes();
        }
        
        return self::log($tableName, $recordId, $action, $oldValues, $newValues, $userId);
    }

    public static function getModelHistory($modelClass, $recordId)
    {
        $tableName = (new $modelClass)->getTable();
        
        return self::byTable($tableName)
            ->byRecord($recordId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getUserActivity($userId, $limit = 50)
    {
        return self::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getRecentActivity($limit = 50)
    {
        return self::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getActivityByTable($tableName, $limit = 50)
    {
        return self::byTable($tableName)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function cleanup($daysToKeep = 90)
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return self::where('created_at', '<', $cutoffDate)->delete();
    }
}
