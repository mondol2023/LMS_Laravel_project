<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Calendar, BadgeDollarSign, CheckCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Invoice {
    id: number;
    remote_invoice_id: number | null;
    invoice_number: string | null;
    subtotal: string;
    discount: string;
    total: string;
    due_date: string | null;
    status: 'unpaid' | 'paid' | 'overdue' | 'cancelled';
    data: Record<string, unknown> | null;
    received_at: string | null;
    created_at: string;
}

const props = defineProps<{ invoice: Invoice }>();

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
const canPay = computed(() => props.invoice.status === 'unpaid' || props.invoice.status === 'overdue');
const paying = ref(false);
const hasDiscount = computed(() => Number(props.invoice.discount) > 0);

const csrfToken = computed(() => (page.props as Record<string, unknown>).csrf_token as string || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Billing', href: '/billing' },
    { title: props.invoice.invoice_number || `Invoice #${props.invoice.id}`, href: `/billing/${props.invoice.id}` },
];

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
        month: 'long',
        day: 'numeric',
    });
}

function formatValue(value: unknown): string {
    const str = String(value);
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(str)) {
        return new Date(str).toLocaleDateString('en-IN', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        return new Date(str).toLocaleDateString('en-IN', { year: 'numeric', month: 'long', day: 'numeric' });
    }
    return str;
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
    <Head :title="`Invoice ${invoice.invoice_number || '#' + invoice.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen space-y-5 bg-slate-50 p-5">
            <!-- Back Link -->
            <Link href="/billing" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                <ArrowLeft class="h-4 w-4" />
                Back to Billing
            </Link>

            <!-- Flash Messages -->
            <div v-if="flash?.success" class="rounded-2xl border border-green-200 bg-green-50 p-4">
                <div class="flex items-center gap-3">
                    <CheckCircle class="h-5 w-5 text-green-600" />
                    <p class="text-sm font-medium text-green-800">{{ flash.success }}</p>
                </div>
            </div>
            <div v-if="flash?.error" class="rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">{{ flash.error }}</p>
            </div>

            <!-- Invoice Card -->
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="mb-6 flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ invoice.invoice_number || `Invoice #${invoice.id}` }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Received: {{ formatDate(invoice.received_at || invoice.created_at) }}
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium capitalize ring-1 ring-inset"
                        :class="getStatusColor(invoice.status)"
                    >
                        {{ invoice.status }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-6" :class="hasDiscount ? 'sm:grid-cols-4' : 'sm:grid-cols-3'">
                    <!-- Regular Price -->
                    <div v-if="hasDiscount" class="flex items-start gap-3 rounded-xl bg-gray-50 p-4">
                        <div class="rounded-lg bg-gray-200 p-2 text-gray-500">
                            <BadgeDollarSign class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Regular Price</p>
                            <p class="text-lg font-bold text-gray-400 line-through">{{ formatCurrency(invoice.subtotal) }}</p>
                        </div>
                    </div>

                    <!-- Discount -->
                    <div v-if="hasDiscount" class="flex items-start gap-3 rounded-xl bg-green-50 p-4 ring-1 ring-green-200">
                        <div class="rounded-lg bg-green-100 p-2 text-green-600">
                            <BadgeDollarSign class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-green-600">Discount</p>
                            <p class="text-lg font-bold text-green-700">-{{ formatCurrency(invoice.discount) }}</p>
                        </div>
                    </div>

                    <!-- Payable Amount -->
                    <div class="flex items-start gap-3 rounded-xl p-4" :class="hasDiscount ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'bg-gray-50'">
                        <div class="rounded-lg p-2" :class="hasDiscount ? 'bg-indigo-100 text-indigo-600' : 'bg-indigo-100 text-indigo-600'">
                            <BadgeDollarSign class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-medium" :class="hasDiscount ? 'text-indigo-600' : 'text-gray-500'">{{ hasDiscount ? 'Payable Amount' : 'Amount' }}</p>
                            <p class="text-lg font-bold" :class="hasDiscount ? 'text-indigo-700' : 'text-gray-900'">{{ formatCurrency(invoice.total) }}</p>
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-4">
                        <div class="rounded-lg bg-amber-100 p-2 text-amber-600">
                            <Calendar class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Due Date</p>
                            <p class="text-lg font-bold text-gray-900">{{ formatDate(invoice.due_date) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pay with bKash -->
                <div v-if="canPay" class="mt-6 rounded-xl border border-pink-200 bg-gradient-to-r from-pink-50 to-white p-5">
                    <form
                        :action="`/billing/${invoice.id}/pay/bkash`"
                        method="POST"
                        class="flex items-center justify-between"
                        @submit="paying = true"
                    >
                        <input type="hidden" name="_token" :value="csrfToken" />
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Pay this invoice</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Complete payment securely via bKash</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="paying"
                            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:shadow-md disabled:opacity-50"
                            style="background: linear-gradient(135deg, #E2136E, #A4004F)"
                        >
                            <span>{{ paying ? 'Redirecting to bKash...' : `Pay ${formatCurrency(invoice.total)} with bKash` }}</span>
                        </button>
                    </form>
                </div>

                <!-- Paid via bKash -->
                <div v-if="invoice.status === 'paid' && invoice.data?.payment_method === 'bkash'" class="mt-6 rounded-xl border border-green-200 bg-green-50 p-5">
                    <div class="flex items-center gap-3">
                        <CheckCircle class="h-5 w-5 text-green-600" />
                        <div>
                            <h3 class="text-sm font-semibold text-green-800">Paid via bKash</h3>
                            <p v-if="invoice.data?.bkash_trx_id" class="text-sm text-green-600">
                                Transaction ID: {{ invoice.data.bkash_trx_id }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Data -->
                <div v-if="invoice.data && Object.keys(invoice.data).length > 0" class="mt-6">
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Additional Details</h3>
                    <div class="rounded-xl bg-gray-50 p-4">
                        <dl class="space-y-2">
                            <div
                                v-for="(value, key) in invoice.data"
                                :key="String(key)"
                                class="flex items-start justify-between text-sm"
                            >
                                <dt class="font-medium capitalize text-gray-500">
                                    {{ String(key).replace(/_/g, ' ') }}
                                </dt>
                                <dd class="text-gray-900">{{ typeof value === 'object' ? JSON.stringify(value) : formatValue(value) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
