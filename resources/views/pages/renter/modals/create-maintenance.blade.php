<?php

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Models\Renter;
use App\Notifications\MaintenanceCreatedNotification;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|string|max:1000')]
    public $description = '';

    #[Validate('required|in:low,medium,high,urgent')]
    public $priority = 'low';

    public function create()
    {
        $this->validate();

        $renter = Renter::where('user_id', auth()->id())->first();

        if (!$renter) {
            $this->js("Flux.toast('Profil locataire introuvable.', variant: 'danger')");
            return;
        }

        $lease = $renter->leases()->where('status', 'active')->first();

        if (!$lease) {
            $this->js("Flux.toast('Aucun bail actif trouvé.', variant: 'danger')");
            return;
        }

        $request = $lease->unit->maintenanceRequests()->create([
            'property_id' => $lease->unit->property_id,
            'user_id' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => MaintenanceStatus::Pending,
        ]);

        // Notify property managers (all users in tenant for now)
        // In a real app we might filter by role
        \App\Models\User::all()->each(function ($user) use ($request) {
            $user->notify(new MaintenanceCreatedNotification($request));
        });

        $this->reset(['title', 'description', 'priority']);

        $this->js("Flux.toast('Demande de maintenance envoyée.')");
        $this->js("Flux.modal('create-maintenance').close()");

        // Refresh parent component if needed, or redirect
        $this->dispatch('maintenance-created');

        return redirect()->route('renter.dashboard');
    }
};
?>

<flux:modal name="create-maintenance" class="min-w-[400px] md:w-[500px]">
    <form wire:submit="create" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Signaler un problème</h2>
            <p class="text-sm text-gray-500">Décrivez le problème pour que nous puissions intervenir.</p>
        </div>

        <flux:input wire:model="title" label="Titre" placeholder="ex: Fuite d'eau cuisine" />

        <flux:textarea wire:model="description" label="Description détaillée" rows="4"
            placeholder="Expliquez le problème..." />

        <flux:select wire:model="priority" label="Urgence">
            @foreach (MaintenancePriority::cases() as $p)
                <flux:select.option value="{{ $p->value }}">{{ $p->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Envoyer la demande</flux:button>
        </div>
    </form>
</flux:modal>