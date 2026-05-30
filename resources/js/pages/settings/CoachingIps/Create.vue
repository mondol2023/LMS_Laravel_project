<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Form, Link } from '@inertiajs/vue3';

defineProps<{
    currentIp: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Coaching IPs',
        href: '/settings/coaching-ips',
    },
    {
        title: 'Add IP',
        href: '/settings/coaching-ips/create',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto">
                <!-- Header Section -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Add Coaching IP</h1>
                        <p class="mt-1 text-sm text-gray-500">Add an IP address that can access exams without login</p>
                    </div>
                    <Link
                        href="/settings/coaching-ips"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                </div>

                <!-- Current IP Info -->
                <div class="mb-6 flex items-center gap-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-blue-900">Your Current IP Address</p>
                        <p class="font-mono text-lg font-bold text-blue-700">{{ currentIp }}</p>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <Form action="/settings/coaching-ips" method="post" #default="{ errors, processing }">
                        <div class="space-y-4 p-6">
                            <!-- IP Address -->
                            <div>
                                <label for="ip_address" class="block text-sm font-medium text-gray-700">
                                    IP Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="ip_address"
                                    name="ip_address"
                                    type="text"
                                    required
                                    :value="currentIp"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="192.168.1.1"
                                />
                                <p class="mt-1 text-xs text-gray-500">Enter a valid IPv4 or IPv6 address</p>
                                <div v-if="errors.ip_address" class="mt-1 text-sm text-red-600">{{ errors.ip_address }}</div>
                            </div>

                            <!-- Label -->
                            <div>
                                <label for="label" class="block text-sm font-medium text-gray-700">
                                    Label (Optional)
                                </label>
                                <input
                                    id="label"
                                    name="label"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="e.g., Main Office, Computer Lab"
                                />
                                <p class="mt-1 text-xs text-gray-500">A descriptive name to identify this IP</p>
                                <div v-if="errors.label" class="mt-1 text-sm text-red-600">{{ errors.label }}</div>
                            </div>

                            <!-- Active Status -->
                            <div>
                                <label class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        checked
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Active</span>
                                        <p class="text-xs text-gray-500">Enabled IPs can access exams without login</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <Link
                                    href="/settings/coaching-ips"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 disabled:opacity-50"
                                >
                                    <svg v-if="processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ processing ? 'Adding...' : 'Add IP Address' }}
                                </button>
                            </div>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
