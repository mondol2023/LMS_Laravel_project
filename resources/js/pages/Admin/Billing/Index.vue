<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileText, AlertTriangle, CheckCircle, Clock, BadgeDollarSign, Filter, CreditCard } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Invoice {
    id: number;
    remote_invoice_id: number | null;
    invoice_number: string | null;
    subtotal: string;
    discount: string;
    total: string;
    due_date: string | null;
    status: 'unpaid' | 'paid' | 'overdue' | 'cancelled';
    received_at: string | null;
    created_at: string;
}

interface Props {
    invoices: {
        data: Invoice[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
    };
    stats: {
        total_invoices: number;
        unpaid_count: number;
        overdue_count: number;
        paid_count: number;
        total_due: string;
    };
    sellerStatus: string;
    statusMessage: string;
    filters: {
        status?: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
const billing = computed(() => page.props.billingNotifications as {
    unpaid: Array<{ id: number; invoice_number: string; total: string; due_date: string }>;
    overdue: Array<{ id: number; invoice_number: string; total: string; due_date: string }>;
    total_count: number;
    grace_period_days: number;
    auto_suspend_after_days: number;
} | null);

const daysUntilSuspend = computed(() => {
    if (!billing.value || billing.value.overdue.length === 0) return null;
    const oldest = billing.value.overdue.reduce((a, b) => new Date(a.due_date) < new Date(b.due_date) ? a : b);
    const dueDate = new Date(oldest.due_date);
    const today = new Date();
    const daysOverdue = Math.floor((today.getTime() - dueDate.getTime()) / (1000 * 60 * 60 * 24));
    const totalAllowed = (billing.value.grace_period_days ?? 7) + (billing.value.auto_suspend_after_days ?? 15);
    return Math.max(totalAllowed - daysOverdue, 0);
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Billing', href: '/billing' },
];

const selectedStatus = ref(props.filters.status || '');
const payingInvoiceId = ref<number | null>(null);
const csrfToken = computed(() => (page.props as Record<string, unknown>).csrf_token as string || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

function filterByStatus(status: string) {
    selectedStatus.value = status;
    router.get('/billing', status ? { status } : {}, { preserveState: true });
}

function getStatusColor(status: string) {
    return {
        unpaid: 'bg-yellow-50 text-yellow-700 ring-yellow-200',
        paid: 'bg-green-50 text-green-700 ring-green-200',
        overdue: 'bg-red-50 text-red-700 ring-red-200',
        cancelled: 'bg-gray-50 text-gray-500 ring-gray-200',
    }[status] || 'bg-gray-50 text-gray-500 ring-gray-200';
}

function formatDate(date: string | null) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatCurrency(amount: string | number) {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0,
    }).format(Number(amount));
}
</script>

<template>
    <Head title="Billing - Invoices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen space-y-5 bg-slate-50 p-5">
            <!-- Flash Messages -->
            <div v-if="flash?.error" class="rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">{{ flash.error }}</p>
            </div>

            <!-- Seller Status Banner -->
            <div
                v-if="sellerStatus !== 'active'"
                class="rounded-2xl p-4 shadow-sm"
                :class="sellerStatus === 'suspended' ? 'bg-amber-50 ring-1 ring-amber-200' : 'bg-red-50 ring-1 ring-red-200'"
            >
                <div class="flex items-center gap-3">
                    <AlertTriangle
                        class="h-5 w-5 shrink-0"
                        :class="sellerStatus === 'suspended' ? 'text-amber-600' : 'text-red-600'"
                    />
                    <div>
                        <p class="font-semibold" :class="sellerStatus === 'suspended' ? 'text-amber-800' : 'text-red-800'">
                            Account {{ sellerStatus === 'suspended' ? 'Suspended' : 'Blocked' }}
                        </p>
                        <p class="text-sm" :class="sellerStatus === 'suspended' ? 'text-amber-600' : 'text-red-600'">
                            {{ statusMessage }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Overdue Alert -->
            <div v-if="billing && billing.overdue.length > 0 && sellerStatus === 'active'" class="rounded-2xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-red-100 p-2.5 text-red-600">
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-semibold text-red-800">Overdue Invoice{{ billing.overdue.length > 1 ? 's' : '' }}</h3>
                            <span v-if="daysUntilSuspend !== null && daysUntilSuspend > 0" class="rounded-lg bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white">
                                Account suspension in {{ daysUntilSuspend }} day{{ daysUntilSuspend !== 1 ? 's' : '' }}
                            </span>
                            <span v-else-if="daysUntilSuspend === 0" class="rounded-lg bg-red-700 px-2 py-0.5 text-[11px] font-bold text-white animate-pulse">
                                Suspension imminent
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-red-600">
                            You have {{ billing.overdue.length }} overdue invoice{{ billing.overdue.length > 1 ? 's' : '' }}.
                            Grace period: {{ billing.grace_period_days }} days, auto-suspend after {{ billing.auto_suspend_after_days }} additional days.
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <div v-for="inv in billing.overdue" :key="inv.id" class="flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-sm ring-1 ring-red-200">
                                <BadgeDollarSign class="h-3.5 w-3.5 text-red-500" />
                                <span class="font-semibold text-red-700">{{ formatCurrency(inv.total) }}</span>
                                <span class="text-red-400">-</span>
                                <span class="text-xs text-red-500">Due {{ formatDate(inv.due_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unpaid Alert -->
            <div v-else-if="billing && billing.unpaid.length > 0 && sellerStatus === 'active'" class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-amber-100 p-2.5 text-amber-600">
                        <Clock class="h-5 w-5" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-amber-800">Unpaid Invoice{{ billing.unpaid.length > 1 ? 's' : '' }}</h3>
                        <p class="mt-0.5 text-sm text-amber-600">
                            You have {{ billing.unpaid.length }} unpaid invoice{{ billing.unpaid.length > 1 ? 's' : '' }}.
                            Please make payment before the due date. Grace period: {{ billing.grace_period_days }} days after due date.
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <div v-for="inv in billing.unpaid" :key="inv.id" class="flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-sm ring-1 ring-amber-200">
                                <BadgeDollarSign class="h-3.5 w-3.5 text-amber-500" />
                                <span class="font-semibold text-amber-700">{{ formatCurrency(inv.total) }}</span>
                                <span class="text-amber-400">-</span>
                                <span class="text-xs text-amber-500">Due {{ formatDate(inv.due_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.total_invoices }}</p>
                            <p class="text-sm text-gray-500">Total Invoices</p>
                        </div>
                        <div class="rounded-xl bg-blue-100 p-3 text-blue-600">
                            <FileText class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-blue-500 to-blue-400"></div>
                </div>

                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-yellow-600">{{ stats.unpaid_count }}</p>
                            <p class="text-sm text-gray-500">Unpaid</p>
                        </div>
                        <div class="rounded-xl bg-yellow-100 p-3 text-yellow-600">
                            <Clock class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-yellow-500 to-yellow-400"></div>
                </div>

                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-red-600">{{ stats.overdue_count }}</p>
                            <p class="text-sm text-gray-500">Overdue</p>
                        </div>
                        <div class="rounded-xl bg-red-100 p-3 text-red-600">
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-red-500 to-rose-400"></div>
                </div>

                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(stats.total_due) }}</p>
                            <p class="text-sm text-gray-500">Total Due</p>
                        </div>
                        <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600">
                            <BadgeDollarSign class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-emerald-500 to-teal-400"></div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">Invoices</h3>

                    <!-- Status Filter -->
                    <div class="flex items-center gap-2">
                        <Filter class="h-4 w-4 text-gray-400" />
                        <button
                            v-for="status in ['', 'unpaid', 'overdue', 'paid', 'cancelled']"
                            :key="status"
                            @click="filterByStatus(status)"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="selectedStatus === status
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                            {{ status || 'All' }}
                        </button>
                    </div>
                </div>

                <div v-if="invoices.data.length === 0" class="py-12 text-center">
                    <FileText class="mx-auto mb-3 h-10 w-10 text-gray-300" />
                    <p class="text-sm text-gray-400">No invoices found</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                                <th class="pb-3">Invoice #</th>
                                <th class="pb-3 text-right">Regular</th>
                                <th class="pb-3 text-right">Discount</th>
                                <th class="pb-3 text-right">Payable</th>
                                <th class="pb-3 text-center">Status</th>
                                <th class="pb-3">Due Date</th>
                                <th class="pb-3">Received</th>
                                <th class="pb-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="group hover:bg-gray-50/80">
                                <td class="py-3">
                                    <Link
                                        :href="`/billing/${invoice.id}`"
                                        class="font-medium text-indigo-600 underline underline-offset-2 hover:text-indigo-800"
                                    >
                                        {{ invoice.invoice_number || `#${invoice.id}` }}
                                    </Link>
                                </td>
                                <td class="py-3 text-right text-gray-500">
                                    <span v-if="Number(invoice.discount) > 0" class="line-through">{{ formatCurrency(invoice.subtotal) }}</span>
                                    <span v-else>{{ formatCurrency(invoice.subtotal || invoice.total) }}</span>
                                </td>
                                <td class="py-3 text-right">
                                    <span v-if="Number(invoice.discount) > 0" class="text-green-600 font-medium">-{{ formatCurrency(invoice.discount) }}</span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="py-3 text-right font-semibold text-gray-800">
                                    {{ formatCurrency(invoice.total) }}
                                </td>
                                <td class="py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
                                        :class="getStatusColor(invoice.status)"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="py-3 text-gray-500">
                                    {{ formatDate(invoice.due_date) }}
                                </td>
                                <td class="py-3 text-gray-500">
                                    {{ formatDate(invoice.received_at || invoice.created_at) }}
                                </td>
                                <td class="py-3 text-center">
                                    <form
                                        v-if="invoice.status === 'unpaid' || invoice.status === 'overdue'"
                                        :action="`/billing/${invoice.id}/pay/bkash`"
                                        method="POST"
                                        class="inline"
                                        @submit="payingInvoiceId = invoice.id"
                                    >
                                        <input type="hidden" name="_token" :value="csrfToken" />
                                        <button
                                            type="submit"
                                            :disabled="payingInvoiceId === invoice.id"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:shadow-md disabled:opacity-50"
                                            style="background: linear-gradient(135deg, #E2136E, #A4004F)"
                                        >
                                            <CreditCard class="h-3.5 w-3.5" />
                                            {{ payingInvoiceId === invoice.id ? 'Redirecting...' : 'Pay' }}
                                        </button>
                                    </form>
                                    <span v-else-if="invoice.status === 'paid'" class="inline-flex items-center gap-1 text-xs text-green-600">
                                        <CheckCircle class="h-3.5 w-3.5" />
                                        Paid
                                    </span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.last_page > 1" class="mt-4 flex items-center justify-center gap-1">
                    <template v-for="link in invoices.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="link.active
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded-lg px-3 py-1.5 text-sm text-gray-300"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
