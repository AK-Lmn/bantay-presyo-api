<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListPriceReportsRequest;
use App\Http\Requests\StorePriceReportRequest;
use App\Http\Resources\PriceReportResource;
use App\Models\PriceReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;

#[Group('Price reports', 'Prices seen at a market on a given day, submitted by registered users.')]
class PriceReportController extends Controller
{
    public function index(ListPriceReportsRequest $request): JsonResponse
    {
        $reports = PriceReport::query()
            ->with(['commodity', 'market'])
            ->when($request->filled('commodity_id'), fn ($query) => $query->where('commodity_id', $request->integer('commodity_id')))
            ->when($request->filled('market_id'), fn ($query) => $query->where('market_id', $request->integer('market_id')))
            ->when($request->filled('from'), fn ($query) => $query->where('reported_at', '>=', Carbon::parse($request->string('from'))->startOfDay()))
            ->when($request->filled('to'), fn ($query) => $query->where('reported_at', '<=', Carbon::parse($request->string('to'))->endOfDay()))
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->paginated($reports, PriceReportResource::class);
    }

    /**
     * Submit a price report
     *
     * `reported_at` defaults to now and cannot be in the future.
     */
    #[Authenticated]
    public function store(StorePriceReportRequest $request): JsonResponse
    {
        $report = $request->user()->priceReports()->create([
            ...$request->safe()->only(['commodity_id', 'market_id', 'price']),
            'reported_at' => $request->filled('reported_at')
                ? Carbon::parse($request->string('reported_at'))->utc()
                : now(),
        ]);

        return PriceReportResource::make($report->load(['commodity', 'market']))
            ->response()
            ->setStatusCode(201);
    }
}
