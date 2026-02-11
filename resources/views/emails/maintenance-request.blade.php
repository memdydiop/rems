@component('emails.layout')
<h1>Nouvelle demande de maintenance</h1>

<p>Bonjour,</p>

<p>Une nouvelle demande de maintenance a été soumise.</p>

<div class="info-box">
    <p><strong>Priorité :</strong>
        @if($request->priority === 'urgent')
            🔴 Urgent
        @elseif($request->priority === 'high')
            🟠 Haute
        @elseif($request->priority === 'medium')
            🟡 Moyenne
        @else
            🟢 Basse
        @endif
    </p>
    <p><strong>Propriété :</strong> {{ $request->unit->property->name }}</p>
    <p><strong>Unité :</strong> {{ $request->unit->name }}</p>
    <p><strong>Locataire :</strong> {{ $request->renter->first_name }} {{ $request->renter->last_name }}</p>
</div>

<p><strong>Description :</strong></p>
<p style="background: #f4f4f5; padding: 15px; border-radius: 8px;">{{ $request->description }}</p>

<p style="text-align: center;">
    <a href="{{ $requestUrl ?? '#' }}" class="button">Voir la demande</a>
</p>

<p>Cordialement,<br>{{ config('app.name') }}</p>
@endcomponent