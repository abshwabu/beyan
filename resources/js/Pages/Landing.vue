<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    content: {
        type: Object,
        default: () => ({}),
    },
    locale: {
        type: String,
        default: 'en',
    },
    direction: {
        type: String,
        default: 'ltr',
    },
    supportedLocales: {
        type: Array,
        default: () => [
            { code: 'en', name: 'English', native: 'English', dir: 'ltr' },
            { code: 'am', name: 'Amharic', native: 'አማርኛ', dir: 'ltr' },
            { code: 'om', name: 'Afaan Oromoo', native: 'Afaan Oromoo', dir: 'ltr' },
            { code: 'ar', name: 'Arabic', native: 'العربية', dir: 'rtl' },
        ],
    },
});

const isRtl = computed(() => props.direction === 'rtl' || props.locale === 'ar');

// Mobile navigation menu toggle
const mobileMenuOpen = ref(false);
const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

// Billing interval toggle (monthly vs yearly)
const billingCycle = ref('monthly'); // 'monthly' | 'yearly'

// FAQ accordion state
const openFaqIndices = ref([0]); // Default first question open
const toggleFaq = (index) => {
    if (openFaqIndices.value.includes(index)) {
        openFaqIndices.value = openFaqIndices.value.filter((i) => i !== index);
    } else {
        openFaqIndices.value.push(index);
    }
};

// Language switcher dropdown
const langDropdownOpen = ref(false);
const switchLocale = (code) => {
    langDropdownOpen.value = false;
    mobileMenuOpen.value = false;
    router.visit(`/locale/${code}`, {
        preserveScroll: true,
    });
};

// Demo request form
const form = useForm({
    name: '',
    madrasa_name: '',
    phone: '',
    email: '',
    mode: 'physical',
    student_count_range: '50_150',
    message: '',
    hp_website: '', // Honeypot field
});

const submitDemo = () => {
    form.post('/demo-request', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

// JSON-LD Structured Data
const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: 'Beyan Madrasa Management System',
    applicationCategory: 'EducationalApplication',
    operatingSystem: 'Web, Mobile (iOS, Android)',
    offers: {
        '@type': 'AggregateOffer',
        priceCurrency: 'ETB',
        lowPrice: '600',
        highPrice: '2800',
        offerCount: props.plans?.length || 3,
    },
    description: props.content?.meta?.description || 'Complete madrasa management software for physical, online, and hybrid Islamic institutes.',
    aggregateRating: {
        '@type': 'AggregateRating',
        ratingValue: '4.9',
        ratingCount: '48',
    },
}));
</script>

<template>
    <Head>
        <title>{{ content.meta?.title || 'Beyan - Complete Madrasa Management SaaS' }}</title>
        <meta name="description" :content="content.meta?.description" />
        <meta name="keywords" :content="content.meta?.keywords" />
        <meta property="og:title" :content="content.meta?.title" />
        <meta property="og:description" :content="content.meta?.description" />
        <meta property="og:type" content="website" />
        <meta property="og:locale" :content="locale" />
        <meta name="twitter:card" content="summary_large_image" />
        <script type="application/ld+json" v-text="JSON.stringify(jsonLd)"></script>
    </Head>

    <div :dir="direction" class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-emerald-500 selection:text-white">

        <!-- 1. HEADER SECTION -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80 transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <Link href="/" class="flex items-center gap-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-600 rounded-lg p-1">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-800 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-emerald-700/20">
                                <span>ب</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xl font-extrabold tracking-tight text-slate-900 leading-none">Beyan</span>
                                <span class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider mt-0.5">Madrasa OS</span>
                            </div>
                        </Link>
                    </div>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden md:flex items-center gap-6 lg:gap-8 text-sm font-medium text-slate-600">
                        <a href="#features" class="hover:text-emerald-700 transition-colors">{{ content.header?.features || 'Features' }}</a>
                        <a href="#modes" class="hover:text-emerald-700 transition-colors">{{ content.header?.modes || 'Modes' }}</a>
                        <a href="#how-it-works" class="hover:text-emerald-700 transition-colors">{{ content.header?.how_it_works || 'How It Works' }}</a>
                        <a href="#pricing" class="hover:text-emerald-700 transition-colors">{{ content.header?.pricing || 'Pricing' }}</a>
                        <a href="#faq" class="hover:text-emerald-700 transition-colors">{{ content.header?.faq || 'FAQ' }}</a>
                        <a href="#demo" class="hover:text-emerald-700 transition-colors">{{ content.header?.demo || 'Request Demo' }}</a>
                    </nav>

                    <!-- Desktop Actions & Language Switcher -->
                    <div class="hidden md:flex items-center gap-3">
                        <!-- Language selector -->
                        <div class="relative">
                            <button
                                @click="langDropdownOpen = !langDropdownOpen"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                aria-haspopup="true"
                                :aria-expanded="langDropdownOpen"
                            >
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                </svg>
                                <span>{{ supportedLocales.find(l => l.code === locale)?.native || locale.toUpperCase() }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                v-show="langDropdownOpen"
                                class="absolute end-0 mt-2 w-40 bg-white rounded-xl shadow-lg border border-slate-100 py-1.5 z-50 text-start"
                            >
                                <button
                                    v-for="l in supportedLocales"
                                    :key="l.code"
                                    @click="switchLocale(l.code)"
                                    type="button"
                                    class="w-full text-start px-4 py-2 text-xs font-medium hover:bg-emerald-50 hover:text-emerald-700 flex items-center justify-between"
                                    :class="locale === l.code ? 'text-emerald-700 font-bold bg-emerald-50/50' : 'text-slate-700'"
                                >
                                    <span>{{ l.native }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-mono">{{ l.code }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Login link -->
                        <Link
                            href="/login"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 hover:text-emerald-700 rounded-lg hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-600"
                        >
                            {{ content.header?.login || 'Log in' }}
                        </Link>

                        <!-- Free Trial CTA -->
                        <Link
                            href="/register-institution"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl shadow-sm hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                        >
                            {{ content.header?.start_trial || 'Start Free Trial' }}
                        </Link>
                    </div>

                    <!-- Mobile Hamburger Button -->
                    <div class="flex items-center gap-2 md:hidden">
                        <button
                            @click="toggleMobileMenu"
                            type="button"
                            class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                            :aria-label="mobileMenuOpen ? 'Close navigation menu' : 'Open navigation menu'"
                        >
                            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Mobile Dropdown Menu -->
            <div v-show="mobileMenuOpen" class="md:hidden border-b border-slate-200 bg-white px-4 pt-2 pb-6 space-y-4 shadow-xl">
                <nav class="flex flex-col space-y-2 text-base font-medium text-slate-700">
                    <a @click="mobileMenuOpen = false" href="#features" class="px-3 py-2 rounded-lg hover:bg-slate-100">{{ content.header?.features || 'Features' }}</a>
                    <a @click="mobileMenuOpen = false" href="#modes" class="px-3 py-2 rounded-lg hover:bg-slate-100">{{ content.header?.modes || 'Modes' }}</a>
                    <a @click="mobileMenuOpen = false" href="#how-it-works" class="px-3 py-2 rounded-lg hover:bg-slate-100">{{ content.header?.how_it_works || 'How It Works' }}</a>
                    <a @click="mobileMenuOpen = false" href="#pricing" class="px-3 py-2 rounded-lg hover:bg-slate-100">{{ content.header?.pricing || 'Pricing' }}</a>
                    <a @click="mobileMenuOpen = false" href="#faq" class="px-3 py-2 rounded-lg hover:bg-slate-100">{{ content.header?.faq || 'FAQ' }}</a>
                    <a @click="mobileMenuOpen = false" href="#demo" class="px-3 py-2 rounded-lg hover:bg-slate-100">{{ content.header?.demo || 'Request Demo' }}</a>
                </nav>

                <div class="pt-4 border-t border-slate-100 flex flex-col gap-3">
                    <div class="flex items-center justify-between px-3 py-2 bg-slate-50 rounded-lg">
                        <span class="text-xs font-semibold text-slate-500 uppercase">Language / ቋንቋ</span>
                        <div class="flex gap-1">
                            <button
                                v-for="l in supportedLocales"
                                :key="l.code"
                                @click="switchLocale(l.code)"
                                type="button"
                                class="px-2 py-1 text-xs rounded font-medium"
                                :class="locale === l.code ? 'bg-emerald-700 text-white font-bold' : 'text-slate-600 hover:bg-slate-200'"
                            >
                                {{ l.native }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <Link
                            href="/login"
                            class="w-full text-center py-2.5 px-4 text-sm font-semibold text-slate-700 border border-slate-300 rounded-xl hover:bg-slate-50"
                        >
                            {{ content.header?.login || 'Log in' }}
                        </Link>
                        <Link
                            href="/register-institution"
                            class="w-full text-center py-2.5 px-4 text-sm font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl shadow-sm"
                        >
                            {{ content.header?.start_trial || 'Free Trial' }}
                        </Link>
                    </div>
                </div>
            </div>
        </header>


        <!-- 2. HERO SECTION -->
        <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-32 bg-gradient-to-b from-white via-emerald-50/30 to-slate-50">
            <!-- Subtle Islamic Geometric Pattern Overlay (SVG pure CSS) -->
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[radial-gradient(#047857_1px,transparent_1px)] [background-size:24px_24px]"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="text-center max-w-4xl mx-auto">
                    
                    <!-- Region / Purpose Badge -->
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-100/80 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold mb-6 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                        <span>{{ content.hero?.badge || 'Built for Islamic institutes across Ethiopia & East Africa' }}</span>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-[1.15] mb-6">
                        {{ content.hero?.headline || 'One platform. Complete madrasa management.' }}
                    </h1>

                    <!-- Subheadline -->
                    <p class="text-lg sm:text-xl text-slate-600 max-w-3xl mx-auto leading-relaxed mb-10">
                        {{ content.hero?.subheadline || 'Streamline student admissions, track Qur\'an and Hifz memorization with precision, record attendance in one tap, and manage tuition fees — engineered to work reliably on mobile networks.' }}
                    </p>

                    <!-- CTAs -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-6">
                        <Link
                            href="/register-institution"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl shadow-lg shadow-emerald-700/25 transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-emerald-600/30"
                        >
                            <span>{{ content.hero?.primary_cta || 'Start Free Trial' }}</span>
                            <svg class="w-5 h-5 ms-2" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <a
                            href="#demo"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-4 text-base font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-emerald-600"
                        >
                            {{ content.hero?.secondary_cta || 'Schedule a Live Demo' }}
                        </a>
                    </div>

                    <p class="text-xs text-slate-500 font-medium">
                        {{ content.hero?.trial_note || '14 days free trial · No credit card required · Setup in 2 minutes' }}
                    </p>
                </div>

                <!-- Product Illustrated UI Mockup (No images of people/animals; pure clean vector UI) -->
                <div class="mt-14 max-w-5xl mx-auto">
                    <div class="relative rounded-2xl bg-slate-900/5 p-2 sm:p-4 ring-1 ring-slate-900/10 shadow-2xl">
                        <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-inner">
                            
                            <!-- Browser Chrome Bar -->
                            <div class="bg-slate-100 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                    <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                    <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                                </div>
                                <div class="px-4 py-1 rounded-md bg-white border border-slate-200 text-xs font-mono text-slate-500 w-64 text-center truncate">
                                    https://beyan.app/dashboard
                                </div>
                                <div class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    Beyan Academy (Addis Ababa)
                                </div>
                            </div>

                            <!-- Mock Dashboard Canvas -->
                            <div class="p-4 sm:p-6 lg:p-8 bg-slate-50">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
                                    
                                    <!-- Card 1: Today's Attendance -->
                                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Attendance</span>
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200">
                                                    {{ content.hero?.mockup_attendance_badge || '96% Present' }}
                                                </span>
                                            </div>
                                            <h4 class="font-bold text-slate-900 text-base mb-1">
                                                {{ content.hero?.mockup_today_title || 'Today\'s Morning Halaqah' }}
                                            </h4>
                                            <p class="text-xs text-slate-500">Class: Tajweed & Memorization (Room 3)</p>
                                        </div>

                                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-1.5 text-emerald-700 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                                <span>24 Present</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-amber-600 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                <span>1 Late</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-slate-400 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                                <span>0 Absent</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card 2: Hifz Progress -->
                                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Hifz Tracking</span>
                                                <span class="px-2 py-0.5 bg-teal-50 text-teal-800 text-xs font-bold rounded-full border border-teal-200">
                                                    Sabaq Tier
                                                </span>
                                            </div>
                                            <h4 class="font-bold text-slate-900 text-base mb-1">
                                                {{ content.hero?.mockup_hifz_title || 'Sabaq Progress: Surah Al-Baqarah' }}
                                            </h4>
                                            <p class="text-xs text-slate-500">{{ content.hero?.mockup_ayah_badge || 'Ayah 142 - 152' }}</p>
                                        </div>

                                        <div class="mt-4 pt-3 border-t border-slate-100">
                                            <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                                                <span>Juz 2 Coverage</span>
                                                <span class="font-bold text-slate-800">10 / 10 Ayahs</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-teal-600 h-2 rounded-full" style="width: 85%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card 3: Finance / Ledger -->
                                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tuition Fees</span>
                                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-bold rounded-full border border-blue-200">
                                                    CBE / Cash
                                                </span>
                                            </div>
                                            <h4 class="font-bold text-slate-900 text-base mb-1">
                                                {{ content.hero?.mockup_fee_title || 'Tuition Ledger (CBE / Cash)' }}
                                            </h4>
                                            <p class="text-xs text-slate-500">Tikimt 2018 E.C. Collection Period</p>
                                        </div>

                                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                            <span class="text-xs font-semibold text-slate-600">Total Settled</span>
                                            <span class="text-sm font-extrabold text-emerald-700">
                                                {{ content.hero?.mockup_fee_collected || '48,500 ETB collected' }}
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </section>


        <!-- 3. PROBLEM & SOLUTION SECTION -->
        <section id="problems" class="py-20 bg-white border-y border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">
                        {{ content.problems?.title || 'Tired of Paper Registers and Lost Records?' }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600">
                        {{ content.problems?.subtitle || 'Traditional madrasa administration steals precious time from teaching the Book of Allah. Beyan fixes the core bottlenecks.' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="(item, idx) in (content.problems?.items || [])"
                        :key="idx"
                        class="bg-slate-50 rounded-2xl p-6 sm:p-8 border border-slate-200 flex flex-col justify-between hover:border-emerald-500 transition-all hover:shadow-md"
                    >
                        <div>
                            <!-- Challenge Box -->
                            <div class="flex items-center gap-3 mb-4">
                                <span class="text-2xl">{{ item.icon || '⚠️' }}</span>
                                <h3 class="text-lg font-bold text-slate-900">{{ item.title }}</h3>
                            </div>
                            <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                                {{ item.description }}
                            </p>
                        </div>

                        <!-- Solution Box -->
                        <div class="pt-4 border-t border-slate-200/80 bg-emerald-50/50 -mx-6 -mb-6 p-6 rounded-b-2xl border-t border-emerald-100">
                            <div class="text-xs font-bold text-emerald-800 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>The Beyan Solution</span>
                            </div>
                            <p class="text-xs text-emerald-950 font-medium leading-relaxed">
                                {{ item.solution }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </section>


        <!-- 4. CORE FEATURES SECTION -->
        <section id="features" class="py-20 lg:py-28 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest bg-emerald-100/70 px-3 py-1 rounded-full border border-emerald-200">
                        Modular Architecture
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3 mb-4">
                        {{ content.features?.title || 'Everything Your Madrasa Needs, In One Simple Place' }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600">
                        {{ content.features?.subtitle || 'Every tool was designed hand-in-hand with madrasa ustadhs and administrators in Addis Ababa.' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    
                    <!-- Attendance -->
                    <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xl mb-5">
                            ✓
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">
                            {{ content.features?.items?.attendance?.title || 'Smart Session Attendance' }}
                        </h3>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                            {{ content.features?.items?.attendance?.description }}
                        </p>
                        <ul class="space-y-2 text-xs font-medium text-slate-700">
                            <li v-for="(b, i) in (content.features?.items?.attendance?.bullets || [])" :key="i" class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span>{{ b }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Hifz Progress -->
                    <div class="bg-white rounded-2xl p-7 border border-emerald-300 shadow-sm hover:shadow-md transition-shadow relative">
                        <span class="absolute top-4 end-4 text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Core Specialized
                        </span>
                        <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xl mb-5">
                            📖
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">
                            {{ content.features?.items?.hifz?.title || 'Authentic Qur\'an & Hifz Tracking' }}
                        </h3>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                            {{ content.features?.items?.hifz?.description }}
                        </p>
                        <ul class="space-y-2 text-xs font-medium text-slate-700">
                            <li v-for="(b, i) in (content.features?.items?.hifz?.bullets || [])" :key="i" class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span>{{ b }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Students & Guardians -->
                    <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xl mb-5">
                            👥
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">
                            {{ content.features?.items?.students?.title || 'Students & Guardians Directory' }}
                        </h3>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                            {{ content.features?.items?.students?.description }}
                        </p>
                        <ul class="space-y-2 text-xs font-medium text-slate-700">
                            <li v-for="(b, i) in (content.features?.items?.students?.bullets || [])" :key="i" class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span>{{ b }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Tuition Fees & Bank -->
                    <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xl mb-5">
                            💵
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">
                            {{ content.features?.items?.finance?.title || 'Tuition Fees & Bank Reconciliation' }}
                        </h3>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                            {{ content.features?.items?.finance?.description }}
                        </p>
                        <ul class="space-y-2 text-xs font-medium text-slate-700">
                            <li v-for="(b, i) in (content.features?.items?.finance?.bullets || [])" :key="i" class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span>{{ b }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Classes & Timetable -->
                    <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold text-xl mb-5">
                            📅
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">
                            {{ content.features?.items?.timetables?.title || 'Classes & Conflict-Free Timetables' }}
                        </h3>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                            {{ content.features?.items?.timetables?.description }}
                        </p>
                        <ul class="space-y-2 text-xs font-medium text-slate-700">
                            <li v-for="(b, i) in (content.features?.items?.timetables?.bullets || [])" :key="i" class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span>{{ b }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Reports & Exports -->
                    <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xl mb-5">
                            📊
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">
                            {{ content.features?.items?.reports?.title || 'Executive Reports & Data Export' }}
                        </h3>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                            {{ content.features?.items?.reports?.description }}
                        </p>
                        <ul class="space-y-2 text-xs font-medium text-slate-700">
                            <li v-for="(b, i) in (content.features?.items?.reports?.bullets || [])" :key="i" class="flex items-center gap-2">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span>{{ b }}</span>
                            </li>
                        </ul>
                    </div>

                </div>

            </div>
        </section>


        <!-- 5. CAMPUSES & MODES SECTION -->
        <section id="modes" class="py-20 bg-white border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">
                        {{ content.modes?.title || 'Physical, Online, or Hybrid — Handled Seamlessly' }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600">
                        {{ content.modes?.subtitle || 'Whether you gather in a neighborhood masjid, teach diaspora students online across timezones, or run both simultaneously.' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Physical -->
                    <div class="rounded-2xl bg-slate-50 p-8 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 mb-4">
                                {{ content.modes?.physical?.badge || 'Campus / Masjid' }}
                            </span>
                            <h3 class="text-2xl font-bold text-slate-900 mb-3">
                                {{ content.modes?.physical?.title || 'Physical Madrasas' }}
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                {{ content.modes?.physical?.description }}
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-200 text-xs font-semibold text-emerald-700">
                            ✓ Mobile 3G/EDGE low-bandwidth mode
                        </div>
                    </div>

                    <!-- Online -->
                    <div class="rounded-2xl bg-slate-50 p-8 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 mb-4">
                                {{ content.modes?.online?.badge || 'Virtual Halaqah' }}
                            </span>
                            <h3 class="text-2xl font-bold text-slate-900 mb-3">
                                {{ content.modes?.online?.title || 'Online Academies' }}
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                {{ content.modes?.online?.description }}
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-200 text-xs font-semibold text-teal-700">
                            ✓ Global timezone viewer conversion
                        </div>
                    </div>

                    <!-- Hybrid -->
                    <div class="rounded-2xl bg-slate-50 p-8 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 mb-4">
                                {{ content.modes?.hybrid?.badge || 'Combined Model' }}
                            </span>
                            <h3 class="text-2xl font-bold text-slate-900 mb-3">
                                {{ content.modes?.hybrid?.title || 'Hybrid Institutes' }}
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                {{ content.modes?.hybrid?.description }}
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-200 text-xs font-semibold text-indigo-700">
                            ✓ Unified finance & consolidated reporting
                        </div>
                    </div>
                </div>

            </div>
        </section>


        <!-- 6. HOW IT WORKS SECTION -->
        <section id="how-it-works" class="py-20 lg:py-28 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">
                        {{ content.how_it_works?.title || 'Get Started in Three Simple Steps' }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600">
                        {{ content.how_it_works?.subtitle || 'No complex IT setup. If your teachers know how to use Telegram or WhatsApp, they will master Beyan in 10 minutes.' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                    <!-- Step 1 -->
                    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm relative z-10">
                        <div class="w-10 h-10 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-lg mb-6">
                            1
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">
                            {{ content.how_it_works?.step_1_title || '1. Register Your Madrasa' }}
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            {{ content.how_it_works?.step_1_desc }}
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm relative z-10">
                        <div class="w-10 h-10 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-lg mb-6">
                            2
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">
                            {{ content.how_it_works?.step_2_title || '2. Add Students & Teachers' }}
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            {{ content.how_it_works?.step_2_desc }}
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm relative z-10">
                        <div class="w-10 h-10 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-lg mb-6">
                            3
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">
                            {{ content.how_it_works?.step_3_title || '3. Start Recording Instantly' }}
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            {{ content.how_it_works?.step_3_desc }}
                        </p>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <Link
                        href="/register-institution"
                        class="inline-flex items-center px-6 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm shadow-md transition-all"
                    >
                        <span>{{ content.header?.start_trial || 'Start Your 14-Day Free Trial' }}</span>
                        <svg class="w-4 h-4 ms-2" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </Link>
                </div>

            </div>
        </section>


        <!-- 7. PRICING SECTION -->
        <section id="pricing" class="py-20 lg:py-28 bg-white border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">
                        {{ content.pricing?.title || 'Transparent, Fair Pricing for Every Madrasa' }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600 mb-8">
                        {{ content.pricing?.subtitle || 'Start with a 14-day free trial. Upgrade or cancel anytime. All prices in Ethiopian Birr (ETB).' }}
                    </p>

                    <!-- Monthly / Yearly Billing Toggle -->
                    <div class="inline-flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200">
                        <button
                            @click="billingCycle = 'monthly'"
                            type="button"
                            class="px-5 py-2 text-xs sm:text-sm font-bold rounded-lg transition-all"
                            :class="billingCycle === 'monthly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            {{ content.pricing?.monthly || 'Monthly Billing' }}
                        </button>
                        <button
                            @click="billingCycle = 'yearly'"
                            type="button"
                            class="px-5 py-2 text-xs sm:text-sm font-bold rounded-lg transition-all flex items-center gap-1.5"
                            :class="billingCycle === 'yearly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>{{ content.pricing?.yearly || 'Annual Billing' }}</span>
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-1.5 py-0.5 rounded-full">
                                {{ content.pricing?.save_pill || 'Save up to 17%' }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Plan Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch max-w-6xl mx-auto mb-12">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="bg-white rounded-2xl p-8 border flex flex-col justify-between transition-all relative"
                        :class="plan.badge ? 'border-2 border-emerald-600 shadow-xl ring-4 ring-emerald-500/10' : 'border-slate-200 shadow-sm hover:shadow-md'"
                    >
                        <!-- Badge -->
                        <div v-if="plan.badge" class="absolute -top-3.5 start-1/2 -translate-x-1/2 bg-emerald-600 text-white text-[11px] font-extrabold uppercase px-3 py-1 rounded-full shadow-sm tracking-wider">
                            {{ plan.badge }}
                        </div>

                        <div>
                            <div class="mb-6">
                                <h3 class="text-2xl font-black text-slate-900 mb-1">{{ plan.name }}</h3>
                                <p class="text-xs text-slate-500 font-medium">
                                    {{ plan.student_limit ? `Up to ${plan.student_limit} active students` : 'Unlimited active students' }}
                                </p>
                            </div>

                            <!-- Price Display -->
                            <div class="mb-6 pb-6 border-b border-slate-100">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-black text-slate-900">
                                        {{ (billingCycle === 'yearly' ? plan.yearly_price_major / 12 : plan.monthly_price_major).toLocaleString() }}
                                    </span>
                                    <span class="text-sm font-bold text-slate-500">
                                        {{ plan.currency }} / mo
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 mt-1">
                                    {{ billingCycle === 'yearly' ? `Billed annually (${plan.yearly_price_major.toLocaleString()} ${plan.currency}/yr)` : 'Billed month-to-month' }}
                                </div>
                            </div>

                            <!-- Feature List -->
                            <ul class="space-y-3 mb-8 text-xs font-medium text-slate-700">
                                <li v-for="(feat, fi) in plan.features" :key="fi" class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- CTA -->
                        <Link
                            :href="`/register-institution?plan=${plan.slug}`"
                            class="w-full text-center py-3.5 px-6 rounded-xl font-bold text-sm transition-all focus:outline-none focus:ring-2 focus:ring-emerald-600"
                            :class="plan.badge ? 'bg-emerald-700 text-white hover:bg-emerald-800 shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200'"
                        >
                            {{ content.pricing?.select_plan || 'Start 14-Day Free Trial' }}
                        </Link>
                    </div>
                </div>

                <!-- Custom / Multi-Branch Enterprise Card -->
                <div class="max-w-4xl mx-auto rounded-2xl bg-gradient-to-r from-slate-900 to-emerald-950 p-8 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-bold border border-emerald-500/30 uppercase tracking-wider">
                            Multi-Campus Network
                        </span>
                        <h3 class="text-2xl font-bold mt-2 mb-2">
                            {{ content.pricing?.custom_title || 'Custom & Multi-Branch Network' }}
                        </h3>
                        <p class="text-sm text-slate-300 max-w-xl">
                            {{ content.pricing?.custom_desc || 'For madrasas with multiple campuses, over 500 students, or custom government reporting requirements.' }}
                        </p>
                    </div>
                    <a
                        href="#demo"
                        class="whitespace-nowrap px-6 py-3.5 rounded-xl bg-white text-slate-900 font-bold text-sm hover:bg-slate-100 transition-colors shadow-sm"
                    >
                        {{ content.pricing?.custom_cta || 'Contact Our Addis Ababa Team' }}
                    </a>
                </div>

            </div>
        </section>


        <!-- 8. FAQ ACCORDION SECTION -->
        <section id="faq" class="py-20 lg:py-28 bg-slate-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">
                        {{ content.faq?.title || 'Frequently Asked Questions' }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600">
                        {{ content.faq?.subtitle || 'Everything you need to know about adopting Beyan in your Islamic institute.' }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, fIndex) in (content.faq?.items || [])"
                        :key="fIndex"
                        class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm transition-all"
                    >
                        <button
                            @click="toggleFaq(fIndex)"
                            type="button"
                            class="w-full text-start px-6 py-5 flex items-center justify-between gap-4 font-bold text-slate-900 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                            :aria-expanded="openFaqIndices.includes(fIndex)"
                        >
                            <span class="text-base sm:text-lg">{{ faq.question }}</span>
                            <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-sm flex-shrink-0">
                                {{ openFaqIndices.includes(fIndex) ? '−' : '+' }}
                            </span>
                        </button>
                        
                        <div
                            v-show="openFaqIndices.includes(fIndex)"
                            class="px-6 pb-6 pt-1 text-sm text-slate-600 leading-relaxed border-t border-slate-100 bg-slate-50/50"
                        >
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>

            </div>
        </section>


        <!-- 9. DEMO REQUEST FORM SECTION -->
        <section id="demo" class="py-20 lg:py-28 bg-white border-t border-slate-200">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center mb-12">
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest bg-emerald-100/70 px-3 py-1 rounded-full border border-emerald-200">
                        Direct Assistance
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3 mb-4">
                        {{ content.demo?.title || 'Request a Personalized Demo' }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600">
                        {{ content.demo?.subtitle || 'See how Beyan works for your madrasa. We will walk you through the platform via video call or in person in Addis Ababa.' }}
                    </p>
                </div>

                <div class="bg-slate-50 rounded-3xl p-6 sm:p-10 border border-slate-200 shadow-md">
                    
                    <!-- Flash Success Message -->
                    <div
                        v-if="$page.props.flash?.success"
                        class="mb-6 p-4 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-900 text-sm font-semibold flex items-center gap-3"
                    >
                        <svg class="w-5 h-5 text-emerald-700 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $page.props.flash.success }}</span>
                    </div>

                    <form @submit.prevent="submitDemo" class="space-y-6">
                        
                        <!-- Honeypot Field (Invisible to legitimate human users) -->
                        <div class="hidden" aria-hidden="true">
                            <label for="hp_website">Do not fill this field:</label>
                            <input
                                id="hp_website"
                                v-model="form.hp_website"
                                type="text"
                                name="hp_website"
                                tabindex="-1"
                                autocomplete="off"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="demo_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    {{ content.demo?.name || 'Your Full Name' }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="demo_name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Ustadh Ahmed Mohammed"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.name }}</p>
                            </div>

                            <!-- Madrasa Name -->
                            <div>
                                <label for="demo_madrasa" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    {{ content.demo?.madrasa_name || 'Madrasa / Institute Name' }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="demo_madrasa"
                                    v-model="form.madrasa_name"
                                    type="text"
                                    required
                                    placeholder="Beyan Academy"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent"
                                />
                                <p v-if="form.errors.madrasa_name" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.madrasa_name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Phone -->
                            <div>
                                <label for="demo_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    {{ content.demo?.phone || 'Phone Number' }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="demo_phone"
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    placeholder="+251 91 123 4567"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent font-mono"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.phone }}</p>
                            </div>

                            <!-- Email (Optional) -->
                            <div>
                                <label for="demo_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    {{ content.demo?.email || 'Email Address' }} <span class="text-slate-400 font-normal">({{ content.demo?.optional || 'optional' }})</span>
                                </label>
                                <input
                                    id="demo_email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="contact@example.com"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Madrasa Mode -->
                            <div>
                                <label for="demo_mode" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    {{ content.demo?.mode || 'Madrasa Type' }} <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="demo_mode"
                                    v-model="form.mode"
                                    required
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                >
                                    <option value="physical">{{ content.demo?.mode_physical || 'Physical Campus (In-Person)' }}</option>
                                    <option value="online">{{ content.demo?.mode_online || 'Online Academy' }}</option>
                                    <option value="hybrid">{{ content.demo?.mode_hybrid || 'Hybrid (Both)' }}</option>
                                </select>
                                <p v-if="form.errors.mode" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.mode }}</p>
                            </div>

                            <!-- Student Count Range -->
                            <div>
                                <label for="demo_count" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    {{ content.demo?.student_count || 'Approximate Students' }} <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="demo_count"
                                    v-model="form.student_count_range"
                                    required
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                >
                                    <option value="under_50">{{ content.demo?.count_under_50 || 'Under 50 students' }}</option>
                                    <option value="50_150">{{ content.demo?.count_50_150 || '50 - 150 students' }}</option>
                                    <option value="150_300">{{ content.demo?.count_150_300 || '150 - 300 students' }}</option>
                                    <option value="over_300">{{ content.demo?.count_over_300 || 'Over 300 students' }}</option>
                                </select>
                                <p v-if="form.errors.student_count_range" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.student_count_range }}</p>
                            </div>
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="demo_msg" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                {{ content.demo?.message || 'Additional Notes or Questions' }} <span class="text-slate-400 font-normal">({{ content.demo?.optional || 'optional' }})</span>
                            </label>
                            <textarea
                                id="demo_msg"
                                v-model="form.message"
                                rows="3"
                                placeholder="Tell us about your campus, number of halaqahs, or preferred call time..."
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent"
                            ></textarea>
                            <p v-if="form.errors.message" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.message }}</p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-4 px-6 rounded-xl bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 text-white font-bold text-base shadow-lg shadow-emerald-700/20 transition-all focus:outline-none focus:ring-4 focus:ring-emerald-600/30"
                        >
                            <span v-if="form.processing">{{ content.demo?.submitting || 'Submitting...' }}</span>
                            <span v-else>{{ content.demo?.submit || 'Submit Demo Request' }}</span>
                        </button>

                    </form>

                </div>

            </div>
        </section>


        <!-- 10. FOOTER SECTION -->
        <footer class="bg-slate-900 text-slate-400 pt-16 pb-12 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                    
                    <!-- Col 1: Brand & Bio -->
                    <div class="md:col-span-1">
                        <Link href="/" class="flex items-center gap-2.5 mb-4">
                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xl">
                                <span>ب</span>
                            </div>
                            <span class="text-xl font-extrabold text-white tracking-tight">Beyan</span>
                        </Link>
                        <p class="text-xs text-slate-400 leading-relaxed mb-4">
                            {{ content.footer?.tagline || 'The complete, trustworthy management software for madrasas and Islamic institutes.' }}
                        </p>
                        <div class="text-xs text-slate-300 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 me-1.5"></span>
                            {{ content.footer?.location || 'Addis Ababa, Ethiopia' }}
                        </div>
                    </div>

                    <!-- Col 2: Navigation -->
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">
                            {{ content.footer?.quick_links || 'Platform' }}
                        </h4>
                        <ul class="space-y-2 text-xs">
                            <li><a href="#features" class="hover:text-white transition-colors">{{ content.header?.features || 'Features' }}</a></li>
                            <li><a href="#modes" class="hover:text-white transition-colors">{{ content.header?.modes || 'Modes' }}</a></li>
                            <li><a href="#pricing" class="hover:text-white transition-colors">{{ content.header?.pricing || 'Pricing' }}</a></li>
                            <li><a href="#faq" class="hover:text-white transition-colors">{{ content.header?.faq || 'FAQ' }}</a></li>
                            <li><Link href="/register-institution" class="hover:text-white transition-colors">{{ content.header?.start_trial || 'Free Trial' }}</Link></li>
                        </ul>
                    </div>

                    <!-- Col 3: Legal & Trust -->
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">
                            {{ content.footer?.legal || 'Legal & Trust' }}
                        </h4>
                        <ul class="space-y-2 text-xs">
                            <li><Link href="/privacy" class="hover:text-white transition-colors">{{ content.footer?.privacy || 'Privacy Policy' }}</Link></li>
                            <li><Link href="/terms" class="hover:text-white transition-colors">{{ content.footer?.terms || 'Terms of Service' }}</Link></li>
                            <li><a href="/sitemap.xml" target="_blank" class="hover:text-white transition-colors">Sitemap XML</a></li>
                        </ul>
                    </div>

                    <!-- Col 4: Contact & Social -->
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">
                            Direct Support (Addis Ababa)
                        </h4>
                        <div class="space-y-2 text-xs">
                            <p class="flex items-center gap-2">
                                <span class="text-slate-500 font-mono">Phone:</span>
                                <a :href="`tel:${content.footer?.phone || '+251911234567'}`" class="text-white hover:text-emerald-400 font-mono font-medium">
                                    {{ content.footer?.phone || '+251 91 123 4567' }}
                                </a>
                            </p>
                            <p class="flex items-center gap-2">
                                <span class="text-slate-500 font-mono">Telegram:</span>
                                <a href="https://t.me/beyan_academy" target="_blank" rel="noopener noreferrer" class="text-white hover:text-emerald-400 font-medium">
                                    @beyan_academy
                                </a>
                            </p>
                            <p class="flex items-center gap-2">
                                <span class="text-slate-500 font-mono">Email:</span>
                                <a :href="`mailto:${content.footer?.email || 'salam@beyan.io'}`" class="text-white hover:text-emerald-400 font-medium">
                                    {{ content.footer?.email || 'salam@beyan.io' }}
                                </a>
                            </p>
                        </div>

                        <!-- Language Switcher in Footer -->
                        <div class="mt-6 pt-4 border-t border-slate-800">
                            <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold mb-2">Language:</div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <button
                                    v-for="l in supportedLocales"
                                    :key="l.code"
                                    @click="switchLocale(l.code)"
                                    type="button"
                                    class="px-2 py-1 rounded transition-colors"
                                    :class="locale === l.code ? 'bg-emerald-800 text-white font-bold' : 'text-slate-400 hover:text-white bg-slate-800'"
                                >
                                    {{ l.native }}
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Copyright -->
                <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                    <p>&copy; {{ new Date().getFullYear() }} {{ content.footer?.copyright || 'Beyan Madrasa Management System. Built with care for the Ummah.' }}</p>
                    <p class="font-mono text-[11px] text-slate-600">v1.0.0 · Addis Ababa</p>
                </div>

            </div>
        </footer>

    </div>
</template>
