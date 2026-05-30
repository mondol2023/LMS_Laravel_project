<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    payments: Object,
    stats: Object,
    filter: String,
    search: String,
    selectedPackage: String,
    packages: Array,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payment History',
        href: dashboard().url,
    },
];

// Modal states
const showRenewModal = ref(false);
const showPayRestModal = ref(false);
const showDetailsModal = ref(false);
const paymentToRenew = ref(null);
const paymentToPay = ref(null);
const paymentDetails = ref(null);

// Search and filter states
const searchQuery = ref(props.search || '');
const packageFilter = ref(props.selectedPackage || '');

const paymentForm = ref({
    payment_type: 'full',
    paid_amount: '',
    expiry_date: '',
    due_date: '',
    payment_method: 'bKash',
    note: '',
});

const filterPayments = (filterValue: string) => {
    router.get(
        '/payment-history',
        {
            filter: filterValue,
            search: searchQuery.value,
            package: packageFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const applyFilters = () => {
    router.get(
        '/payment-history',
        {
            filter: props.filter,
            search: searchQuery.value,
            package: packageFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const openDetailsModal = (payment) => {
    paymentDetails.value = payment;
    showDetailsModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    paymentDetails.value = null;
};

const formatDate = (date: string) => {
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

const getStatusBadge = (payment: any) => {
    // Check if payment is overdue
    const isDue = payment.due_amount > 0 && payment.due_date && new Date(payment.due_date) < new Date();

    if (isDue) {
        return { text: 'Due', class: 'bg-red-100 text-red-800' };
    } else if (payment.payment_status === 'paid') {
        return { text: 'Paid', class: 'bg-green-100 text-green-800' };
    } else if (payment.payment_status === 'partial') {
        return { text: 'Partial', class: 'bg-amber-100 text-amber-800' };
    }
    return { text: 'Unknown', class: 'bg-gray-100 text-gray-800' };
};

// Renew Modal
const openRenewModal = (payment) => {
    paymentToRenew.value = payment;

    // Calculate new expiry date: current expiry + package duration (in months)
    let newExpiryDate = '';
    if (payment.subscription?.expires_at && payment.package?.duration) {
        const currentExpiry = new Date(payment.subscription.expires_at);
        const durationMonths = payment.package.duration;

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
    paymentToRenew.value = null;
};

const confirmRenew = () => {
    if (!paymentToRenew.value) return;

    const payload = {
        user_id: paymentToRenew.value.user.id,
        package_id: paymentToRenew.value.package.id,
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

// Pay Rest Modal
const openPayRestModal = (payment) => {
    paymentToPay.value = payment;

    // Auto-fill expiry date: subscribed_at + full package duration
    let expiryDate = '';
    if (payment.subscription?.subscribed_at && payment.package?.duration) {
        const subscribedAt = new Date(payment.subscription.subscribed_at);
        const durationMonths = payment.package.duration;

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
    paymentToPay.value = null;
};

const confirmPayRest = () => {
    if (!paymentToPay.value) return;

    const payload = {
        user_id: paymentToPay.value.user.id,
        package_id: paymentToPay.value.package.id,
        payment_type: 'full',
        paid_amount: paymentForm.value.paid_amount,
        expiry_date: paymentForm.value.expiry_date,
        payment_method: paymentForm.value.payment_method || null,
        note: paymentForm.value.note || 'Rest payment',
    };

    router.post('/subscriptions/users', payload, {
        preserveScroll: true,
        onSuccess: () => {
            closePayRestModal();
        },
        onError: (errors) => {
            console.error('Payment failed:', errors);
            alert('Failed to process payment. Please check all fields and try again.');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Payment History</h1>
                    <p class="mt-1 text-sm text-gray-500">Track all subscription payments and dues</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Earned -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Earned</p>
                            <p class="text-2xl font-bold text-gray-900">BDT {{ stats.total_earned || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Paid Count -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Paid</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.paid_count || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Partial Count -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Partial</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.partial_count || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Due Amount -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Due</p>
                            <p class="text-2xl font-bold text-gray-900">BDT {{ stats.total_due || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Search -->
                <div class="lg:col-span-1">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by name or phone..."
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 pl-10 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Package Filter -->
                <div class="lg:col-span-1">
                    <select
                        v-model="packageFilter"
                        @change="applyFilters"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Packages</option>
                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                    </select>
                </div>

                <!-- Filter Tabs -->
                <div class="lg:col-span-1 flex gap-2 rounded-lg border border-gray-200 bg-gray-50 p-1">
                    <button
                        @click="filterPayments('all')"
                        :class="[
                            'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            !filter || filter === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        All
                    </button>
                    <button
                        @click="filterPayments('paid')"
                        :class="[
                            'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            filter === 'paid' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Paid
                    </button>
                    <button
                        @click="filterPayments('partial')"
                        :class="[
                            'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            filter === 'partial' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Partial
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="payments.data.length === 0" class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">No payments found</h3>
                <p class="mt-1 text-sm text-gray-500">No payment records match the selected filter.</p>
            </div>

            <!-- Payments Table (Compact) -->
            <div v-else class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Package</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Payment Date</th>
                                <th class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-700"
                                        >
                                            {{ payment.user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ payment.user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ payment.user.phone || payment.user.roll_number }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ payment.package.name }}</div>
                                    <div class="text-xs text-gray-500">{{ formatDuration(payment.package.duration) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', getStatusBadge(payment).class]">
                                        {{ getStatusBadge(payment).text }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-green-600">BDT {{ payment.paid_amount }}</div>
                                    <div v-if="payment.due_amount > 0" class="text-xs text-red-600">
                                        Due: BDT {{ payment.due_amount }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap text-gray-900">
                                    {{ formatDate(payment.payment_date) }}
                                </td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Details Eye Button -->
                                        <button
                                            @click="openDetailsModal(payment)"
                                            class="text-gray-500 hover:text-gray-900"
                                            title="View Details"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Renew button for paid status -->
                                        <button
                                            v-if="payment.payment_status === 'paid'"
                                            @click="openRenewModal(payment)"
                                            class="text-green-600 hover:text-green-900 font-medium"
                                            title="Renew Package"
                                        >
                                            Renew
                                        </button>

                                        <!-- Pay button for partial status -->
                                        <button
                                            v-if="payment.payment_status === 'partial' && payment.due_amount > 0"
                                            @click="openPayRestModal(payment)"
                                            class="text-blue-600 hover:text-blue-900 font-medium"
                                            title="Pay Rest Amount"
                                        >
                                            Pay
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="payments.data.length > 0 && payments.links.length > 3" class="mt-6 flex justify-center">
                <div class="flex gap-1">
                    <template v-for="link in payments.links" :key="link.label">
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

        <!-- Renew Package Modal -->
        <Teleport to="body">
            <div v-if="showRenewModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                <div class="fixed inset-0 bg-black/40" @click="closeRenewModal"></div>
                <div class="relative my-8 w-full max-w-lg border border-gray-200 bg-white shadow-xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Renew Package</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Renew <span class="font-semibold">{{ paymentToRenew?.package?.name }}</span> for <span class="font-semibold">{{ paymentToRenew?.user?.name }}</span>
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
                            <p v-if="paymentToRenew?.package && paymentForm.payment_type === 'full'" class="mt-2 text-xs text-gray-500">
                                User will pay <strong>BDT {{ paymentToRenew.package.price }}</strong> for <strong>{{ formatDuration(paymentToRenew.package.duration) }}</strong>
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
                                        :max="paymentToRenew?.package?.price"
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
                                        :max="paymentToRenew?.package?.duration ? getMaxExpiryDate(paymentToRenew.package.duration) : ''"
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
                            Complete payment for <span class="font-semibold">{{ paymentToPay?.user?.name }}</span>
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <!-- Payment Summary -->
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <h4 class="text-sm font-semibold text-gray-900">Payment Summary</h4>
                            <div class="mt-2 space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Package:</span>
                                    <strong class="ml-2 text-gray-900">{{ paymentToPay?.package?.name }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Package Price:</span>
                                    <strong class="ml-2 text-gray-900">BDT {{ paymentToPay?.package_price }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Already Paid:</span>
                                    <strong class="ml-2 text-green-600">BDT {{ paymentToPay?.paid_amount }}</strong>
                                </div>
                                <div class="flex justify-between border-t border-blue-200 pt-1">
                                    <span class="text-gray-600">Due Amount:</span>
                                    <strong class="ml-2 text-red-600">BDT {{ paymentToPay?.due_amount }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Due Date:</span>
                                    <strong class="ml-2 text-gray-900">{{ formatDate(paymentToPay?.due_date) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Paid Amount (read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount to Pay (BDT)</label>
                            <input
                                v-model="paymentForm.paid_amount"
                                type="number"
                                readonly
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900"
                            />
                        </div>

                        <!-- Expiry Date (auto-filled) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">New Expiry Date</label>
                            <input
                                v-model="paymentForm.expiry_date"
                                type="date"
                                readonly
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Will be upgraded to full package duration ({{ formatDuration(paymentToPay?.package?.duration) }})
                            </p>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="pay_payment_method" class="block text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
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
                            Complete Payment
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Payment Details Modal -->
        <Teleport to="body">
            <div v-if="showDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
                <div class="fixed inset-0 bg-black/40" @click="closeDetailsModal"></div>
                <div class="relative my-8 w-full max-w-2xl border border-gray-200 bg-white shadow-xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Payment Details</h3>
                        <p class="mt-1 text-sm text-gray-600">Complete payment information</p>
                    </div>

                    <!-- Content -->
                    <div class="max-h-[70vh] overflow-y-auto p-6">
                        <div class="space-y-6">
                            <!-- User Information -->
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">User Information</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Name:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails?.user?.name }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Roll Number:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails?.user?.roll_number }}</p>
                                    </div>
                                    <div v-if="paymentDetails?.user?.phone">
                                        <span class="text-gray-600">Phone:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails.user.phone }}</p>
                                    </div>
                                    <div v-if="paymentDetails?.user?.email">
                                        <span class="text-gray-600">Email:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails.user.email }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Package Information -->
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Package Information</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Package Name:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails?.package?.name }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Duration:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ formatDuration(paymentDetails?.package?.duration) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Package Price:</span>
                                        <p class="font-medium text-gray-900 mt-1">BDT {{ paymentDetails?.package_price }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Status:</span>
                                        <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold mt-1', getStatusBadge(paymentDetails).class]">
                                            {{ getStatusBadge(paymentDetails).text }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information -->
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Payment Information</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Paid Amount:</span>
                                        <p class="font-medium text-green-600 mt-1">BDT {{ paymentDetails?.paid_amount }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Due Amount:</span>
                                        <p :class="['font-medium mt-1', paymentDetails?.due_amount > 0 ? 'text-red-600' : 'text-gray-400']">
                                            {{ paymentDetails?.due_amount > 0 ? `BDT ${paymentDetails.due_amount}` : 'N/A' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Payment Date:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ formatDate(paymentDetails?.payment_date) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Due Date:</span>
                                        <p class="font-medium text-gray-900 mt-1">
                                            {{ paymentDetails?.due_date ? formatDate(paymentDetails.due_date) : 'N/A' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Expiry Date:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ formatDate(paymentDetails?.expiry_date) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Payment Method:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails?.payment_method || '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Additional Information</h4>
                                <div class="space-y-3 text-sm">
                                    <div>
                                        <span class="text-gray-600">Assigned By:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails?.assigned_by?.name }}</p>
                                    </div>
                                    <div v-if="paymentDetails?.transaction_note">
                                        <span class="text-gray-600">Transaction Note:</span>
                                        <p class="font-medium text-gray-900 mt-1">{{ paymentDetails.transaction_note }}</p>
                                    </div>
                                    <div v-if="paymentDetails?.subscription">
                                        <span class="text-gray-600">Subscription Status:</span>
                                        <p class="font-medium text-gray-900 mt-1">
                                            {{ paymentDetails.subscription.is_active ? 'Active' : 'Inactive' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            @click="closeDetailsModal"
                            class="bg-gray-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
