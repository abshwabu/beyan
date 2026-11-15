<script setup>
import { Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({
    stats: Object,
    recentDemoRequests: Array,
    recentAuditEvents: Array,
    recentPayments: Array,
});
</script>

<template>
    <PlatformLayout title="Overview Dashboard">
        
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Platform Overview</h1>
                <p class="text-xs text-slate-500 mt-1">Multi-tenant health metrics, aggregate counts, and revenue telemetry.</p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    href="/platform/institutions/create"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
                >
                    + Register Madrasa
                </Link>
                <Link
                    href="/platform/health"
                    class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold shadow-sm transition"
                >
                    System Health &rarr;
                </Link>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            
            <!-- Card 1: Total Institutions -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        <span>Total Madrasas</span>
                        <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">+{{ stats.new_this_month }} this mo</span>
                    </div>
                    <div class="text-3xl font-black text-slate-900">{{ stats.total_institutions }}</div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-slate-500">
                    <span class="text-emerald-700">Active: {{ stats.statuses.active }}</span>
                    <span class="text-teal-700">Trial: {{ stats.statuses.trial }}</span>
                    <span class="text-rose-600">Suspended: {{ stats.statuses.suspended }}</span>
                </div>
            </div>

            <!-- Card 2: MRR in ETB -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Estimated MRR</div>
                    <div class="text-3xl font-black text-slate-900">{{ stats.mrr_etb?.toLocaleString() }} <span class="text-sm font-bold text-slate-400">ETB</span></div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-slate-500">
                    <span>Active Subscriptions</span>
                    <Link href="/platform/payments" class="text-rose-600 hover:underline">View Ledger &rarr;</Link>
                </div>
            </div>

            <!-- Card 3: Platform Totals (Students & Teachers) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Platform Scale</div>
                    <div class="text-3xl font-black text-slate-900">{{ stats.total_students?.toLocaleString() }} <span class="text-sm font-bold text-slate-400">Students</span></div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-slate-500">
                    <span>{{ stats.total_teachers }} Teachers enrolled</span>
                    <span class="text-slate-400">Anonymous totals</span>
                </div>
            </div>

            <!-- Card 4: Action Items (Trials & Overdue) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Subscription Alerts</div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-amber-600">{{ stats.trials_ending_soon }}</span>
                        <span class="text-xs font-bold text-slate-500">trials ending in 7d</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold">
                    <span class="text-rose-600 font-bold">{{ stats.overdue_subscriptions }} Overdue/Grace</span>
                    <Link href="/platform/subscriptions?overdue=1" class="text-rose-600 hover:underline">Review &rarr;</Link>
                </div>
            </div>

        </div>

        <!-- Grid 2-cols: Recent Demo Leads and Recent Audit Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Recent Demo Requests -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900">Recent Landing Page Leads</h2>
                    <Link href="/platform/demo-requests" class="text-xs font-bold text-rose-600 hover:underline">View All &rarr;</Link>
                </div>

                <div v-if="recentDemoRequests?.length" class="space-y-3">
                    <div
                        v-for="lead in recentDemoRequests"
                        :key="lead.id"
                        class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between text-xs"
                    >
                        <div>
                            <div class="font-bold text-slate-900">{{ lead.madrasa_name }}</div>
                            <div class="text-slate-500">{{ lead.name }} · {{ lead.phone }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase" :class="lead.status === 'new' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700'">
                                {{ lead.status }}
                            </span>
                            <Link :href="`/platform/demo-requests/${lead.id}/convert`" class="px-2.5 py-1 bg-white border border-slate-300 rounded-lg font-bold hover:bg-slate-100">
                                Convert &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 py-6 text-center">
                    No demo requests logged yet.
                </div>
            </div>

            <!-- Recent Platform Audit Events -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900">Recent Platform Audit Events</h2>
                    <Link href="/platform/audit-logs" class="text-xs font-bold text-rose-600 hover:underline">View Audit Trail &rarr;</Link>
                </div>

                <div v-if="recentAuditEvents?.length" class="space-y-3">
                    <div
                        v-for="ev in recentAuditEvents"
                        :key="ev.id"
                        class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between text-xs"
                    >
                        <div>
                            <div class="font-bold text-slate-900">{{ ev.event }}</div>
                            <div class="text-slate-500 font-mono text-[11px]">
                                By: {{ ev.user?.name || 'System' }} · {{ new Date(ev.created_at).toLocaleString() }}
                            </div>
                        </div>
                        <span class="font-mono text-[10px] text-slate-400">{{ ev.ip_address || '127.0.0.1' }}</span>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 py-6 text-center">
                    No recent audit events.
                </div>
            </div>

        </div>

    </PlatformLayout>
</template>
