<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Flux\Flux;
use App\Traits\WithDataTable;

new #[Layout('layouts.app', ['title' => 'Rôles & Permissions'])] class extends Component {
    use WithDataTable;

    public $name = '';
    public $description = '';
    public $selectedPermissions = [];
    public ?Role $role = null;

    public function mount()
    {
        $this->sortCol = 'name';
        $this->sortAsc = true;
    }

    public function create()
    {
        $this->reset(['name', 'description', 'selectedPermissions', 'role']);
        $this->dispatch('open-modal', 'role-modal');
    }

    public function edit(Role $role)
    {
        $this->role = $role;
        $this->name = $role->name;
        $this->description = $role->description;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->dispatch('open-modal', 'role-modal');
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'min:3', 'unique:roles,name,' . ($this->role?->id ?? 'NULL') . ',id'],
            'description' => ['nullable', 'string', 'max:255'],
            'selectedPermissions' => ['array'],
        ]);

        if ($this->role && in_array($this->role->name, ['Ghost', 'Admin'])) {
            Flux::toast('Impossible de modifier les rôles système.', 'danger');
            return;
        }

        if ($this->role) {
            $this->role->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);
            $this->role->syncPermissions($this->selectedPermissions);
            Flux::toast('Rôle mis à jour avec succès.');
        } else {
            $role = Role::create([
                'name' => $this->name,
                'description' => $this->description,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($this->selectedPermissions);
            Flux::toast('Rôle créé avec succès.');
        }

        $this->dispatch('close-modal', 'role-modal');
        $this->reset(['name', 'description', 'selectedPermissions', 'role']);
    }

    public function delete($id)
    {
        $role = Role::find($id);

        if (in_array($role->name, ['admin', 'Admin', 'Ghost'])) {
            Flux::toast('Impossible de supprimer les rôles admin.', 'danger');
            return;
        }

        $role->delete();
        Flux::toast('Rôle supprimé avec succès.');
    }

    #[Computed]
    public function permissions()
    {
        return Permission::all();
    }

    public function with()
    {
        return [
            'roles' => Role::where('guard_name', 'web')
                ->where('name', '!=', 'Ghost')
                ->where('name', '!=', 'Admin')
                ->withCount('users')
                ->with('permissions')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('description', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                ->get(),
        ];
    }
}; ?>

<div>
    <x-layouts::content heading="Rôles & Permissions" subheading="Gérez l'accès et les privilèges de l'équipe">

        <x-slot:actions>
            <div class="flex gap-3">
                <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Rechercher..."
                    class="max-w-xs" />
                <flux:modal.trigger name="role-modal">
                    <flux:button icon="plus" wire:click="create">Créer un rôle</flux:button>
                </flux:modal.trigger>
            </div>
        </x-slot:actions>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($roles as $role)
                @php
                    $isAdmin = in_array($role->name, ['Responsable', 'Manager']);
                    $isManager = $role->name === 'Superviseur';

                    $accentColor = $isAdmin ? 'indigo' : ($isManager ? 'blue' : 'zinc');
                    $accentBorder = $isAdmin ? 'border-t-indigo-500' : ($isManager ? 'border-t-blue-500' : 'border-t-zinc-400');
                    $iconColor = $isAdmin ? 'text-indigo-600' : ($isManager ? 'text-blue-600' : 'text-zinc-600');
                    $bgColor = $isAdmin ? 'bg-indigo-50' : ($isManager ? 'bg-blue-50' : 'bg-zinc-50');
                @endphp

                <div
                    class="group relative flex flex-col bg-white border border-zinc-200 rounded-xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 {{ $accentBorder }} border-t-4 overflow-hidden">

                    <!-- Header -->
                    <div class="p-6 pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-lg {{ $bgColor }} {{ $iconColor }}">
                                    @if($isAdmin)
                                        <flux:icon name="shield-check" class="size-6" />
                                    @elseif($isManager)
                                        <flux:icon name="briefcase" class="size-6" />
                                    @else
                                        <flux:icon name="users" class="size-6" />
                                    @endif
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <h3 class="font-bold text-lg text-zinc-900 leading-tight truncate">
                                        {{ $role->name }}
                                    </h3>
                                    <p class="text-xs text-zinc-500 font-medium tracking-wide uppercase mt-0.5">
                                        {{ $role->users_count }} Membre{{ $role->users_count > 1 ? 's' : '' }}
                                    </p>
                                </div>
                            </div>

                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                    class="text-zinc-400 hover:text-zinc-600" />
                                <flux:menu>
                                    <flux:modal.trigger name="role-modal">
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $role->id }})">Modifier
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" wire:click="delete({{ $role->id }})"
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce rôle ?" class="text-red-600">
                                        Supprimer</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <p class="text-sm text-zinc-600 leading-relaxed min-h-[40px] line-clamp-2">
                            {{ $role->description ?? 'Rôle système standard avec permissions prédéfinies.' }}
                        </p>
                    </div>

                    <!-- Divider -->
                    <div class="h-px w-full bg-linear-to-r from-transparent via-zinc-200 to-transparent">
                    </div>

                    <!-- Permissions -->
                    <div class="p-6 pt-4 flex-1 bg-zinc-50/50">
                        <div class="flex items-center gap-2 mb-3">
                            <flux:icon name="key" class="size-3.5 text-zinc-400" />
                            <span class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Capacités</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @forelse($role->permissions->take(5) as $permission)
                                <div
                                    class="px-2.5 py-1 rounded-md text-xs font-medium bg-white border border-zinc-200 text-zinc-600 shadow-sm">
                                    {{ $permission->name }}
                                </div>
                            @empty
                                <span class="text-xs italic text-zinc-400">Accès restreint</span>
                            @endforelse

                            @if($role->permissions->count() > 5)
                                <div
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-zinc-100 text-zinc-500 border border-transparent">
                                    +{{ $role->permissions->count() - 5 }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-layouts::content>

    <flux:modal name="role-modal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $role ? 'Modifier le rôle' : 'Créer un rôle' }}</flux:heading>
                <flux:subheading>Gérez les détails du rôle et les permissions.</flux:subheading>
            </div>

            <flux:input label="Nom" wire:model="name" placeholder="ex: Manager" required />
            <flux:textarea label="Description" wire:model="description" placeholder="Description du rôle..." />

            <flux:field>
                <flux:label>Permissions</flux:label>
                <div
                    class="space-y-2 max-h-48 overflow-y-auto border border-zinc-200 rounded-lg p-3 dark:border-zinc-700">
                    @foreach($this->permissions as $permission)
                        <flux:checkbox :label="$permission->name" wire:model="selectedPermissions"
                            :value="$permission->name" />
                    @endforeach
                </div>
                <flux:error name="selectedPermissions" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $role ? 'Enregistrer les modifications' : 'Créer le rôle' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>