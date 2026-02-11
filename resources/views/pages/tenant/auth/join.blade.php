<?php

use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth', ['title' => 'Rejoindre l\'espace de travail'])] class extends Component {
    public $token = '';
    public $email = '';
    public $name = '';
    public $password = '';
    public $password_confirmation = '';

    public $invitation = null;
    public $userExists = false;

    public function mount($token)
    {
        $this->token = $token;
        $this->invitation = TenantInvitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $this->email = $this->invitation->email;

        // Check if user already exists in THIS tenant
        $user = User::where('email', $this->email)->first();
        if ($user) {
            $this->userExists = true;
            // If user exists, we might want to auto-accept or ask for login?
            // For simplicity: specific flow. If logged in as THAT user, auto join.
            // If not logged in, ask to login?
            // If logged in as ANOTHER user, error.

            if (Auth::check()) {
                if (Auth::user()->email === $this->email) {
                    $this->acceptExisting();
                } else {
                    abort(403, 'Vous êtes connecté avec un utilisateur différent.');
                }
            }
        }
    }

    public function acceptExisting()
    {
        $user = User::where('email', $this->email)->first();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $this->invitation->role, 'guard_name' => 'web']);

        $user->assignRole($this->invitation->role);
        $this->invitation->update(['accepted_at' => now()]);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function register()
    {
        if ($this->userExists) {
            // Should login instead
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Rules\Password::default(), 'confirmed'],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // Ensure role exists in this tenant before assigning
        // This acts as a lazy seeder
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $this->invitation->role, 'guard_name' => 'web']);

        $user->assignRole($this->invitation->role);
        $this->invitation->update(['accepted_at' => now()]);

        Auth::login($user);

        $this->redirect(route('dashboard'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="'Rejoindre ' . tenancy()->tenant->company" :description="'Vous avez été invité à rejoindre cet espace de travail.'" />

    @if($userExists && !Auth::check())
        <div class="flex flex-col gap-4">
            <x-flux::card class="text-center">
                <p class="mb-4">Un compte avec l'email <strong>{{ $email }}</strong> existe déjà.</p>
                <flux:button href="{{ route('login') }}?return_to={{ url()->current() }}" variant="filled" class="w-full">
                    Se connecter pour accepter</flux:button>
            </x-flux::card>
        </div>
    @elseif($userExists && Auth::check())
        <div class="flex flex-col gap-4">
            <x-flux::card class="text-center">
                <p class="mb-4">Vous êtes connecté en tant que <strong>{{ Auth::user()->name }}</strong>.</p>
                <flux:button wire:click="acceptExisting" variant="primary" class="w-full">Accepter l'invitation
                </flux:button>
            </x-flux::card>
        </div>
    @else
        <form wire:submit="register" class="flex flex-col gap-6">
            <flux:input wire:model="email" label="Email" type="email" readonly disabled />
            <flux:input wire:model="name" label="Nom" type="text" required autofocus autocomplete="name" />
            <flux:input wire:model="password" label="Mot de passe" type="password" required autocomplete="new-password" />
            <flux:input wire:model="password_confirmation" label="Confirmer le mot de passe" type="password" required
                autocomplete="new-password" />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">Créer un compte & rejoindre</flux:button>
            </div>
        </form>
    @endif
</div>