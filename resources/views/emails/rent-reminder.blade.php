@component('emails.layout')
<h1>Rappel de paiement de loyer</h1>

<p>Bonjour {{ $renter->first_name }},</p>

<p>Ceci est un rappel amical concernant votre paiement de loyer à venir.</p>

<div class="info-box">
    <p><strong>Montant dû :</strong> {{ number_format($amount, 0, ',', ' ') }} FCFA</p>
    <p><strong>Date d'échéance :</strong> {{ $dueDate->format('d/m/Y') }}</p>
    <p><strong>Propriété :</strong> {{ $property }}</p>
    <p><strong>Unité :</strong> {{ $unit }}</p>
</div>

<p>Veuillez vous assurer que votre paiement est effectué avant la date d'échéance pour éviter tout retard.</p>

<p style="text-align: center;">
    <a href="{{ $paymentUrl ?? '#' }}" class="button">Effectuer le paiement</a>
</p>

<p>Si vous avez déjà effectué ce paiement, veuillez ignorer ce message.</p>

<p>Cordialement,<br>{{ $landlordName ?? 'Votre propriétaire' }}</p>
@endcomponent