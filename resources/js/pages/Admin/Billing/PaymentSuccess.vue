<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';

interface Invoice {
    id: number;
    invoice_number: string | null;
    total: string;
    status: string;
}

defineProps<{
    invoice: Invoice;
    trxID: string | null;
}>();

function formatCurrency(amount: string | number) {
    return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0 }).format(Number(amount));
}
</script>

<template>
    <Head title="Payment Successful" />
    <AppLayout>
        <div class="flex min-h-[70vh] items-center justify-center bg-slate-50 p-5">
            <div class="w-full max-w-md text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                    <CheckCircle class="h-10 w-10 text-green-600" />
                </div>
                <h1 class="mb-2 text-2xl font-bold text-green-700">Payment Successful</h1>
                <p class="mb-6 text-gray-500">Your payment has been processed successfully.</p>

                <div class="mx-auto mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Invoice</dt>
                            <dd class="font-semibold text-gray-900">{{ invoice.invoice_number || `#${invoice.id}` }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Amount</dt>
                            <dd class="font-semibold text-green-700">{{ formatCurrency(invoice.total) }}</dd>
                        </div>
                        <div v-if="trxID" class="flex justify-between">
                            <dt class="text-gray-500">Transaction ID</dt>
                            <dd class="font-mono text-xs font-medium text-gray-900">{{ trxID }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Status</dt>
                            <dd class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200">
                                Paid
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="flex justify-center gap-3">
                    <Link :href="`/billing/${invoice.id}`" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
                        View Invoice
                    </Link>
                    <Link href="/billing" class="rounded-xl bg-gray-100 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200">
                        All Invoices
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
