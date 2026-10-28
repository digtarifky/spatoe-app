<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'service_id',
        'technician_id',
        'item_tag_code',
        'shoe_brand_model',
        'shoe_material',
        'shoe_color',
        'defect_notes',
        'storage_shelf_number',
        'status',
        'estimated_completion',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_completion' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function qc(): HasOne
    {
        return $this->hasOne(OrderItemQc::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OrderItemPhoto::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderItemStatusLog::class);
    }
}
