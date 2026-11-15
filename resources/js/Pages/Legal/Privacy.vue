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
    <Head title="Privacy Policy - Beyan" />

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
                <h1 class="text-3xl font-extrabold text-slate-900 mb-4">Privacy Policy</h1>
                <p class="text-sm text-slate-500 mb-8">Effective Date: November 15, 2026 · Last Updated: November 2026</p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">1. Commitment to Privacy in the Ummah</h2>
                <p class="text-slate-600 leading-relaxed">
                    At Beyan Academy, we hold privacy and confidentiality as a sacred trust (Amanah). This Privacy Policy explains how we collect, store, and protect data belonging to madrasas, ustadhs, students, and guardians.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">2. Strict Multi-Tenant Data Isolation</h2>
                <p class="text-slate-600 leading-relaxed">
                    Beyan uses an isolated multi-tenant architecture where every institution's records are compartmentalized with global institution scopes and tenant ID scoping. No institution can view, query, or infer another madrasa's students, teachers, or financial transactions.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">3. Data We Collect</h2>
                <ul class="list-disc pl-6 text-slate-600 space-y-2">
                    <li><strong>Account Credentials:</strong> Name, phone number, email address, and cryptographically hashed passwords.</li>
                    <li><strong>Student & Guardian Records:</strong> Student names, dates of birth, admission codes, emergency contact numbers, and Quranic memorization benchmarks (Sabaq, Sabqi, Manzil).</li>
                    <li><strong>Financial Ledgers:</strong> Tuition invoices, bank transaction references (CBE, Awash, Telebirr), and payment receipts stored exclusively in integer minor units.</li>
                    <li><strong>Audit Logs:</strong> Operational timestamps and user action footprints to protect institutions from unauthorized record tampering.</li>
                </ul>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">4. We Never Sell Your Data</h2>
                <p class="text-slate-600 leading-relaxed">
                    We will never sell, lease, or monetize student or madrasa data to third parties, ad networks, or data brokers under any circumstances.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">5. Data Retention & Backups</h2>
                <p class="text-slate-600 leading-relaxed">
                    Database snapshots are backed up securely using automated point-in-time transactions. Institutions retain complete ownership of their data and may export full records to Excel (CSV with UTF-8 BOM) or PDF at any time.
                </p>

                <h2 class="text-xl font-bold text-slate-900 mt-6 mb-2">6. Contact Our Security Officer</h2>
                <p class="text-slate-600 leading-relaxed">
                    For privacy inquiries or data export assistance in Addis Ababa or East Africa, contact us directly at <a href="mailto:privacy@beyan.io" class="text-emerald-700 underline">privacy@beyan.io</a> or call +251 91 123 4567.
                </p>
            </div>
        </main>

        <footer class="bg-white border-t border-slate-200 py-6 px-6 text-center text-sm text-slate-500">
            &copy; 2026 Beyan Madrasa Management System. All rights reserved.
        </footer>
    </div>
</template>
