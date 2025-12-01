<?php

namespace App\Traits;

trait GlobalPagination
{
    /**
     * Get pagination parameters from request
     *
     * @param \Illuminate\Http\Request $request
     * @param int $defaultPerPage
     * @return array
     */
    protected function getPaginationParams($request, $defaultPerPage = 50)
    {
        $perPage = $request->input('per_page', $defaultPerPage);

        // Handle 'all' option
        if ($perPage === 'all') {
            return [
                'per_page' => 'all',
                'per_page_numeric' => 999999, // Very high number for 'all'
                'is_all' => true
            ];
        }

        // Validate and sanitize per_page
        $perPage = is_numeric($perPage) ? (int) $perPage : $defaultPerPage;
        $perPage = $perPage > 0 ? $perPage : $defaultPerPage;

        // Set reasonable limits
        $perPage = min($perPage, 1000); // Maximum 1000 records per page

        return [
            'per_page' => $perPage,
            'per_page_numeric' => $perPage,
            'is_all' => false
        ];
    }

    /**
     * Apply pagination to query
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param array $paginationParams
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    protected function applyPagination($query, $paginationParams)
    {
        if ($paginationParams['is_all']) {
            // Return all records without pagination
            return $query->get();
        }

        return $query->paginate($paginationParams['per_page_numeric']);
    }

    /**
     * Handle AJAX pagination response
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $data
     * @param string $tableView
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    protected function handlePaginationResponse($request, $data, $tableView)
    {
        if ($request->ajax()) {
            $tableData = view($tableView, ['data' => $data])->render();

            // Handle pagination links for AJAX
            if (method_exists($data, 'links')) {
                $paginationLinks = $data->links('components.global-pagination')->render();
            } else {
                $paginationLinks = '';
            }

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'total' => method_exists($data, 'total') ? $data->total() : $data->count(),
                'per_page' => method_exists($data, 'perPage') ? $data->perPage() : $data->count(),
            ]);
        }

        return view($tableView, ['data' => $data]);
    }

    /**
     * Get available per page options
     *
     * @return array
     */
    protected function getPerPageOptions()
    {
        return [20, 50, 100, 'all'];
    }

    /**
     * Get default per page value
     *
     * @return int
     */
    protected function getDefaultPerPage()
    {
        return 50;
    }
}
