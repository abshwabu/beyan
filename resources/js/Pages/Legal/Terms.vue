<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    appName: {
        type: String,
        default: 'Beyan Madrasa Management',
    },
});
</script>

<template>
    <Head title="Terms of Service - Beyan" />

    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col justify-between">
        <header class="bg-white border-b border-slate-200 py-4 px-6">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2 text-emerald-700 font-bold text-xl tracking-tight">
                    <span class="inline-block w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold">ب</span>
                    <span>Beyan</span>
                </Link>
                <Link href="/" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">
                    &larr; Back to Home
                </Link>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-6 py-12 flex-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 sm:p-12 prose prose-emerald max-w-none">
                <h1 class="text-3xl font-extrabold text-slate-900 mb-4">Terms of Service</h1>
                <p class="text-sm text-slate-500 mb-8">Effective Date: November 15, 2026</p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">1. Agreement to Terms</h2>
                <p class="text-slate-600 leading-relaxed">
                    By registering an institution on Beyan, you agree to comply with and be bound by these Terms of Service. These terms govern the relationship between your madrasa and Beyan Academy.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">2. 14-Day Free Trial</h2>
                <p class="text-slate-600 leading-relaxed">
                    Every newly registered madrasa receives an automatic 14-day full-access trial period without requiring a payment method. You may explore all modules including student admission, attendance, Hifz progress tracking, and fee invoicing.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">3. Subscription & Payments</h2>
                <p class="text-slate-600 leading-relaxed">
                    Subscription fees are priced in Ethiopian Birr (ETB) for local institutions and can be settled via Ethiopian bank transfer (Commercial Bank of Ethiopia, Awash, Telebirr) or direct invoicing. Continued use beyond the 14-day trial requires an active plan subscription.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">4. User Responsibilities & Acceptable Use</h2>
                <p class="text-slate-600 leading-relaxed">
                    Institutions are responsible for maintaining the confidentiality of staff and teacher credentials. Beyan must only be used for legitimate educational, academic, religious, and institutional management purposes.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">5. Data Ownership & Portability</h2>
                <p class="text-slate-600 leading-relaxed">
                    You own 100% of your data. You may download complete copies of student registers, attendance records, and tuition ledgers at any time. If an account is closed, data can be permanently purged upon verified administrator request.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">6. Limitation of Liability</h2>
                <p class="text-slate-600 leading-relaxed">
                    Beyan is provided on an "as is" and "as available" basis. While we maintain reliable uptime, automated daily backups, and robust SQLite WAL transaction guarantees, we will not be held liable for unforeseeable disruptions beyond reasonable technical control.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">7. Governing Law</h2>
                <p class="text-slate-600 leading-relaxed">
                    These terms are governed by the applicable commercial and digital laws of the Federal Democratic Republic of Ethiopia.
                </p>
            </div>
        </main>

        <footer class="bg-white border-t border-slate-200 py-6 px-6 text-center text-sm text-slate-500">
            &copy; 2026 Beyan Madrasa Management System. All rights reserved.
        </footer>
    </div>
</template>
