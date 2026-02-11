<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Flux\Flux;

new #[Layout('layouts.app', ['title' => 'Rôles & Permissions'])] class extends Component {
    public $name = '';
    public $description = '';
    public $selectedPermissions = [];
    public ?Role $role = null;

    public function mount()
    {
        // No initial data needed for index, modals will load/reset state
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
        $this->description = $role->description; // Custom column
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

        if ($this->role && $this->role->name === 'Ghost') {
            Flux::toast('Impossible de modifier le rôle Fantôme.', 'danger');
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
                'guard_name' => 'web', // Default for central
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

        if ($role->name === 'Ghost') {
            Flux::toast('Impossible de supprimer le rôle Fantôme.', 'danger');
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
            'roles' => Role::where('name', '!=', 'Ghost')->withCount('users')->get(),
        ];
    }
}; ?>

<div>
    <x-layouts::content heading="Rôles & Permissions" subheading="Gérez les accès et privilèges du système.">

        <x-slot name="actions">
            <flux:modal.trigger name="role-modal">
                <flux:button icon="plus" variant="primary" wire:click="create">Créer un Rôle</flux:button>
            </flux:modal.trigger>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($roles as $role)
                @php
                    $isSuper = in_array($role->name, ['Admin', 'Super Admin']);
                    $isManager = $role->name === 'Manager';

                    // Using semantic colors based on role importance
                    $iconColor = $isSuper ? 'text-indigo-600' : ($isManager ? 'text-blue-600' : 'text-zinc-600');
                    $bgColor = $isSuper ? 'bg-indigo-50' : ($isManager ? 'bg-blue-50' : 'bg-zinc-50');
                    $borderColor = $isSuper ? 'border-t-indigo-500' : ($isManager ? 'border-t-blue-500' : 'border-t-zinc-400');
                @endphp

                <x-flux::card class="relative flex flex-col !p-0 overflow-hidden border-t-4! {{ $borderColor }}">

                    <!-- Header -->
                    <div class="p-6 pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center size-10 rounded-xl {{ $bgColor }} {{ $iconColor }}">
                                    @if($isSuper)
                                        <flux:icon.shield-check class="size-6" />
                                    @elseif($isManager)
                                        <flux:icon.briefcase class="size-6" />
                                    @else
                                        <flux:icon.users class="size-6" />
                                    @endif
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-zinc-900 leading-tight">
                                        {{ $role->name }}
                                    </h3>
                                    <p class="text-xs text-zinc-500 font-medium tracking-wide uppercase mt-0.5">
                                        {{ $role->users_count }} {{ Str::plural('Membre', $role->users_count) }}
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
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce rôle ?" variant="danger">
                                        Supprimer</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <p class="text-sm text-zinc-600 leading-relaxed min-h-[40px] line-clamp-2">
                            {{ $role->description ?? 'Rôle système standard avec permissions prédéfinies.' }}
                        </p>
                    </div>

                    <!-- Divider -->
                    <div class="h-px w-full bg-zinc-100"></div>

                    <!-- Permissions -->
                    <div class="p-6 pt-4 flex-1 bg-zinc-50/50">
                        <div class="flex items-center gap-2 mb-3">
                            <flux:icon.key class="size-3.5 text-zinc-400" />
                            <span class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Droits d'accès</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @forelse($role->permissions->take(5) as $permission)
                                <div
                                    class="px-2.5 py-1 rounded-md text-xs font-medium bg-white border border-zinc-200 text-zinc-600 shadow-xs">
                                    {{ $permission->name }}
                                </div>
                            @empty
                                <span class="text-xs italic text-zinc-400">Aucune permission</span>
                            @endforelse

                            @if($role->permissions->count() > 5)
                                <div
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-zinc-100 text-zinc-500 border border-transparent">
                                    +{{ $role->permissions->count() - 5 }} autres
                                </div>
                            @endif
                        </div>
                    </div>
                </x-flux::card>
            @endforeach
        </div>
    </x-layouts::content>

    <flux:modal name="role-modal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $role ? 'Modifier le Rôle' : 'Créer un Rôle' }}</flux:heading>
                <flux:subheading>Gérez les détails du rôle et ses accès.</flux:subheading>
            </div>

            <flux:input label="Nom du Rôle" wire:model="name" placeholder="ex: Manager" required />
            <flux:textarea label="Description" wire:model="description" placeholder="Description courte du rôle..." />

            <flux:field>
                <flux:label>Permissions</flux:label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-zinc-200 rounded-lg p-3 bg-white">
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
                <flux:button type="submit" variant="primary">{{ $role ? 'Enregistrer' : 'Créer' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>