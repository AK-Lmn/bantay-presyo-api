<?php

namespace App\Models;

use Database\Factories\CommodityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Commodity extends Model
{
    /** @use HasFactory<CommodityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit',
    ];

    /**
     * @return HasMany<Srp, $this>
     */
    public function srps(): HasMany
    {
        return $this->hasMany(Srp::class);
    }

    /**
     * @return HasMany<PriceReport, $this>
     */
    public function priceReports(): HasMany
    {
        return $this->hasMany(PriceReport::class);
    }

    public function currentSrp(?Carbon $asOf = null): ?Srp
    {
        return $this->srps()
            ->whereDate('effective_date', '<=', ($asOf ?? now())->toDateString())
            ->orderByDesc('effective_date')
            ->first();
    }
}
