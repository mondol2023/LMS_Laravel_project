<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    packages: Object,
    search: String,
    filter: String,
});

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string });

const showDeleteModal = ref(false);
const packageToDelete = ref(null);

const formatDuration = (months: number) => {
    return months === 1 ? '1 month' : `${months} months`;
};

const deletePackage = (pkg) => {
    packageToDelete.value = pkg;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (packageToDelete.value) {
        router.delete(`/subscriptions/packages/${packageToDelete.value.id}`, {
            onFinish: () => {
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    packageToDelete.value = null;
};

const toggleStatus = (pkg) => {
    router.post(`/subscriptions/packages/${pkg.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const getStatusBadge = (pkg) => {
    if (pkg.is_active) {
        return { text: 'Active', class: 'bg-green-100 text-green-800' };
    }
    return { text: 'Inactive', class: 'bg-gray-100 text-gray-800' };
};

const formatLimit = (limit) => {
    return limit === null ? 'Unlimited' : limit;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscription Packages',
        href: dashboard().url,
    },
];

const searchPackages = (event) => {
    router.get('/subscriptions/packages', {
        search: event.target.value,
        filter: props.filter,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filterPackages = (filterValue) => {
    router.get('/subscriptions/packages', {
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

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Subscription Packages</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage subscription plans and pricing</p>
                </div>
                <Link
                    href="/subscriptions/packages/create"
                    class="inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Package
                </Link>
            </div>

            <!-- Filters -->
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Search -->
                <div class="relative flex-1 sm:max-w-xs">
                    <input
                        type="text"
                        :value="search"
                        @input="searchPackages"
                        placeholder="Search packages..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 pl-10 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filter Tabs -->
                <div class="flex gap-2 rounded-lg border border-gray-200 bg-gray-50 p-1">
                    <button
                        @click="filterPackages('all')"
                        :class="[
                            'rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                            filter === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        All
                    </button>
                    <button
                        @click="filterPackages('active')"
                        :class="[
                            'rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                            filter === 'active' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Active
                    </button>
                    <button
                        @click="filterPackages('inactive')"
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
            <div v-if="packages.data.length === 0" class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">No packages found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first subscription package.</p>
                <Link
                    href="/subscriptions/packages/create"
                    class="mt-6 inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Package
                </Link>
            </div>

            <!-- Packages Grid -->
            <div v-else class="mt-6 grid gap-6 lg:grid-cols-2">
                <div v-for="pkg in packages.data" :key="pkg.id" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ pkg.name }}</h3>
                                <p v-if="pkg.description" class="mt-1 text-sm text-gray-600">{{ pkg.description }}</p>
                            </div>
                            <span :class="['inline-flex items-center px-2.5 py-0.5 text-xs font-medium', getStatusBadge(pkg).class]">
                                {{ getStatusBadge(pkg).text }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-gray-900">BDT {{ pkg.price }}</span>
                            <span class="text-sm text-gray-500">/ {{ formatDuration(pkg.duration) }}</span>
                            <span v-if="pkg.discount" class="ml-2 rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                {{ pkg.discount_type === 'percent' ? `${pkg.discount}% off` : `BDT ${pkg.discount} off` }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Mock Tests -->
                        <div class="mb-4">
                            <h4 class="mb-2 text-sm font-semibold text-gray-900">Mock Tests</h4>
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between rounded bg-gray-50 px-3 py-2 text-sm">
                                    <span class="text-gray-600">Full Mock Test</span>
                                    <span class="font-medium text-gray-900">{{ formatLimit(pkg.full_mock_test_limit) }}</span>
                                </div>
                                <div class="rounded bg-gray-50 px-3 py-2">
                                    <div class="mb-1 text-xs font-medium text-gray-500">Partial Tests by Part</div>
                                    <div class="grid grid-cols-4 gap-2 text-sm">
                                        <div class="flex flex-col items-center">
                                            <span class="text-xs text-gray-500">Reading</span>
                                            <span class="font-medium text-gray-900">{{ formatLimit(pkg.partial_reading_limit) }}</span>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-xs text-gray-500">Writing</span>
                                            <span class="font-medium text-gray-900">{{ formatLimit(pkg.partial_writing_limit) }}</span>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-xs text-gray-500">Listening</span>
                                            <span class="font-medium text-gray-900">{{ formatLimit(pkg.partial_listening_limit) }}</span>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-xs text-gray-500">Speaking</span>
                                            <span class="font-medium text-gray-900">{{ formatLimit(pkg.partial_speaking_limit) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Practice Modules -->
                        <div class="mb-4">
                            <h4 class="mb-2 text-sm font-semibold text-gray-900">Practice Modules</h4>
                            <div class="grid grid-cols-4 gap-2">
                                <div class="rounded bg-gray-50 px-3 py-2 text-center">
                                    <div class="text-xs text-gray-500">Reading</div>
                                    <div v-if="pkg.practice_reading_enabled" class="mt-1 text-xs font-medium text-green-600">✓ Unlimited</div>
                                    <div v-else class="mt-1 text-xs font-medium text-gray-400">Disabled</div>
                                </div>
                                <div class="rounded bg-gray-50 px-3 py-2 text-center">
                                    <div class="text-xs text-gray-500">Listening</div>
                                    <div v-if="pkg.practice_listening_enabled" class="mt-1 text-xs font-medium text-green-600">✓ Unlimited</div>
                                    <div v-else class="mt-1 text-xs font-medium text-gray-400">Disabled</div>
                                </div>
                                <div class="rounded bg-gray-50 px-3 py-2 text-center">
                                    <div class="text-xs text-gray-500">Writing</div>
                                    <div v-if="pkg.practice_writing_enabled" class="mt-1 text-xs font-medium text-blue-600">{{ formatLimit(pkg.practice_writing_limit) }}</div>
                                    <div v-else class="mt-1 text-xs font-medium text-gray-400">Disabled</div>
                                </div>
                                <div class="rounded bg-gray-50 px-3 py-2 text-center">
                                    <div class="text-xs text-gray-500">Speaking</div>
                                    <div v-if="pkg.practice_speaking_enabled" class="mt-1 text-xs font-medium text-blue-600">{{ formatLimit(pkg.practice_speaking_limit) }}</div>
                                    <div v-else class="mt-1 text-xs font-medium text-gray-400">Disabled</div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="rounded bg-blue-50 px-3 py-2 text-center">
                            <span class="text-xs text-blue-600">Active Subscriptions: </span>
                            <span class="text-sm font-bold text-blue-900">{{ pkg.active_subscriptions_count || 0 }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                        <div class="mb-2 flex items-center gap-2">
                            <Link
                                :href="`/subscriptions/users?package=${pkg.id}`"
                                class="flex-1 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-center text-sm font-medium text-blue-700 transition-colors hover:bg-blue-100"
                            >
                                <span class="flex items-center justify-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Assign Users
                                </span>
                            </Link>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="`/subscriptions/packages/${pkg.id}/edit`"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-center text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Edit
                            </Link>
                            <button
                                @click="toggleStatus(pkg)"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium transition-colors hover:bg-gray-50"
                                :class="pkg.is_active ? 'text-orange-600 hover:border-orange-300 hover:bg-orange-50' : 'text-green-600 hover:border-green-300 hover:bg-green-50'"
                            >
                                {{ pkg.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button
                                @click="deletePackage(pkg)"
                                class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                                :title="pkg.total_subscriptions_count > 0 ? 'Cannot delete package with subscriptions' : 'Delete package'"
                                :disabled="pkg.total_subscriptions_count > 0"
                                :class="pkg.total_subscriptions_count > 0 ? 'cursor-not-allowed opacity-50' : ''"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="packages.data.length > 0 && packages.links.length > 3" class="mt-6 flex justify-center">
                <div class="flex gap-1">
                    <template v-for="link in packages.links" :key="link.label">
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
                    <h3 class="text-lg font-bold text-gray-900">Delete Package</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Are you sure you want to delete the package
                        <span class="font-semibold text-gray-900">{{ packageToDelete?.name }}</span
                        >? This cannot be undone.
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
