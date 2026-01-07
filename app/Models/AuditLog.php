<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
    'user_name',
    'user_email',
    'event_type',
    'action',
    'description',
    'ip_address',
    'user_agent',
    'device_type',
    'browser',
    'platform',
    'severity',
    'status', 
];

    protected $casts = [
        'req_time' => 'datetime',
        'vardata' => 'array', // JSON decode automatically
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Scope for filtering by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('req_time', [$from, $to]);
    }

    /**
     * Scope for searching
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('user_id', 'LIKE', "%{$search}%")
              ->orWhere('category', 'LIKE', "%{$search}%")
              ->orWhereRaw("vardata::text LIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * Get action from vardata
     */
    public function getActionAttribute()
    {
        $data = is_array($this->vardata) ? $this->vardata : json_decode($this->vardata, true);
        return $data['action'] ?? 'unknown';
    }

    /**
     * Get description from vardata
     */
    public function getDescriptionAttribute()
    {
        $data = is_array($this->vardata) ? $this->vardata : json_decode($this->vardata, true);
        return $data['description'] ?? '';
    }

    /**
     * Get severity from vardata
     */
    public function getSeverityAttribute()
    {
        $data = is_array($this->vardata) ? $this->vardata : json_decode($this->vardata, true);
        return $data['severity'] ?? 'low';
    }

    /**
     * Get status from vardata
     */
    public function getStatusAttribute()
    {
        $data = is_array($this->vardata) ? $this->vardata : json_decode($this->vardata, true);
        return $data['status'] ?? 'success';
    }

    /**
     * Get IP address from vardata
     */
    public function getIpAddressAttribute()
    {
        $data = is_array($this->vardata) ? $this->vardata : json_decode($this->vardata, true);
        return $data['ip_address'] ?? 'N/A';
    }

    /**
     * Get user agent from vardata
     */
    public function getUserAgentAttribute()
    {
        $data = is_array($this->vardata) ? $this->vardata : json_decode($this->vardata, true);
        return $data['user_agent'] ?? 'N/A';
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColorAttribute()
    {
        return match($this->severity) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'dark',
            default => 'secondary'
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'suspicious' => 'warning',
            'blocked' => 'dark',
            default => 'secondary'
        };
    }
}