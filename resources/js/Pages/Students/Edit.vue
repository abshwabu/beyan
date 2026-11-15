<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  full_name: props.student.full_name || props.student.name || '',
  gender: props.student.gender || 'male',
  date_of_birth: props.student.date_of_birth || '',
  admission_date: props.student.admission_date || '',
  contact: {
    phone: props.student.contact?.phone || '',
    email: props.student.contact?.email || '',
    address: props.student.contact?.address || '',
  },
});

const submit = () => {
  form.put(route('students.update', props.student.id));
};
</script>

<template>
  <Head :title="`Edit ${student.full_name || student.name} - Beyan`" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Student</h1>
          <p class="text-sm text-slate-500">Update student personal details and contact information.</p>
        </div>
        <Link
          :href="route('students.show', student.id)"
          class="text-sm font-medium text-slate-600 hover:text-slate-900"
        >
          ← Back to Profile
        </Link>
      </div>

      <form @submit.prevent="submit" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700">Full Name *</label>
          <input
            v-model="form.full_name"
            type="text"
            required
            class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
          />
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
            <label class="block text-sm font-medium text-slate-700">Admission Date</label>
            <input
              v-model="form.admission_date"
              type="date"
              class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Phone</label>
            <input
              v-model="form.contact.phone"
              type="tel"
              placeholder="+251 9..."
              class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Email</label>
            <input
              v-model="form.contact.email"
              type="email"
              class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Address / City</label>
            <input
              v-model="form.contact.address"
              type="text"
              class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
          <Link
            :href="route('students.show', student.id)"
            class="px-4 py-2.5 text-sm font-medium text-slate-700 hover:text-slate-900"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex justify-center rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
