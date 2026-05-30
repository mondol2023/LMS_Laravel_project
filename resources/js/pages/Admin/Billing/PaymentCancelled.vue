<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { XCircle } from 'lucide-vue-next';

interface Invoice {
    id: number;
    invoice_number: string | null;
    total: string;
}

defineProps<{ invoice: Invoice }>();
</script>

<template>
    <Head title="Payment Cancelled" />
    <AppLayout>
        <div class="flex min-h-[70vh] items-center justify-center bg-slate-50 p-5">
            <div class="w-full max-w-md text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-amber-100">
                    <XCircle class="h-10 w-10 text-amber-600" />
                </div>
                <h1 class="mb-2 text-2xl font-bold text-amber-700">Payment Cancelled</h1>
                <p class="mb-6 text-gray-500">
                    You cancelled the payment for invoice <strong>{{ invoice.invoice_number || `#${invoice.id}` }}</strong>.
                    No charges were made.
                </p>
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
