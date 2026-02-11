<?php

use App\Models\Expense;
use App\Models\Property;
use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Dépenses'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $property_filter = '';
    public $vendor_filter = '';
    public $date_filter = '';
    public $perPage = 10;
    public $sortCol = 'date';
    public $sortAsc = false;

    public function sortBy($column)
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $column;
            $this->sortAsc = true;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPropertyFilter()
    {
        $this->resetPage();
    }

    public function updatedVendorFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    #[On('expense-created')]
    public function refresh()
    {
    }

    public function delete($id)
    {
        Expense::findOrFail($id)->delete();
        flash()->success('Dépense supprimée.');
    }

    public function with()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Efficient single query for totals
        $totals = Expense::toBase()
            ->selectRaw('sum(amount) as total_all_time')
            ->selectRaw("sum(case when extract(year from date) = ? then amount else 0 end) as total_this_year", [$currentYear])
            ->first();

        // Separate efficient query for top category
        $topCategory = Expense::toBase()
            ->whereRaw("extract(year from date) = ?", [$currentYear])
            ->select('category')
            ->selectRaw('sum(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->value('category');

        $totalExpenses = $totals->total_all_time ?? 0;
        $thisYearTotal = $totals->total_this_year ?? 0;
        // Average over elapsed months in current year (avoid division by zero)
        $monthlyAverage = $currentMonth > 0 ? ($thisYearTotal / $currentMonth) : 0;

        $query = Expense::query()
            ->with(['property', 'vendor'])
            ->when($this->search, fn($q) => $q->where('description', 'like', '%' . $this->search . '%')
                ->orWhere('category', 'like', '%' . $this->search . '%'))
            ->when($this->property_filter, fn($q) => $q->where('property_id', $this->property_filter))
            ->when($this->vendor_filter, fn($q) => $q->where('vendor_id', $this->vendor_filter))
            ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');

        return [
            'expenses' => $query->paginate($this->perPage),
            'properties' => Property::all(),
            'vendors' => Vendor::all(),
            'totalExpenses' => $totalExpenses,
            'monthlyAverage' => $monthlyAverage,
            'topCategory' => $topCategory ?: 'N/A', // Default if no expenses
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Dépenses" subheading="Suivez vos coûts opérationnels et vos dépenses de maintenance.">
        <x-slot:actions>
            <flux:button icon="plus" wire:click="$dispatch('open-modal', { name: 'create-expense' })" variant="primary">
                Ajouter une Dépense
            </flux:button>
        </x-slot:actions>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <x-stats-card title="Total Dépenses" :value="Number::currency($totalExpenses)" icon="banknotes"
                color="red" />
            <x-stats-card title="Moyenne Mensuelle" :value="Number::currency($monthlyAverage)" icon="calendar"
                color="blue" />
            <x-stats-card title="Top Catégorie" :value="ucfirst($topCategory)" icon="chart-pie" color="orange" />
        </div>

        <x-flux::card class="overflow-hidden border-zinc-200">
            <x-flux::card.header icon="banknotes" title="Registre des Dépenses"
                subtitle="Suivi des paiements effectués." />

            <x-flux::table :paginate="$expenses" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="property_filter" placeholder="Propriété" size="sm"
                        class="w-full sm:w-48">
                        <flux:select.option value="">Toutes</flux:select.option>
                        @foreach($properties as $property)
                            <flux:select.option value="{{ $property->id }}">{{ $property->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="vendor_filter" placeholder="Fournisseur" size="sm"
                        class="w-full sm:w-48">
                        <flux:select.option value="">Tous</flux:select.option>
                        @foreach($vendors as $vendor)
                            <flux:select.option value="{{ $vendor->id }}">{{ $vendor->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button icon="arrow-down-tray" size="sm" variant="ghost">Exporter</flux:button>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'date'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('date')">Date</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'description'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('description')">Description</x-flux::table.column>
                    <x-flux::table.column>Propriété</x-flux::table.column>
                    <x-flux::table.column>Fournisseur</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'amount'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('amount')">Montant</x-flux::table.column>
                    <x-flux::table.column align="right">Actions</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse($expenses as $expense)
                        <x-flux::table.row :key="$expense->id">
                            <x-flux::table.cell
                                class="whitespace-nowrap text-zinc-500">{{ $expense->date->format('d M Y') }}</x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900">{{ $expense->description }}</span>
                                    <span class="text-xs text-zinc-500 capitalize">{{ $expense->category }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @if($expense->property)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar src="{{ $expense->property->image_url ?? null }}" size="xs"
                                            initials="{{ substr($expense->property->name, 0, 1) }}" />
                                        <span class="text-sm text-zinc-700">{{ $expense->property->name }}</span>
                                    </div>
                                @else
                                    <span class="text-zinc-400 text-sm">-</span>
                                @endif
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @if($expense->vendor)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200">
                                        {{ $expense->vendor->name }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 text-sm">-</span>
                                @endif
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <span class="font-bold text-zinc-900">{{ Number::currency($expense->amount) }}</span>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square"
                                            wire:click="$dispatch('edit-expense', { expense: '{{ $expense->id }}' })">
                                            Modifier
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" wire:click="delete('{{ $expense->id }}')"
                                            wire:confirm="Êtes-vous sûr ?" variant="danger">Supprimer</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="6">
                                <div class="text-center py-12">
                                    <div
                                        class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-100 mb-4">
                                        <flux:icon.banknotes class="size-6 text-zinc-400" />
                                    </div>
                                    <h3 class="text-lg font-medium text-zinc-900">Aucune dépense trouvée</h3>
                                    <p class="text-zinc-500 max-w-sm mx-auto mt-1">
                                        {{ $search ? 'Essayez d\'ajuster votre recherche ou vos filtres.' : 'Commencez par enregistrer votre première dépense.' }}
                                    </p>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.expenses.modals.create />
    <livewire:pages::tenant.expenses.modals.edit />
</div>