<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto">
                <!-- Header Section -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Create Subscription Package</h1>
                        <p class="mt-1 text-sm text-gray-500">Define a new subscription plan with limits and pricing</p>
                    </div>
                    <Link
                        href="/subscriptions/packages"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                </div>

                <!-- Main Form Card -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <Form action="/subscriptions/packages" method="post" #default="{ errors, processing }">
                        <!-- Basic Information -->
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
                            <h2 class="text-base font-semibold text-gray-900">Basic Information</h2>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label for="name" class="block text-sm font-medium text-gray-700">
                                        Package Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="e.g., Basic Plan, Premium Plan"
                                    />
                                    <div v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Duration -->
                        <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                            <h2 class="text-base font-semibold text-gray-900">Pricing & Duration</h2>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="price" class="block text-sm font-medium text-gray-700">
                                        Price (BDT) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="price"
                                        name="price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="1000"
                                    />
                                    <div v-if="errors.price" class="mt-1 text-sm text-red-600">{{ errors.price }}</div>
                                </div>

                                <div>
                                    <label for="duration" class="block text-sm font-medium text-gray-700">
                                        Duration (Months) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="duration"
                                        name="duration"
                                        type="number"
                                        min="1"
                                        required
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="e.g., 1, 3, 6, 12"
                                    />
                                    <div v-if="errors.duration" class="mt-1 text-sm text-red-600">{{ errors.duration }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <label class="mt-2 flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <span class="text-sm text-gray-700">Active</span>
                                    </label>
                                    <div v-if="errors.is_active" class="mt-1 text-sm text-red-600">{{ errors.is_active }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="discount" class="block text-sm font-medium text-gray-700">Discount</label>
                                    <input
                                        id="discount"
                                        name="discount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="20"
                                    />
                                    <div v-if="errors.discount" class="mt-1 text-sm text-red-600">{{ errors.discount }}</div>
                                </div>

                                <div>
                                    <label for="discount_type" class="block text-sm font-medium text-gray-700">Type</label>
                                    <select
                                        id="discount_type"
                                        name="discount_type"
                                        class="mt-1 block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="">None</option>
                                        <option value="percent">%</option>
                                        <option value="flat">BDT</option>
                                    </select>
                                    <div v-if="errors.discount_type" class="mt-1 text-sm text-red-600">{{ errors.discount_type }}</div>
                                </div>

                                <div>
                                    <label for="discount_till" class="block text-sm font-medium text-gray-700">Valid Until</label>
                                    <input
                                        id="discount_till"
                                        name="discount_till"
                                        type="datetime-local"
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <div v-if="errors.discount_till" class="mt-1 text-sm text-red-600">{{ errors.discount_till }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Mock Test Limits -->
                        <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                            <h2 class="text-base font-semibold text-gray-900">Mock Test Limits</h2>
                            <p class="mt-1 text-xs text-gray-500">Enable each test type and set limits (leave empty for unlimited)</p>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="grid grid-cols-3 gap-6">
                                <!-- Full Mock Test -->
                                <div>
                                    <label class="mb-2 flex items-center gap-2">
                                        <input type="checkbox" name="full_mock_test_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <span class="text-sm font-medium text-gray-700">Full Mock Test</span>
                                    </label>
                                    <input
                                        id="full_mock_test_limit"
                                        name="full_mock_test_limit"
                                        type="number"
                                        min="0"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Limit"
                                    />
                                    <div v-if="errors.full_mock_test_limit" class="mt-1 text-sm text-red-600">{{ errors.full_mock_test_limit }}</div>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <p class="mb-3 text-sm font-medium text-gray-700">Partial Mock Tests by Part</p>
                                <div class="grid grid-cols-4 gap-4">
                                    <div>
                                        <label class="mb-2 flex items-center gap-2">
                                            <input type="checkbox" name="partial_reading_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                            <span class="text-sm font-medium text-gray-700">Reading</span>
                                        </label>
                                        <input
                                            id="partial_reading_limit"
                                            name="partial_reading_limit"
                                            type="number"
                                            min="0"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Limit"
                                        />
                                        <div v-if="errors.partial_reading_limit" class="mt-1 text-sm text-red-600">{{ errors.partial_reading_limit }}</div>
                                    </div>

                                    <div>
                                        <label class="mb-2 flex items-center gap-2">
                                            <input type="checkbox" name="partial_writing_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                            <span class="text-sm font-medium text-gray-700">Writing</span>
                                        </label>
                                        <input
                                            id="partial_writing_limit"
                                            name="partial_writing_limit"
                                            type="number"
                                            min="0"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Limit"
                                        />
                                        <div v-if="errors.partial_writing_limit" class="mt-1 text-sm text-red-600">{{ errors.partial_writing_limit }}</div>
                                    </div>

                                    <div>
                                        <label class="mb-2 flex items-center gap-2">
                                            <input type="checkbox" name="partial_listening_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                            <span class="text-sm font-medium text-gray-700">Listening</span>
                                        </label>
                                        <input
                                            id="partial_listening_limit"
                                            name="partial_listening_limit"
                                            type="number"
                                            min="0"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Limit"
                                        />
                                        <div v-if="errors.partial_listening_limit" class="mt-1 text-sm text-red-600">{{ errors.partial_listening_limit }}</div>
                                    </div>

                                    <div>
                                        <label class="mb-2 flex items-center gap-2">
                                            <input type="checkbox" name="partial_speaking_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                            <span class="text-sm font-medium text-gray-700">Speaking</span>
                                        </label>
                                        <input
                                            id="partial_speaking_limit"
                                            name="partial_speaking_limit"
                                            type="number"
                                            min="0"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Limit"
                                        />
                                        <div v-if="errors.partial_speaking_limit" class="mt-1 text-sm text-red-600">{{ errors.partial_speaking_limit }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Practice Module Access -->
                        <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                            <h2 class="text-base font-semibold text-gray-900">Practice Module Access</h2>
                            <p class="mt-1 text-xs text-gray-500">Reading/Listening are unlimited when enabled. Writing/Speaking have limits.</p>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="grid grid-cols-4 gap-4">
                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="practice_reading_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <span class="text-sm font-medium text-gray-700">Reading (∞)</span>
                                    </label>
                                    <div v-if="errors.practice_reading_enabled" class="mt-1 text-sm text-red-600">{{ errors.practice_reading_enabled }}</div>
                                </div>

                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="practice_listening_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <span class="text-sm font-medium text-gray-700">Listening (∞)</span>
                                    </label>
                                    <div v-if="errors.practice_listening_enabled" class="mt-1 text-sm text-red-600">{{ errors.practice_listening_enabled }}</div>
                                </div>

                                <div>
                                    <label class="mb-2 flex items-center gap-2">
                                        <input type="checkbox" name="practice_writing_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <span class="text-sm font-medium text-gray-700">Writing</span>
                                    </label>
                                    <input
                                        id="practice_writing_limit"
                                        name="practice_writing_limit"
                                        type="number"
                                        min="0"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Limit"
                                    />
                                    <div v-if="errors.practice_writing_enabled || errors.practice_writing_limit" class="mt-1 text-sm text-red-600">
                                        {{ errors.practice_writing_enabled || errors.practice_writing_limit }}
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 flex items-center gap-2">
                                        <input type="checkbox" name="practice_speaking_enabled" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                        <span class="text-sm font-medium text-gray-700">Speaking</span>
                                    </label>
                                    <input
                                        id="practice_speaking_limit"
                                        name="practice_speaking_limit"
                                        type="number"
                                        min="0"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Limit"
                                    />
                                    <div v-if="errors.practice_speaking_enabled || errors.practice_speaking_limit" class="mt-1 text-sm text-red-600">
                                        {{ errors.practice_speaking_enabled || errors.practice_speaking_limit }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <Link
                                href="/subscriptions/packages"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg v-if="processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ processing ? 'Creating...' : 'Create Package' }}</span>
                            </button>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Form, Link } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Package',
        href: dashboard().url,
    },
];
</script>
