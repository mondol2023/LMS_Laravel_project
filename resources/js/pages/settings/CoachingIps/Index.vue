<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface CoachingIp {
    id: number;
    ip_address: string;
    label: string | null;
    is_active: boolean;
    created_at: string;
}

const props = defineProps<{
    coachingIps: {
        data: CoachingIp[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    search: string | null;
    filter: string;
    currentIp: string;
}>();

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string });

const showDeleteModal = ref(false);
const ipToDelete = ref<CoachingIp | null>(null);

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const deleteIp = (ip: CoachingIp) => {
    ipToDelete.value = ip;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (ipToDelete.value) {
        router.delete(`/settings/coaching-ips/${ipToDelete.value.id}`, {
            onFinish: () => {
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    ipToDelete.value = null;
};

const toggleStatus = (ip: CoachingIp) => {
    router.post(`/settings/coaching-ips/${ip.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const addCurrentIp = () => {
    router.post('/settings/coaching-ips/add-current', {}, {
        preserveScroll: true,
    });
};

const getStatusBadge = (ip: CoachingIp) => {
    if (ip.is_active) {
        return { text: 'Active', class: 'bg-green-100 text-green-800' };
    }
    return { text: 'Inactive', class: 'bg-gray-100 text-gray-800' };
};

const isCurrentIp = (ip: string) => {
    return ip === props.currentIp;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Coaching IPs',
        href: '/settings/coaching-ips',
    },
];

const searchIps = (event: Event) => {
    const target = event.target as HTMLInputElement;
    router.get('/settings/coaching-ips', {
        search: target.value,
        filter: props.filter,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filterIps = (filterValue: string) => {
    router.get('/settings/coaching-ips', {
        search: props.search,
        filter: filterValue,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            <div v-if="flash?.error" class="mb-4 flex items-center gap-3 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ flash.error }}
            </div>
            <div v-if="flash?.success" class="mb-4 flex items-center gap-3 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <svg class="h-5 w-5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ flash.success }}
            </div>

            <!-- Current IP Info -->
            <div class="mb-6 flex items-center gap-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-blue-900">Your Current IP Address</p>
                    <p class="text-lg font-bold text-blue-700">{{ currentIp }}</p>
                </div>
                <button
                    @click="addCurrentIp"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add This IP
                </button>
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Coaching Center IPs</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage IP addresses that can access exams without login</p>
                </div>
                <Link
                    href="/settings/coaching-ips/create"
                    class="inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add IP Address
                </Link>
            </div>

            <!-- Filters -->
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Search -->
                <div class="relative flex-1 sm:max-w-xs">
                    <input
                        type="text"
                        :value="search"
                        @input="searchIps"
                        placeholder="Search by IP or label..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 pl-10 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filter Tabs -->
                <div class="flex gap-2 rounded-lg border border-gray-200 bg-gray-50 p-1">
                    <button
                        @click="filterIps('all')"
                        :class="[
                            'rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                            filter === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        All
                    </button>
                    <button
                        @click="filterIps('active')"
                        :class="[
                            'rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                            filter === 'active' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Active
                    </button>
                    <button
                        @click="filterIps('inactive')"
                        :class="[
                            'rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                            filter === 'inactive' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Inactive
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="coachingIps.data.length === 0" class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                    />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">No coaching IPs configured</h3>
                <p class="mt-1 text-sm text-gray-500">Add IP addresses to allow exam access without login from coaching center.</p>
                <Link
                    href="/settings/coaching-ips/create"
                    class="mt-6 inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add IP Address
                </Link>
            </div>

            <!-- IPs Table -->
            <div v-else class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Label</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Added On</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="ip in coachingIps.data" :key="ip.id" :class="{ 'bg-blue-50': isCurrentIp(ip.ip_address) }">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm font-medium text-gray-900">{{ ip.ip_address }}</span>
                                    <span v-if="isCurrentIp(ip.ip_address)" class="rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                                        Your IP
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ ip.label || '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getStatusBadge(ip).class]">
                                    {{ getStatusBadge(ip).text }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ formatDate(ip.created_at) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="`/settings/coaching-ips/${ip.id}/edit`"
                                        class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="toggleStatus(ip)"
                                        class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium transition-colors hover:bg-gray-50"
                                        :class="ip.is_active ? 'text-orange-600 hover:border-orange-300 hover:bg-orange-50' : 'text-green-600 hover:border-green-300 hover:bg-green-50'"
                                    >
                                        {{ ip.is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button
                                        @click="deleteIp(ip)"
                                        class="rounded border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="coachingIps.data.length > 0 && coachingIps.links.length > 3" class="mt-6 flex justify-center">
                <div class="flex gap-1">
                    <template v-for="link in coachingIps.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1.5 text-sm font-medium transition-colors',
                                link.active ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-200 hover:text-gray-700',
                            ]"
                            v-html="link.label"
                        />
                        <span v-else class="cursor-not-allowed px-3 py-1.5 text-sm font-medium text-gray-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="closeDeleteModal"></div>
                <div class="relative w-full max-w-sm border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Delete Coaching IP</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Are you sure you want to delete the IP
                        <span class="font-mono font-semibold text-gray-900">{{ ipToDelete?.ip_address }}</span
                        >? Users from this IP will need to login to access exams.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="closeDeleteModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button @click="confirmDelete" class="bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
