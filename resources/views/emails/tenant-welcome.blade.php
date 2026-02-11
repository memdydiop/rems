@component('emails.layout')
<h1>Bienvenue sur {{ config('app.name') }} ! 🎉</h1>

<p>Bonjour {{ $tenant->company }},</p>

<p>Votre compte a été créé avec succès. Vous pouvez maintenant accéder à votre espace de gestion immobilière
    personnalisé.</p>

<div class="info-box">
    <p><strong>Votre domaine :</strong> {{ $domain }}</p>
</div>

<p style="text-align: center;">
    <a href="http://{{ $domain }}" class="button">Accéder à mon espace</a>
</p>

<p>Voici ce que vous pouvez faire dès maintenant :</p>
<ul>
    <li>📍 Ajouter vos propriétés et unités</li>
    <li>👥 Gérer vos locataires</li>
    <li>📋 Créer des baux</li>
    <li>💰 Suivre les paiements de loyer</li>
    <li>🔧 Gérer les demandes de maintenance</li>
</ul>

<p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

<p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>
@endcomponent