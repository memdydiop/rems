<?php

use App\Models\Lead;
use App\Models\Tenant;
use App\Jobs\CreateTenantJob;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Stancl\Tenancy\Database\Models\Domain;

new class extends Component {
    public $company = '';
    public $subdomain = '';
    public $name = '';
    public $email = '';
    public $lead_id = null;

    #[On('open-create-tenant')]
    public function open($leadId = null)
    {
        $this->reset();

        if ($leadId) {
            $this->lead_id = $leadId;
            $lead = Lead::find($leadId);
            if ($lead) {
                $this->company = $lead->company;
                $this->name = $lead->name;
                $this->email = $lead->email;
                $this->subdomain = Str::slug($lead->company);
            }
        }

        $this->js("Flux.modal('create-tenant').show()");
    }

    public function register()
    {
        $this->validate([
            'company' => 'required|string|max:255',
            'subdomain' => 'required|string|max:20|alpha_dash',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);

        $restricted = ['admin', 'central', 'www', 'mail', 'dashboard', 'app', 'login', 'register', 'api', 'support', 'help'];
        if (in_array(strtolower($this->subdomain), $restricted)) {
            $this->addError('subdomain', 'This subdomain is reserved.');
            return;
        }

        $domain = config('tenancy.central_domains')[0] ?? 'localhost';
        $fullDomain = $this->subdomain . '.' . $domain;

        if (Domain::where('domain', $fullDomain)->exists()) {
            $this->addError('subdomain', 'This subdomain is already taken.');
            return;
        }

        // Dispatch Job
        CreateTenantJob::dispatch(
            $this->company,
            $this->subdomain,
            $this->name,
            $this->email,
            $this->lead_id
        );

        $this->reset();
        $this->js("Flux.modal('create-tenant').close()");
        $this->dispatch('lead-approved'); // Refresh table immediately (Optimistic UI? Or wait?)
        // If we wait, table won't update LEAD STATUS until Job finishes.
        // But we want to close modal.
        // Maybe toast: "Creation started in background."

        $this->js("Flux.toast('Workspace provisioning started in background.')");
    }
};
?>

<flux:modal name="create-tenant" class="min-w-120">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium tracking-tight">Create new workspace</h1>
            <p class="text-sm text-zinc-500">Provision a new tenant environment.</p>
        </div>

        <form wire:submit="register" class="flex flex-col gap-6">
            <input type="hidden" wire:model="lead_id" />
            <flux:input wire:model="company" label="Company Name" placeholder="Acme Inc." />

            <flux:input wire:model="subdomain" label="Workspace URL" placeholder="acme">
                <x-slot name="prefix">https://</x-slot>
                <x-slot name="suffix">.{{ config('tenancy.central_domains')[0] ?? 'localhost' }}</x-slot>
            </flux:input>

            <flux:separator />

            <div class="grid grid-cols-2 gap-6">
                <flux:input wire:model="name" label="Admin Name" placeholder="Anna" />
                <flux:input wire:model="email" label="Email address" type="email" placeholder="anna@example.com" />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Create Workspace</flux:button>
            </div>
        </form>
    </div>
</flux:modal>