<?php

namespace App\Models;

use App\Models\Concerns\ClearsPriceAnalyticsCache;
use Database\Factories\SrpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $price
 * @property Carbon $effective_date
 */
class Srp extends Model
{
    use ClearsPriceAnalyticsCache;

    /** @use HasFactory<SrpFactory> */
    use HasFactory;

    protected $fillable = [
        'commodity_id',
        'price',
        'effective_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Commodity, $this>
     */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
