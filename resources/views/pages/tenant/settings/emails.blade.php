<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Lease;
use App\Models\Client;
use App\Models\MaintenanceRequest;

new
    #[Layout('layouts.app', ['title' => 'Prévisualisation des Emails'])]
    class extends Component {

    public string $selectedTemplate = 'welcome';

    public function getPreviewDataProperty()
    {
        return match($this->selectedTemplate) {
            'welcome' => [
                'company' => 'Exemple Entreprise',
                'domain' => 'exemple.votreapp.com',
            ],
            'rent-reminder' => [
                'client' => (object)['first_name' => 'Jean'],
                'amount' => 150000,
                'dueDate' => now()->addDays(3),
                'property' => 'Résidence Les Jardins',
                'unit' => 'Appartement A1',
            ],
            'lease-expiring' => [
                'lease' => (object)[
                    'client' => (object)['first_name' => 'Marie', 'last_name' => 'Dupont'],
                    'unit' => (object)[
                        'name' => 'Bureau B2',
                        'property' => (object)['name' => 'Immeuble Commerce'],
                    ],
                    'end_date' => now()->addDays(25),
                ],
                'daysRemaining' => 25,
            ],
            'maintenance' => [
                'request' => (object)[
                    'priority' => 'high',
                    'description' => 'Fuite d\'eau dans la salle de bain, urgent.',
                    'client' => (object)['first_name' => 'Pierre', 'last_name' => 'Martin'],
                    'unit' => (object)[
                        'name' => 'Apt C3',
                        'property' => (object)['name' => 'Résidence Soleil'],
                    ],
                ],
            ],
            default => [],
        };
    }

    public function getTemplateViewProperty()
    {
        return match($this->selectedTemplate) {
            'welcome' => 'emails.tenant-welcome',
            'rent-reminder' => 'emails.rent-reminder',
            'lease-expiring' => 'emails.lease-expiring',
            'maintenance' => 'emails.maintenance-request',
            default => 'emails.tenant-welcome',
        };
    }
};
?>

<div>
    <x-layouts::content heading="Prévisualisation des Emails"
        subheading="Visualisez les templates d'email avant envoi.">

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Template Selector -->
            <div class="lg:col-span-1">
                <x-flux::card>
                    <x-flux::card.header title="Templates" />
                    <div class="p-4 space-y-2">
                        <button wire:click="$set('selectedTemplate', 'welcome')"
                            class="w-full text-left px-4 py-3 rounded-lg transition-colors {{ $selectedTemplate === 'welcome' ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-zinc-50' }}">
                            <div class="flex items-center gap-3">
                                <flux:icon name="envelope-open" class="w-5 h-5" />
                                <span>Bienvenue Tenant</span>
                            </div>
                        </button>
                        
                        <button wire:click="$set('selectedTemplate', 'rent-reminder')"
                            class="w-full text-left px-4 py-3 rounded-lg transition-colors {{ $selectedTemplate === 'rent-reminder' ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-zinc-50' }}">
                            <div class="flex items-center gap-3">
                                <flux:icon name="bell-alert" class="w-5 h-5" />
                                <span>Rappel Loyer</span>
                            </div>
                        </button>
                        
                        <button wire:click="$set('selectedTemplate', 'lease-expiring')"
                            class="w-full text-left px-4 py-3 rounded-lg transition-colors {{ $selectedTemplate === 'lease-expiring' ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-zinc-50' }}">
                            <div class="flex items-center gap-3">
                                <flux:icon name="clock" class="w-5 h-5" />
                                <span>Expiration Bail</span>
                            </div>
                        </button>
                        
                        <button wire:click="$set('selectedTemplate', 'maintenance')"
                            class="w-full text-left px-4 py-3 rounded-lg transition-colors {{ $selectedTemplate === 'maintenance' ? 'bg-blue-50 text-blue-700 font-medium' : 'hover:bg-zinc-50' }}">
                            <div class="flex items-center gap-3">
                                <flux:icon name="wrench-screwdriver" class="w-5 h-5" />
                                <span>Maintenance</span>
                            </div>
                        </button>
                    </div>
                </x-flux::card>
            </div>

            <!-- Email Preview -->
            <div class="lg:col-span-3">
                <x-flux::card>
                    <x-flux::card.header title="Aperçu" />
                    <div class="p-4">
                        <div class="border border-zinc-200 rounded-lg overflow-hidden">
                            <!-- Email Header Bar -->
                            <div class="bg-zinc-100 px-4 py-2 border-b border-zinc-200">
                                <div class="flex items-center gap-2 text-sm text-zinc-600">
                                    <span class="font-medium">De:</span>
                                    <span>noreply@{{ config('app.name') }}.com</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-zinc-600">
                                    <span class="font-medium">À:</span>
                                    <span>utilisateur@exemple.com</span>
                                </div>
                            </div>
                            
                            <!-- Email Content -->
                            <div class="bg-gray-100 p-6">
                                @php
                                    $data = $this->previewData;
                                @endphp
                                
                                @if($selectedTemplate === 'welcome')
                                    @include('emails.tenant-welcome', [
                                        'tenant' => (object)['company' => $data['company']],
                                        'domain' => $data['domain'],
                                    ])
                                @elseif($selectedTemplate === 'rent-reminder')
                                    @include('emails.rent-reminder', $data)
                                @elseif($selectedTemplate === 'lease-expiring')
                                    @include('emails.lease-expiring', $data)
                                @elseif($selectedTemplate === 'maintenance')
                                    @include('emails.maintenance-request', $data)
                                @endif
                            </div>
                        </div>
                    </div>
                </x-flux::card>
            </div>
        </div>

    </x-layouts::content>
</div>
