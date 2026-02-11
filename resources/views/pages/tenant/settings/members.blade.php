<?php

use App\Models\User;
use App\Models\TenantInvitation;
use App\Notifications\TenantInvitationNotification;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Notification;

use Spatie\Permission\Models\Role;

new #[Layout('layouts.app', ['title' => 'Team Members'])] class extends Component {
    use WithPagination;

    // Force refresh
    public $email = '';
    public $role = ''; // Default to empty to force selection
    public $tab = 'active';

    // Search & Sort
    public $search = '';
    public $sortCol = 'created_at';
    public $sortAsc = false;
    public $perPage = 10;

    public function updatedPerPage()
    {
        $this->resetPage('usersPage');
        $this->resetPage('invitationsPage');
    }

    // Edit Role State
    public $editingUserId = null;
    public $editingRole = '';

    public function updatedSearch()
    {
        $this->resetPage('usersPage');
        $this->resetPage('invitationsPage');
    }

    public function updatedTab()
    {
        $this->reset(['search', 'sortCol', 'sortAsc']);
        $this->resetPage('usersPage');
        $this->resetPage('invitationsPage');

        // distinct defaults
        if ($this->tab === 'active') {
            $this->sortCol = 'name';
            $this->sortAsc = true;
        } else {
            $this->sortCol = 'created_at';
            $this->sortAsc = false;
        }
    }

    public function sortBy($col)
    {
        if ($this->sortCol === $col) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $col;
            $this->sortAsc = true;
        }
    }

    // For listing
    public function with()
    {
        $users = User::whereNotGhost()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->tab === 'active', function ($q) {
                $q->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');
            })
            ->paginate($this->perPage, ['*'], 'usersPage');

        $invitations = TenantInvitation::query()
            ->whereNull('accepted_at')
            ->when($this->search, fn($q) => $q->where('email', 'like', '%' . $this->search . '%'))
            ->when($this->tab === 'invitations', function ($q) {
                $q->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');
            })
            ->paginate($this->perPage, ['*'], 'invitationsPage');

        return [
            'users' => $users,
            'invitations' => $invitations,
            'roles' => Role::where('name', '!=', 'Ghost')->orderBy('name')->get(),
        ];
    }

    public function invite()
    {
        $this->validate([
            'email' => 'required|email|unique:users,email|unique:tenant_invitations,email',
            'role' => ['required', 'string', \Illuminate\Validation\Rule::exists('roles', 'name')],
        ]);

        // Check Plan Limits (Users + Invitations)
        $currentCount = User::whereNotGhost()->count() + TenantInvitation::count();
        if (!tenant()->canCreate('max_users', $currentCount)) {
            $this->addError('base', "Limite du forfait atteinte. Passez au forfait supérieur pour inviter plus d'utilisateurs.");
            $this->js("Flux.toast('Limite du forfait atteinte. Passez au forfait supérieur pour inviter plus d\'utilisateurs.', 'danger')");
            return;
        }

        $token = Str::random(32);

        $invitation = TenantInvitation::create([
            'email' => $this->email,
            'role' => $this->role,
            'token' => $token,
            'expires_at' => now()->addHours(48),
        ]);

        // Send Notification
        // We need a notifiable object. Usually we route to mail.
        Notification::route('mail', $this->email)
            ->notify(new TenantInvitationNotification($invitation));

        $this->reset(['email', 'role']);
        $this->js("Flux.toast('Invitation envoyée avec succès.')");
        $this->js("Flux.modal('invite-member').close()");
    }

    public function resendInvitation(TenantInvitation $invitation)
    {
        $invitation->update([
            'token' => Str::random(32),
            'expires_at' => now()->addHours(48),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TenantInvitationNotification($invitation));

        $this->js("Flux.toast('Invitation renvoyée avec succès.')");
    }

    public function cancelInvitation(TenantInvitation $invitation)
    {
        $invitation->delete();
        $this->js("Flux.toast('Invitation annulée.')");
    }

    public function removeUser(User $user)
    {
        // Don't remove yourself
        if ($user->id === auth()->id()) {
            $this->js("Flux.toast('Vous ne pouvez pas vous retirer vous-même.', 'danger')");
            return;
        }

        $user->delete(); // This deletes from TENANT DB.
        $this->js("Flux.toast('Utilisateur retiré de l\'équipe.')");
    }

    public function changeRole(User $user, string $role)
    {
        if ($user->id === auth()->id()) {
            $this->js("Flux.toast('Vous ne pouvez pas modifier votre propre rôle.', 'danger')");
            return;
        }

        if (!Role::where('name', $role)->exists()) {
            $this->js("Flux.toast('Rôle sélectionné invalide.', 'danger')");
            return;
        }

        $user->syncRoles([$role]);
        $this->js("Flux.toast('Rôle mis à jour vers ' . ucfirst($role) . '.')");
    }
    public function openEditRole($userId)
    {
        // Direct assignment, no DB check for now
        $this->editingUserId = $userId;
        $this->editingRole = 'member'; // Default fallback

        $this->js("Flux.modal('edit-role-modal').show()");
    }

    public function updateRole()
    {
        $this->validate([
            'editingRole' => ['required', 'string'],
        ]);

        if (!Role::where('name', $this->editingRole)->exists()) {
            $this->addError('editingRole', 'Le rôle sélectionné est invalide.');
            return;
        }

        $user = User::whereNotGhost()->find($this->editingUserId);

        if (!$user) {
            $this->js("Flux.toast('Utilisateur non trouvé.', 'danger')");
            return;
        }

        if ($user->id === auth()->id()) {
            $this->js("Flux.toast('Vous ne pouvez pas modifier votre propre rôle.', 'danger')");
            return;
        }

        $user->syncRoles([$this->editingRole]);
        $this->js("Flux.toast('Rôle mis à jour avec succès.')");
        $this->js("Flux.modal('edit-role-modal').close()");
    }
    #[Computed]
    public function stats()
    {
        return [
            'active' => User::whereNotGhost()->count(),
            'invitations' => TenantInvitation::count(),
            'valid' => TenantInvitation::whereNull('accepted_at')->where('expires_at', '>', now())->count(),
            'expired' => TenantInvitation::whereNull('accepted_at')->where('expires_at', '<=', now())->count(),
            'accepted' => TenantInvitation::whereNotNull('accepted_at')->count(),
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Membres de l'équipe" subheading="Gérez les accès à cet espace de travail.">

        <x-slot:actions>
            <flux:modal.trigger name="invite-member">
                <flux:button icon="plus">
                    Inviter un membre
                </flux:button>
            </flux:modal.trigger>
        </x-slot:actions>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
            <x-flux::card class="flex items-center gap-3 p-3 border-t-4! border-t-blue-500!">
                <div class="flex items-center justify-center size-10 rounded-xl bg-blue-50 text-blue-600 shadow-sm">
                    <flux:icon.users class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Membres</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['active'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 p-3 border-t-4! border-t-emerald-500!">
                <div
                    class="flex items-center justify-center size-10 rounded-xl bg-emerald-50 text-emerald-600 shadow-sm">
                    <flux:icon.clock class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Valides</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['valid'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 p-3 border-t-4! border-t-purple-500!">
                <div class="flex items-center justify-center size-10 rounded-xl bg-purple-50 text-purple-600 shadow-sm">
                    <flux:icon.check-circle class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Acceptées</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['accepted'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 p-3 border-t-4! border-t-red-500!">
                <div class="flex items-center justify-center size-10 rounded-xl bg-red-50 text-red-600 shadow-sm">
                    <flux:icon.exclamation-triangle class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Expirées</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['expired'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-3 p-3 border-t-4! border-t-orange-500!">
                <div class="flex items-center justify-center size-10 rounded-xl bg-orange-50 text-orange-600 shadow-sm">
                    <flux:icon.envelope class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total</span>
                    <span class="text-xl font-bold text-zinc-900">{{ $this->stats['invitations'] }}</span>
                </div>
            </x-flux::card>
        </div>


        <div class="border-b border-zinc-200">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button wire:click="$set('tab', 'active')"
                        class="{{ $tab === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        Membres Actifs
                    </button>
                    <button wire:click="$set('tab', 'invitations')"
                        class="{{ $tab === 'invitations' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        Invitations en attente
                    </button>
                </nav>
            </div>

            <!-- Active Members -->
            @if($tab === 'active')
                <x-flux::card class="p-0">
                    <x-flux::card.header class="px-6 py-4 border-b border-zinc-100 flex justify-between items-center">
                        <x-flux::card.title>Membres de l'équipe</x-flux::card.title>
                        <div class="flex gap-2">
                            <flux:select wire:model.live="perPage" class="w-20" size="sm">
                                <flux:select.option value="5">5</flux:select.option>
                                <flux:select.option value="10">10</flux:select.option>
                                <flux:select.option value="25">25</flux:select.option>
                            </flux:select>
                            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" size="sm"
                                placeholder="Rechercher..." class="max-w-xs" />
                        </div>
                    </x-flux::card.header>
                    <x-flux::table>
                        <x-flux::table.columns>
                            <x-flux::table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('name')">Membre</x-flux::table.column>
                            <x-flux::table.column sortable :sorted="$sortCol === 'email'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('email')">Email</x-flux::table.column>
                            <x-flux::table.column>Rôle</x-flux::table.column>
                            <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Rejoint le</x-flux::table.column>
                            <x-flux::table.column align="right"></x-flux::table.column>
                        </x-flux::table.columns>
                        <x-flux::table.rows>
                            @foreach ($users as $user)
                                <x-flux::table.row :key="$user->id">
                                    <x-flux::table.cell>
                                        <div class="flex items-center gap-3">
                                            <flux:avatar :name="$user->name" size="sm" />
                                            <span class="font-medium text-zinc-900">{{ $user->name }}</span>
                                        </div>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell class="text-zinc-500">{{ $user->email }}</x-flux::table.cell>
                                    <x-flux::table.cell>
                                        <flux:badge size="sm" inset="top bottom">
                                            {{ ucfirst($user->roles->first()?->name ?? 'membre') }}
                                        </flux:badge>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell
                                        class="text-zinc-500">{{ $user->created_at->format('M j, Y') }}</x-flux::table.cell>
                                    <x-flux::table.cell align="right">
                                        @if($user->id !== auth()->id())
                                            <flux:dropdown>
                                                <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                                <flux:menu>
                                                    <flux:menu.item icon="pencil-square"
                                                        wire:click="openEditRole('{{ $user->id }}')">
                                                        Modifier le rôle
                                                    </flux:menu.item>
                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="trash" wire:click="removeUser('{{ $user->id }}')"
                                                        wire:confirm="Êtes-vous sûr de vouloir retirer cet utilisateur ?"
                                                        class="text-red-600">
                                                        Retirer l'utilisateur
                                                    </flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endif
                                    </x-flux::table.cell>
                                </x-flux::table.row>
                            @endforeach
                        </x-flux::table.rows>
                    </x-flux::table>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </x-flux::card>
            @endif

            <!-- Pending Invitations -->
            @if($tab === 'invitations')
                <x-flux::card class="p-0">
                    <x-flux::card.header class="px-6 py-4 border-b border-zinc-100 flex justify-between items-center">
                        <x-flux::card.title>Invitations envoyées</x-flux::card.title>
                        <div class="flex gap-2">
                            <flux:select wire:model.live="perPage" class="w-20" size="sm">
                                <flux:select.option value="5">5</flux:select.option>
                                <flux:select.option value="10">10</flux:select.option>
                                <flux:select.option value="25">25</flux:select.option>
                            </flux:select>
                            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" size="sm"
                                placeholder="Rechercher..." class="max-w-xs" />
                        </div>
                    </x-flux::card.header>
                    @if($invitations->isEmpty())
                        <div class="p-6 text-center text-zinc-500 text-sm italic">
                            Aucune invitation en attente.
                        </div>
                    @else
                        <x-flux::table>
                            <x-flux::table.columns>
                                <x-flux::table.column sortable :sorted="$sortCol === 'email'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('email')">Email</x-flux::table.column>
                                <x-flux::table.column>Rôle</x-flux::table.column>
                                <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Envoyée</x-flux::table.column>
                                <x-flux::table.column>Expire</x-flux::table.column>
                                <x-flux::table.column>Statut</x-flux::table.column>
                                <x-flux::table.column align="right"></x-flux::table.column>
                            </x-flux::table.columns>
                            <x-flux::table.rows>
                                @foreach ($invitations as $invitation)
                                    <x-flux::table.row :key="$invitation->id">
                                        <x-flux::table.cell
                                            class="font-medium text-zinc-900">{{ $invitation->email }}</x-flux::table.cell>
                                        <x-flux::table.cell>
                                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $invitation->role }}
                                            </flux:badge>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell
                                            class="text-zinc-500">{{ $invitation->created_at->format('M j, Y') }}</x-flux::table.cell>
                                        <x-flux::table.cell
                                            class="text-zinc-500">{{ $invitation->expires_at->diffForHumans() }}</x-flux::table.cell>
                                        <x-flux::table.cell>
                                            @if ($invitation->expires_at->isPast())
                                                <flux:badge color="red" size="sm" inset="top bottom">Expirée</flux:badge>
                                            @else
                                                <flux:badge color="green" size="sm" inset="top bottom">Valide</flux:badge>
                                            @endif
                                        </x-flux::table.cell>
                                        <x-flux::table.cell align="right">
                                            <flux:dropdown>
                                                <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                                <flux:menu>
                                                    <flux:menu.item icon="paper-airplane"
                                                        wire:click="resendInvitation({{ $invitation->id }})">
                                                        Renvoyer
                                                    </flux:menu.item>
                                                    <flux:menu.item icon="trash"
                                                        wire:click="cancelInvitation({{ $invitation->id }})"
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

                        <div class="mt-4">
                            {{ $invitations->links() }}
                        </div>
                    @endif
                </x-flux::card>
            @endif
        </div>

        <!-- Invite Modal -->
        <flux:modal name="invite-member" class="min-w-100">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">Inviter un membre</h2>
                    <p class="text-sm text-gray-500">Envoyer une invitation par email à un nouveau membre.</p>
                </div>

                <form wire:submit="invite" class="space-y-6">
                    <flux:input wire:model="email" label="Adresse email" type="email"
                        placeholder="collegue@exemple.com" />

                    <flux:select wire:model="role" label="Rôle" placeholder="Sélectionner un rôle">
                        @foreach($roles as $r)
                            <flux:select.option value="{{ $r->name }}">{{ ucfirst(str_replace('_', ' ', $r->name)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Annuler</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Envoyer l'invitation</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <!-- Edit Role Modal -->
        <flux:modal name="edit-role-modal" class="min-w-100">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">Modifier le rôle du membre</h2>
                    <p class="text-sm text-gray-500">Mettre à jour le niveau d'accès pour cet utilisateur.</p>
                </div>

                <form wire:submit="updateRole" class="space-y-6">
                    <flux:select wire:model="editingRole" label="Rôle" placeholder="Sélectionner un rôle">
                        @foreach($roles as $r)
                            <flux:select.option value="{{ $r->name }}">{{ ucfirst(str_replace('_', ' ', $r->name)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Annuler</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Mettre à jour le rôle</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    </x-layouts::content>
</div>