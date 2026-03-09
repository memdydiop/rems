@component('emails.layout')
<h1>Bail arrivant à expiration ⏰</h1>

<p>Bonjour,</p>

<p>Ce message est pour vous informer qu'un bail arrive à expiration prochainement.</p>

<div class="info-box">
    <p><strong>Client :</strong> {{ $lease->client->first_name }} {{ $lease->client->last_name }}</p>
    <p><strong>Propriété :</strong> {{ $lease->unit->property->name }}</p>
    <p><strong>Unité :</strong> {{ $lease->unit->name }}</p>
    <p><strong>Date de fin :</strong> {{ $lease->end_date->format('d/m/Y') }}</p>
    <p><strong>Jours restants :</strong> {{ $daysRemaining }} jours</p>
</div>

<p>Actions recommandées :</p>
<ul>
    <li>Contacter le client pour discuter du renouvellement</li>
    <li>Préparer un nouveau contrat si nécessaire</li>
    <li>Planifier l'état des lieux de sortie</li>
</ul>

<p style="text-align: center;">
    <a href="{{ $leaseUrl ?? '#' }}" class="button">Voir les détails du bail</a>
</p>

<p>Cordialement,<br>{{ config('app.name') }}</p>
@endcomponent