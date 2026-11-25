<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
  institution: {
    type: Object,
    default: null,
  },
  isTeacherOnly: {
    type: Boolean,
    default: null,
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const tenant = computed(() => props.institution || page.props.tenant);
const teacherOnly = computed(() => {
  if (props.isTeacherOnly !== null) return props.isTeacherOnly;
  return user.value?.role === 'teacher';
});

const mobileMenuOpen = ref(false);
const desktopSidebarOpen = ref(
  typeof window !== 'undefined'
    ? window.localStorage.getItem('beyan_sidebar_open') !== 'false'
    : true
);

const toggleDesktopSidebar = () => {
  desktopSidebarOpen.value = !desktopSidebarOpen.value;
  if (typeof window !== 'undefined') {
    window.localStorage.setItem('beyan_sidebar_open', String(desktopSidebarOpen.value));
  }
};

const isCurrent = (path) => {
  const url = page.url || '';
  if (path === '/dashboard') {
    return url === '/dashboard' || url === '/';
  }
  return url === path || url.startsWith(path + '/') || url.startsWith(path + '?');
};

const navSections = computed(() => {
  const sections = [
    {
      label: 'Overview',
      items: [
        { name: 'Dashboard', href: route('dashboard'), path: '/dashboard', icon: '🏠' },
      ],
    },
    {
      label: 'Academics',
      items: [
        { name: 'Students', href: route('students.index'), path: '/students', icon: '🎓' },
        ...(!teacherOnly.value
          ? [
              { name: 'Courses', href: route('courses.index'), path: '/courses', icon: '📚' },
              { name: 'Classes', href: route('classes.index'), path: '/classes', icon: '🏫' },
            ]
          : []),
        { name: 'Timetable', href: route('timetable.today'), path: '/timetable', icon: '📅' },
        { name: 'Attendance', href: route('attendance.index'), path: '/attendance', icon: '✓' },
      ],
    },
    {
      label: "Qur'an & Hifz",
      items: [
        { name: "Qur'an & Hifz", href: route('hifz.index'), path: '/hifz', exactOnly: true, icon: '📖' },
        { name: 'Fast Entry', href: route('hifz.fast-entry'), path: '/hifz/fast-entry', icon: '⚡' },
      ],
    },
  ];

  if (!teacherOnly.value) {
    sections.push({
      label: 'Administration',
      items: [
        { name: 'Staff & Roles', href: route('staff.index'), path: '/staff', icon: '👥' },
        { name: 'Finance & Fees', href: route('finance.invoices.index'), path: '/finance', icon: '💳' },
        { name: 'Reports', href: route('reports.index'), path: '/reports', icon: '📊' },
      ],
    });
  }

  return sections;
});

const isItemActive = (item) => {
  const url = page.url || '';
  if (item.exactOnly) {
    return url === '/hifz' || url.startsWith('/hifz?');
  }
  return isCurrent(item.path);
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col lg:flex-row" dir="ltr">
    <!-- Mobile Slide-over Backdrop -->
    <div
      v-if="mobileMenuOpen"
      @click="mobileMenuOpen = false"
      class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 lg:hidden"
    ></div>

    <!-- Sidebar (Fixed on Desktop, Slide-over on Mobile) -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out',
        mobileMenuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full',
        desktopSidebarOpen ? 'lg:translate-x-0' : 'lg:-translate-x-full'
      ]"
    >
      <!-- Brand Header -->
      <div class="h-16 px-4 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
        <Link :href="route('dashboard')" class="flex items-center gap-2.5 min-w-0">
          <span class="w-9 h-9 rounded-xl bg-emerald-600 text-white font-black text-sm flex items-center justify-center flex-shrink-0 shadow-xs">
            BM
          </span>
          <div class="min-w-0">
            <div class="text-sm font-bold text-slate-900 truncate">
              {{ tenant?.name || 'Madrasa Workspace' }}
            </div>
            <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-800 uppercase tracking-wide">
              {{ tenant?.mode || 'hybrid' }}
            </span>
          </div>
        </Link>

        <!-- Mobile Close Button -->
        <button
          type="button"
          @click="mobileMenuOpen = false"
          class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100"
          aria-label="Close menu"
        >
          ✕
        </button>

        <!-- Desktop Collapse/Close Button -->
        <button
          type="button"
          @click="toggleDesktopSidebar"
          title="Collapse sidebar"
          class="hidden lg:inline-flex items-center justify-center p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition"
          aria-label="Collapse sidebar"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
          </svg>
        </button>
      </div>

      <!-- Navigation Groups -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
        <div v-for="section in navSections" :key="section.label" class="space-y-1">
          <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            {{ section.label }}
          </div>
          <Link
            v-for="item in section.items"
            :key="item.name"
            :href="item.href"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
              isItemActive(item)
                ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/20 shadow-2xs'
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
            ]"
          >
            <span class="w-5 text-center text-sm">{{ item.icon }}</span>
            <span class="truncate">{{ item.name }}</span>
          </Link>
        </div>
      </nav>

      <!-- Bottom User Card & Logout -->
      <div class="p-3 border-t border-slate-200 bg-slate-50/70 flex-shrink-0">
        <div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-slate-200/80">
          <div class="min-w-0">
            <div class="text-xs font-bold text-slate-900 truncate">{{ user?.name }}</div>
            <div class="text-[10px] text-slate-500 uppercase font-semibold">{{ user?.role }}</div>
          </div>
          <Link
            href="/logout"
            method="post"
            as="button"
            class="px-2.5 py-1.5 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition flex-shrink-0"
          >
            Logout
          </Link>
        </div>
      </div>
    </aside>

    <!-- Main Column -->
    <div
      :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-200 ease-in-out',
        desktopSidebarOpen ? 'lg:pl-64' : 'lg:pl-0'
      ]"
    >
      <!-- Desktop Floating Open-Sidebar Button (only visible when collapsed) -->
      <button
        v-if="!desktopSidebarOpen"
        type="button"
        @click="toggleDesktopSidebar"
        title="Open sidebar"
        class="hidden lg:inline-flex items-center gap-1.5 fixed top-4 left-4 z-40 px-3 py-2 rounded-xl bg-white border border-slate-200 shadow-md text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-emerald-700 transition"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <span>Menu</span>
      </button>
      <!-- Impersonation Banner -->
      <div v-if="$page.props.impersonation?.active" class="bg-amber-400 text-slate-900 px-4 py-2.5 font-bold text-xs sm:text-sm shadow-md flex flex-wrap items-center justify-between gap-2 border-b border-amber-500">
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 bg-slate-900 text-white rounded text-[11px] uppercase tracking-wider font-extrabold">Support Session</span>
          <span>You are viewing <strong>{{ $page.props.impersonation.institution_name }}</strong>. Reason: <em>"{{ $page.props.impersonation.reason }}"</em>. (Auto-expires in ~{{ $page.props.impersonation.minutes_remaining }} min)</span>
        </div>
        <Link href="/platform/impersonate/stop" method="post" as="button" class="px-3 py-1 bg-slate-900 text-white rounded-md text-xs hover:bg-slate-800 font-bold transition">
          Leave Support Impersonation &rarr;
        </Link>
      </div>

      <!-- Platform Announcement Banner -->
      <div v-if="$page.props.announcement" class="bg-indigo-700 text-white px-4 py-2 text-xs sm:text-sm shadow-sm flex items-center justify-between gap-4 border-b border-indigo-800">
        <div class="flex items-center gap-2 truncate">
          <span class="font-extrabold uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded text-[10px]">Announcement</span>
          <span class="truncate"><strong>{{ $page.props.announcement.title }}:</strong> {{ $page.props.announcement.body }}</span>
        </div>
        <Link :href="`/announcements/${$page.props.announcement.id}/dismiss`" method="post" as="button" class="text-white/80 hover:text-white font-black text-sm ms-2 flex-shrink-0" title="Dismiss announcement">
          ✕
        </Link>
      </div>

      <!-- Mobile Top Bar -->
      <header class="lg:hidden bg-white border-b border-slate-200 sticky top-0 z-30 px-4 h-14 flex items-center justify-between">
        <div class="flex items-center gap-2.5 min-w-0">
          <button
            type="button"
            @click="mobileMenuOpen = true"
            class="p-2 -ml-2 rounded-lg text-slate-700 hover:bg-slate-100 font-bold text-lg"
            aria-label="Open menu"
          >
            ☰
          </button>
          <span class="p-1.5 bg-emerald-600 rounded-lg text-white font-bold text-xs">BM</span>
          <span class="text-sm font-bold text-slate-900 truncate">{{ tenant?.name || 'Madrasa Workspace' }}</span>
        </div>
        <Link
          href="/logout"
          method="post"
          as="button"
          class="px-2.5 py-1 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
        >
          Logout
        </Link>
      </header>

      <!-- Page Content -->
      <main class="flex-1">
        <slot />
      </main>
    </div>
  </div>
</template>
