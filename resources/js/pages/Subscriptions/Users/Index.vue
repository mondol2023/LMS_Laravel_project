<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Object,
    packages: Array,
    selectedPackage: [String, Number],
    search: String,
    filter: String,
});

const showAssignModal = ref(false);
const showBulkAssignModal = ref(false);
const showRemoveModal = ref(false);
const showBulkRemoveModal = ref(false);
const showAssignConfirmModal = ref(false);
const showChangeConfirmModal = ref(false);
const showRenewModal = ref(false);
const showPayRestModal = ref(false);
const selectedUser = ref(null);
const selectedPackageId = ref(props.selectedPackage || '');
const bulkPackageId = ref(props.selectedPackage || '');
const selectedUsers = ref<number[]>([]);
const subscriptionToRemove = ref(null);
const userToAssignOrChange = ref(null);
const userToRenew = ref(null);
const userToPay = ref(null);

// Payment form data
const paymentForm = ref({
    payment_type: 'full',
    paid_amount: '',
    expiry_date: '',
    due_date: '',
    payment_method: 'bKash',
    note: '',
});

const allSelected = computed({
    get: () => props.users.data.length > 0 && selectedUsers.value.length === props.users.data.length,
    set: (value) => {
        selectedUsers.value = value ? props.users.data.map((u) => u.id) : [];
    },
});

const selectedPackageInfo = computed(() => {
    if (!props.selectedPackage) return null;
    return props.packages.find(pkg => pkg.id == props.selectedPackage);
});

const currentlySelectedPackage = computed(() => {
    if (!selectedPackageId.value) return null;
    return props.packages.find(pkg => pkg.id == selectedPackageId.value);
});

const hasSelectedPackage = computed(() => !!props.selectedPackage);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscription Users',
        href: dashboard().url,
    },
];

const searchUsers = (event) => {
    router.get('/subscriptions/users', {
        search: event.target.value,
        package: props.selectedPackage,
        filter: props.filter,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filterUsers = (filterValue) => {
    selectedUsers.value = [];
    router.get('/subscriptions/users', {
        search: props.search,
        package: props.selectedPackage,
        filter: filterValue,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const isAssignedTab = computed(() => props.filter === 'assigned');
const isAllTab = computed(() => !props.filter || props.filter === 'all');

const toggleUserSelection = (userId) => {
    const index = selectedUsers.value.indexOf(userId);
    if (index > -1) {
        selectedUsers.value.splice(index, 1);
    } else {
        selectedUsers.value.push(userId);
    }
};

const openAssignConfirmModal = (user) => {
    userToAssignOrChange.value = user;
    // Reset payment form
    paymentForm.value = {
        payment_type: 'full',
        paid_amount: '',
        expiry_date: '',
        due_date: '',
        payment_method: 'bKash',
        note: '',
    };
    showAssignConfirmModal.value = true;
};

const openChangeConfirmModal = (user) => {
    userToAssignOrChange.value = user;
    // Reset payment form
    paymentForm.value = {
        payment_type: 'full',
        paid_amount: '',
        expiry_date: '',
        due_date: '',
        payment_method: 'bKash',
        note: '',
    };
    showChangeConfirmModal.value = true;
};

const closeAssignConfirmModal = () => {
    showAssignConfirmModal.value = false;
    userToAssignOrChange.value = null;
};

const closeChangeConfirmModal = () => {
    showChangeConfirmModal.value = false;
    userToAssignOrChange.value = null;
};

const confirmAssignUser = () => {
    if (!props.selectedPackage || !userToAssignOrChange.value) return;

    const payload = {
        user_id: userToAssignOrChange.value.id,
        package_id: props.selectedPackage,
        payment_type: paymentForm.value.payment_type,
        payment_method: paymentForm.value.payment_method || null,
        note: paymentForm.value.note || null,
    };

    // Add partial payment fields if applicable
    if (paymentForm.value.payment_type === 'partial') {
        payload.paid_amount = paymentForm.value.paid_amount;
        payload.expiry_date = paymentForm.value.expiry_date;
        payload.due_date = paymentForm.value.due_date;
    }

    router.post('/subscriptions/users', payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeAssignConfirmModal();
        },
    });
};

const confirmChangeUser = () => {
    if (!props.selectedPackage || !userToAssignOrChange.value) return;

    const payload = {
        user_id: userToAssignOrChange.value.id,
        package_id: props.selectedPackage,
        payment_type: paymentForm.value.payment_type,
        payment_method: paymentForm.value.payment_method || null,
        note: paymentForm.value.note || null,
    };

    // Add partial payment fields if applicable
    if (paymentForm.value.payment_type === 'partial') {
        payload.paid_amount = paymentForm.value.paid_amount;
        payload.expiry_date = paymentForm.value.expiry_date;
        payload.due_date = paymentForm.value.due_date;
    }

    router.post('/subscriptions/users', payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeChangeConfirmModal();
        },
    });
};

const openBulkAssignModal = () => {
    if (selectedUsers.value.length === 0) {
        return;
    }
    bulkPackageId.value = props.selectedPackage || '';
    showBulkAssignModal.value = true;
};

const closeBulkAssignModal = () => {
    showBulkAssignModal.value = false;
    bulkPackageId.value = props.selectedPackage || '';
};

const bulkAssignPackage = () => {
    if (!bulkPackageId.value || selectedUsers.value.length === 0) return;

    router.post('/subscriptions/users/bulk-assign', {
        user_ids: selectedUsers.value,
        package_id: bulkPackageId.value,
    }, {
        onSuccess: () => {
            closeBulkAssignModal();
            selectedUsers.value = [];
        },
    });
};


const openAssignModal = (user) => {
    selectedUser.value = user;
    selectedPackageId.value = user.subscription?.package.id || props.selectedPackage || '';
    // Reset payment form
    paymentForm.value = {
        payment_type: 'full',
        paid_amount: '',
        expiry_date: '',
        due_date: '',
        payment_method: 'bKash',
        note: '',
    };
    showAssignModal.value = true;
};

const closeAssignModal = () => {
    showAssignModal.value = false;
    selectedUser.value = null;
    selectedPackageId.value = props.selectedPackage || '';
    paymentForm.value = {
        payment_type: 'full',
        paid_amount: '',
        expiry_date: '',
        due_date: '',
        payment_method: 'bKash',
        note: '',
    };
};

const assignPackage = () => {
    if (!selectedUser.value || !selectedPackageId.value) return;

    const payload = {
        user_id: selectedUser.value.id,
        package_id: selectedPackageId.value,
        payment_type: paymentForm.value.payment_type,
        payment_method: paymentForm.value.payment_method || null,
        note: paymentForm.value.note || null,
    };

    // Add partial payment fields if applicable
    if (paymentForm.value.payment_type === 'partial') {
        payload.paid_amount = paymentForm.value.paid_amount;
        payload.expiry_date = paymentForm.value.expiry_date;
        payload.due_date = paymentForm.value.due_date;
    }

    router.post('/subscriptions/users', payload, {
        onSuccess: () => {
            closeAssignModal();
        },
    });
};

const openRemoveModal = (subscription) => {
    subscriptionToRemove.value = subscription;
    showRemoveModal.value = true;
};

const closeRemoveModal = () => {
    showRemoveModal.value = false;
    subscriptionToRemove.value = null;
};

const confirmRemoveSubscription = () => {
    if (!subscriptionToRemove.value) return;

    router.delete(`/subscriptions/users/${subscriptionToRemove.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeRemoveModal();
        },
    });
};

const openBulkRemoveModal = () => {
    if (selectedUsers.value.length === 0) {
        return;
    }
    showBulkRemoveModal.value = true;
};

const closeBulkRemoveModal = () => {
    showBulkRemoveModal.value = false;
};

const confirmBulkRemove = () => {
    router.post('/subscriptions/users/bulk-remove', {
        user_ids: selectedUsers.value,
    }, {
        onSuccess: () => {
            selectedUsers.value = [];
            closeBulkRemoveModal();
        },
        preserveScroll: true,
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDuration = (months: number) => {
    return months === 1 ? '1 month' : `${months} months`;
};

const getMaxExpiryDate = (durationMonths: number) => {
    const maxDate = new Date();
    maxDate.setMonth(maxDate.getMonth() + durationMonths);
    return maxDate.toISOString().split('T')[0];
};

const getStatusBadge = (user) => {
    if (!user.subscription) {
        return { text: 'No Subscription', class: 'bg-gray-100 text-gray-800' };
    }
    if (user.subscription.is_active) {
        return { text: 'Active', class: 'bg-green-100 text-green-800' };
    }
    return { text: 'Expired', class: 'bg-red-100 text-red-800' };
};

const getPaymentTypeBadge = (payment) => {
    if (!payment) return { text: 'N/A', class: 'bg-gray-100 text-gray-800' };

    if (payment.payment_status === 'paid') {
        return { text: 'Paid', class: 'bg-green-100 text-green-800' };
    } else if (payment.payment_status === 'partial') {
        return { text: 'Partial', class: 'bg-amber-100 text-amber-800' };
    }
    return { text: 'Unknown', class: 'bg-gray-100 text-gray-800' };
};

const openRenewModal = (user) => {
    userToRenew.value = user;

    // Calculate new expiry date: current expiry + package duration (in months)
    let newExpiryDate = '';
    if (user.subscription?.expires_at && user.subscription?.package?.duration) {
        const currentExpiry = new Date(user.subscription.expires_at);
        const durationMonths = user.subscription.package.duration;

        // Add duration in months to current expiry
        currentExpiry.setMonth(currentExpiry.getMonth() + durationMonths);
        newExpiryDate = currentExpiry.toISOString().split('T')[0];
    }

    paymentForm.value = {
        payment_type: 'full',
        paid_amount: '',
        expiry_date: newExpiryDate,
        due_date: '',
        payment_method: 'bKash',
        note: '',
    };
    showRenewModal.value = true;
};

const closeRenewModal = () => {
    showRenewModal.value = false;
    userToRenew.value = null;
};

const confirmRenew = () => {
    if (!userToRenew.value) return;

    const payload = {
        user_id: userToRenew.value.id,
        package_id: userToRenew.value.subscription.package.id,
        payment_type: paymentForm.value.payment_type,
        payment_method: paymentForm.value.payment_method || null,
        note: paymentForm.value.note || 'Package renewal',
        expiry_date: paymentForm.value.expiry_date, // Always send calculated expiry date
    };

    // Add partial payment fields if applicable
    if (paymentForm.value.payment_type === 'partial') {
        payload.paid_amount = paymentForm.value.paid_amount;
        payload.due_date = paymentForm.value.due_date;
    }

    router.post('/subscriptions/users', payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeRenewModal();
        },
        onError: (errors) => {
            console.error('Renewal failed:', errors);
            alert('Failed to renew package. Please check all fields and try again.');
        },
    });
};

const openPayRestModal = (user) => {
    userToPay.value = user;
    const payment = user.subscription?.payment;

    // Auto-fill expiry date: subscribed_at + full package duration
    let expiryDate = '';
    if (user.subscription?.subscribed_at && user.subscription?.package?.duration) {
        const subscribedAt = new Date(user.subscription.subscribed_at);
        const durationMonths = user.subscription.package.duration;

        subscribedAt.setMonth(subscribedAt.getMonth() + durationMonths);
        expiryDate = subscribedAt.toISOString().split('T')[0];
    }

    paymentForm.value = {
        payment_type: 'full',
        paid_amount: payment?.due_amount || '',
        expiry_date: expiryDate,
        due_date: '',
        payment_method: 'bKash',
        note: '',
    };
    showPayRestModal.value = true;
};

const closePayRestModal = () => {
    showPayRestModal.value = false;
    userToPay.value = null;
};

const confirmPayRest = () => {
    if (!userToPay.value || !userToPay.value.subscription?.payment) return;

    const payment = userToPay.value.subscription.payment;

    router.post(`/payment-history/${payment.id}/add-payment`, {
        amount: paymentForm.value.paid_amount,
        payment_method: paymentForm.value.payment_method,
        payment_date: new Date().toISOString().split('T')[0],
        new_expiry_date: paymentForm.value.expiry_date,
        note: paymentForm.value.note || 'Rest amount payment',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closePayRestModal();
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="h-full overflow-auto px-4 py-4 sm:px-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Subscription Users</h1>
                    <p v-if="selectedPackage && selectedPackageInfo" class="mt-0.5 text-sm text-gray-900">
                        Package: <span class="font-semibold">{{ selectedPackageInfo.name }}</span>
                        <span class="text-gray-500 ml-2">BDT {{ selectedPackageInfo.price }} / {{ formatDuration(selectedPackageInfo.duration) }}</span>
                    </p>
                </div>
                <Link
                    href="/subscriptions/packages"
                    class="inline-flex items-center gap-2 border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Packages
                </Link>
            </div>

            <!-- Filters -->
            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <!-- Search -->
                <div class="relative flex-1 sm:max-w-xs">
                    <input
                        type="text"
                        :value="search"
                        @input="searchUsers"
                        placeholder="Search users..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-1.5 pl-9 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                    <svg class="absolute left-3 top-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filter Tabs -->
                <div v-if="selectedPackage" class="flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                    <button
                        @click="filterUsers('assigned')"
                        :class="[
                            'rounded-md px-3 py-1 text-sm font-medium transition-colors',
                            filter === 'assigned' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Package User
                    </button>
                    <button
                        @click="filterUsers('all')"
                        :class="[
                            'rounded-md px-3 py-1 text-sm font-medium transition-colors',
                            (!filter || filter === 'all') ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Assign Users
                    </button>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div v-if="selectedUsers.length > 0 && selectedPackage" class="mt-3 flex items-center justify-between rounded-lg bg-blue-50 px-4 py-2">
                <span class="text-sm font-medium text-blue-900">{{ selectedUsers.length }} user(s) selected</span>
                <div class="flex items-center gap-2">
                    <button
                        v-if="isAllTab"
                        @click="openBulkAssignModal"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        Bulk Assign
                    </button>
                    <button
                        v-if="isAssignedTab"
                        @click="openBulkRemoveModal"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-red-700"
                    >
                        Bulk Remove
                    </button>
                    <button
                        @click="selectedUsers = []"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="users.data.length === 0" class="py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-3 text-sm font-semibold text-gray-900">No users found</h3>
                <p class="text-sm text-gray-500">Try adjusting your search criteria.</p>
            </div>

            <!-- Users Table -->
            <div v-else class="mt-3 overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-10 px-3 py-2 text-left">
                                <input
                                    type="checkbox"
                                    v-model="allSelected"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Current Package</th>
                            <th v-if="isAssignedTab || (selectedPackage && isAllTab)" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subscription Info</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50" :class="{ 'bg-blue-50': selectedUsers.includes(user.id) }">
                            <td class="w-10 whitespace-nowrap px-3 py-2 text-left">
                                <input
                                    type="checkbox"
                                    :checked="selectedUsers.includes(user.id)"
                                    @change="toggleUserSelection(user.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <div class="flex items-center">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-700">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        <div class="text-xs text-gray-500">{{ user.email }}</div>
                                        <div class="text-xs text-gray-500">{{ user.phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <div v-if="user.subscription" class="text-sm font-medium text-gray-900">
                                    {{ user.subscription.package.name }}
                                </div>
                                <div v-else class="text-sm text-gray-400">No package assigned</div>
                            </td>
                            <td v-if="isAssignedTab || (selectedPackage && isAllTab)" class="whitespace-nowrap px-3 py-2">
                                <div v-if="user.subscription" class="text-xs">
                                    <span
                                        v-if="user.subscription?.payment"
                                        :class="[
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                            getPaymentTypeBadge(user.subscription.payment).class,
                                        ]"
                                    >
                                        {{ getPaymentTypeBadge(user.subscription.payment).text }}
                                    </span>
                                    <div class="text-gray-600">{{ formatDate(user.subscription.subscribed_at) }}</div>
                                    <div class="text-gray-600">{{ formatDate(user.subscription.expires_at) }}</div>
                                    <div class="text-gray-500">{{ user.subscription.days_remaining }} days left</div>
                                </div>
                                <div v-else class="text-sm text-gray-400">-</div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Package context: All tab -->
                                    <template v-if="selectedPackage && isAllTab">
                                        <button
                                            v-if="user.is_assigned_to_selected_package"
                                            @click="openRemoveModal(user.subscription)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Remove
                                        </button>
                                        <button
                                            v-else-if="user.subscription"
                                            @click="openChangeConfirmModal(user)"
                                            class="text-orange-600 hover:text-orange-900"
                                        >
                                            Change
                                        </button>
                                        <button
                                            v-else
                                            @click="openAssignConfirmModal(user)"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Assign
                                        </button>
                                    </template>

                                    <!-- Package context: Assigned tab -->
                                    <template v-if="selectedPackage && isAssignedTab && user.subscription">
                                        <button
                                            v-if="user.subscription.payment?.payment_status === 'paid'"
                                            @click="openRenewModal(user)"
                                            class="text-green-600 hover:text-green-900"
                                        >
                                            Renew
                                        </button>
                                        <button
                                            v-else-if="user.subscription.payment?.payment_status === 'partial'"
                                            @click="openPayRestModal(user)"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Pay
                                        </button>
                                        <button
                                            @click="openRemoveModal(user.subscription)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Remove
                                        </button>
                                    </template>

                                    <!-- No package context -->
                                    <button
                                        v-if="!selectedPackage"
                                        @click="openAssignModal(user)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        {{ user.subscription ? 'Change' : 'Assign' }}
                                    </button>
                                    <button
                                        v-if="!selectedPackage && user.subscription?.is_active"
                                        @click="openRemoveModal(user.subscription)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.data.length > 0 && users.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1">
                    <template v-for="link in users.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1 text-sm font-medium transition-colors',
                                link.active ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-200 hover:text-gray-700',
                            ]"
                            v-html="link.label"
                        />
                        <span v-else class="cursor-not-allowed px-3 py-1 text-sm font-medium text-gray-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Assign Package Modal -->
        <Teleport to="body">
            <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                <div class="fixed inset-0 bg-black/40" @click="closeAssignModal"></div>
                <div class="relative my-8 w-full max-w-2xl border border-gray-200 bg-white shadow-xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Assign Package & Payment</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Assign package to <span class="font-semibold">{{ selectedUser?.name }}</span> and record payment details
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="max-h-[70vh] space-y-6 overflow-y-auto p-6">
                        <!-- Package Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Package <span class="text-red-500">*</span></label>
                            <div v-if="hasSelectedPackage && selectedPackageInfo && !selectedUser?.subscription" class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                                <p class="text-sm font-medium text-gray-900">{{ selectedPackageInfo.name }}</p>
                                <p class="text-xs text-gray-500">BDT {{ selectedPackageInfo.price }} / {{ formatDuration(selectedPackageInfo.duration) }}</p>
                            </div>
                            <select
                                v-else
                                v-model="selectedPackageId"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="">Select a package</option>
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} - BDT {{ pkg.price }} / {{ formatDuration(pkg.duration) }}
                                </option>
                            </select>
                        </div>

                        <!-- Payment Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="full"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Full Payment</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="partial"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Partial Payment</span>
                                </label>
                            </div>
                            <p v-if="currentlySelectedPackage && paymentForm.payment_type === 'full'" class="mt-2 text-xs text-gray-500">
                                User will pay <strong>BDT {{ currentlySelectedPackage.price }}</strong> for <strong>{{ formatDuration(currentlySelectedPackage.duration) }}</strong>
                            </p>
                        </div>

                        <!-- Partial Payment Fields -->
                        <div v-if="paymentForm.payment_type === 'partial'" class="space-y-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="paid_amount" class="block text-sm font-medium text-gray-700">
                                        Paid Amount (BDT) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="paid_amount"
                                        v-model="paymentForm.paid_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :max="currentlySelectedPackage?.price"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="500"
                                    />
                                    <p v-if="currentlySelectedPackage" class="mt-1 text-xs text-gray-500">
                                        Package price: BDT {{ currentlySelectedPackage.price }}
                                    </p>
                                </div>

                                <div>
                                    <label for="expiry_date" class="block text-sm font-medium text-gray-700">
                                        Expiry Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="expiry_date"
                                        v-model="paymentForm.expiry_date"
                                        type="date"
                                        :max="currentlySelectedPackage?.duration"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="currentlySelectedPackage" class="mt-1 text-xs text-gray-500">
                                        Max: {{ getMaxExpiryDate(currentlySelectedPackage.duration) }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700">
                                    Due Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="due_date"
                                    v-model="paymentForm.due_date"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Date when remaining payment is due
                                </p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select
                                id="payment_method"
                                v-model="paymentForm.payment_method"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Note -->
                        <div>
                            <label for="note" class="block text-sm font-medium text-gray-700">Note (Optional)</label>
                            <textarea
                                id="note"
                                v-model="paymentForm.note"
                                rows="3"
                                maxlength="500"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Add any additional notes about this payment..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">{{ paymentForm.note?.length || 0 }}/500 characters</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            @click="closeAssignModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="assignPackage"
                            :disabled="!selectedPackageId"
                            class="bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Assign & Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Bulk Assign Package Modal -->
        <Teleport to="body">
            <div v-if="showBulkAssignModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="closeBulkAssignModal"></div>
                <div class="relative w-full max-w-md border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Bulk Assign Package</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Assign <span class="font-semibold">{{ selectedPackageInfo?.name }}</span> to <span class="font-semibold">{{ selectedUsers.length }} user(s)</span>
                    </p>

                    <div class="mt-4 rounded-lg bg-yellow-50 p-3">
                        <p class="text-xs text-yellow-800">
                            <strong>Note:</strong> This will replace any existing active subscriptions for the selected users.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="closeBulkAssignModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="bulkAssignPackage"
                            class="bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            Assign to {{ selectedUsers.length }} User(s)
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Remove Subscription Modal -->
        <Teleport to="body">
            <div v-if="showRemoveModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="closeRemoveModal"></div>
                <div class="relative w-full max-w-md border border-gray-200 bg-white p-6 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Remove Subscription</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Are you sure you want to remove this subscription? This action cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="closeRemoveModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmRemoveSubscription"
                            class="bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Bulk Remove Modal -->
        <Teleport to="body">
            <div v-if="showBulkRemoveModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="closeBulkRemoveModal"></div>
                <div class="relative w-full max-w-md border border-gray-200 bg-white p-6 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Bulk Remove Subscriptions</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Are you sure you want to remove subscriptions for <span class="font-semibold">{{ selectedUsers.length }} user(s)</span>? This action cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="closeBulkRemoveModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmBulkRemove"
                            class="bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                        >
                            Remove {{ selectedUsers.length }} Subscription(s)
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Assign Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showAssignConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                <div class="fixed inset-0 bg-black/40" @click="closeAssignConfirmModal"></div>
                <div class="relative my-8 w-full max-w-lg border border-gray-200 bg-white shadow-xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Assign Package & Payment</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Assign <span class="font-semibold">{{ selectedPackageInfo?.name }}</span> to <span class="font-semibold">{{ userToAssignOrChange?.name }}</span>
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <!-- Payment Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="full"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Full Payment</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="partial"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Partial Payment</span>
                                </label>
                            </div>
                            <p v-if="selectedPackageInfo && paymentForm.payment_type === 'full'" class="mt-2 text-xs text-gray-500">
                                User will pay <strong>BDT {{ selectedPackageInfo.price }}</strong> for <strong>{{ formatDuration(selectedPackageInfo.duration) }}</strong>
                            </p>
                        </div>

                        <!-- Partial Payment Fields -->
                        <div v-if="paymentForm.payment_type === 'partial'" class="space-y-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="confirm_paid_amount" class="block text-sm font-medium text-gray-700">
                                        Paid Amount (BDT) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="confirm_paid_amount"
                                        v-model="paymentForm.paid_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :max="selectedPackageInfo?.price"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="500"
                                    />
                                    <p v-if="selectedPackageInfo" class="mt-1 text-xs text-gray-500">
                                        Package price: BDT {{ selectedPackageInfo.price }}
                                    </p>
                                </div>

                                <div>
                                    <label for="confirm_expiry_date" class="block text-sm font-medium text-gray-700">
                                        Expiry Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="confirm_expiry_date"
                                        v-model="paymentForm.expiry_date"
                                        type="date"
                                        :max="selectedPackageInfo?.duration"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="selectedPackageInfo" class="mt-1 text-xs text-gray-500">
                                        Max: {{ getMaxExpiryDate(selectedPackageInfo.duration) }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="confirm_due_date" class="block text-sm font-medium text-gray-700">
                                    Due Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="confirm_due_date"
                                    v-model="paymentForm.due_date"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Date when remaining payment is due
                                </p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="confirm_payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select
                                id="confirm_payment_method"
                                v-model="paymentForm.payment_method"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Note -->
                        <div>
                            <label for="confirm_note" class="block text-sm font-medium text-gray-700">Note (Optional)</label>
                            <textarea
                                id="confirm_note"
                                v-model="paymentForm.note"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Add any additional notes..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            @click="closeAssignConfirmModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmAssignUser"
                            class="bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            Assign & Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Change Package Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showChangeConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                <div class="fixed inset-0 bg-black/40" @click="closeChangeConfirmModal"></div>
                <div class="relative my-8 w-full max-w-lg border border-gray-200 bg-white shadow-xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Change Package & Payment</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Change <span class="font-semibold">{{ userToAssignOrChange?.name }}</span>'s package from <span class="font-semibold">{{ userToAssignOrChange?.subscription?.package?.name }}</span> to <span class="font-semibold">{{ selectedPackageInfo?.name }}</span>
                        </p>
                    </div>

                    <!-- Warning -->
                    <div class="border-b border-yellow-200 bg-yellow-50 px-6 py-3">
                        <p class="text-xs text-yellow-800">
                            <strong>Warning:</strong> This will replace the user's current subscription. All previous usage data will be reset.
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <!-- Payment Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="full"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Full Payment</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="partial"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Partial Payment</span>
                                </label>
                            </div>
                            <p v-if="selectedPackageInfo && paymentForm.payment_type === 'full'" class="mt-2 text-xs text-gray-500">
                                User will pay <strong>BDT {{ selectedPackageInfo.price }}</strong> for <strong>{{ formatDuration(selectedPackageInfo.duration) }}</strong>
                            </p>
                        </div>

                        <!-- Partial Payment Fields -->
                        <div v-if="paymentForm.payment_type === 'partial'" class="space-y-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="change_paid_amount" class="block text-sm font-medium text-gray-700">
                                        Paid Amount (BDT) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="change_paid_amount"
                                        v-model="paymentForm.paid_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :max="selectedPackageInfo?.price"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="500"
                                    />
                                    <p v-if="selectedPackageInfo" class="mt-1 text-xs text-gray-500">
                                        Package price: BDT {{ selectedPackageInfo.price }}
                                    </p>
                                </div>

                                <div>
                                    <label for="change_expiry_date" class="block text-sm font-medium text-gray-700">
                                        Expiry Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="change_expiry_date"
                                        v-model="paymentForm.expiry_date"
                                        type="date"
                                        :max="selectedPackageInfo?.duration"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="selectedPackageInfo" class="mt-1 text-xs text-gray-500">
                                        Max: {{ getMaxExpiryDate(selectedPackageInfo.duration) }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="change_due_date" class="block text-sm font-medium text-gray-700">
                                    Due Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="change_due_date"
                                    v-model="paymentForm.due_date"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Date when remaining payment is due
                                </p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="change_payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select
                                id="change_payment_method"
                                v-model="paymentForm.payment_method"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Note -->
                        <div>
                            <label for="change_note" class="block text-sm font-medium text-gray-700">Note (Optional)</label>
                            <textarea
                                id="change_note"
                                v-model="paymentForm.note"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Add any additional notes..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            @click="closeChangeConfirmModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmChangeUser"
                            class="bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700"
                        >
                            Change & Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Renew Package Modal -->
        <Teleport to="body">
            <div v-if="showRenewModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                <div class="fixed inset-0 bg-black/40" @click="closeRenewModal"></div>
                <div class="relative my-8 w-full max-w-lg border border-gray-200 bg-white shadow-xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Renew Package</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Renew <span class="font-semibold">{{ userToRenew?.subscription?.package?.name }}</span> for <span class="font-semibold">{{ userToRenew?.name }}</span>
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <!-- Payment Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="full"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Full Payment</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="paymentForm.payment_type"
                                        type="radio"
                                        value="partial"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">Partial Payment</span>
                                </label>
                            </div>
                            <p v-if="userToRenew?.subscription?.payment && paymentForm.payment_type === 'full'" class="mt-2 text-xs text-gray-500">
                                User will pay <strong>BDT {{ userToRenew.subscription.payment.package_price }}</strong>
                            </p>
                        </div>

                        <!-- Partial Payment Fields -->
                        <div v-if="paymentForm.payment_type === 'partial'" class="space-y-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="renew_paid_amount" class="block text-sm font-medium text-gray-700">
                                        Paid Amount (BDT) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="renew_paid_amount"
                                        v-model="paymentForm.paid_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :max="userToRenew?.subscription?.payment?.package_price"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="500"
                                    />
                                </div>

                                <div>
                                    <label for="renew_expiry_date" class="block text-sm font-medium text-gray-700">
                                        Expiry Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="renew_expiry_date"
                                        v-model="paymentForm.expiry_date"
                                        type="date"
                                        :max="userToRenew?.subscription?.package?.duration ? getMaxExpiryDate(userToRenew.subscription.package.duration) : ''"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="renew_due_date" class="block text-sm font-medium text-gray-700">
                                    Due Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="renew_due_date"
                                    v-model="paymentForm.due_date"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="renew_payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select
                                id="renew_payment_method"
                                v-model="paymentForm.payment_method"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Note -->
                        <div>
                            <label for="renew_note" class="block text-sm font-medium text-gray-700">Note (Optional)</label>
                            <textarea
                                id="renew_note"
                                v-model="paymentForm.note"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Add any additional notes..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            @click="closeRenewModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmRenew"
                            class="bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                        >
                            Renew Package
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Pay Rest Amount Modal -->
        <Teleport to="body">
            <div v-if="showPayRestModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                <div class="fixed inset-0 bg-black/40" @click="closePayRestModal"></div>
                <div class="relative my-8 w-full max-w-lg border border-gray-200 bg-white shadow-xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Pay Rest Amount</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Complete payment for <span class="font-semibold">{{ userToPay?.name }}</span>
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <!-- Payment Summary -->
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600">Package Price:</span>
                                    <strong class="ml-2 text-gray-900">BDT {{ userToPay?.subscription?.payment?.package_price }}</strong>
                                </div>
                                <div>
                                    <span class="text-gray-600">Already Paid:</span>
                                    <strong class="ml-2 text-green-600">BDT {{ userToPay?.subscription?.payment?.paid_amount }}</strong>
                                </div>
                                <div>
                                    <span class="text-gray-600">Due Amount:</span>
                                    <strong class="ml-2 text-red-600">BDT {{ userToPay?.subscription?.payment?.due_amount }}</strong>
                                </div>
                                <div v-if="userToPay?.subscription?.payment?.due_date">
                                    <span class="text-gray-600">Due Date:</span>
                                    <strong class="ml-2 text-gray-900">{{ formatDate(userToPay.subscription.payment.due_date) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Amount -->
                        <div>
                            <label for="pay_amount" class="block text-sm font-medium text-gray-700">
                                Payment Amount (BDT) <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="pay_amount"
                                v-model="paymentForm.paid_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="userToPay?.subscription?.payment?.due_amount"
                                required
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Maximum: BDT {{ userToPay?.subscription?.payment?.due_amount }}
                            </p>
                        </div>

                        <!-- New Expiry Date -->
                        <div>
                            <label for="pay_expiry_date" class="block text-sm font-medium text-gray-700">
                                New Expiry Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="pay_expiry_date"
                                v-model="paymentForm.expiry_date"
                                type="date"
                                required
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Extend subscription validity to package duration
                            </p>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="pay_payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select
                                id="pay_payment_method"
                                v-model="paymentForm.payment_method"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Note -->
                        <div>
                            <label for="pay_note" class="block text-sm font-medium text-gray-700">Note (Optional)</label>
                            <textarea
                                id="pay_note"
                                v-model="paymentForm.note"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Add any additional notes..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            @click="closePayRestModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmPayRest"
                            class="bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            Submit Payment
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
