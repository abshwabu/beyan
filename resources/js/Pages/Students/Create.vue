<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const showGuardianSection = ref(true);

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
  form.guardians.push({
    full_name: '',
    phone: '',
    email: '',
    relationship_type: 'Mother',
    is_primary: false,
    create_account: false,
  });
};

const removeGuardian = (index) => {
  if (form.guardians.length > 1) {
    form.guardians.splice(index, 1);
  }
};

const submit = () => {
  form.post(route('students.store'));
};
</script>

<template>
  <Head title="Register Student - Beyan" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Student Registration</h1>
          <p class="text-sm text-slate-500">Minimal, mobile-first registration. Student ID is auto-assigned.</p>
        </div>
        <Link
          :href="route('students.index')"
          class="text-sm font-medium text-slate-600 hover:text-slate-900"
        >
          ← Back to Students
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- 1. Student Personal Information -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
          <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-2">
            1. Student Information
          </h2>

          <div>
            <label class="block text-sm font-medium text-slate-700">Full Name *</label>
            <input
              v-model="form.full_name"
              type="text"
              required
              placeholder="e.g. Abdullah Ahmed"
              class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            />
            <span v-if="form.errors.full_name" class="text-xs text-rose-600">{{ form.errors.full_name }}</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700">Gender *</label>
              <select
                v-model="form.gender"
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              >
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">Date of Birth</label>
              <input
                v-model="form.date_of_birth"
                type="date"
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700">Admission Date *</label>
              <input
                v-model="form.admission_date"
                type="date"
                required
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">Initial Class / Program</label>
              <input
                v-model="form.initial_class"
                type="text"
                placeholder="e.g. Hifz Level 1"
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Address / City</label>
            <input
              v-model="form.contact.address"
              type="text"
              placeholder="e.g. Bole, Addis Ababa"
              class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>
        </div>

        <!-- 2. Guardian Information (Optional & Extensible) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <h2 class="text-base font-semibold text-slate-900">
              2. Guardian Information
            </h2>
            <button
              type="button"
              @click="addGuardian"
              class="text-xs font-semibold text-emerald-600 hover:text-emerald-700"
            >
              + Add Another Guardian
            </button>
          </div>

          <div
            v-for="(guardian, index) in form.guardians"
            :key="index"
            class="p-4 bg-slate-50/75 rounded-lg border border-slate-200 space-y-3 relative"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase text-slate-600">
                Guardian #{{ index + 1 }} {{ guardian.is_primary ? '(Primary)' : '' }}
              </span>
              <button
                v-if="form.guardians.length > 1"
                type="button"
                @click="removeGuardian(index)"
                class="text-xs text-rose-500 hover:text-rose-700"
              >
                Remove
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-700">Full Name</label>
                <input
                  v-model="guardian.full_name"
                  type="text"
                  placeholder="e.g. Sheikh Ahmed"
                  class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-700">Relationship</label>
                <select
                  v-model="guardian.relationship_type"
                  class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                >
                  <option value="Father">Father</option>
                  <option value="Mother">Mother</option>
                  <option value="Brother">Brother</option>
                  <option value="Sister">Sister</option>
                  <option value="Uncle">Uncle</option>
                  <option value="Aunt">Aunt</option>
                  <option value="Guardian">Guardian</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-700">Phone</label>
                <input
                  v-model="guardian.phone"
                  type="tel"
                  placeholder="+251 9..."
                  class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-700">Email</label>
                <input
                  v-model="guardian.email"
                  type="email"
                  placeholder="guardian@example.com"
                  class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                />
              </div>
            </div>

            <!-- Optional Account Creation -->
            <div class="pt-1 flex items-center space-x-2">
              <input
                :id="'create_acc_' + index"
                v-model="guardian.create_account"
                type="checkbox"
                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
              />
              <label :for="'create_acc_' + index" class="text-xs text-slate-700">
                Create user login portal account for this guardian (requires email)
              </label>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end space-x-3">
          <Link
            :href="route('students.index')"
            class="px-4 py-2.5 text-sm font-medium text-slate-700 hover:text-slate-900"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex justify-center rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition disabled:opacity-50"
          >
            {{ form.processing ? 'Registering...' : 'Register Student' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
