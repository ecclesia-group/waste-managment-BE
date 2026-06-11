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

    protected function paginatedApiResponse(LengthAwarePaginator $paginator, string $reason)
    {
        return $this->apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: $reason,
            status_code: $this::API_SUCCESS,
            data: [
                'items' => $paginator->items(),
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]
        );
    }

    /**
     * @param  callable(mixed): mixed  $mapper
     */
    protected function paginatedApiResponseMapped(LengthAwarePaginator $paginator, string $reason, callable $mapper)
    {
        $paginator->setCollection(
            $paginator->getCollection()->map($mapper)->values()
        );

        return $this->paginatedApiResponse($paginator, $reason);
    }
}
