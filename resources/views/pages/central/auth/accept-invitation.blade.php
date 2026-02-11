<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Flux\Flux;

use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.auth', ['title' => 'Accept Invitation'])] class extends Component {
    public $token;
    public $email;
    public $password = '';
    public $password_confirmation = '';
    public $invitation;

    public function mount($token)
    {
        $this->token = $token;
        $this->invitation = Invitation::where('token', $token)->firstOrFail();

        if ($this->invitation->expires_at->isPast()) {
            abort(403, 'This invitation has expired.');
        }

        $this->email = $this->invitation->email;
    }

    public function register()
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => strstr($this->email, '@', true), // Default name from email part
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->markEmailAsVerified();
        $user->assignRole($this->invitation->role);

        $this->invitation->update(['accepted_at' => now()]);

        Auth::login($user);

        return redirect()->route('central.dashboard');
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Join the Team')" :description="__('Set your password to accept the invitation')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <flux:input label="Email" value="{{ $email }}" readonly disabled />

        <flux:input wire:model="password" label="Password" type="password" required />
        <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" required />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                Create Account
            </flux:button>
        </div>
    </form>
</div>