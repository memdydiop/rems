<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Property;
use App\Models\Client;
use App\Models\Lease;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Auth;

class GlobalSearch extends Component
{
    public $query = '';
    public $results = [];
    public $showResults = false;

    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->showResults = false;
            return;
        }

        $this->showResults = true;
        $this->search();
    }

    public function search()
    {
        $query = '%' . $this->query . '%';

        $this->results = [
            'properties' => Property::where('name', 'like', $query)
                ->orWhere('address', 'like', $query)
                ->limit(3)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'title' => $item->name,
                    'subtitle' => $item->address,
                    'icon' => 'home',
                    'url' => route('tenant.properties.show', $item),
                    'type' => 'Propriété'
                ]),

            'clients' => Client::where('first_name', 'like', $query)
                ->orWhere('last_name', 'like', $query)
                ->orWhere('email', 'like', $query)
                ->limit(3)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'title' => $item->first_name . ' ' . $item->last_name,
                    'subtitle' => $item->email,
                    'icon' => 'user',
                    'url' => route('tenant.clients.index') . '?client=' . $item->id,
                    'type' => 'Client'
                ]),

            'leases' => Lease::with(['client', 'unit.property'])
                ->whereHas('client', function ($q) use ($query) {
                    $q->where('first_name', 'like', $query)
                        ->orWhere('last_name', 'like', $query);
                })
                ->orWhereHas('unit.property', function ($q) use ($query) {
                    $q->where('name', 'like', $query);
                })
                ->limit(3)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'title' => $item->client->first_name . ' ' . $item->client->last_name,
                    'subtitle' => $item->unit->property->name . ' - ' . $item->unit->name,
                    'icon' => 'document-text',
                    'url' => route('tenant.leases.index') . '?lease=' . $item->id,
                    'type' => 'Bail'
                ]),

            'maintenance' => MaintenanceRequest::where('title', 'like', $query)
                ->orWhere('description', 'like', $query)
                ->limit(3)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'subtitle' => 'Statut: ' . ucfirst($item->status->value),
                    'icon' => 'wrench-screwdriver',
                    'url' => route('tenant.maintenance.index') . '?maintenance=' . $item->id,
                    'type' => 'Maintenance'
                ]),

            'expenses' => Expense::where('description', 'like', $query)
                ->limit(3)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'title' => $item->description,
                    'subtitle' => \Illuminate\Support\Number::currency($item->amount, 'USD'),
                    'icon' => 'banknotes',
                    'url' => route('tenant.expenses.index') . '?expense=' . $item->id,
                    'type' => 'Dépense'
                ]),
        ];
    }

    public function closeResults()
    {
        $this->showResults = false;
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
