<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const form = useForm({
  full_name: '',
  gender: 'male',
  date_of_birth: '',
  admission_date: new Date().toISOString().slice(0, 10),
  initial_class: '',
  contact: {
    phone: '',
    email: '',
    address: '',
  },
  guardians: [
    {
      full_name: '',
      phone: '',
      email: '',
      relationship_type: 'Father',
      is_primary: true,
      create_account: false,
    },
  ],
});

const addGuardian = () => {
  const isFirstFather = form.guardians.length === 1 && form.guardians[0].relationship_type === 'Father';
  form.guardians.push({
    full_name: '',
    phone: '',
    email: '',
    relationship_type: isFirstFather ? 'Mother' : 'Guardian',
    is_primary: false,
    create_account: false,
  });
};

const removeGuardian = (index) => {
  if (form.guardians.length > 1) {
    const wasPrimary = form.guardians[index].is_primary;
    form.guardians.splice(index, 1);
    if (wasPrimary && form.guardians.length > 0) {
      form.guardians[0].is_primary = true;
    }
  }
};

const setPrimaryGuardian = (index) => {
  form.guardians.forEach((guardian, i) => {
    guardian.is_primary = (i === index);
  });
};

const submit = () => {
  form.post(route('students.store'));
};
</script>

<template>
  <Head title="Register Student - Beyan" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-3xl mx-auto space-y-6">

      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1.5">
            <Link :href="route('students.index')" class="hover:text-emerald-700 transition-colors">
              Students
            </Link>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold">New Admission</span>
          </nav>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2.5">
            Student Registration
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
              Auto ID Code
            </span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            Register a new student into the madrasa system. An institutional ID (e.g. BYN-2026-0001) will be assigned automatically.
          </p>
        </div>

        <Link
          :href="route('students.index')"
          class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-xs hover:bg-slate-50 transition-all self-start sm:self-auto"
        >
          <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Students
        </Link>
      </div>

      <!-- General Form Validation Error Alert -->
      <div
        v-if="form.hasErrors"
        class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-800 text-sm flex items-start gap-3"
      >
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <p class="font-semibold">Please fix the highlighted errors before submitting:</p>
          <ul class="mt-1 list-disc list-inside text-xs space-y-0.5">
            <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
          </ul>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">

        <!-- 1. Student Personal Information -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-6">
          <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
              01
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Student Personal Information</h2>
              <p class="text-xs text-slate-500">Core personal identity and admission details.</p>
            </div>
          </div>

          <div class="space-y-4">
            <!-- Full Name -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                Full Name <span class="text-rose-500">*</span>
              </label>
              <div class="relative rounded-lg shadow-xs">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <input
                  v-model="form.full_name"
                  type="text"
                  required
                  placeholder="e.g. Abdullah Ahmed"
                  class="block w-full rounded-lg border text-sm ps-9 pe-3 py-2.5 transition-all outline-none"
                  :class="form.errors.full_name ? 'border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20' : 'border-slate-300 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20'"
                />
              </div>
              <span v-if="form.errors.full_name" class="text-xs text-rose-600 mt-1 block">{{ form.errors.full_name }}</span>
            </div>

            <!-- Gender & Date of Birth -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  Gender <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="form.gender = 'male'"
                    class="py-2.5 px-3 rounded-lg border text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer"
                    :class="form.gender === 'male' ? 'bg-emerald-50 text-emerald-800 border-emerald-400 ring-1 ring-emerald-500/20 shadow-xs' : 'bg-slate-50/70 text-slate-600 border-slate-200 hover:bg-slate-100'"
                  >
                    <span>👦</span>
                    <span>Male</span>
                  </button>
                  <button
                    type="button"
                    @click="form.gender = 'female'"
                    class="py-2.5 px-3 rounded-lg border text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer"
                    :class="form.gender === 'female' ? 'bg-emerald-50 text-emerald-800 border-emerald-400 ring-1 ring-emerald-500/20 shadow-xs' : 'bg-slate-50/70 text-slate-600 border-slate-200 hover:bg-slate-100'"
                  >
                    <span>👧</span>
                    <span>Female</span>
                  </button>
                </div>
                <span v-if="form.errors.gender" class="text-xs text-rose-600 mt-1 block">{{ form.errors.gender }}</span>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  Date of Birth
                </label>
                <div class="relative rounded-lg shadow-xs">
                  <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <input
                    v-model="form.date_of_birth"
                    type="date"
                    class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-3 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  />
                </div>
                <span v-if="form.errors.date_of_birth" class="text-xs text-rose-600 mt-1 block">{{ form.errors.date_of_birth }}</span>
              </div>
            </div>

            <!-- Admission Date & Initial Class -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  Admission Date <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-xs">
                  <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <input
                    v-model="form.admission_date"
                    type="date"
                    required
                    class="block w-full rounded-lg border text-sm ps-9 pe-3 py-2.5 transition-all outline-none"
                    :class="form.errors.admission_date ? 'border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20' : 'border-slate-300 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20'"
                  />
                </div>
                <span v-if="form.errors.admission_date" class="text-xs text-rose-600 mt-1 block">{{ form.errors.admission_date }}</span>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  Initial Class / Program
                </label>
                <div class="relative rounded-lg shadow-xs">
                  <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                  </div>
                  <input
                    v-model="form.initial_class"
                    type="text"
                    placeholder="e.g. Hifz Level 1 or Tajweed"
                    class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-3 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  />
                </div>
                <span v-if="form.errors.initial_class" class="text-xs text-rose-600 mt-1 block">{{ form.errors.initial_class }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Student Contact & Location -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-6">
          <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
              02
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Student Contact Details</h2>
              <p class="text-xs text-slate-500">Optional direct contact information for the student.</p>
            </div>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  Student Phone
                </label>
                <div class="relative rounded-lg shadow-xs">
                  <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <input
                    v-model="form.contact.phone"
                    type="tel"
                    placeholder="+251 9..."
                    class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-3 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  />
                </div>
                <span v-if="form.errors['contact.phone']" class="text-xs text-rose-600 mt-1 block">{{ form.errors['contact.phone'] }}</span>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  Student Email
                </label>
                <div class="relative rounded-lg shadow-xs">
                  <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <input
                    v-model="form.contact.email"
                    type="email"
                    placeholder="student@example.com"
                    class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-3 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  />
                </div>
                <span v-if="form.errors['contact.email']" class="text-xs text-rose-600 mt-1 block">{{ form.errors['contact.email'] }}</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                Residential Address / City
              </label>
              <div class="relative rounded-lg shadow-xs">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <input
                  v-model="form.contact.address"
                  type="text"
                  placeholder="e.g. Bole Subcity, Woreda 03, Addis Ababa"
                  class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-3 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                />
              </div>
              <span v-if="form.errors['contact.address']" class="text-xs text-rose-600 mt-1 block">{{ form.errors['contact.address'] }}</span>
            </div>
          </div>
        </div>

        <!-- 3. Guardian / Parent Information -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-6">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
                03
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Guardian & Parent Information</h2>
                <p class="text-xs text-slate-500">Designate primary emergency contacts and portal access.</p>
              </div>
            </div>

            <button
              type="button"
              @click="addGuardian"
              class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100/80 px-3 py-1.5 rounded-lg border border-emerald-200 transition-all cursor-pointer active:scale-95"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              Add Guardian
            </button>
          </div>

          <!-- Guardians List -->
          <div class="space-y-4">
            <div
              v-for="(guardian, index) in form.guardians"
              :key="index"
              class="p-5 bg-slate-50/60 rounded-xl border border-slate-200 space-y-4 transition-all"
            >
              <!-- Card Header -->
              <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                    Guardian #{{ index + 1 }}
                  </span>
                  <span
                    v-if="guardian.is_primary"
                    class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200"
                  >
                    Primary Contact
                  </span>
                  <button
                    v-else
                    type="button"
                    @click="setPrimaryGuardian(index)"
                    class="text-[11px] text-slate-500 hover:text-emerald-700 font-medium underline cursor-pointer"
                  >
                    Make Primary
                  </button>
                </div>

                <button
                  v-if="form.guardians.length > 1"
                  type="button"
                  @click="removeGuardian(index)"
                  class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 rounded-md hover:bg-rose-50 transition-colors cursor-pointer"
                  title="Remove Guardian"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Remove
                </button>
              </div>

              <!-- Names & Relationship -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Full Name <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="guardian.full_name"
                    type="text"
                    required
                    placeholder="e.g. Sheikh Ahmed"
                    class="block w-full rounded-lg border border-slate-300 text-sm px-3.5 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white"
                  />
                  <span v-if="form.errors[`guardians.${index}.full_name`]" class="text-xs text-rose-600 mt-1 block">
                    {{ form.errors[`guardians.${index}.full_name`] }}
                  </span>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Relationship
                  </label>
                  <select
                    v-model="guardian.relationship_type"
                    class="block w-full rounded-lg border border-slate-300 text-sm px-3.5 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white"
                  >
                    <option value="Father">Father</option>
                    <option value="Mother">Mother</option>
                    <option value="Brother">Brother</option>
                    <option value="Sister">Sister</option>
                    <option value="Uncle">Uncle</option>
                    <option value="Aunt">Aunt</option>
                    <option value="Grandparent">Grandparent</option>
                    <option value="Guardian">Legal Guardian</option>
                  </select>
                </div>
              </div>

              <!-- Phone & Email -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Phone Number
                  </label>
                  <div class="relative rounded-lg shadow-xs">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                      <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                    </div>
                    <input
                      v-model="guardian.phone"
                      type="tel"
                      placeholder="+251 91 123 4567"
                      class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-3 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white"
                    />
                  </div>
                  <span v-if="form.errors[`guardians.${index}.phone`]" class="text-xs text-rose-600 mt-1 block">
                    {{ form.errors[`guardians.${index}.phone`] }}
                  </span>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Email Address
                  </label>
                  <div class="relative rounded-lg shadow-xs">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                      <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <input
                      v-model="guardian.email"
                      type="email"
                      placeholder="parent@example.com"
                      class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-3 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white"
                    />
                  </div>
                  <span v-if="form.errors[`guardians.${index}.email`]" class="text-xs text-rose-600 mt-1 block">
                    {{ form.errors[`guardians.${index}.email`] }}
                  </span>
                </div>
              </div>

              <!-- Create Account Checkbox -->
              <div class="pt-2 border-t border-slate-200/70">
                <label class="flex items-start gap-2.5 cursor-pointer">
                  <input
                    :id="'create_acc_' + index"
                    v-model="guardian.create_account"
                    type="checkbox"
                    class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                  />
                  <div>
                    <span class="text-xs font-medium text-slate-800 block">
                      Enable Guardian Portal Access
                    </span>
                    <span class="text-[11px] text-slate-500 block">
                      Generates a login account for this guardian to monitor attendance, Hifz milestones, and fee invoices (requires email address).
                    </span>
                  </div>
                </label>
              </div>
            </div>

            <!-- Dashed Add Button If Multiple -->
            <button
              type="button"
              @click="addGuardian"
              class="w-full py-3.5 px-4 rounded-xl border-2 border-dashed border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/40 text-slate-600 hover:text-emerald-800 text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer"
            >
              <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              Add Another Guardian
            </button>
          </div>
        </div>

        <!-- Submit & Actions Footer -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-slate-200/90 flex items-center justify-between gap-4">
          <Link
            :href="route('students.index')"
            class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all"
          >
            Cancel
          </Link>

          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-6 sm:px-8 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-xs hover:shadow transition-all disabled:opacity-50 cursor-pointer transform active:scale-[0.98]"
          >
            <svg
              v-if="form.processing"
              class="animate-spin -ms-1 me-2 h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span v-if="!form.processing">Complete Registration</span>
            <span v-else>Registering Student...</span>
          </button>
        </div>

      </form>
    </div>
  </div>
</template>
