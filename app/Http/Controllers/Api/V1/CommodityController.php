<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompareRequest;
use App\Http\Requests\StoreCommodityRequest;
use App\Http\Requests\TrendRequest;
use App\Http\Resources\CommodityResource;
use App\Models\Commodity;
use App\Services\PriceAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;

#[Group('Commodities', 'Basic goods tracked by the monitor, with price trends and SRP comparison.')]
class CommodityController extends Controller
{
    public function __construct(private readonly PriceAnalyticsService $analytics) {}

    #[QueryParam('category', 'string', 'Filter by category.', required: false, example: 'Rice')]
    #[QueryParam('q', 'string', 'Search by name.', required: false, example: 'pork')]
    #[QueryParam('page', 'integer', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Max 100.', required: false, example: 20)]
    public function index(Request $request): JsonResponse
    {
        $commodities = Commodity::query()
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('q'), fn ($query) => $query->whereLike('name', '%'.$request->string('q').'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated($commodities, CommodityResource::class);
    }

    public function show(Commodity $commodity): CommodityResource
    {
        return CommodityResource::make($commodity);
    }

    #[Authenticated]
    public function store(StoreCommodityRequest $request): JsonResponse
    {
        $commodity = Commodity::query()->create($request->validated());

        return CommodityResource::make($commodity)->response()->setStatusCode(201);
    }

    #[Authenticated]
    public function update(StoreCommodityRequest $request, Commodity $commodity): CommodityResource
    {
        $commodity->update($request->validated());

        return CommodityResource::make($commodity);
    }

    #[Authenticated]
    public function destroy(Commodity $commodity): Response
    {
        $commodity->delete();

        return response()->noContent();
    }

    /**
     * Daily price trend
     *
     * Average, minimum and maximum reported price per day. Defaults to the last 30 days.
     */
    public function trend(TrendRequest $request, Commodity $commodity): JsonResponse
    {
        $from = $request->from();
        $to = $request->to();
        $marketId = $request->filled('market_id') ? $request->integer('market_id') : null;

        return $this->data([
            'commodity_id' => $commodity->id,
            'market_id' => $marketId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => $this->analytics->dailyTrend($commodity, $from, $to, $marketId),
        ]);
    }

    /**
     * Compare with SRP
     *
     * Average reported price over the last `days` days (default 7) against the SRP in effect today.
     */
    public function compare(CompareRequest $request, Commodity $commodity): JsonResponse
    {
        $marketId = $request->filled('market_id') ? $request->integer('market_id') : null;

        return $this->data($this->analytics->compareWithSrp($commodity, $request->days(), $marketId));
    }
}
