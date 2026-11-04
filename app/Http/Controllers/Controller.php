<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    protected const DEFAULT_PER_PAGE = 20;

    protected const MAX_PER_PAGE = 100;

    protected function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }

    /**
     * @template TModel of Model
     *
     * @param  LengthAwarePaginator<int, TModel>  $paginator
     * @param  class-string<JsonResource>  $resource
     */
    protected function paginated(LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        return response()->json([
            'data' => $resource::collection($paginator->getCollection()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $data
     */
    protected function data(array $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }
}
