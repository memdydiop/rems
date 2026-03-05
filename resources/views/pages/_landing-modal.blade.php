{{-- ═══════════════════════════════════════════════════════════════
MODAL D'INSCRIPTION — Logique PHP inchangée
═══════════════════════════════════════════════════════════════ --}}
<flux:modal name="request-access" class="md:w-[400px]">
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900">
                {{ $selectedPlan ? "Démarrer avec {$selectedPlan}" : 'Créer mon compte gratuit' }}
            </h2>
            @if($selectedPlan)
                <div class="flex items-center gap-2 mt-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[rgba(1,98,232,0.1)] text-[rgb(1,98,232)] text-sm font-semibold">
                        <flux:icon name="sparkles" class="size-3.5" />
                        Plan {{ $selectedPlan }}
                    </span>
                </div>
            @endif
            <p class="text-sm text-gray-500 mt-2">Remplissez le formulaire ci-dessous pour commencer votre essai gratuit
                de
                14 jours.</p>
        </div>

        @if($sent)
            <div class="flex flex-col items-center gap-4 py-8 bg-green-50 rounded-xl border border-green-100">
                <div class="rounded-full bg-green-100 p-3 text-green-600">
                    <flux:icon name="check" class="size-6" />
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-medium text-zinc-900">Demande envoyée !</h3>
                    <p class="text-zinc-500 text-sm mt-1">Nous vous contacterons sur <strong>{{ $email }}</strong> sous peu.
                    </p>
                </div>
                <div class="w-full">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="w-full mt-2" type="button">Fermer</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @else
            <form wire:submit="registerTenant" class="space-y-4">
                <flux:input wire:model="company" label="Nom de l'entreprise" placeholder="Acme Living" required />

                <div>
                    <flux:input wire:model="subdomain" label="Sous-domaine souhaité" placeholder="acme" required
                        prefix="https://" suffix=".pms.ci" />
                    <p class="text-xs text-gray-500 mt-1">L'adresse de votre espace de travail.</p>
                </div>

                <flux:input wire:model="name" label="Personne à contacter" placeholder="Jane Doe" required />
                <flux:input wire:model="email" label="Email professionnel" type="email" placeholder="jane@acme.com"
                    required />

                <div class="relative" x-data="{ showPassword: false }">
                    <flux:input wire:model="password" label="Mot de passe" type="password"
                        x-bind:type="showPassword ? 'text' : 'password'" placeholder="********" required />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 top-[2.2rem] text-gray-400 hover:text-gray-600 focus:outline-none"
                        tabindex="-1">
                        <flux:icon name="eye" x-show="!showPassword" class="size-5" />
                        <flux:icon name="eye-slash" x-show="showPassword" x-cloak class="size-5" />
                    </button>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Annuler</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Démarrer l'essai gratuit</flux:button>
                </div>
            </form>
        @endif
    </div>
</flux:modal>