<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemQc extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_item_qc';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_item_id',
        'inspected_by',
        'is_upper_clean',
        'is_midsole_clean',
        'is_outsole_clean',
        'is_completely_dry',
        'is_perfumed',
        'is_silica_packed',
        'qc_passed',
        'qc_notes',
        'inspected_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_upper_clean' => 'boolean',
            'is_midsole_clean' => 'boolean',
            'is_outsole_clean' => 'boolean',
            'is_completely_dry' => 'boolean',
            'is_perfumed' => 'boolean',
            'is_silica_packed' => 'boolean',
            'qc_passed' => 'boolean',
            'inspected_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
