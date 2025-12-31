<template>
  <NuxtLayout name="public">
    <!-- Hero Section -->
    <section class="relative py-20 lg:py-32 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-primary-50 via-white to-cyan-50 dark:from-slate-900 dark:via-slate-900 dark:to-primary-950" />
      <div class="absolute inset-0 bg-[url('/grid.svg')] opacity-10" />
      
      <div class="container mx-auto px-4 relative">
        <div class="max-w-3xl mx-auto text-center">
          <div class="inline-block animate-fade-in">
            <span class="px-4 py-2 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-medium">
              {{ $t('landing.badge') || '🏥 Smart Healthcare Platform' }}
            </span>
          </div>
          
          <h1 class="mt-6 text-4xl md:text-5xl lg:text-6xl font-bold text-[var(--color-text-primary)] leading-tight animate-slide-up">
            {{ $t('landing.heroTitle') || 'Modern Hospital Ticketing & Booking System' }}
          </h1>
          
          <p class="mt-6 text-lg text-[var(--color-text-secondary)] max-w-2xl mx-auto animate-slide-up" style="animation-delay: 0.1s">
            {{ $t('landing.heroDescription') || 'Streamline your hospital visits with AI-powered symptom guidance, easy appointment booking, and real-time ticket tracking.' }}
          </p>
          
          <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center animate-slide-up" style="animation-delay: 0.2s">
            <NuxtLink to="/auth/login" class="btn-primary text-lg px-8 py-3">
              {{ $t('auth.login') }}
            </NuxtLink>
            <NuxtLink to="/auth/register" class="btn-secondary text-lg px-8 py-3">
              {{ $t('landing.createAccount') || 'Create Patient Account' }}
            </NuxtLink>
          </div>
          
          <p class="mt-4 text-sm text-[var(--color-text-muted)]">
            {{ $t('landing.staffNote') || 'Staff accounts are managed by administration' }}
          </p>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-[var(--color-bg-secondary)]">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $t('landing.featuresTitle') || 'Powerful Features' }}</h2>
          <p class="mt-4 text-[var(--color-text-secondary)] max-w-2xl mx-auto">{{ $t('landing.featuresDescription') || 'Everything you need to manage hospital visits efficiently' }}</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="(feature, i) in features" :key="i" class="card p-6 text-center hover:shadow-lg transition-shadow">
            <div :class="['w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4', feature.bgClass]">
              <svg class="w-7 h-7" :class="feature.iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="feature.iconPath" />
            </div>
            <h3 class="font-semibold text-[var(--color-text-primary)] mb-2">{{ feature.title }}</h3>
            <p class="text-sm text-[var(--color-text-muted)]">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $t('landing.howItWorksTitle') || 'How It Works' }}</h2>
          <p class="mt-4 text-[var(--color-text-secondary)]">{{ $t('landing.howItWorksDescription') || 'Get started in just 3 simple steps' }}</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
          <div v-for="(step, i) in steps" :key="i" class="text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mb-4">
              <span class="text-2xl font-bold text-primary-600">{{ i + 1 }}</span>
            </div>
            <h3 class="font-semibold text-[var(--color-text-primary)] mb-2">{{ step.title }}</h3>
            <p class="text-sm text-[var(--color-text-muted)]">{{ step.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 bg-[var(--color-bg-secondary)]">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $t('landing.faqTitle') || 'Frequently Asked Questions' }}</h2>
        </div>
        
        <div class="max-w-2xl mx-auto space-y-4">
          <div v-for="(faq, i) in faqs" :key="i" class="card overflow-hidden">
            <button @click="openFaq = openFaq === i ? -1 : i" class="w-full p-4 text-start flex items-center justify-between">
              <span class="font-medium text-[var(--color-text-primary)]">{{ faq.question }}</span>
              <svg :class="['w-5 h-5 text-[var(--color-text-muted)] transition-transform', openFaq === i && 'rotate-180']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-40"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 max-h-40"
              leave-to-class="opacity-0 max-h-0"
            >
              <div v-if="openFaq === i" class="px-4 pb-4 text-sm text-[var(--color-text-secondary)]">
                {{ faq.answer }}
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20">
      <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
          <h2 class="text-3xl font-bold text-[var(--color-text-primary)] mb-4">{{ $t('landing.contactTitle') || 'Need Help?' }}</h2>
          <p class="text-[var(--color-text-secondary)] mb-8">{{ $t('landing.contactDescription') || 'Our support team is here to assist you' }}</p>
          
          <div class="grid sm:grid-cols-3 gap-6">
            <a href="https://wa.me/966500000000" target="_blank" class="card p-6 hover:shadow-lg transition-shadow group">
              <div class="w-12 h-12 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
              </div>
              <h3 class="font-medium text-[var(--color-text-primary)]">WhatsApp</h3>
              <p class="text-sm text-[var(--color-text-muted)]">+966 50 000 0000</p>
            </a>
            
            <a href="mailto:support@hospital.com" class="card p-6 hover:shadow-lg transition-shadow group">
              <div class="w-12 h-12 mx-auto rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <h3 class="font-medium text-[var(--color-text-primary)]">{{ $t('about.contact') }}</h3>
              <p class="text-sm text-[var(--color-text-muted)]">support@hospital.com</p>
            </a>
            
            <a href="tel:+966112345678" class="card p-6 hover:shadow-lg transition-shadow group">
              <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
              </div>
              <h3 class="font-medium text-[var(--color-text-primary)]">{{ $t('landing.phone') || 'Phone' }}</h3>
              <p class="text-sm text-[var(--color-text-muted)]">+966 11 234 5678</p>
            </a>
          </div>
        </div>
      </div>
    </section>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const { t } = useI18n()
const openFaq = ref(-1)

const features = computed(() => [
  {
    title: t('landing.feature1Title') || 'Smart Booking',
    description: t('landing.feature1Desc') || 'Book appointments with any department in just a few clicks',
    bgClass: 'bg-primary-100 dark:bg-primary-900/30',
    iconClass: 'text-primary-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />'
  },
  {
    title: t('landing.feature2Title') || 'AI Symptom Guide',
    description: t('landing.feature2Desc') || 'Get AI-powered suggestions for the right department',
    bgClass: 'bg-cyan-100 dark:bg-cyan-900/30',
    iconClass: 'text-cyan-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />'
  },
  {
    title: t('landing.feature3Title') || 'Real-time Tracking',
    description: t('landing.feature3Desc') || 'Track your ticket status and get notifications',
    bgClass: 'bg-amber-100 dark:bg-amber-900/30',
    iconClass: 'text-amber-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />'
  },
  {
    title: t('landing.feature4Title') || 'Admin Dashboard',
    description: t('landing.feature4Desc') || 'Comprehensive reporting and analytics for staff',
    bgClass: 'bg-purple-100 dark:bg-purple-900/30',
    iconClass: 'text-purple-600',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />'
  }
])

const steps = computed(() => [
  {
    title: t('landing.step1Title') || 'Create Account',
    description: t('landing.step1Desc') || 'Sign up with your email and basic information'
  },
  {
    title: t('landing.step2Title') || 'Book Appointment',
    description: t('landing.step2Desc') || 'Use our chatbot or manual booking to schedule'
  },
  {
    title: t('landing.step3Title') || 'Visit Hospital',
    description: t('landing.step3Desc') || 'Show your ticket at reception and get treated'
  }
])

const faqs = computed(() => [
  {
    question: t('landing.faq1Q') || 'How do I create a patient account?',
    answer: t('landing.faq1A') || 'Click "Create Patient Account" and fill in your details. You\'ll receive a confirmation email to verify your account.'
  },
  {
    question: t('landing.faq2Q') || 'Can staff members register on their own?',
    answer: t('landing.faq2A') || 'No, staff accounts are created by hospital administration. If you\'re a new staff member, contact your HR department.'
  },
  {
    question: t('landing.faq3Q') || 'How does the AI symptom guide work?',
    answer: t('landing.faq3A') || 'Our chatbot asks about your symptoms and suggests the most appropriate department. Note: This is guidance only, not medical diagnosis.'
  },
  {
    question: t('landing.faq4Q') || 'Can I cancel or reschedule my appointment?',
    answer: t('landing.faq4A') || 'Yes, you can manage your appointments from your dashboard up to 24 hours before the scheduled time.'
  }
])
</script>
