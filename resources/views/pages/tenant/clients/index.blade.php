<?php

use App\Models\Client;
use App\Enums\ClientStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Lease;
use App\Enums\LeaseStatus;
use App\Notifications\OverduePaymentNotification;
use App\Traits\WithDataTable;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientsExport;

new #[Layout('layouts.app', ['title' => 'Clients'])] class extends Component {
    use WithDataTable;

    #[Computed]
    public function clients()
    {
        return $this->applySorting(
            $this->applySearch(Client::query(), ['first_name', 'last_name', 'email', 'phone'])
        )->paginate($this->perPage);
    }

    public function delete($id)
    {
        $client = Client::find($id);
        if ($client) {
            $client->delete();
            $this->js("Flux.toast('Client supprimé.')");
        }
    }

    public function sendManualReminder($id)
    {
        $client = Client::find($id);

        if (!$client) {
            return;
        }

        // On cherche un bail actif en retard pour ce client
        $lease = $client->leases()->where('status', LeaseStatus::Active)->first();

        if (!$lease) {
            $this->js("Flux.toast({ variant: 'danger', description: 'Aucun bail actif pour ce client.' })");
            return;
        }

        // Check if phone or email exists
        if (empty($client->email) && empty($client->phone)) {
            $this->js("Flux.toast({ variant: 'danger', description: 'Le client n\'a ni email ni téléphone.' })");
            return;
        }

        // Calculate a generic days overdue value
        $dueDate = now()->startOfMonth();
        $daysOverdue = max(0, now()->diffInDays($dueDate));
        $level = $daysOverdue >= 15 ? 'warning' : 'reminder';

        $client->notify(new OverduePaymentNotification($lease, $daysOverdue, $level));

        $this->js("Flux.toast('Relance WhatsApp / Email envoyée avec succès.')");
    }

    public function with()
    {
        $stats = Client::toBase()
            ->selectRaw("count(*) as total")
            ->selectRaw("count(case when status = 'active' then 1 end) as active")
            ->selectRaw("count(case when status = 'lead' then 1 end) as leads")
            ->first();

        return [
            'totalClients' => $stats->total ?? 0,
            'activeClients' => $stats->active ?? 0,
            'totalLeads' => $stats->leads ?? 0,
        ];
    }
    public function exportClients()
    {
        return Excel::download(new ClientsExport, 'clients_' . now()->format('Y-m-d') . '.xlsx');
    }
};
?>

<div>
    <x-layouts::content heading="Clients" subheading="Gérez vos clients et prospects.">
        <x-slot:actions>
            <flux:button icon="arrow-down-tray" variant="ghost" wire:click="exportClients">
                Exporter
            </flux:button>
            <flux:button icon="plus" wire:click="$dispatch('open-modal', { name: 'create-client' })" variant="primary">
                Ajouter un Client
            </flux:button>
        </x-slot:actions>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-stats-card title="Total Clients" :value="$totalClients" icon="users" color="blue" />
            <x-stats-card title="Clients Actifs" :value="$activeClients" icon="user-circle" color="emerald" />
            <x-stats-card title="Prospects" :value="$totalLeads" icon="user-plus" color="orange" />
        </div>

        <x-flux::card class="overflow-hidden border-zinc-200">
            <x-flux::card.header icon="users" title="Liste des Clients" subtitle="Gérez vos clients et prospects." />

            <x-flux::table :paginate="$this->clients" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="status" placeholder="Statut" size="sm" class="w-full sm:w-40">
                        <flux:select.option value="">Tous</flux:select.option>
                        @foreach (ClientStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'first_name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('first_name')">Client</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'email'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('email')">Contact</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'status'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('status')">Statut</x-flux::table.column>
                    <x-flux::table.column>Rejoint le</x-flux::table.column>
                    <x-flux::table.column align="end">Actions</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse ($this->clients as $client)
                        <x-flux::table.row :key="$client->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$client->full_name" class="ring-2 ring-white shadow-sm" />
                                    <span class="font-medium text-zinc-900">{{ $client->full_name }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="text-sm text-zinc-900 font-medium">{{ $client->email ?? 'Email non fourni' }}
                                </div>
                                <div class="text-xs text-zinc-500">{{ $client->phone ?? 'Téléphone non fourni' }}</div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" inset="top bottom" :color="$client->status->color()">
                                    {{ $client->status->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell class="text-zinc-500">
                                {{ $client->created_at->format('d M Y') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell align="end">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square"
                                            wire:click="$dispatch('edit-client', { client: '{{ $client->id }}' })">
                                            Modifier</flux:menu.item>
                                        <flux:menu.item icon="paper-airplane"
                                            wire:click="sendManualReminder('{{ $client->id }}')"
                                            wire:confirm="Envoyer une relance WhatsApp et Email à ce client ?">
                                            Relancer (WhatsApp/Email)</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:click="delete('{{ $client->id }}')" wire:confirm="Supprimer ce client ?">
                                            Supprimer
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
                                    <h3 class="text-lg font-medium text-zinc-900">Aucun client trouvé</h3>
                                    <p class="text-zinc-500 max-w-sm mx-auto mt-1">
                                        {{ $search ? 'Essayez d\'ajuster votre recherche.' : 'Commencez par ajouter votre premier client.' }}
                                    </p>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.clients.modals.create />
    <livewire:pages::tenant.clients.modals.edit />
</div>