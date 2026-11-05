<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\HidePriceReportRequest;
use App\Http\Resources\PriceReportResource;
use App\Models\PriceReport;
use App\Models\User;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;

#[Group('Price reports')]
class PriceReportReviewController extends Controller
{
    /**
     * Hide a report
     *
     * Admins only. Hidden reports are left out of listings, trends and SRP comparisons.
     */
    #[Authenticated]
    public function hide(HidePriceReportRequest $request, PriceReport $priceReport): PriceReportResource
    {
        /** @var User $moderator */
        $moderator = $request->user();

        $priceReport->hide($moderator, $request->validated('reason'));

        return PriceReportResource::make($priceReport->load(['commodity', 'market']));
    }

    /**
     * Unhide a report
     *
     * Admins only.
     */
    #[Authenticated]
    public function unhide(PriceReport $priceReport): PriceReportResource
    {
        $priceReport->unhide();

        return PriceReportResource::make($priceReport->load(['commodity', 'market']));
    }
}
