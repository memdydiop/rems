<?php

namespace App\Traits;

use Livewire\WithPagination;

trait WithDataTable
{
    use WithPagination;

    public $search = '';
    public $sortCol = 'created_at';
    public $sortAsc = false;
    public $perPage = 10;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($column)
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $column;
            $this->sortAsc = true;
        }
    }

    /**
     * Apply search filter to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $columns Columns to search in
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applySearch($query, array $columns)
    {
        if (empty($this->search)) {
            return $query;
        }

        $search = $this->search;

        return $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%' . $search . '%');
            }
        });
    }

    /**
     * Apply sorting to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applySorting($query)
    {
        return $query->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');
    }
}
