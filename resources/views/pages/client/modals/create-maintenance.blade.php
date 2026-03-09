<?php

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Models\Client;
use App\Notifications\MaintenanceCreatedNotification;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|string|max:1000')]
    public $description = '';

    #[Validate('required|in:low,medium,high,urgent')]
    public $priority = 'medium';

    #[Validate('nullable|image|max:10240')]
    public $photo;

    public function create()
    {
        $this->validate();

        $client = Client::where('user_id', auth()->id())->first();

        if (!$client) {
            $this->js("Flux.toast('Profil client introuvable.', variant: 'danger')");
            return;
        }

        $lease = $client->leases()->where('status', 'active')->first();

        if (!$lease) {
            $this->js("Flux.toast('Aucun bien affecté trouvé.', variant: 'danger')");
            return;
        }

        $request = $lease->unit->maintenanceRequests()->create([
            'property_id' => $lease->unit->property_id,
            'user_id' => auth()->id(),
            'client_id' => $client->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'category' => \App\Enums\MaintenanceCategory::Unit,
            'status' => MaintenanceStatus::Pending,
        ]);

        if ($this->photo) {
            $request->addMedia($this->photo->getRealPath())
                ->usingFileName($this->photo->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        // Notify property managers
        \App\Models\User::all()->each(function ($user) use ($request) {
            $user->notify(new MaintenanceCreatedNotification($request));
        });

        $this->reset(['title', 'description', 'priority', 'photo']);

        $this->js("Flux.toast('Demande d\'intervention envoyée.')");
        $this->js("Flux.modal('client-create-maintenance').close()");

        $this->dispatch('maintenance-created');

        return redirect()->route('client.dashboard');
    }
};
?>

<flux:modal name="client-create-maintenance" class="min-w-100 md:w-125">
    <form wire:submit="create" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Signaler un problème</h2>
            <p class="text-sm text-gray-500">Décrivez le problème pour que nous puissions intervenir.</p>
        </div>

        <flux:input wire:model="title" label="Titre" placeholder="ex: Fuite d'eau cuisine" />

        <flux:textarea wire:model="description" label="Description" rows="4" placeholder="Expliquez le problème..."
            description="Plus vous donnez de détails, plus vite nous pourrons intervenir." />

        <flux:input wire:model="photo" type="file" label="Photo (optionnel)"
            description="Une photo aide souvent à mieux comprendre le problème." />

        @if ($photo)
            <div class="mt-4 relative inline-block">
                <img src="{{ $photo->temporaryUrl() }}"
                    class="w-32 h-32 object-cover rounded-xl shadow-sm border border-zinc-200">
                <button type="button" wire:click="$set('photo', null)"
                    class="absolute -top-2 -right-2 bg-white rounded-full shadow-md p-1 border border-zinc-200 hover:bg-zinc-50">
                    <flux:icon.x-mark class="size-4 text-zinc-500" />
                </button>
            </div>
        @endif

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