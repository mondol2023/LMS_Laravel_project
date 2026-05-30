<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ShieldAlert, ShieldOff, Mail, Phone } from 'lucide-vue-next';

interface Props {
    status: 'suspended' | 'blocked';
    message: string;
    reason: string | null;
    lastUpdate: string | null;
}

const props = defineProps<Props>();

const isSuspended = props.status === 'suspended';
</script>

<template>
    <Head :title="isSuspended ? 'Account Suspended' : 'Account Blocked'" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-lg text-center">
            <!-- Icon -->
            <div
                class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full"
                :class="isSuspended ? 'bg-amber-100' : 'bg-red-100'"
            >
                <ShieldAlert v-if="isSuspended" class="h-10 w-10 text-amber-600" />
                <ShieldOff v-else class="h-10 w-10 text-red-600" />
            </div>

            <!-- Title -->
            <h1
                class="mb-3 text-3xl font-bold"
                :class="isSuspended ? 'text-amber-700' : 'text-red-700'"
            >
                {{ isSuspended ? 'Account Suspended' : 'Account Blocked' }}
            </h1>

            <!-- Message -->
            <p class="mb-4 text-lg text-gray-600">
                {{ message }}
            </p>

            <!-- Reason -->
            <div v-if="reason" class="mx-auto mb-6 max-w-md rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">Reason</p>
                <p class="mt-1 text-sm text-gray-700">{{ reason }}</p>
            </div>

            <!-- Last Updated -->
            <p v-if="lastUpdate" class="mb-8 text-sm text-gray-400">
                Status updated: {{ lastUpdate }}
            </p>

            <!-- Contact Info -->
            <div class="mx-auto max-w-sm rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="mb-4 text-base font-semibold text-gray-800">Contact Support</h3>
                <p class="mb-4 text-sm text-gray-500">
                    If you believe this is an error or need assistance, please reach out to our support team.
                </p>
                <div class="space-y-3">
                    <a
                        href="mailto:support@ieltsmock.com"
                        class="flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700"
                    >
                        <Mail class="h-4 w-4" />
                        Email Support
                    </a>
                    <a
                        href="tel:+1234567890"
                        class="flex items-center justify-center gap-2 rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                    >
                        <Phone class="h-4 w-4" />
                        Call Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
