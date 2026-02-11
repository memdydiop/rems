<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\WithDataTable;
use App\Jobs\CreateTenantJob;
use App\Enums\PropertyStatus;

new
    #[Layout('layouts.app', ['title' => 'Mes Clients'])]
    class extends Component {
    use WithDataTable;

    // Creation Form Properties
    public $company = '';
    public $name = '';
    public $email = '';
    public $subdomain = '';
    public $password = '';
    public $plan = 'Starter';

    #[Computed]
    public function tenants()
    {
        return $this->applySorting(
            $this->applySearch(Tenant::query()->with(['domains', 'subscription.plan']), ['id', 'company', 'data'])
        )->paginate($this->perPage);
    }

    public function delete($id)
    {
        $tenant = Tenant::find($id);
        if ($tenant) {
            $tenant->delete();
            $this->dispatch('tenant-deleted'); 
        }
    }
    
    public function save()
    {
        $this->validate([
            'company' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subdomain' => 'required|string|max:50|alpha_dash|unique:tenants,id',
            'password' => 'required|min:8',
        ]);

        try {
            CreateTenantJob::dispatchSync(
                $this->company,
                $this->subdomain,
                $this->name,
                $this->email,
                $this->password,
                $this->plan
            );
            
            $this->reset(['company', 'name', 'email', 'subdomain', 'password']);
            $this->dispatch('close-modal', 'create-tenant'); // Close modal
            $this->dispatch('tenant-created'); // Optional notification
            
        } catch (\Exception $e) {
            $this->addError('subdomain', 'Erreur: ' . $e->getMessage());
        }
    }

    public function generateSubdomain() {
        if ($this->company && !$this->subdomain) {
            $this->subdomain = Str::slug($this->company);
        }
    }
    
    // Helper to generate a consistent color based on string
    public function getAvatarColor($name) {
        $colors = ['bg-red-50 text-red-600', 'bg-orange-50 text-orange-600', 'bg-amber-50 text-amber-600', 'bg-yellow-50 text-yellow-600', 'bg-lime-50 text-lime-600', 'bg-green-50 text-green-600', 'bg-emerald-50 text-emerald-600', 'bg-teal-50 text-teal-600', 'bg-cyan-50 text-cyan-600', 'bg-sky-50 text-sky-600', 'bg-blue-50 text-blue-600', 'bg-indigo-50 text-indigo-600', 'bg-violet-50 text-violet-600', 'bg-purple-50 text-purple-600', 'bg-fuchsia-50 text-fuchsia-600', 'bg-pink-50 text-pink-600', 'bg-rose-50 text-rose-600'];
        return $colors[abs(crc32($name)) % count($colors)];
    }
};
?>

<div>

    <x-layouts::content heading="Mes Clients" subheading="Gérez vos espaces de travail et abonnements">

        <x-slot:actions>
            <flux:modal.trigger name="create-tenant">
                <flux:button variant="primary" icon="plus">
                    Nouveau Client
                </flux:button>
            </flux:modal.trigger>
        </x-slot:actions>

        <x-flux::card class="overflow-hidden">

        <x-flux::card.header 
            icon="building-office"
            title="Espaces de Travail"
            subtitle="Liste complète de vos clients ({{\App\Models\Tenant::count()}})"
            >

            
        </x-flux::card.header>

            <x-flux::table :paginate="$this->tenants" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="status" size="sm" class="w-full md:w-40">
                        <flux:select.option value="all">Tous statut</flux:select.option>
                        @foreach(PropertyStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'company'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('company')">Entreprise</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'id'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('id')">Identifiant</x-flux::table.column>
                    <x-flux::table.column>Abonnement</x-flux::table.column>
                    <x-flux::table.column>Domaines</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Date de création</x-flux::table.column>
                    <x-flux::table.column></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @foreach ($this->tenants as $tenant)
                        <x-flux::table.row :key="$tenant->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-sm shadow-sm md:w-10 md:h-10 {{ $this->getAvatarColor($tenant->company) }}">
                                        {{ substr($tenant->company, 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900">{{ $tenant->company }}</span>
                                        <span class="text-xs text-zinc-500">{{ $tenant->email ?? 'Sans email' }}</span>
                                    </div>
                                </div>
                            </x-flux::table.cell>
                            
                            <x-flux::table.cell>
                                <div class="flex items-center gap-2">
                                     <flux:icon name="circle-stack" class="w-4 h-4 text-zinc-300" variant="micro" />
                                     <span class="font-mono text-sm text-zinc-600 bg-zinc-50 px-1.5 py-0.5 rounded border border-zinc-200">{{ $tenant->id }}</span>
                                </div>
                            </x-flux::table.cell>

                            <x-flux::table.cell>
                                @if($tenant->subscription && $tenant->subscription->active())
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="text-sm font-medium text-emerald-700">Actif</span>
                                        </div>
                                        <span class="text-xs text-zinc-500">{{ $tenant->subscription->plan->name ?? 'Plan inconnu' }}</span>
                                    </div>
                                @elseif($tenant->subscription && $tenant->subscription->onTrial())
                                     <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                            <span class="text-sm font-medium text-blue-700">Essai</span>
                                        </div>
                                        <span class="text-xs text-zinc-500">Fin: {{ $tenant->subscription->trial_ends_at?->format('d/m') }}</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-zinc-100 text-zinc-500 text-xs font-medium">Inactif</span>
                                @endif
                            </x-flux::table.cell>
                            
                            <x-flux::table.cell>
                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                    @foreach($tenant->domains as $domain)
                                        @php
                                            $protocol = request()->secure() ? 'https' : 'http';
                                            $port = request()->getPort();
                                            $portPart = ($port !== 80 && $port !== 443) ? ":$port" : '';
                                            $url = $protocol . '://' . $domain->domain . $portPart;
                                        @endphp
                                        <a href="{{ $url }}" target="_blank" class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-white border border-zinc-200 shadow-sm text-xs text-zinc-600 hover:bg-zinc-50 transition-colors">
                                            <flux:icon name="globe-alt" class="w-3 h-3 text-zinc-400" />
                                            {{ $domain->domain }}
                                        </a>
                                    @endforeach
                                </div>
                            </x-flux::table.cell>
                            
                            <x-flux::table.cell>
                                <span class="text-zinc-500 text-sm" title="{{ $tenant->created_at->format('d/m/Y H:i') }}">{{ $tenant->created_at->diffForHumans() }}</span>
                            </x-flux::table.cell>
                            
                            <x-flux::table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" inset="top bottom" />

                                    <flux:menu>
                                        <flux:menu.item icon="eye" :href="route('central.tenants.show', $tenant)" wire:navigate>
                                            Voir les détails
                                        </flux:menu.item>
                                        @php
                                            $firstDomain = $tenant->domains->first();
                                            $protocol = request()->secure() ? 'https' : 'http';
                                            $port = request()->getPort();
                                            $portPart = ($port !== 80 && $port !== 443) ? ":$port" : '';
                                            $url = $firstDomain ? $protocol . '://' . $firstDomain->domain . $portPart : '#';
                                        @endphp
                                        <flux:menu.item icon="arrow-top-right-on-square" href="{{ $url }}" target="_blank">
                                            Accéder au site
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" wire:click="delete('{{ $tenant->id }}')"
                                            wire:confirm="Voulez-vous vraiment supprimer ce client et toutes ses données ? Cette action est irréversible." class="text-red-600">
                                            Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>

    </x-layouts::content>

    <!-- Create Tenant Modal -->
    <flux:modal name="create-tenant" class="md:w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nouveau Client</flux:heading>
                <flux:subheading>Créez un nouvel espace de travail.</flux:subheading>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="company" label="Nom de l'entreprise" placeholder="Acme Inc." wire:blur="generateSubdomain" />
                
                <div class="grid grid-cols-2 gap-6">
                     <flux:input wire:model="name" label="Propriétaire" placeholder="John Doe" />
                     <flux:input wire:model="email" label="Email" type="email" placeholder="john@example.com" />
                </div>
                
                <flux:input wire:model="subdomain" label="Sous-domaine" placeholder="acme" suffix=".propella.ci" />
                
                <flux:input wire:model="password" label="Mot de passe" type="password" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Annuler</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Créer</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

</div>