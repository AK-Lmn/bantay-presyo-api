<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSrpsRequest;
use App\Http\Requests\StoreSrpRequest;
use App\Http\Resources\SrpResource;
use App\Models\Srp;
use App\Services\SrpCsvImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;

#[Group('SRPs', 'DTI suggested retail prices. The SRP in effect is the one with the latest effective date on or before today.')]
class SrpController extends Controller
{
    #[QueryParam('commodity_id', 'integer', 'Filter by commodity.', required: false, example: 1)]
    #[QueryParam('page', 'integer', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Max 100.', required: false, example: 20)]
    public function index(Request $request): JsonResponse
    {
        $srps = Srp::query()
            ->when($request->filled('commodity_id'), fn ($query) => $query->where('commodity_id', $request->integer('commodity_id')))
            ->orderByDesc('effective_date')
            ->orderBy('commodity_id')
            ->paginate($this->perPage($request));

        return $this->paginated($srps, SrpResource::class);
    }

    #[Authenticated]
    public function store(StoreSrpRequest $request): JsonResponse
    {
        $srp = Srp::query()->create($request->validated());

        return SrpResource::make($srp)->response()->setStatusCode(201);
    }

    #[Authenticated]
    public function destroy(Srp $srp): Response
    {
        $srp->delete();

        return response()->noContent();
    }

    /**
     * Import an SRP bulletin
     *
     * Upload a CSV with a header row. Rows are matched to commodities by name (and `unit` if given).
     * An existing SRP for the same commodity and date is updated. If any row is invalid, nothing is imported
     * and every bad row is listed in `error.details.file`. See `docs/srp-import-example.csv`.
     */
    #[Authenticated]
    public function import(ImportSrpsRequest $request, SrpCsvImporter $importer): JsonResponse
    {
        $result = $importer->import(
            $request->file('file')->getRealPath(),
            $request->validated('effective_date'),
        );

        return $this->data($result);
    }
}
