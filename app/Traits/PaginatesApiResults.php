<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginatesApiResults
{
    protected function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        return min(max((int) $request->query('per_page', $default), 1), $max);
    }

    /**
     * @return array{total: int, per_page: int, current_page: int, last_page: int}
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    protected function paginatedApiResponse(
        LengthAwarePaginator $paginator,
        string $reason,
        string $itemsKey = 'items',
        array $extra = []
    ) {
        $data = array_merge(
            [$itemsKey => $paginator->items()],
            $extra,
            ['pagination' => $this->paginationMeta($paginator)]
        );

        return $this->apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: $reason,
            status_code: $this::API_SUCCESS,
            data: $data
        );
    }

    /**
     * @param  callable(mixed): mixed  $mapper
     */
    protected function paginatedApiResponseMapped(
        LengthAwarePaginator $paginator,
        string $reason,
        callable $mapper,
        string $itemsKey = 'items',
        array $extra = []
    ) {
        $paginator->setCollection(
            $paginator->getCollection()->map($mapper)->values()
        );

        return $this->paginatedApiResponse($paginator, $reason, $itemsKey, $extra);
    }
}
