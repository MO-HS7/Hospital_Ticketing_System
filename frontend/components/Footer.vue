<template>
  <footer class="bg-slate-900 dark:bg-slate-950 text-white">
    <div class="container mx-auto px-4 py-12">
      <div class="grid md:grid-cols-4 gap-8">
        <!-- Brand -->
        <div class="md:col-span-2">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <span class="font-bold text-lg">{{ $t('app.name') }}</span>
          </div>
          <p class="text-slate-400 text-sm max-w-md mb-4">
            {{ $t('app.description') }}
          </p>
          <!-- Social Links -->
          <div class="flex gap-3">
            <a href="https://wa.me/966500000000" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-green-600 flex items-center justify-center transition-colors" aria-label="WhatsApp">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
              </svg>
            </a>
            <a href="https://instagram.com/hospital" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 flex items-center justify-center transition-all" aria-label="Instagram">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
              </svg>
            </a>
            <a href="https://twitter.com/hospital" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-blue-500 flex items-center justify-center transition-colors" aria-label="Twitter">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
            </a>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="font-semibold mb-4">{{ $t('footer.quickLinks') || 'Quick Links' }}</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/#features" class="text-slate-400 hover:text-white transition-colors">{{ $t('nav.features') || 'Features' }}</NuxtLink></li>
            <li><NuxtLink to="/#how-it-works" class="text-slate-400 hover:text-white transition-colors">{{ $t('nav.howItWorks') || 'How It Works' }}</NuxtLink></li>
            <li><NuxtLink to="/#faq" class="text-slate-400 hover:text-white transition-colors">{{ $t('nav.faq') || 'FAQ' }}</NuxtLink></li>
            <li><NuxtLink to="/auth/login" class="text-slate-400 hover:text-white transition-colors">{{ $t('auth.login') }}</NuxtLink></li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <h4 class="font-semibold mb-4">{{ $t('footer.legal') || 'Legal' }}</h4>
          <ul class="space-y-2 text-sm">
            <li><NuxtLink to="/about" class="text-slate-400 hover:text-white transition-colors">{{ $t('about.title') }}</NuxtLink></li>
            <li><NuxtLink to="/privacy" class="text-slate-400 hover:text-white transition-colors">{{ $t('footer.privacy') || 'Privacy Policy' }}</NuxtLink></li>
            <li><NuxtLink to="/terms" class="text-slate-400 hover:text-white transition-colors">{{ $t('footer.terms') || 'Terms of Service' }}</NuxtLink></li>
            <li><NuxtLink to="/#contact" class="text-slate-400 hover:text-white transition-colors">{{ $t('nav.contact') || 'Contact' }}</NuxtLink></li>
          </ul>
        </div>
      </div>

      <!-- Disclaimer -->
      <div class="mt-8 pt-8 border-t border-slate-800">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <p class="text-slate-500 text-sm">
            © {{ new Date().getFullYear() }} {{ $t('app.name') }}. {{ $t('about.rights') }}
          </p>
          <p class="text-slate-600 text-xs max-w-md text-center md:text-end">
            {{ $t('chatbot.disclaimer') }}
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
</script>
