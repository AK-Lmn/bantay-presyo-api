<?php

namespace App\Models;

use Database\Factories\MarketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    /** @use HasFactory<MarketFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'region',
    ];

    /**
     * @return HasMany<PriceReport, $this>
     */
    public function priceReports(): HasMany
    {
        return $this->hasMany(PriceReport::class);
    }
}
