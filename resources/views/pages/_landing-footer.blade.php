{{-- ═══════════════════════════════════════════════════════════════
FOOTER
═══════════════════════════════════════════════════════════════ --}}
<footer class="bg-gray-900 text-gray-300 py-16">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
            <div class="col-span-1 lg:col-span-1">
                <div class="flex items-center gap-2 mb-6">
                    <div
                        class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-500/20">
                        P</div>
                    <span class="text-2xl font-bold text-white">PMS</span>
                </div>
                <p class="text-gray-400 mb-6 leading-relaxed">
                    La solution #1 pour les gestionnaires immobiliers modernes. Simplifiez, automatisez, grandissez.
                </p>
                <div class="flex gap-4">
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition-all">
                        <flux:icon name="camera" class="w-5 h-5" />
                    </a>
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition-all">
                        <flux:icon name="paper-airplane" class="w-5 h-5" />
                    </a>
                </div>
            </div>

            <div>
                <h4 class="text-white font-bold text-lg mb-6">Produit</h4>
                <ul class="space-y-4">
                    <li><a href="#features" class="hover:text-blue-400 transition-colors">Fonctionnalités</a></li>
                    <li><a href="#pricing" class="hover:text-blue-400 transition-colors">Tarifs</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition-colors">Portail Locataire</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition-colors">Portail Propriétaire</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold text-lg mb-6">Société</h4>
                <ul class="space-y-4">
                    <li><a href="#" class="hover:text-blue-400 transition-colors">À propos</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition-colors">Carrières</a> <span
                            class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded ml-1">Recrutement</span></li>
                    <li><a href="#" class="hover:text-blue-400 transition-colors">Blog</a></li>
                    <li><a href="#" class="hover:text-blue-400 transition-colors">Contact</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold text-lg mb-6">Contact</h4>
                <ul class="space-y-4">
                    <li class="flex items-start gap-4">
                        <flux:icon name="map-pin" class="w-6 h-6 text-blue-500 mt-1" />
                        <span>123 Boulevard de l'Innovation,<br>Abidjan, Côte d'Ivoire</span>
                    </li>
                    <li class="flex items-center gap-4">
                        <flux:icon name="envelope" class="w-5 h-5 text-blue-500" />
                        <span>hello@pms.ci</span>
                    </li>
                    <li class="flex items-center gap-4">
                        <flux:icon name="phone" class="w-5 h-5 text-blue-500" />
                        <span>+225 07 07 07 07 07</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} PMS Inc. Tous droits réservés.</p>
            <div class="flex gap-8 text-sm text-gray-500">
                <a href="#" class="hover:text-white transition-colors">Confidentialité</a>
                <a href="#" class="hover:text-white transition-colors">CGU</a>
                <a href="#" class="hover:text-white transition-colors">Sécurité</a>
            </div>
        </div>
    </div>
</footer>