<?php

use App\Models\Subscription;
use App\Models\Plan;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Vérification du Paiement'])] class extends Component {
    public $message = 'Vérification du paiement en cours...';
    public $status = 'processing'; // processing, success, error

    public function mount(Request $request, PaystackService $paystack)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('tenant.settings.billing');
        }

        try {
            $verification = $paystack->verifyTransaction($reference);

            if (($verification['status'] ?? false) && ($verification['data']['status'] ?? '') === 'success') {
                $metadata = $verification['data']['metadata'] ?? [];
                $planId = $metadata['plan_id'] ?? null;
                $tenantId = $metadata['tenant_id'] ?? tenancy()->tenant->id;

                if ($planId) {
                    $plan = Plan::find($planId);

                    if ($plan) {
                        Subscription::updateOrCreate(
                            ['tenant_id' => $tenantId],
                            [
                                'plan_id' => $plan->id,
                                'status' => 'active',
                                'paystack_subscription_code' => $verification['data']['plan'] ?? null,
                                'paystack_customer_code' => $verification['data']['customer']['customer_code'] ?? null,
                                'starts_at' => now(),
                                'ends_at' => $plan->interval === 'monthly' ? now()->addMonth() : now()->addYear(),
                            ]
                        );

                        tenancy()->tenant->update([
                            'plan_id' => $plan->id,
                            'trial_ends_at' => null
                        ]);

                        $this->status = 'success';
                        $this->message = 'Paiement réussi ! Redirection...';

                        // Use a short delay before redirect so user sees the success state
                        $this->js("setTimeout(() => window.location.href = '" . route('tenant.settings.billing') . "', 2000)");
                        session()->flash('flux.toast', 'Abonnement activé avec succès !');
                        return;
                    }
                }
            }

            $this->status = 'error';
            $this->message = 'La vérification du paiement a échoué.';
            \Illuminate\Support\Facades\Log::error('Payment verification failed', ['response' => $verification]);

        } catch (\Exception $e) {
            $this->status = 'error';
            $this->message = 'Erreur lors de la vérification : ' . $e->getMessage();
            \Illuminate\Support\Facades\Log::error('Paystack Callback Error: ' . $e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
    @if($status === 'processing')
        <flux:icon name="arrow-path" class="size-12 animate-spin text-blue-500" />
        <h2 class="text-xl font-semibold text-zinc-900">{{ $message }}</h2>
    @elseif($status === 'success')
        <div class="rounded-full bg-green-100 p-4 text-green-600">
            <flux:icon name="check" class="size-12" />
        </div>
        <h2 class="text-xl font-semibold text-green-700">Paiement Réussi !</h2>
        <p class="text-zinc-500">Votre abonnement est maintenant actif.</p>
    @else
        <div class="rounded-full bg-red-100 p-4 text-red-600">
            <flux:icon name="x-mark" class="size-12" />
        </div>
        <h2 class="text-xl font-semibold text-red-700">Échec du paiement</h2>
        <p class="text-zinc-500">{{ $message }}</p>
        <flux:button href="{{ route('tenant.settings.billing') }}" variant="primary">Retour à la facturation</flux:button>
    @endif
</div>