<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListPriceReportsRequest;
use App\Http\Requests\StorePriceReportRequest;
use App\Http\Resources\PriceReportResource;
use App\Models\Commodity;
use App\Models\PriceReport;
use App\Services\PriceAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;

#[Group('Price reports', 'Prices seen at a market on a given day, submitted by registered users.')]
class PriceReportController extends Controller
{
    /**
     * List price reports
     *
     * Newest first. Reports hidden by moderators are left out.
     */
    public function index(ListPriceReportsRequest $request): JsonResponse
    {
        $includeHidden = $request->boolean('include_hidden') && $request->user('sanctum')?->is_admin;

        $reports = PriceReport::query()
            ->with(['commodity', 'market'])
            ->unless($includeHidden, fn ($query) => $query->visible())
            ->when($request->has('flagged'), fn ($query) => $query->where('is_flagged', $request->boolean('flagged')))
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
     * Requires a verified email. `reported_at` defaults to now and cannot be in the future.
     * Reports more than 30% above the SRP in effect at `reported_at` come back with `flagged: true`.
     * Limited to 10 reports a minute and 200 a day per user.
     */
    #[Authenticated]
    public function store(StorePriceReportRequest $request, PriceAnalyticsService $analytics): JsonResponse
    {
        $reportedAt = $request->filled('reported_at')
            ? Carbon::parse($request->string('reported_at'))->utc()
            : now();
        $price = (float) $request->validated('price');
        $commodity = Commodity::query()->findOrFail($request->integer('commodity_id'));

        $report = $request->user()->priceReports()->create([
            ...$request->safe()->only(['commodity_id', 'market_id', 'price']),
            ...$analytics->assess($commodity, $price, $reportedAt),
            'reported_at' => $reportedAt,
        ]);

        return PriceReportResource::make($report->load(['commodity', 'market']))
            ->response()
            ->setStatusCode(201);
    }
}
