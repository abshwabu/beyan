<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  staff: {
    type: Array,
    default: () => [],
  },
  pendingInvitations: {
    type: Array,
    default: () => [],
  },
  assignableRoles: {
    type: Array,
    default: () => ['Admin', 'Academic Coordinator', 'Teacher', 'Accountant', 'Receptionist'],
  },
});

// Modals
const showAddModal = ref(false);
const showInviteModal = ref(false);
const copiedId = ref(null);

// Forms
const addForm = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  role: 'Teacher',
  specialization: 'Hifz & Tajweed',
});

const inviteForm = useForm({
  email: '',
  roles: ['Teacher'],
});

const submitAdd = () => {
  addForm.post(route('staff.store'), {
    onSuccess: () => {
      showAddModal.value = false;
      addForm.reset();
    },
  });
};

const submitInvite = () => {
  inviteForm.post(route('invitations.store'), {
    onSuccess: () => {
      showInviteModal.value = false;
      inviteForm.reset();
    },
  });
};

const deleteStaff = (id, name) => {
  if (confirm(`Are you sure you want to remove staff member "${name}"?`)) {
    useForm({}).delete(route('staff.destroy', id));
  }
};

const copyLink = (id, url) => {
  navigator.clipboard.writeText(url).then(() => {
    copiedId.value = id;
    setTimeout(() => {
      copiedId.value = null;
    }, 2500);
  });
};

// Filter
const searchQuery = ref('');
const roleFilter = ref('all');

const filteredStaff = computed(() => {
  return props.staff.filter((s) => {
    const matchesSearch =
      s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      s.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (s.phone && s.phone.includes(searchQuery.value));
    const matchesRole =
      roleFilter.value === 'all' ||
      s.role === roleFilter.value ||
      (s.roles && s.roles.includes(roleFilter.value));
    return matchesSearch && matchesRole;
  });
});

const getRoleBadgeClass = (role) => {
  switch (role?.toLowerCase()) {
    case 'owner':
      return 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20';
    case 'admin':
      return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20';
    case 'teacher':
      return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
    case 'academic_coordinator':
    case 'academic coordinator':
      return 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20';
    case 'accountant':
      return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
    default:
      return 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/20';
  }
};
</script>

<template>
  <Head title="Staff & Users Management - Beyan" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-6xl mx-auto space-y-6">

      <!-- Breadcrumbs & Navigation -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
          <Link :href="route('dashboard')" class="hover:text-emerald-700 transition">Dashboard</Link>
          <span>/</span>
          <span class="text-slate-800">Staff & Users</span>
        </div>
        <Link
          :href="route('dashboard')"
          class="text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1"
        >
          &larr; Back to Dashboard
        </Link>
      </div>

      <!-- Header Card -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-sm">👥</span>
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Madrasa Administration</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 mt-1">Staff & User Management</h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Manage your teachers, academic coordinators, administrators, and staff roles.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            @click="showInviteModal = true"
            class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5"
          >
            <span>✉️</span>
            <span>Invite via Link</span>
          </button>
          <button
            type="button"
            @click="showAddModal = true"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5"
          >
            <span>+</span>
            <span>Add Staff Member</span>
          </button>
        </div>
      </div>

      <!-- Quick Bento Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Staff</div>
          <div class="text-2xl font-black text-slate-900 mt-1">{{ staff.length }}</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Assigned to institution</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Teachers</div>
          <div class="text-2xl font-black text-emerald-700 mt-1">
            {{ staff.filter((s) => s.is_teacher || (s.roles && s.roles.includes('Teacher'))).length }}
          </div>
          <div class="text-[11px] text-emerald-600 mt-0.5">Qur'an & Tajweed ustadhs</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Administrators</div>
          <div class="text-2xl font-black text-slate-900 mt-1">
            {{ staff.filter((s) => s.role === 'owner' || s.role === 'admin' || (s.roles && (s.roles.includes('Owner') || s.roles.includes('Admin')))).length }}
          </div>
          <div class="text-[11px] text-slate-500 mt-0.5">Owner & admin staff</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Invites</div>
          <div class="text-2xl font-black text-amber-600 mt-1">{{ pendingInvitations.length }}</div>
          <div class="text-[11px] text-amber-700 mt-0.5">Waiting for acceptance</div>
        </div>
      </div>

      <!-- Pending Invitations Banner / Card -->
      <div v-if="pendingInvitations.length > 0" class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
        <div class="bg-amber-50/70 px-6 py-3 border-b border-amber-200 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-amber-700 font-bold text-xs uppercase tracking-wider">Pending Email Invitations</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-800">
              {{ pendingInvitations.length }}
            </span>
          </div>
          <span class="text-[11px] text-amber-700 font-medium">Click copy to share the invitation link directly</span>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="inv in pendingInvitations"
            :key="inv.id"
            class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 transition"
          >
            <div>
              <div class="text-xs font-bold text-slate-900">{{ inv.email }}</div>
              <div class="flex items-center gap-2 mt-1">
                <span
                  v-for="r in inv.roles"
                  :key="r"
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700"
                >
                  {{ r }}
                </span>
                <span class="text-[10px] text-slate-400">• Expires {{ inv.expires_at }}</span>
              </div>
            </div>

            <button
              type="button"
              @click="copyLink(inv.id, inv.accept_url)"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition border"
              :class="copiedId === inv.id ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border-slate-300'"
            >
              <span>{{ copiedId === inv.id ? '✓ Copied Link' : '📋 Copy Invite URL' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Staff List & Filter Bar -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Search and Role Filter -->
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
          <div class="relative flex-1 max-w-sm">
            <span class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-slate-400 text-xs">
              🔍
            </span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search staff by name or email..."
              class="w-full ps-8 pe-3 py-1.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-500">Filter Role:</label>
            <select
              v-model="roleFilter"
              class="text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl px-3 py-1.5 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="all">All Roles</option>
              <option value="owner">Owner</option>
              <option value="admin">Admin</option>
              <option value="teacher">Teacher</option>
              <option value="academic coordinator">Academic Coordinator</option>
              <option value="accountant">Accountant</option>
              <option value="receptionist">Receptionist</option>
            </select>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-start text-xs">
            <thead class="bg-slate-100/75 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-bold text-[10px]">
              <tr>
                <th class="py-3 px-4 text-start">Staff Member</th>
                <th class="py-3 px-4 text-start">Assigned Roles</th>
                <th class="py-3 px-4 text-start">Specialization</th>
                <th class="py-3 px-4 text-start">Phone</th>
                <th class="py-3 px-4 text-start">Added On</th>
                <th class="py-3 px-4 text-end">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="user in filteredStaff"
                :key="user.id"
                class="hover:bg-slate-50/70 transition"
              >
                <!-- Name & Email -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                      {{ user.name.slice(0, 2).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-900">{{ user.name }}</div>
                      <div class="text-[11px] text-slate-400 font-mono">{{ user.email }}</div>
                    </div>
                  </div>
                </td>

                <!-- Roles -->
                <td class="py-3.5 px-4">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="r in (user.roles.length ? user.roles : [user.role])"
                      :key="r"
                      :class="['px-2 py-0.5 rounded-full text-[10px] font-bold uppercase', getRoleBadgeClass(r)]"
                    >
                      {{ r }}
                    </span>
                  </div>
                </td>

                <!-- Specialization (If Teacher) -->
                <td class="py-3.5 px-4 text-slate-600">
                  <span v-if="user.specialization" class="font-medium text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded text-[11px]">
                    {{ user.specialization }}
                  </span>
                  <span v-else class="text-slate-400 italic text-[11px]">—</span>
                </td>

                <!-- Phone -->
                <td class="py-3.5 px-4 font-mono text-slate-600">
                  {{ user.phone || '—' }}
                </td>

                <!-- Created At -->
                <td class="py-3.5 px-4 text-slate-500 font-medium">
                  {{ user.created_at || '—' }}
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-end">
                  <button
                    v-if="user.role !== 'owner' && user.id !== $page.props.auth?.user?.id"
                    type="button"
                    @click="deleteStaff(user.id, user.name)"
                    class="text-red-600 hover:text-red-800 font-bold text-[11px] px-2 py-1 rounded hover:bg-red-50 transition"
                  >
                    Remove
                  </button>
                  <span v-else class="text-slate-400 text-[10px] italic">Primary Owner</span>
                </td>
              </tr>

              <tr v-if="filteredStaff.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-400 italic">
                  No staff members matched your search criteria.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- MODAL: Directly Add Staff Member -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4 border border-slate-200">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">Add New Staff Member</h3>
            <p class="text-xs text-slate-500">Directly create an account for a teacher or administrator</p>
          </div>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitAdd" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Full Name *</label>
            <input
              v-model="addForm.name"
              type="text"
              required
              placeholder="e.g. Ustadh Khalid Ibrahim"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
            <div v-if="addForm.errors.name" class="text-red-600 text-[11px] mt-1">{{ addForm.errors.name }}</div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Email Address *</label>
            <input
              v-model="addForm.email"
              type="email"
              required
              placeholder="e.g. khalid@beyan.edu.et"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
            <div v-if="addForm.errors.email" class="text-red-600 text-[11px] mt-1">{{ addForm.errors.email }}</div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Assign Role *</label>
            <select
              v-model="addForm.role"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option v-for="role in assignableRoles" :key="role" :value="role">{{ role }}</option>
            </select>
          </div>

          <!-- Specialization (If Teacher) -->
          <div v-if="addForm.role === 'Teacher'">
            <label class="block font-bold text-slate-700 mb-1">Teacher Specialization</label>
            <input
              v-model="addForm.specialization"
              type="text"
              placeholder="e.g. Hifz, Tajweed, Islamic Studies"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Phone Number (Optional)</label>
            <input
              v-model="addForm.phone"
              type="text"
              placeholder="+251 91 234 5678"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Temporary Password *</label>
            <input
              v-model="addForm.password"
              type="password"
              required
              placeholder="Min 8 characters (e.g. Password123!)"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
            <div v-if="addForm.errors.password" class="text-red-600 text-[11px] mt-1">{{ addForm.errors.password }}</div>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button
              type="button"
              @click="showAddModal = false"
              class="px-4 py-2 border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="addForm.processing"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow"
            >
              {{ addForm.processing ? 'Creating...' : 'Create Account' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: Send Email Invitation -->
    <div
      v-if="showInviteModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4 border border-slate-200">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">Invite Staff via Email</h3>
            <p class="text-xs text-slate-500">Sends an invitation link allowing the user to create their own password</p>
          </div>
          <button @click="showInviteModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitInvite" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Staff Email Address *</label>
            <input
              v-model="inviteForm.email"
              type="email"
              required
              placeholder="e.g. teacher@beyan.edu.et"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
            <div v-if="inviteForm.errors.email" class="text-red-600 text-[11px] mt-1">{{ inviteForm.errors.email }}</div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Select Role(s) *</label>
            <div class="space-y-2 mt-2">
              <label
                v-for="role in assignableRoles"
                :key="role"
                class="flex items-center gap-2.5 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="role"
                  v-model="inviteForm.roles"
                  class="rounded text-emerald-600 focus:ring-emerald-500"
                />
                <span class="font-bold text-slate-800">{{ role }}</span>
              </label>
            </div>
            <div v-if="inviteForm.errors.roles" class="text-red-600 text-[11px] mt-1">{{ inviteForm.errors.roles }}</div>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button
              type="button"
              @click="showInviteModal = false"
              class="px-4 py-2 border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="inviteForm.processing"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow"
            >
              {{ inviteForm.processing ? 'Dispatching...' : 'Dispatch Invitation' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
