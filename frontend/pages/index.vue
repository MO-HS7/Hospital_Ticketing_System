<template>
  <NuxtLayout name="public">
    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
      <!-- Background Elements -->
      <div class="absolute inset-0 bg-[var(--color-bg-primary)] transition-colors duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-primary-500/5 via-transparent to-cyan-500/5 dark:from-primary-500/10 dark:to-cyan-500/10" />
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-primary-500/10 blur-[120px] rounded-full" />
      </div>

      <div class="container mx-auto px-4 relative z-10 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/50 dark:bg-slate-800/50 backdrop-blur-md border border-white/20 dark:border-slate-700 shadow-sm mb-8 animate-fade-in">
          <span class="flex h-2 w-2 rounded-full bg-green-500 animate-pulse" />
          <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $t('landing.badge') }}</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-7xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-400 tracking-tight mb-8 max-w-4xl mx-auto animate-slide-up leading-tight">
          {{ $t('landing.heroTitle') }}
        </h1>
        
        <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto mb-12 animate-slide-up animation-delay-100 leading-relaxed">
          {{ $t('landing.heroDescription') }}
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center animate-slide-up animation-delay-200">
           <NuxtLink to="/auth/register" class="btn-primary text-lg px-8 py-4 shadow-xl shadow-primary-500/20 hover:shadow-primary-500/30 hover:-translate-y-1 transition-all duration-300">
            {{ $t('landing.getStarted') }}
          </NuxtLink>
           <a href="#features" class="px-8 py-4 rounded-xl text-lg font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all duration-300">
            {{ $t('landing.learnMore') }}
          </a>
        </div>
      </div>
    </section>

    <!-- Portals Section -->
    <section class="py-20 relative z-10">
      <div class="container mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ $t('landing.portals.title') }}</h2>
          <p class="text-slate-500 dark:text-slate-400">{{ $t('landing.portals.subtitle') }}</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Patient Portal -->
          <div class="group relative p-8 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none hover:-translate-y-1 transition-all duration-300">
            <div class="absolute inset-0 bg-gradient-to-br from-primary-500/5 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity" />
            <div class="relative z-10">
              <div class="w-14 h-14 rounded-2xl bg-primary-100 dark:bg-primary-900/30 text-primary-600 flex items-center justify-center mb-6 text-2xl">
                🏥
              </div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $t('landing.portals.patient') }}</h3>
              <p class="text-slate-500 dark:text-slate-400 mb-6 min-h-[48px]">{{ $t('landing.portals.patientDesc') }}</p>
              <NuxtLink to="/auth/login" class="inline-flex items-center text-primary-600 font-medium group-hover:translate-x-1 transition-transform">
                {{ $t('landing.portals.access') }}
                <svg class="w-4 h-4 ms-2 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
              </NuxtLink>
            </div>
          </div>

          <!-- Doctor Portal -->
          <div class="group relative p-8 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none hover:-translate-y-1 transition-all duration-300">
             <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity" />
            <div class="relative z-10">
              <div class="w-14 h-14 rounded-2xl bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 flex items-center justify-center mb-6 text-2xl">
                👨‍⚕️
              </div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $t('landing.portals.doctor') }}</h3>
              <p class="text-slate-500 dark:text-slate-400 mb-6 min-h-[48px]">{{ $t('landing.portals.doctorDesc') }}</p>
              <NuxtLink to="/auth/login" class="inline-flex items-center text-cyan-600 font-medium group-hover:translate-x-1 transition-transform">
                {{ $t('landing.portals.access') }}
                <svg class="w-4 h-4 ms-2 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
              </NuxtLink>
            </div>
          </div>

          <!-- Admin Portal -->
          <div class="group relative p-8 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none hover:-translate-y-1 transition-all duration-300">
             <div class="absolute inset-0 bg-gradient-to-br from-slate-500/5 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity" />
            <div class="relative z-10">
              <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 flex items-center justify-center mb-6 text-2xl">
                ⚙️
              </div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $t('landing.portals.admin') }}</h3>
              <p class="text-slate-500 dark:text-slate-400 mb-6 min-h-[48px]">{{ $t('landing.portals.adminDesc') }}</p>
              <NuxtLink to="/auth/login" class="inline-flex items-center text-slate-600 dark:text-slate-400 font-medium group-hover:translate-x-1 transition-transform">
                {{ $t('landing.portals.access') }}
                <svg class="w-4 h-4 ms-2 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-slate-50 dark:bg-slate-800/20">
      <div class="container mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ $t('landing.featuresTitle') }}</h2>
          <p class="text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">{{ $t('landing.featuresDescription') }}</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div v-for="(feature, i) in features" :key="i" class="bg-white dark:bg-slate-800 p-8 rounded-3xl border border-slate-100 dark:border-slate-700 hover:shadow-lg transition-all duration-300">
            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center mb-6', feature.bgClass]">
              <svg class="w-7 h-7" :class="feature.iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="feature.iconPath" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">{{ feature.title }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-24">
      <div class="container mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ $t('landing.howItWorksTitle') }}</h2>
          <p class="text-slate-500 dark:text-slate-400">{{ $t('landing.howItWorksDescription') }}</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-12 max-w-5xl mx-auto relative">
          <!-- Connector Line (Desktop) -->
          <div class="hidden md:block absolute top-12 left-[16%] right-[16%] h-0.5 bg-slate-200 dark:bg-slate-700 -z-10" />

          <div v-for="(step, i) in steps" :key="i" class="text-center relative">
            <div class="w-24 h-24 mx-auto bg-white dark:bg-slate-900 rounded-full border-4 border-slate-50 dark:border-slate-800 flex items-center justify-center mb-6 relative z-10 shadow-sm">
              <span class="text-3xl font-bold text-primary-500">{{ i + 1 }}</span>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ step.title }}</h3>
            <p class="text-slate-500 dark:text-slate-400 leading-relaxed px-4">{{ step.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-24 bg-slate-50 dark:bg-slate-800/20">
      <div class="container mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ $t('landing.faqTitle') }}</h2>
        </div>
        
        <div class="max-w-2xl mx-auto space-y-4">
          <div v-for="(faq, i) in faqs" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 overflow-hidden">
            <button @click="openFaq = openFaq === i ? -1 : i" class="w-full p-6 text-start flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ faq.question }}</span>
              <svg :class="['w-5 h-5 text-slate-400 transition-transform duration-300', openFaq === i && 'rotate-180']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div 
              class="grid transition-all duration-300 ease-in-out"
              :class="openFaq === i ? 'grid-rows-[1fr] opacity-100 py-6 px-6 pt-0' : 'grid-rows-[0fr] opacity-0'"
            >
              <div class="overflow-hidden text-slate-500 dark:text-slate-400 leading-relaxed">
                {{ faq.answer }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24">
      <div class="container mx-auto px-4">
        <div class="relative overflow-hidden rounded-[2.5rem] bg-slate-900 px-6 py-16 sm:px-16 sm:py-24 text-center shadow-2xl">
          <div class="absolute inset-0 bg-gradient-to-br from-primary-600 to-purple-600 opacity-20" />
          <div class="relative z-10 max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl mb-6">
              {{ $t('landing.cta.title') }}
            </h2>
            <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-slate-300 mb-10">
              {{ $t('landing.cta.subtitle') }}
            </p>
            <div class="flex items-center justify-center gap-x-6">
              <NuxtLink to="/auth/register" class="rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-slate-900 shadow-sm hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-colors">
                {{ $t('landing.cta.button') }}
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="hidden">
      <!-- Hidden contact section for scroll anchor -->
    </section>

  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const { t } = useI18n()
const openFaq = ref(-1)

const features = computed(() => [
  {
    title: t('landing.feature1Title'),
    description: t('landing.feature1Desc'),
    bgClass: 'bg-primary-100 dark:bg-primary-900/30',
    iconClass: 'text-primary-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />'
  },
  {
    title: t('landing.feature2Title'),
    description: t('landing.feature2Desc'),
    bgClass: 'bg-cyan-100 dark:bg-cyan-900/30',
    iconClass: 'text-cyan-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />'
  },
  {
    title: t('landing.feature3Title'),
    description: t('landing.feature3Desc'),
    bgClass: 'bg-amber-100 dark:bg-amber-900/30',
    iconClass: 'text-amber-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />'
  },
  {
    title: t('landing.feature4Title'),
    description: t('landing.feature4Desc'),
    bgClass: 'bg-purple-100 dark:bg-purple-900/30',
    iconClass: 'text-purple-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'
  }
])

const steps = computed(() => [
  {
    title: t('landing.step1Title'),
    description: t('landing.step1Desc')
  },
  {
    title: t('landing.step2Title'),
    description: t('landing.step2Desc')
  },
  {
    title: t('landing.step3Title'),
    description: t('landing.step3Desc')
  }
])

const faqs = computed(() => [
  {
    question: t('landing.faq1Q'),
    answer: t('landing.faq1A')
  },
  {
    question: t('landing.faq2Q'),
    answer: t('landing.faq2A')
  },
  {
    question: t('landing.faq3Q'),
    answer: t('landing.faq3A')
  },
  {
    question: t('landing.faq4Q'),
    answer: t('landing.faq4A')
  }
])
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.8s ease-out forwards;
  opacity: 0;
}

.animate-slide-up {
  animation: slideUp 0.8s ease-out forwards;
  opacity: 0;
  transform: translateY(20px);
}

.animation-delay-100 {
  animation-delay: 0.1s;
}

.animation-delay-200 {
  animation-delay: 0.2s;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
