<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait WithDataTable
{
    public $sortCol = 'created_at';

    public $sortAsc = false;

    public $search = '';

    public $perPage = 5;

    public function sortBy($column)
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $column;
            $this->sortAsc = true;
        }
    }

    public function applySorting(Builder $query)
    {
        return $query->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');
    }

    public function applySearch(Builder $query, array $columns)
    {
        if ($this->search) {
            $query->where(function ($q) use ($columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%' . $this->search . '%');
                }
            });
        }

        return $query;
    }

    public function updatedSearch()
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function updatedPerPage()
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
