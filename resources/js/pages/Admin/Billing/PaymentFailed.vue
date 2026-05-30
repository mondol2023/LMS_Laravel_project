<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';

interface Invoice {
    id: number;
    invoice_number: string | null;
    total: string;
}

defineProps<{
    invoice: Invoice;
    error: string;
}>();
</script>

<template>
    <Head title="Payment Failed" />
    <AppLayout>
        <div class="flex min-h-[70vh] items-center justify-center bg-slate-50 p-5">
            <div class="w-full max-w-md text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                    <AlertTriangle class="h-10 w-10 text-red-600" />
                </div>
                <h1 class="mb-2 text-2xl font-bold text-red-700">Payment Failed</h1>
                <p class="mb-4 text-gray-500">
                    Payment for invoice <strong>{{ invoice.invoice_number || `#${invoice.id}` }}</strong> could not be processed.
                </p>
                <div v-if="error" class="mx-auto mb-6 rounded-xl bg-red-50 p-4 ring-1 ring-red-200">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>
                <div class="flex justify-center gap-3">
                    <Link :href="`/billing/${invoice.id}`" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
                        Try Again
                    </Link>
                    <Link href="/billing" class="rounded-xl bg-gray-100 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200">
                        All Invoices
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
