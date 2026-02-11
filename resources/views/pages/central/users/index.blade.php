<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\User;
use App\Models\Invitation;
use App\Notifications\CentralUserInvitation;
use App\Traits\WithDataTable;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

new
    #[Layout('layouts.app', ['title' => 'Utilisateurs'])]
    class extends Component {
    use WithPagination;
    use WithDataTable;

    public $tab = 'users';

    #[\Livewire\Attributes\On('user-created')]
    public function refresh()
    {
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function users()
    {
        return $this->applySorting(
            $this->applySearch(User::whereNotGhost(), ['name', 'email'])
        )->paginate($this->perPage, ['*'], 'usersPage');
    }

    #[Computed]
    public function invitations()
    {
        // reusing same sort/search for now, or could duplicate traits if needed
        return $this->applySorting(
            $this->applySearch(Invitation::query()->where('role', '!=', 'Ghost')->whereNull('accepted_at'), ['email', 'role'])
        )->paginate($this->perPage, ['*'], 'invitationsPage');
    }

    #[Computed]
    public function stats()
    {
        return [
            'users' => User::whereNotGhost()->count(),
            'invitations' => Invitation::count(),
            'pending' => Invitation::whereNull('accepted_at')->where('expires_at', '>', now())->count(),
            'expired' => Invitation::whereNull('accepted_at')->where('expires_at', '<=', now())->count(),
            'accepted' => Invitation::whereNotNull('accepted_at')->count(),
        ];
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            $this->js("Flux.toast('Vous ne pouvez pas vous supprimer vous-même.', 'danger')");
            return;
        }

        try {
            $user->delete();
            $this->js("Flux.toast('Utilisateur supprimé.')");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('User deletion failed: ' . $e->getMessage());
            $this->js("Flux.toast('Erreur: Impossible de supprimer l\'utilisateur.', 'danger')");
        }
    }

    // ... (Updating the view below)
// In the same tool call? No, can't easily jump lines. I'll use multi_replace.


    public function resend(Invitation $invitation): void
    {


        $invitation->update([
            'token' => Str::random(32),
            'expires_at' => now()->addMinutes(config('auth.passwords.users.expire', 60)),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new CentralUserInvitation($invitation->token));

        $this->js("Flux.toast('Invitation renvoyée avec succès.')");
    }

    public function deleteInvitation(Invitation $invitation): void
    {
        $invitation->delete();
        $this->js("Flux.toast('Invitation annulée.')");
    }
};
?>

<div>
    <x-layouts::content heading="Gestion de l'équipe" subheading="Gérez les administrateurs et les invitations.">

        <x-slot:actions>
            <flux:modal.trigger name="create-user">
                <flux:button icon="plus" variant="primary">Inviter un utilisateur</flux:button>
            </flux:modal.trigger>
        </x-slot:actions>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <x-flux::card class="flex items-center gap-3 !p-4">
                <div class="flex items-center justify-center size-10 rounded-xl bg-blue-50 text-blue-600">
                    <flux:icon.users class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Membres</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['users'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 !p-4">
                <div class="flex items-center justify-center size-10 rounded-xl bg-emerald-50 text-emerald-600">
                    <flux:icon.clock class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">En Attente</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['pending'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 !p-4">
                <div class="flex items-center justify-center size-10 rounded-xl bg-purple-50 text-purple-600">
                    <flux:icon.check-circle class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Acceptées</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['accepted'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 !p-4">
                <div class="flex items-center justify-center size-10 rounded-xl bg-red-50 text-red-600">
                    <flux:icon.exclamation-triangle class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Expirées</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['expired'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 !p-4">
                <div class="flex items-center justify-center size-10 rounded-xl bg-indigo-50 text-indigo-600">
                    <flux:icon.envelope class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['invitations'] }}</span>
                </div>
            </x-flux::card>
        </div>

        <div class="mt-8">
            <div class="flex space-x-1 bg-zinc-100 p-1 rounded-lg w-fit mb-6">
                <button wire:click="$set('tab', 'users')"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all {{ $tab === 'users' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Utilisateurs Actifs
                </button>
                <button wire:click="$set('tab', 'invitations')"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all {{ $tab === 'invitations' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Invitations
                </button>
            </div>

            @if ($tab === 'users')
                <x-flux::card class="!p-0 overflow-hidden">
                    <div class="flex justify-between items-center p-4 border-b border-zinc-100 bg-white">
                        <h3 class="font-semibold text-zinc-900">Administrateurs</h3>
                        <div class="flex gap-2">
                            <flux:input wire:model.live="search" icon="magnifying-glass" size="sm"
                                placeholder="Rechercher..." class="w-64" />
                        </div>
                    </div>

                    <x-flux::table :paginate="$this->users">
                        <x-flux::table.columns>
                            <x-flux::table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">Nom</x-flux::table.column>
                            <x-flux::table.column sortable :sorted="$sortCol === 'email'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('email')">Email</x-flux::table.column>
                            <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Créé le</x-flux::table.column>
                            <x-flux::table.column>Actions</x-flux::table.column>
                        </x-flux::table.columns>

                        <x-flux::table.rows>
                            @foreach ($this->users as $user)
                                <x-flux::table.row :key="$user->id">
                                    <x-flux::table.cell class="flex items-center gap-3">
                                        <flux:avatar src="https://i.pravatar.cc/150?u={{ $user->email }}" size="sm" />
                                        <span class="font-medium text-zinc-900">{{ $user->name }}</span>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell>{{ $user->email }}</x-flux::table.cell>
                                    <x-flux::table.cell>{{ $user->created_at->format('d/m/Y') }}</x-flux::table.cell>
                                    <x-flux::table.cell>
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" />
                                            <flux:menu>
                                                <flux:menu.item icon="trash" wire:click="deleteUser('{{ $user->id }}')"
                                                    wire:confirm="Êtes-vous sûr de vouloir supprimer cet utilisateur ?"
                                                    variant="danger">
                                                    Supprimer</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </x-flux::table.cell>
                                </x-flux::table.row>
                            @endforeach
                        </x-flux::table.rows>
                    </x-flux::table>
                </x-flux::card>
            @endif

            @if ($tab === 'invitations')
                <x-flux::card class="!p-0 overflow-hidden">
                    <div class="flex justify-between items-center p-4 border-b border-zinc-100 bg-white">
                        <h3 class="font-semibold text-zinc-900">Invitations en cours</h3>
                        <div class="flex gap-2">
                            <flux:input wire:model.live="search" icon="magnifying-glass" size="sm"
                                placeholder="Rechercher..." class="w-64" />
                        </div>
                    </div>

                    <x-flux::table :paginate="$this->invitations">
                        <x-flux::table.columns>
                            <x-flux::table.column sortable :sorted="$sortCol === 'email'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('email')">Email</x-flux::table.column>
                            <x-flux::table.column sortable :sorted="$sortCol === 'role'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('role')">Rôle</x-flux::table.column>
                            <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Envoyée le</x-flux::table.column>
                            <x-flux::table.column sortable :sorted="$sortCol === 'expires_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('expires_at')">Expire</x-flux::table.column>
                            <x-flux::table.column>Statut</x-flux::table.column>
                            <x-flux::table.column>Actions</x-flux::table.column>
                        </x-flux::table.columns>

                        <x-flux::table.rows>
                            @foreach ($this->invitations as $invitation)
                                <x-flux::table.row :key="$invitation->id">
                                    <x-flux::table.cell
                                        class="font-medium text-zinc-900">{{ $invitation->email }}</x-flux::table.cell>
                                    <x-flux::table.cell>
                                        <flux:badge size="sm" color="zinc">{{ $invitation->role }}</flux:badge>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell>{{ $invitation->created_at->format('d/m/Y H:i') }}</x-flux::table.cell>
                                    <x-flux::table.cell>{{ $invitation->expires_at->diffForHumans() }}</x-flux::table.cell>
                                    <x-flux::table.cell>
                                        @if ($invitation->expires_at->isPast())
                                            <flux:badge color="red" size="sm">Expirée</flux:badge>
                                        @else
                                            <flux:badge color="emerald" size="sm">Valide</flux:badge>
                                        @endif
                                    </x-flux::table.cell>
                                    <x-flux::table.cell>
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" />
                                            <flux:menu>
                                                <flux:menu.item icon="paper-airplane"
                                                    wire:click="resend({{ $invitation->id }})">
                                                    Renvoyer
                                                </flux:menu.item>
                                                <flux:menu.item icon="trash"
                                                    wire:click="deleteInvitation({{ $invitation->id }})"
                                                    wire:confirm="Êtes-vous sûr de vouloir annuler cette invitation ?"
                                                    variant="danger">
                                                    Annuler</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </x-flux::table.cell>
                                </x-flux::table.row>
                            @endforeach
                        </x-flux::table.rows>
                    </x-flux::table>
                </x-flux::card>
            @endif
        </div>
    </x-layouts::content>

    <livewire:pages::central.users.modals.create />
</div>