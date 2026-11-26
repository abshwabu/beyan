<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
  plan: Object,
});

const form = useForm({
  name: props.plan.name,
  monthly_price_major: props.plan.monthly_price / 100,
  yearly_price_major: props.plan.yearly_price / 100,
  currency: props.plan.currency,
  student_limit: props.plan.student_limit,
  user_limit: props.plan.user_limit,
  features_raw: props.plan.features?.join('\n') || '',
  badge: props.plan.badge || '',
  is_active: Boolean(props.plan.is_active),
  sort_order: props.plan.sort_order ?? 0,
});

const yearlySavingsPercent = computed(() => {
  const monthlyAnnualized = (Number(form.monthly_price_major) || 0) * 12;
  const yearly = Number(form.yearly_price_major) || 0;
  if (monthlyAnnualized <= 0 || yearly >= monthlyAnnualized) return 0;
  return Math.round(((monthlyAnnualized - yearly) / monthlyAnnualized) * 100);
});

const parsedFeatures = computed(() => {
  return form.features_raw
    ? form.features_raw.split('\n').map((s) => s.trim()).filter(Boolean)
    : [];
});

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      student_limit: data.student_limit === '' ? null : data.student_limit,
      user_limit: data.user_limit === '' ? null : data.user_limit,
      features: parsedFeatures.value,
    }))
    .put(`/platform/plans/${props.plan.id}`);
};
</script>

<template>
  <PlatformLayout :title="`Edit ${plan.name}`">
    <div class="max-w-3xl mx-auto space-y-6" dir="ltr">
      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1.5">
            <Link href="/platform/plans" class="hover:text-rose-700 transition-colors">
              Subscription Plans
            </Link>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold">{{ plan.name }}</span>
          </nav>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">
            Edit Plan: {{ plan.name }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            Update pricing, capacity limits, visibility, and included features.
          </p>
        </div>

        <Link
          href="/platform/plans"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs transition self-start sm:self-auto"
        >
          <span>&larr;</span>
          <span>Back to Plans</span>
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Section 1: Plan Identity & Visibility -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-5">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
              <h2 class="text-sm font-bold text-slate-900">1. Plan Identity & Visibility</h2>
              <p class="text-xs text-slate-500 mt-0.5">Public name, highlight badge, and display status</p>
            </div>
            <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded text-rose-600 focus:ring-rose-500 border-slate-300"
              />
              <span class="text-xs font-bold text-slate-800">Active (Public)</span>
            </label>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Plan Name <span class="text-rose-600">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
            />
            <p v-if="form.errors.name" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.name }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Highlight Badge
              </label>
              <input
                v-model="form.badge"
                type="text"
                placeholder="e.g. Most Popular"
                class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
              />
              <p v-if="form.errors.badge" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.badge }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Sort Order
              </label>
              <input
                v-model.number="form.sort_order"
                type="number"
                min="0"
                class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white font-mono outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
              />
              <p v-if="form.errors.sort_order" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.sort_order }}</p>
            </div>
          </div>
        </div>

        <!-- Section 2: Pricing & Billing -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-5">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
              <h2 class="text-sm font-bold text-slate-900">2. Pricing & Billing</h2>
              <p class="text-xs text-slate-500 mt-0.5">Set monthly and annual subscription rates</p>
            </div>
            <span
              v-if="yearlySavingsPercent > 0"
              class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
            >
              Annual saves {{ yearlySavingsPercent }}%
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Monthly Price <span class="text-rose-600">*</span>
              </label>
              <input
                v-model.number="form.monthly_price_major"
                type="number"
                min="0"
                step="0.01"
                required
                class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white font-mono font-semibold outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
              />
              <p v-if="form.errors.monthly_price_major" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.monthly_price_major }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Yearly Price <span class="text-rose-600">*</span>
              </label>
              <input
                v-model.number="form.yearly_price_major"
                type="number"
                min="0"
                step="0.01"
                required
                class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white font-mono font-semibold outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
              />
              <p v-if="form.errors.yearly_price_major" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.yearly_price_major }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Currency <span class="text-rose-600">*</span>
              </label>
              <input
                v-model="form.currency"
                type="text"
                maxlength="10"
                required
                class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white uppercase font-mono font-semibold outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
              />
              <p v-if="form.errors.currency" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.currency }}</p>
            </div>
          </div>
        </div>

        <!-- Section 3: Capacity & Included Features -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-5">
          <div class="border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-900">3. Capacity Limits & Features</h2>
            <p class="text-xs text-slate-500 mt-0.5">Leave limits blank for unlimited capacity</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Student Limit
              </label>
              <input
                v-model.number="form.student_limit"
                type="number"
                min="1"
                placeholder="Unlimited"
                class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white font-mono outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
              />
              <p v-if="form.errors.student_limit" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.student_limit }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Staff / User Limit
              </label>
              <input
                v-model.number="form.user_limit"
                type="number"
                min="1"
                placeholder="Unlimited"
                class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white font-mono outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
              />
              <p v-if="form.errors.user_limit" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.user_limit }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Included Features (one per line)
              </label>
              <span class="text-[11px] font-semibold text-slate-400">
                {{ parsedFeatures.length }} feature(s)
              </span>
            </div>
            <textarea
              v-model="form.features_raw"
              rows="4"
              class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition"
            ></textarea>
            <p v-if="form.errors.features" class="text-xs text-rose-600 font-medium mt-1">{{ form.errors.features }}</p>
          </div>
        </div>

        <!-- Bottom Action Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between gap-4">
          <Link
            href="/platform/plans"
            class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-rose-600 hover:bg-rose-700 active:scale-[0.98] text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all disabled:opacity-50"
          >
            <span>{{ form.processing ? 'Saving...' : 'Save Changes' }}</span>
          </button>
        </div>
      </form>
    </div>
  </PlatformLayout>
</template>
