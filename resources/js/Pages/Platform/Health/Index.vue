<script setup>
import { router } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({
    health: Object,
});

const triggerBackup = () => {
    if (confirm('Trigger a manual SQLite database backup snapshot now?')) {
        router.post('/platform/health/backup');
    }
};
</script>

<template>
    <PlatformLayout title="System Health">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Health & Storage</h1>
                <p class="text-xs text-slate-500 mt-0.5">Database integrity, WAL engine metrics, background queue, and snapshot history.</p>
            </div>
            <button
                @click="triggerBackup"
                type="button"
                class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2"
            >
                <span>💾 Run Manual Backup</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8 text-xs">
            
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-slate-400 font-bold uppercase text-[10px] block mb-1">SQLite Database Size</span>
                <span class="text-3xl font-black text-slate-900">{{ health.database_size_mb }} <span class="text-xs font-bold text-slate-400">MB</span></span>
                <div class="mt-2 text-[11px] text-slate-500 font-mono truncate" :title="health.database_path">{{ health.database_path }}</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-slate-400 font-bold uppercase text-[10px] block mb-1">SQLite Integrity Check</span>
                <div class="flex items-center gap-2 mt-1">
                    <span class="w-3 h-3 rounded-full" :class="health.integrity_check === 'ok' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                    <span class="text-xl font-bold uppercase tracking-wider" :class="health.integrity_check === 'ok' ? 'text-emerald-700' : 'text-rose-600'">
                        {{ health.integrity_check }}
                    </span>
                </div>
                <div class="mt-2 text-[11px] text-slate-500">PRAGMA quick_check verify</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-slate-400 font-bold uppercase text-[10px] block mb-1">Last Successful Backup</span>
                <span class="text-lg font-black text-slate-900 block mt-1">
                    {{ health.last_successful_backup ? new Date(health.last_successful_backup).toLocaleString() : 'None recorded' }}
                </span>
                <div class="mt-2 text-[11px] text-slate-500">Automated 14-day retention</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-slate-400 font-bold uppercase text-[10px] block mb-1">Background Queue</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-slate-900">{{ health.queue.pending_jobs }}</span>
                    <span class="text-slate-500 font-medium">pending</span>
                </div>
                <div class="mt-2 text-[11px]" :class="health.queue.failed_jobs > 0 ? 'text-rose-600 font-bold' : 'text-slate-500'">
                    {{ health.queue.failed_jobs }} failed jobs
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 text-xs">
            
            <!-- Software Stack Specs -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Environment & Runtimes</h2>
                <div class="divide-y divide-slate-100">
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500 font-medium">Platform App Version</span>
                        <span class="font-bold font-mono text-slate-900">{{ health.app_version }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500 font-medium">PHP Version</span>
                        <span class="font-bold font-mono text-slate-900">{{ health.php_version }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500 font-medium">Laravel Framework</span>
                        <span class="font-bold font-mono text-slate-900">{{ health.laravel_version }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500 font-medium">SQLite Engine Version</span>
                        <span class="font-bold font-mono text-slate-900">{{ health.sqlite_version }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500 font-medium">Server Wall-Clock Time</span>
                        <span class="font-bold font-mono text-slate-900">{{ health.server_time }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Backups List -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Recent SQLite Snapshots</h2>
                <div v-if="health.recent_backups?.length" class="space-y-2">
                    <div v-for="b in health.recent_backups" :key="b.filename" class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 flex items-center justify-between">
                        <div>
                            <span class="font-bold font-mono text-slate-900 text-[11px]">{{ b.filename }}</span>
                            <span class="text-[10px] text-slate-400 block">{{ b.created_at }}</span>
                        </div>
                        <span class="font-mono text-slate-600 font-semibold">{{ b.size_mb }} MB</span>
                    </div>
                </div>
                <div v-else class="text-slate-400 text-center py-6">
                    No recent backups found in storage directory.
                </div>
            </div>

        </div>
    </PlatformLayout>
</template>
