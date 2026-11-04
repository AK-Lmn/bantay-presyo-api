<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarketRequest;
use App\Http\Resources\MarketResource;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;

#[Group('Markets', 'Public markets where prices are reported.')]
class MarketController extends Controller
{
    #[QueryParam('city', 'string', 'Filter by city.', required: false, example: 'Quezon City')]
    #[QueryParam('region', 'string', 'Filter by region.', required: false, example: 'NCR')]
    #[QueryParam('page', 'integer', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Max 100.', required: false, example: 20)]
    public function index(Request $request): JsonResponse
    {
        $markets = Market::query()
            ->when($request->filled('city'), fn ($query) => $query->where('city', $request->string('city')))
            ->when($request->filled('region'), fn ($query) => $query->where('region', $request->string('region')))
            ->orderBy('region')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated($markets, MarketResource::class);
    }

    public function show(Market $market): MarketResource
    {
        return MarketResource::make($market);
    }

    #[Authenticated]
    public function store(StoreMarketRequest $request): JsonResponse
    {
        $market = Market::query()->create($request->validated());

        return MarketResource::make($market)->response()->setStatusCode(201);
    }

    #[Authenticated]
    public function update(StoreMarketRequest $request, Market $market): MarketResource
    {
        $market->update($request->validated());

        return MarketResource::make($market);
    }

    #[Authenticated]
    public function destroy(Market $market): Response
    {
        $market->delete();

        return response()->noContent();
    }
}
