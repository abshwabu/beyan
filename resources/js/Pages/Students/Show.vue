<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
  feePlans: {
    type: Array,
    default: () => [],
  },
});

const showStatusModal = ref(false);
const showTransferModal = ref(false);
const showUploadModal = ref(false);
const showFeePlanModal = ref(false);

const feePlanForm = useForm({
  fee_plan_id: props.student.fee_plan_id || '',
});

const submitFeePlan = () => {
  feePlanForm
    .transform((data) => ({
      fee_plan_id: data.fee_plan_id || null,
    }))
    .put(route('students.update', props.student.id), {
      preserveScroll: true,
      onSuccess: () => {
        showFeePlanModal.value = false;
      },
    });
};

const statusForm = useForm({
  status: props.student.status,
  reason: '',
  effective_date: new Date().toISOString().slice(0, 10),
});

const transferForm = useForm({
  class_name: '',
  transfer_date: new Date().toISOString().slice(0, 10),
  notes: '',
});

const uploadForm = useForm({
  title: '',
  document: null,
});

const submitStatusChange = () => {
  statusForm.post(route('students.status', props.student.id), {
    onSuccess: () => {
      showStatusModal.value = false;
      statusForm.reset('reason');
    },
  });
};

const submitTransfer = () => {
  transferForm.post(route('students.transfer', props.student.id), {
    onSuccess: () => {
      showTransferModal.value = false;
      transferForm.reset();
    },
  });
};

const submitUpload = () => {
  uploadForm.post(route('students.documents.upload', props.student.id), {
    onSuccess: () => {
      showUploadModal.value = false;
      uploadForm.reset();
    },
  });
};

const deleteDoc = (docId) => {
  if (confirm('Are you sure you want to delete this document?')) {
    router.delete(route('students.documents.delete', [props.student.id, docId]));
  }
};

const getStatusBadgeClass = (s) => {
  switch (s) {
    case 'active':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'inactive':
      return 'bg-gray-50 text-gray-700 ring-gray-600/20';
    case 'suspended':
      return 'bg-amber-50 text-amber-700 ring-amber-600/20';
    case 'withdrawn':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'graduated':
      return 'bg-blue-50 text-blue-700 ring-blue-600/20';
    default:
      return 'bg-gray-50 text-gray-700 ring-gray-600/20';
  }
};
</script>

<template>
  <Head :title="`${student.full_name || student.name} - Profile`" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2 text-xs font-medium text-slate-500 mb-1.5">
            <Link :href="route('students.index')" class="hover:text-emerald-700 transition-colors">Students</Link>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold">{{ student.student_code || student.admission_number }}</span>
          </div>
          <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ student.full_name || student.name }}</h1>
            <span
              :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize',
                getStatusBadgeClass(student.status)
              ]"
            >
              {{ student.status }}
            </span>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button
            @click="showStatusModal = true"
            class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.98] transition-all"
          >
            Change Status
          </button>
          <button
            @click="showTransferModal = true"
            class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.98] transition-all"
          >
            Transfer Class
          </button>
          <button
            @click="showUploadModal = true"
            class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.98] transition-all"
          >
            Upload Document
          </button>
          <Link
            :href="route('hifz.student.profile', student.id)"
            class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-sm hover:bg-emerald-100 active:scale-[0.98] transition-all flex items-center gap-1.5"
          >
            <span>📖</span> Qur'an Profile
          </Link>
          <Link
            :href="route('students.edit', student.id)"
            class="px-4 py-2 rounded-xl bg-emerald-600 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-[0.98] transition-all"
          >
            Edit Profile
          </Link>
        </div>
      </div>

      <!-- Main Layout: 2 Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Summary & Guardians -->
        <div class="space-y-6 lg:col-span-1">
          <!-- Student Details Card -->
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">Student Profile</h2>
            <dl class="divide-y divide-slate-100 text-xs space-y-2">
              <div class="pt-2 flex justify-between">
                <dt class="text-slate-500 font-medium">Student Code</dt>
                <dd class="font-mono font-semibold text-slate-900">{{ student.student_code || student.admission_number }}</dd>
              </div>
              <div class="pt-2 flex justify-between">
                <dt class="text-slate-500 font-medium">Gender</dt>
                <dd class="capitalize font-medium text-slate-900">{{ student.gender }}</dd>
              </div>
              <div class="pt-2 flex justify-between">
                <dt class="text-slate-500 font-medium">Date of Birth</dt>
                <dd class="text-slate-900 font-medium">{{ student.date_of_birth || 'Not specified' }}</dd>
              </div>
              <div class="pt-2 flex justify-between">
                <dt class="text-slate-500 font-medium">Admission Date</dt>
                <dd class="text-slate-900 font-medium">{{ student.admission_date || 'N/A' }}</dd>
              </div>
              <div class="pt-2 flex justify-between">
                <dt class="text-slate-500 font-medium">Current Class</dt>
                <dd class="font-bold text-emerald-700">{{ student.current_enrollment?.class_name || 'Unassigned' }}</dd>
              </div>
              <div class="pt-2 flex justify-between items-center gap-2">
                <dt class="text-slate-500 font-medium">Tuition Fee Plan</dt>
                <dd class="text-right">
                  <span v-if="student.fee_plan" class="font-bold text-slate-900 block">
                    {{ student.fee_plan.name }}
                    <span class="text-[11px] font-normal text-slate-500">
                      ({{ ((student.fee_plan.amount || 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 }) }} {{ student.fee_plan.currency || 'ETB' }})
                    </span>
                  </span>
                  <span v-else class="text-slate-400 italic">Not assigned</span>
                  <button
                    type="button"
                    @click="showFeePlanModal = true"
                    class="text-[11px] font-bold text-emerald-700 hover:underline mt-0.5"
                  >
                    {{ student.fee_plan ? 'Change Plan' : '+ Assign Plan' }}
                  </button>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Guardians Card -->
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">Guardians</h2>
            <div v-if="student.guardians && student.guardians.length > 0" class="space-y-3">
              <div
                v-for="g in student.guardians"
                :key="g.id"
                class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-1.5"
              >
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-900">{{ g.full_name }}</span>
                  <span
                    v-if="g.pivot?.is_primary"
                    class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full text-[10px] font-bold"
                  >
                    Primary
                  </span>
                </div>
                <div class="text-slate-600">Relationship: <span class="capitalize font-semibold text-slate-800">{{ g.pivot?.relationship_type || 'Guardian' }}</span></div>
                <div v-if="g.phone" class="text-slate-600">Phone: <span class="font-medium text-slate-800">{{ g.phone }}</span></div>
                <div v-if="g.email" class="text-slate-600">Email: <span class="font-medium text-slate-800">{{ g.email }}</span></div>
                <div v-if="g.user_id" class="text-[11px] text-emerald-700 font-semibold pt-1">✓ Portal Account Active</div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 italic py-2">No guardians linked to this student.</div>
          </div>
        </div>

        <!-- Right: Enrollment History, Status History & Documents -->
        <div class="space-y-6 lg:col-span-2">
          <!-- Class Enrollment History (Effective-dated) -->
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
              <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Class Enrollment History</h2>
              <span class="text-xs text-slate-400">Never deleted • Effective dated</span>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-100 text-xs text-left">
                <thead class="text-slate-600 font-bold bg-slate-50">
                  <tr>
                    <th class="py-2.5 px-3 rounded-l-lg">Class</th>
                    <th class="py-2.5 px-3">Start Date</th>
                    <th class="py-2.5 px-3">End Date</th>
                    <th class="py-2.5 px-3 text-right rounded-r-lg">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="enr in student.enrollments" :key="enr.id" class="hover:bg-slate-50/60 transition-colors">
                    <td class="py-3 px-3 font-bold text-slate-900">{{ enr.class_name }}</td>
                    <td class="py-3 px-3 text-slate-600 font-medium">{{ enr.start_date }}</td>
                    <td class="py-3 px-3 text-slate-600 font-medium">{{ enr.end_date || 'Present' }}</td>
                    <td class="py-3 px-3 text-right">
                      <span
                        :class="[
                          'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                          enr.status === 'current' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'
                        ]"
                      >
                        {{ enr.status }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="!student.enrollments || student.enrollments.length === 0">
                    <td colspan="4" class="py-6 text-center text-slate-400 italic">No enrollment history records found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Documents (Private Storage) -->
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
              <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Documents</h2>
              <span class="text-xs text-slate-400">Private tenant storage</span>
            </div>
            <div v-if="student.documents && student.documents.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="doc in student.documents"
                :key="doc.id"
                class="py-3 flex items-center justify-between text-xs"
              >
                <div>
                  <p class="font-bold text-slate-900">{{ doc.title }}</p>
                  <p class="text-[11px] text-slate-400 mt-0.5">
                    {{ doc.file_name }} • {{ (doc.file_size / 1024).toFixed(1) }} KB • {{ doc.created_at?.slice(0, 10) }}
                  </p>
                </div>
                <div class="flex items-center space-x-3">
                  <a
                    :href="route('students.documents.download', [student.id, doc.id])"
                    class="text-emerald-700 hover:text-emerald-800 font-bold transition-colors"
                  >
                    Download
                  </a>
                  <button
                    @click="deleteDoc(doc.id)"
                    class="text-rose-600 hover:text-rose-700 font-bold transition-colors"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 italic py-4 text-center">No documents uploaded.</div>
          </div>

          <!-- Status History Log -->
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90 space-y-4">
            <div class="border-b border-slate-100 pb-2">
              <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Status Change Log</h2>
            </div>
            <div v-if="student.status_histories && student.status_histories.length > 0" class="space-y-2.5">
              <div
                v-for="hist in student.status_histories"
                :key="hist.id"
                class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-1"
              >
                <div class="flex items-center justify-between">
                  <span class="font-semibold text-slate-900">
                    Changed from <span class="capitalize text-slate-600">{{ hist.previous_status }}</span> → <span class="capitalize font-bold text-emerald-700">{{ hist.new_status }}</span>
                  </span>
                  <span class="text-slate-400 text-[11px]">{{ hist.effective_date }}</span>
                </div>
                <p class="text-slate-600 italic">"{{ hist.reason }}"</p>
                <p v-if="hist.changed_by" class="text-[10px] text-slate-400">By: {{ hist.changed_by.name }}</p>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 italic py-4 text-center">No status changes recorded.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <!-- Status Change Modal -->
    <div v-if="showStatusModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <h3 class="text-base font-bold text-slate-900">Change Student Status</h3>
        <form @submit.prevent="submitStatusChange" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700">New Status</label>
            <select v-model="statusForm.status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
              <option value="withdrawn">Withdrawn</option>
              <option value="graduated">Graduated</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700">Effective Date</label>
            <input v-model="statusForm.effective_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700">Reason *</label>
            <textarea
              v-model="statusForm.reason"
              required
              rows="3"
              placeholder="e.g. Relocated to another city or Completed Hifz program"
              class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            ></textarea>
          </div>
          <div class="flex justify-end space-x-2 pt-2">
            <button type="button" @click="showStatusModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">Cancel</button>
            <button type="submit" :disabled="statusForm.processing" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] transition-all shadow-sm">Save Status</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Class Transfer Modal -->
    <div v-if="showTransferModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <h3 class="text-base font-bold text-slate-900">Transfer Student Class</h3>
        <form @submit.prevent="submitTransfer" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700">New Class Name *</label>
            <input v-model="transferForm.class_name" required type="text" placeholder="e.g. Hifz Level 2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700">Transfer Effective Date</label>
            <input v-model="transferForm.transfer_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700">Notes</label>
            <textarea v-model="transferForm.notes" rows="2" placeholder="e.g. Passed Level 1 evaluation" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
          </div>
          <div class="flex justify-end space-x-2 pt-2">
            <button type="button" @click="showTransferModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">Cancel</button>
            <button type="submit" :disabled="transferForm.processing" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] transition-all shadow-sm">Record Transfer</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Document Upload Modal -->
    <div v-if="showUploadModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <h3 class="text-base font-bold text-slate-900">Upload Student Document</h3>
        <form @submit.prevent="submitUpload" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700">Document Title</label>
            <input v-model="uploadForm.title" type="text" placeholder="e.g. Birth Certificate or Previous Madrasa Report" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700">File * (PDF, JPG, PNG)</label>
            <input
              type="file"
              required
              @input="uploadForm.document = $event.target.files[0]"
              class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
            />
          </div>
          <div class="flex justify-end space-x-2 pt-2">
            <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">Cancel</button>
            <button type="submit" :disabled="uploadForm.processing" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] transition-all shadow-sm">Upload</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign / Change Fee Plan Modal -->
    <div v-if="showFeePlanModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900">Assign Tuition Fee Plan</h3>
          <Link :href="route('finance.fee-plans.index')" class="text-[11px] font-bold text-emerald-700 hover:underline">
            Manage Fee Plans
          </Link>
        </div>
        <form @submit.prevent="submitFeePlan" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700">Select Fee Plan</label>
            <select v-model="feePlanForm.fee_plan_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
              <option value="">No Fee Plan Assigned</option>
              <option v-for="plan in feePlans" :key="plan.id" :value="plan.id">
                {{ plan.name }} — {{ ((plan.amount || 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 }) }} {{ plan.currency || 'ETB' }} ({{ plan.period_type }})
              </option>
            </select>
            <p v-if="feePlanForm.errors.fee_plan_id" class="text-xs text-rose-600 mt-1">{{ feePlanForm.errors.fee_plan_id }}</p>
          </div>
          <div class="flex justify-end space-x-2 pt-2">
            <button type="button" @click="showFeePlanModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">Cancel</button>
            <button type="submit" :disabled="feePlanForm.processing" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] transition-all shadow-sm">Save Fee Plan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
