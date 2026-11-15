<?php

namespace App\Domain\Students\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StudentSearchInterface
{
    /**
     * Search and filter students with pagination.
     *
     * @param  array{
     *     search?: ?string,
     *     status?: ?string,
     *     class?: ?string,
     *     teacher_id?: ?string,
     *     per_page?: ?int
     * }  $filters
     */
    public function search(array $filters): LengthAwarePaginator;
}
