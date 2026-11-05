<?php

namespace App\Models;

use App\Models\Concerns\ClearsPriceAnalyticsCache;
use Database\Factories\PriceReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $price
 * @property string|null $srp_price
 * @property bool $is_flagged
 * @property Carbon $reported_at
 * @property Carbon|null $hidden_at
 */
class PriceReport extends Model
{
    use ClearsPriceAnalyticsCache;

    /** @use HasFactory<PriceReportFactory> */
    use HasFactory;

    protected $attributes = [
        'is_flagged' => false,
    ];

    protected $fillable = [
        'commodity_id',
        'market_id',
        'user_id',
        'price',
        'srp_price',
        'is_flagged',
        'reported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'srp_price' => 'decimal:2',
            'is_flagged' => 'boolean',
            'reported_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<PriceReport>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull('hidden_at');
    }

    public function hide(User $moderator, ?string $reason): void
    {
        $this->forceFill([
            'hidden_at' => now(),
            'hidden_by' => $moderator->id,
            'hidden_reason' => $reason,
        ])->save();
    }

    public function unhide(): void
    {
        $this->forceFill([
            'hidden_at' => null,
            'hidden_by' => null,
            'hidden_reason' => null,
        ])->save();
    }

    /**
     * @return BelongsTo<Commodity, $this>
     */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    /**
     * @return BelongsTo<Market, $this>
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
