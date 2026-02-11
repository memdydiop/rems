<?php

use App\Models\User;
use App\Models\Invitation;
use App\Notifications\CentralUserInvitation;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public $name = '';
    public $email = '';
    public $role = '';
    // Password removed

    #[Computed]
    public function roles()
    {
        return Role::where('name', '!=', 'Ghost')->get();
    }

    #[On('open-modal')]
    public function open($name)
    {
        if ($name === 'create-user') {
            $this->reset();
            $this->js("Flux.modal('create-user').show()");
        }
    }

    public function register()
    {
        $this->validate([
            'email' => 'required|string|email|max:255|unique:users,email|unique:invitations,email',
            'role' => ['required', 'exists:roles,name', 'not_in:Ghost'],
        ]);

        $token = Str::random(32);

        Invitation::create([
            'email' => $this->email,
            'role' => $this->role,
            'token' => $token,
            'expires_at' => now()->addMinutes(config('auth.passwords.users.expire', 60)),
        ]);

        \Illuminate\Support\Facades\Notification::route('mail', $this->email)
            ->notify(new CentralUserInvitation($token));

        $this->reset();
        $this->js("Flux.modal('create-user').close()");
        $this->js("Flux.toast('Invitation envoyée avec succès.')");
    }
};
?>

<flux:modal name="create-user" class="min-w-120">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium tracking-tight">Inviter un Collaborateur</h1>
            <p class="text-sm text-zinc-500">Envoyez une invitation par email pour rejoindre l'administration.</p>
        </div>

        <form wire:submit="register" class="flex flex-col gap-6">
            <flux:input wire:model="email" label="Adresse Email" type="email"
                placeholder="ex: jean.dupont@exemple.com" />

            <flux:select wire:model="role" label="Rôle" placeholder="Sélectionnez un rôle">
                @foreach ($this->roles as $role)
                    <flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Envoyer l'invitation</flux:button>
            </div>
        </form>
    </div>
</flux:modal>