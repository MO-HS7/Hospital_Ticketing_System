<template>
  <footer class="bg-slate-900 border-t border-slate-800 text-slate-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
        <!-- Brand Section -->
        <div class="space-y-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-900/50">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-white tracking-tight">{{ $t('app.name') }}</span>
          </div>
          <p class="text-slate-400 text-sm leading-relaxed max-w-sm">
            {{ $t('app.description') }}
          </p>
          <div class="flex gap-4">
             <a href="#" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-primary-600 text-slate-400 hover:text-white flex items-center justify-center transition-all duration-300">
              <span class="sr-only">Twitter</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/></svg>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-primary-600 text-slate-400 hover:text-white flex items-center justify-center transition-all duration-300">
              <span class="sr-only">GitHub</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
            </a>
          </div>
        </div>

        <!-- Links Sections -->
        <div>
          <h3 class="text-white font-semibold mb-6">{{ $t('footer.quickLinks') }}</h3>
          <ul class="space-y-4">
            <li><NuxtLink to="/#features" class="hover:text-primary-400 transition-colors">{{ $t('nav.features') }}</NuxtLink></li>
            <li><NuxtLink to="/#how-it-works" class="hover:text-primary-400 transition-colors">{{ $t('nav.howItWorks') }}</NuxtLink></li>
            <li><NuxtLink to="/#faq" class="hover:text-primary-400 transition-colors">{{ $t('nav.faq') }}</NuxtLink></li>
            <li><NuxtLink to="/#contact" class="hover:text-primary-400 transition-colors">{{ $t('nav.contact') }}</NuxtLink></li>
          </ul>
        </div>

        <div>
           <h3 class="text-white font-semibold mb-6">{{ $t('footer.legal') }}</h3>
          <ul class="space-y-4">
             <li><NuxtLink to="/about" class="hover:text-primary-400 transition-colors">{{ $t('about.title') }}</NuxtLink></li>
            <li><NuxtLink to="/privacy" class="hover:text-primary-400 transition-colors">{{ $t('footer.privacy') }}</NuxtLink></li>
            <li><NuxtLink to="/terms" class="hover:text-primary-400 transition-colors">{{ $t('footer.terms') }}</NuxtLink></li>
          </ul>
        </div>

        <!-- Contact/Disclaimer -->
        <div>
          <h3 class="text-white font-semibold mb-6">{{ $t('about.contact') }}</h3>
          <ul class="space-y-4 text-sm">
            <li class="flex items-start gap-3">
              <svg class="w-5 h-5 text-primary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span>support@hospital.com</span>
            </li>
             <li class="flex items-start gap-3">
              <svg class="w-5 h-5 text-primary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              <span>+966 11 234 5678</span>
            </li>
            <li class="pt-4 text-xs text-slate-500">
               {{ $t('chatbot.disclaimer') }}
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-16 pt-8 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4">
        <p class="text-sm text-slate-500">
          © {{ new Date().getFullYear() }} {{ $t('app.name') }}. {{ $t('about.rights') }}
        </p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
</script>
