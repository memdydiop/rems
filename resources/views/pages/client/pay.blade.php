@extends('layouts.client', ['title' => 'Payer mon loyer'])

@section('content')
    <div class="min-h-screen bg-zinc-50">
        <!-- Header -->
        <div class="bg-white border-b border-zinc-200">
            <div class="max-w-3xl mx-auto px-4 py-6">
                <div class="flex items-center gap-4">
                    <flux:button variant="ghost" icon="arrow-left" href="{{ route('client.dashboard') }}" />
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900">Paiement en ligne</h1>
                        <p class="text-zinc-500">Paiement sécurisé via Paystack</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-4 py-8">
            @if(session('error'))
                <div
                    class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm flex items-center gap-3">
                    <flux:icon name="exclamation-triangle" class="size-5 shrink-0" />
                    {{ session('error') }}
                </div>
            @endif

            @if($alreadyPaid)
                <!-- Already Paid -->
                <div class="bg-white rounded-2xl shadow-lg border border-zinc-100 p-8 text-center">
                    <div class="size-20 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-6">
                        <flux:icon name="check-circle" class="size-10 text-emerald-500" />
                    </div>
                    <h2 class="text-2xl font-bold text-zinc-900 mb-2">Paiement déjà effectué !</h2>
                    <p class="text-zinc-500 mb-6">Votre paiement pour le mois de {{ now()->translatedFormat('F Y') }} est déjà
                        enregistré.</p>
                    <flux:button variant="filled" href="{{ route('client.dashboard') }}" icon="arrow-left">
                        Retour au tableau de bord
                    </flux:button>
                </div>
            @else
                <!-- Payment Summary -->
                <div class="bg-white rounded-2xl shadow-lg border border-zinc-100 overflow-hidden">
                    <!-- Unit Info Header -->
                    <div class="bg-linear-to-r from-indigo-600 to-indigo-700 p-8 text-white">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="size-14 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur">
                                <flux:icon name="home" class="size-7 text-white" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold">{{ $lease->unit?->property?->name }}</h2>
                                <p class="text-indigo-200">{{ $lease->unit?->name }}</p>
                            </div>
                        </div>
                        <p class="text-indigo-200 text-sm flex items-center gap-1">
                            <flux:icon name="map-pin" class="size-4" />
                            {{ $lease->unit?->property?->address }}
                        </p>
                    </div>

                    <!-- Payment Details -->
                    <div class="p-8 space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-zinc-50 rounded-xl">
                                <span class="text-zinc-500 font-medium">Période</span>
                                <span class="font-bold text-zinc-900">{{ now()->translatedFormat('F Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-zinc-50 rounded-xl">
                                <span class="text-zinc-500 font-medium">Client</span>
                                <span class="font-bold text-zinc-900">{{ $client->first_name }} {{ $client->last_name }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-zinc-50 rounded-xl">
                                <span class="text-zinc-500 font-medium">Méthode</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-zinc-900">Paystack</span>
                                    <flux:icon name="lock-closed" class="size-4 text-emerald-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="border-t border-zinc-100 pt-6">
                            <div class="flex items-center justify-between">
                                <span class="text-lg text-zinc-500">Montant à payer</span>
                                <span class="text-3xl font-bold text-zinc-900">
                                    {{ number_format($lease->rent_amount, 0, ',', ' ') }}
                                    <span class="text-base font-normal text-zinc-500">XOF</span>
                                </span>
                            </div>
                        </div>

                        <!-- Pay Button -->
                        <form action="{{ route('client.pay.initialize') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full py-4 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-lg rounded-xl transition-all duration-200 flex items-center justify-center gap-3 shadow-lg shadow-emerald-200 hover:shadow-emerald-300">
                                <flux:icon name="credit-card" class="size-6" />
                                Payer {{ number_format($lease->rent_amount, 0, ',', ' ') }} XOF
                            </button>
                        </form>

                        <p class="text-center text-xs text-zinc-400">
                            <flux:icon name="shield-check" class="size-4 inline" />
                            Paiement sécurisé. Vos données bancaires ne sont pas stockées sur notre plateforme.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection