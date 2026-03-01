<?php

use App\Models\Renter;
use App\Enums\RenterStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Lease;
use App\Enums\LeaseStatus;
use App\Notifications\OverduePaymentNotification;
use App\Traits\WithDataTable;

new #[Layout('layouts.app', ['title' => 'Renters'])] class extends Component {
    use WithDataTable;

    #[Computed]
    public function renters()
    {
        return $this->applySorting(
            $this->applySearch(Renter::query(), ['first_name', 'last_name', 'email', 'phone'])
        )->paginate($this->perPage);
    }

    public function delete($id)
    {
        $renter = Renter::find($id);
        if ($renter) {
            $renter->delete();
            $this->js("Flux.toast('Locataire supprimé.')");
        }
    }

    public function sendManualReminder($id)
    {
        $renter = Renter::find($id);

        if (!$renter) {
            return;
        }

        // On cherche un bail actif en retard pour ce locataire (simplifié pour manuel)
        $lease = $renter->leases()->where('status', LeaseStatus::Active)->first();

        if (!$lease) {
            $this->js("Flux.toast({ variant: 'danger', description: 'Aucun bail actif pour ce locataire.' })");
            return;
        }

        // Check if phone or email exists
        if (empty($renter->email) && empty($renter->phone)) {
            $this->js("Flux.toast({ variant: 'danger', description: 'Le locataire n\'a ni email ni téléphone.' })");
            return;
        }

        // Calculate a generic days overdue value or default to 5 for the message format
        $dueDate = now()->startOfMonth();
        $daysOverdue = max(0, now()->diffInDays($dueDate));
        $level = $daysOverdue >= 15 ? 'warning' : 'reminder';

        $renter->notify(new OverduePaymentNotification($lease, $daysOverdue, $level));

        $this->js("Flux.toast('Relance WhatsApp / Email envoyée avec succès.')");
    }

    public function with()
    {
        $stats = Renter::toBase()
            ->selectRaw("count(*) as total")
            ->selectRaw("count(case when status = 'active' then 1 end) as active")
            ->selectRaw("count(case when status = 'lead' then 1 end) as leads")
            ->first();

        return [
            'totalRenters' => $stats->total ?? 0,
            'activeRenters' => $stats->active ?? 0,
            'totalLeads' => $stats->leads ?? 0,
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Locataires" subheading="Gérez vos locataires et prospects.">
        <x-slot:actions>
            <flux:button icon="plus" wire:click="$dispatch('open-modal', { name: 'create-renter' })" variant="primary">
                Ajouter un Locataire
            </flux:button>
        </x-slot:actions>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-stats-card title="Total Locataires" :value="$totalRenters" icon="users" color="blue" />
            <x-stats-card title="Locataires Actifs" :value="$activeRenters" icon="user-circle" color="emerald" />
            <x-stats-card title="Prospects" :value="$totalLeads" icon="user-plus" color="orange" />
        </div>

        <x-flux::card class="overflow-hidden border-zinc-200">
            <x-flux::card.header icon="users" title="Liste des Locataires"
                subtitle="Gérez vos locataires et prospects." />

            <x-flux::table :paginate="$this->renters" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="status" placeholder="Statut" size="sm" class="w-full sm:w-40">
                        <flux:select.option value="">Tous</flux:select.option>
                        @foreach (RenterStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'first_name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('first_name')">Locataire</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'email'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('email')">Contact</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'status'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('status')">Statut</x-flux::table.column>
                    <x-flux::table.column>Rejoint le</x-flux::table.column>
                    <x-flux::table.column align="end">Actions</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse ($this->renters as $renter)
                        <x-flux::table.row :key="$renter->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$renter->full_name" class="ring-2 ring-white shadow-sm" />
                                    <span class="font-medium text-zinc-900">{{ $renter->full_name }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="text-sm text-zinc-900 font-medium">{{ $renter->email }}</div>
                                <div class="text-xs text-zinc-500">{{ $renter->phone ?? 'Aucun téléphone' }}</div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" inset="top bottom" :color="$renter->status->color()">
                                    {{ $renter->status->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell class="text-zinc-500">
                                {{ $renter->created_at->format('d M Y') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell align="end">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square"
                                            wire:click="$dispatch('edit-renter', { renter: '{{ $renter->id }}' })">
                                            Modifier</flux:menu.item>
                                        <flux:menu.item icon="paper-airplane"
                                            wire:click="sendManualReminder('{{ $renter->id }}')"
                                            wire:confirm="Envoyer une relance WhatsApp et Email à ce locataire pour son loyer ?">
                                            Relancer (WhatsApp/Email)</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:click="delete('{{ $renter->id }}')"
                                            wire:confirm="Supprimer ce locataire ?">Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="5">
                                <div class="text-center py-12">
                                    <div
                                        class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-100 mb-4">
                                        <flux:icon.users class="size-6 text-zinc-400" />
                                    </div>
                                    <h3 class="text-lg font-medium text-zinc-900">Aucun locataire trouvé</h3>
                                    <p class="text-zinc-500 max-w-sm mx-auto mt-1">
                                        {{ $search ? 'Essayez d\'ajuster votre recherche.' : 'Commencez par ajouter votre premier locataire.' }}
                                    </p>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.renters.modals.create />
    <livewire:pages::tenant.renters.modals.edit />
</div>