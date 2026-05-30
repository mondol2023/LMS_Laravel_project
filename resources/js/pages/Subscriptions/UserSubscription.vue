<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    user: Object,
    availablePackages: Array,
});

const showAssignModal = ref(false);
const showUpgradeModal = ref(false);
const showRenewModal = ref(false);
const showCancelModal = ref(false);

const assignForm = useForm({
    user_id: props.user.id,
    package_id: null,
    start_date: null,
    notes: '',
});

const upgradeForm = useForm({
    package_id: null,
    reset_counters: false,
    notes: '',
});

const renewForm = useForm({
    reset_counters: false,
    notes: '',
});

const cancelForm = useForm({
    immediate: true,
    notes: '',
});

const currentSubscription = computed(() => props.user.active_subscription);
const hasActiveSubscription = computed(() => !!currentSubscription.value);

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getUsagePercentage = (used, limit) => {
    if (limit === null) return 0;
    return Math.min((used / limit) * 100, 100);
};

const getUsageColor = (used, limit) => {
    if (limit === null) return 'bg-green-500';
    const percentage = (used / limit) * 100;
    if (percentage >= 100) return 'bg-red-500';
    if (percentage >= 75) return 'bg-orange-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-green-500';
};

const submitAssign = () => {
    assignForm.post('/subscriptions/assign', {
        onSuccess: () => {
            showAssignModal.value = false;
            assignForm.reset();
        },
    });
};

const submitUpgrade = () => {
    upgradeForm.post(`/subscriptions/users/${props.user.id}/upgrade`, {
        onSuccess: () => {
            showUpgradeModal.value = false;
            upgradeForm.reset();
        },
    });
};

const submitRenew = () => {
    renewForm.post(`/subscriptions/users/${props.user.id}/renew`, {
        onSuccess: () => {
            showRenewModal.value = false;
            renewForm.reset();
        },
    });
};

const submitCancel = () => {
    cancelForm.post(`/subscriptions/users/${props.user.id}/cancel`, {
        onSuccess: () => {
            showCancelModal.value = false;
            cancelForm.reset();
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Subscription',
        href: dashboard().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="h-full overflow-y-auto px-3 py-4 sm:px-4 sm:py-6">
            <!-- Header -->
            <div class="mb-4 sm:mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-bold text-gray-900 sm:text-xl">Subscription Management</h1>
                        <p class="mt-0.5 truncate text-xs text-gray-500 sm:text-sm">{{ user.name }} ({{ user.email }})</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="!hasActiveSubscription"
                            @click="showAssignModal = true"
                            class="inline-flex items-center gap-1.5 bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800 sm:px-4 sm:py-2 sm:text-sm"
                        >
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Assign
                        </button>
                        <template v-else>
                            <button
                                @click="showUpgradeModal = true"
                                class="inline-flex items-center gap-1.5 border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                Upgrade
                            </button>
                            <button
                                @click="showRenewModal = true"
                                class="inline-flex items-center gap-1.5 border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                Renew
                            </button>
                            <button
                                @click="showCancelModal = true"
                                class="inline-flex items-center gap-1.5 border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                Cancel
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Current Subscription -->
            <div v-if="hasActiveSubscription" class="mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm sm:mb-6">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-sm font-bold text-gray-900 sm:text-base">Current Subscription</h2>
                </div>
                <div class="p-3 sm:p-4">
                    <!-- Package Info - Compact Grid -->
                    <div class="mb-3 grid grid-cols-3 gap-2 sm:mb-4 sm:gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Package</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 sm:text-base">{{ currentSubscription.package.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Subscribed</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 sm:text-base">{{ formatDate(currentSubscription.subscribed_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Expires</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 sm:text-base">{{ formatDate(currentSubscription.expires_at) }}</p>
                        </div>
                    </div>

                    <!-- Usage Statistics - Compact -->
                    <div class="space-y-3 border-t border-gray-100 pt-3">
                        <h3 class="text-xs font-semibold text-gray-900 sm:text-sm">Usage Statistics</h3>

                        <!-- Full Mock Tests -->
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700">Full Mock Tests</span>
                                <span class="text-xs text-gray-600">
                                    {{ currentSubscription.full_mock_tests_used }} /
                                    {{ currentSubscription.package.full_mock_test_limit ?? '∞' }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                <div
                                    :class="[
                                        'h-full transition-all duration-300',
                                        getUsageColor(currentSubscription.full_mock_tests_used, currentSubscription.package.full_mock_test_limit),
                                    ]"
                                    :style="{ width: `${getUsagePercentage(currentSubscription.full_mock_tests_used, currentSubscription.package.full_mock_test_limit)}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Partial Tests - 2x2 Grid -->
                        <div class="grid grid-cols-2 gap-2 sm:gap-3">
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-700">Reading</span>
                                    <span class="text-xs text-gray-600">
                                        {{ currentSubscription.partial_reading_used }}/{{ currentSubscription.package.partial_reading_limit ?? '∞' }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :class="['h-full transition-all duration-300', getUsageColor(currentSubscription.partial_reading_used, currentSubscription.package.partial_reading_limit)]"
                                        :style="{ width: `${getUsagePercentage(currentSubscription.partial_reading_used, currentSubscription.package.partial_reading_limit)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-700">Writing</span>
                                    <span class="text-xs text-gray-600">
                                        {{ currentSubscription.partial_writing_used }}/{{ currentSubscription.package.partial_writing_limit ?? '∞' }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :class="['h-full transition-all duration-300', getUsageColor(currentSubscription.partial_writing_used, currentSubscription.package.partial_writing_limit)]"
                                        :style="{ width: `${getUsagePercentage(currentSubscription.partial_writing_used, currentSubscription.package.partial_writing_limit)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-700">Listening</span>
                                    <span class="text-xs text-gray-600">
                                        {{ currentSubscription.partial_listening_used }}/{{ currentSubscription.package.partial_listening_limit ?? '∞' }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :class="['h-full transition-all duration-300', getUsageColor(currentSubscription.partial_listening_used, currentSubscription.package.partial_listening_limit)]"
                                        :style="{ width: `${getUsagePercentage(currentSubscription.partial_listening_used, currentSubscription.package.partial_listening_limit)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-700">Speaking</span>
                                    <span class="text-xs text-gray-600">
                                        {{ currentSubscription.partial_speaking_used }}/{{ currentSubscription.package.partial_speaking_limit ?? '∞' }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :class="['h-full transition-all duration-300', getUsageColor(currentSubscription.partial_speaking_used, currentSubscription.package.partial_speaking_limit)]"
                                        :style="{ width: `${getUsagePercentage(currentSubscription.partial_speaking_used, currentSubscription.package.partial_speaking_limit)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Practice Modules - Compact -->
                        <div class="grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 sm:gap-3">
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-700">Writing Practice</span>
                                    <span v-if="currentSubscription.package.practice_writing_enabled" class="text-xs text-gray-600">
                                        {{ currentSubscription.practice_writing_used }}/{{ currentSubscription.package.practice_writing_limit ?? '∞' }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">Off</span>
                                </div>
                                <div v-if="currentSubscription.package.practice_writing_enabled" class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :class="['h-full transition-all duration-300', getUsageColor(currentSubscription.practice_writing_used, currentSubscription.package.practice_writing_limit)]"
                                        :style="{ width: `${getUsagePercentage(currentSubscription.practice_writing_used, currentSubscription.package.practice_writing_limit)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-700">Speaking Practice</span>
                                    <span v-if="currentSubscription.package.practice_speaking_enabled" class="text-xs text-gray-600">
                                        {{ currentSubscription.practice_speaking_used }}/{{ currentSubscription.package.practice_speaking_limit ?? '∞' }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">Off</span>
                                </div>
                                <div v-if="currentSubscription.package.practice_speaking_enabled" class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :class="['h-full transition-all duration-300', getUsageColor(currentSubscription.practice_speaking_used, currentSubscription.package.practice_speaking_limit)]"
                                        :style="{ width: `${getUsagePercentage(currentSubscription.practice_speaking_used, currentSubscription.package.practice_speaking_limit)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Practice Module Status - Compact badges -->
                        <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs text-gray-500">Reading:</span>
                                <span :class="['rounded px-1.5 py-0.5 text-xs font-medium', currentSubscription.package.practice_reading_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ currentSubscription.package.practice_reading_enabled ? 'On' : 'Off' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs text-gray-500">Listening:</span>
                                <span :class="['rounded px-1.5 py-0.5 text-xs font-medium', currentSubscription.package.practice_listening_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ currentSubscription.package.practice_listening_enabled ? 'On' : 'Off' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Active Subscription -->
            <div v-else class="mb-4 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center sm:mb-6 sm:p-8">
                <svg class="mx-auto h-8 w-8 text-gray-400 sm:h-10 sm:w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                </svg>
                <h3 class="mt-3 text-sm font-semibold text-gray-900 sm:text-base">No Active Subscription</h3>
                <p class="mt-1 text-xs text-gray-500 sm:text-sm">Assign a package to get started.</p>
                <button
                    @click="showAssignModal = true"
                    class="mt-4 inline-flex items-center gap-1.5 bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800 sm:px-4 sm:py-2 sm:text-sm"
                >
                    <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Assign Package
                </button>
            </div>

            <!-- Subscription History -->
            <div v-if="user.subscription_history && user.subscription_history.length > 0" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-sm font-bold text-gray-900 sm:text-base">Subscription History</h2>
                </div>
                <div class="max-h-64 overflow-auto sm:max-h-80">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="sticky top-0 bg-gray-50">
                            <tr>
                                <th class="whitespace-nowrap px-3 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Date</th>
                                <th class="whitespace-nowrap px-3 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Action</th>
                                <th class="hidden whitespace-nowrap px-3 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase sm:table-cell">From</th>
                                <th class="whitespace-nowrap px-3 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">To</th>
                                <th class="hidden whitespace-nowrap px-3 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase md:table-cell">By</th>
                                <th class="hidden whitespace-nowrap px-3 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase lg:table-cell">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="history in user.subscription_history" :key="history.id" class="transition-colors hover:bg-gray-50">
                                <td class="whitespace-nowrap px-3 py-2 text-xs text-gray-900">{{ formatDate(history.created_at) }}</td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium',
                                            history.action === 'assigned' ? 'bg-blue-100 text-blue-800' : '',
                                            history.action === 'upgraded' ? 'bg-green-100 text-green-800' : '',
                                            history.action === 'renewed' ? 'bg-purple-100 text-purple-800' : '',
                                            history.action === 'cancelled' ? 'bg-red-100 text-red-800' : '',
                                        ]"
                                    >
                                        {{ history.action }}
                                    </span>
                                </td>
                                <td class="hidden whitespace-nowrap px-3 py-2 text-xs text-gray-600 sm:table-cell">{{ history.old_package?.name || '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-xs text-gray-900">{{ history.new_package?.name || '-' }}</td>
                                <td class="hidden whitespace-nowrap px-3 py-2 text-xs text-gray-600 md:table-cell">{{ history.performed_by?.name || 'System' }}</td>
                                <td class="hidden max-w-[150px] truncate px-3 py-2 text-xs text-gray-600 lg:table-cell">{{ history.notes || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Assign Modal -->
        <Teleport to="body">
            <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
                <div class="fixed inset-0 bg-black/40" @click="showAssignModal = false"></div>
                <div class="relative max-h-[85vh] w-full overflow-y-auto border border-gray-200 bg-white p-4 shadow-xl sm:max-w-md sm:rounded-lg sm:p-5">
                    <h3 class="text-sm font-bold text-gray-900 sm:text-base">Assign Subscription Package</h3>
                    <form @submit.prevent="submitAssign" class="mt-4 space-y-3">
                        <div>
                            <label for="package_id" class="block text-xs font-medium text-gray-700 sm:text-sm">Package</label>
                            <select
                                id="package_id"
                                v-model="assignForm.package_id"
                                required
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Select a package</option>
                                <option v-for="pkg in availablePackages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} - BDT {{ pkg.price }} / {{ pkg.duration }} days
                                </option>
                            </select>
                            <div v-if="assignForm.errors.package_id" class="mt-1 text-xs text-red-600">{{ assignForm.errors.package_id }}</div>
                        </div>

                        <div>
                            <label for="start_date" class="block text-xs font-medium text-gray-700 sm:text-sm">Start Date (Optional)</label>
                            <input
                                id="start_date"
                                v-model="assignForm.start_date"
                                type="datetime-local"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            />
                            <p class="mt-0.5 text-xs text-gray-500">Leave empty to start immediately</p>
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-medium text-gray-700 sm:text-sm">Notes (Optional)</label>
                            <textarea
                                id="notes"
                                v-model="assignForm.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                placeholder="Add any notes..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-200 pt-3">
                            <button
                                type="button"
                                @click="showAssignModal = false"
                                class="border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="assignForm.processing"
                                class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                {{ assignForm.processing ? 'Assigning...' : 'Assign' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Upgrade Modal -->
        <Teleport to="body">
            <div v-if="showUpgradeModal" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
                <div class="fixed inset-0 bg-black/40" @click="showUpgradeModal = false"></div>
                <div class="relative max-h-[85vh] w-full overflow-y-auto border border-gray-200 bg-white p-4 shadow-xl sm:max-w-md sm:rounded-lg sm:p-5">
                    <h3 class="text-sm font-bold text-gray-900 sm:text-base">Upgrade Subscription</h3>
                    <form @submit.prevent="submitUpgrade" class="mt-4 space-y-3">
                        <div>
                            <label for="upgrade_package_id" class="block text-xs font-medium text-gray-700 sm:text-sm">New Package</label>
                            <select
                                id="upgrade_package_id"
                                v-model="upgradeForm.package_id"
                                required
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Select a package</option>
                                <option v-for="pkg in availablePackages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} - BDT {{ pkg.price }} / {{ pkg.duration }} days
                                </option>
                            </select>
                            <div v-if="upgradeForm.errors.package_id" class="mt-1 text-xs text-red-600">{{ upgradeForm.errors.package_id }}</div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input v-model="upgradeForm.reset_counters" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                <span class="text-xs font-medium text-gray-700 sm:text-sm">Reset usage counters</span>
                            </label>
                        </div>

                        <div>
                            <label for="upgrade_notes" class="block text-xs font-medium text-gray-700 sm:text-sm">Notes (Optional)</label>
                            <textarea
                                id="upgrade_notes"
                                v-model="upgradeForm.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                placeholder="Add any notes..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-200 pt-3">
                            <button
                                type="button"
                                @click="showUpgradeModal = false"
                                class="border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="upgradeForm.processing"
                                class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                {{ upgradeForm.processing ? 'Upgrading...' : 'Upgrade' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Renew Modal -->
        <Teleport to="body">
            <div v-if="showRenewModal" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
                <div class="fixed inset-0 bg-black/40" @click="showRenewModal = false"></div>
                <div class="relative max-h-[85vh] w-full overflow-y-auto border border-gray-200 bg-white p-4 shadow-xl sm:max-w-md sm:rounded-lg sm:p-5">
                    <h3 class="text-sm font-bold text-gray-900 sm:text-base">Renew Subscription</h3>
                    <p class="mt-1 text-xs text-gray-600 sm:text-sm">
                        Package: <span class="font-semibold">{{ currentSubscription?.package.name }}</span>
                    </p>
                    <form @submit.prevent="submitRenew" class="mt-4 space-y-3">
                        <div>
                            <label class="flex items-center gap-2">
                                <input v-model="renewForm.reset_counters" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                <span class="text-xs font-medium text-gray-700 sm:text-sm">Reset usage counters</span>
                            </label>
                        </div>

                        <div>
                            <label for="renew_notes" class="block text-xs font-medium text-gray-700 sm:text-sm">Notes (Optional)</label>
                            <textarea
                                id="renew_notes"
                                v-model="renewForm.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                placeholder="Add any notes..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-200 pt-3">
                            <button
                                type="button"
                                @click="showRenewModal = false"
                                class="border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="renewForm.processing"
                                class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                {{ renewForm.processing ? 'Renewing...' : 'Renew' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Cancel Modal -->
        <Teleport to="body">
            <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
                <div class="fixed inset-0 bg-black/40" @click="showCancelModal = false"></div>
                <div class="relative max-h-[85vh] w-full overflow-y-auto border border-gray-200 bg-white p-4 shadow-xl sm:max-w-md sm:rounded-lg sm:p-5">
                    <h3 class="text-sm font-bold text-gray-900 sm:text-base">Cancel Subscription</h3>
                    <p class="mt-1 text-xs text-gray-600 sm:text-sm">
                        Package: <span class="font-semibold">{{ currentSubscription?.package.name }}</span>
                    </p>
                    <form @submit.prevent="submitCancel" class="mt-4 space-y-3">
                        <div>
                            <label class="flex items-center gap-2">
                                <input v-model="cancelForm.immediate" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                <span class="text-xs font-medium text-gray-700 sm:text-sm">Cancel immediately</span>
                            </label>
                            <p class="ml-6 mt-0.5 text-xs text-gray-500">If unchecked, continues until expiry</p>
                        </div>

                        <div>
                            <label for="cancel_notes" class="block text-xs font-medium text-gray-700 sm:text-sm">Notes (Optional)</label>
                            <textarea
                                id="cancel_notes"
                                v-model="cancelForm.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                placeholder="Reason for cancellation..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-200 pt-3">
                            <button
                                type="button"
                                @click="showCancelModal = false"
                                class="border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                Back
                            </button>
                            <button
                                type="submit"
                                :disabled="cancelForm.processing"
                                class="bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:py-2 sm:text-sm"
                            >
                                {{ cancelForm.processing ? 'Cancelling...' : 'Cancel' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
