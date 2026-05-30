<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
            <div class="mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="mb-4 text-4xl font-bold text-slate-900">Edit Coupon</h1>
                    <p class="text-lg text-slate-600">Update coupon details and settings</p>
                </div>

                <!-- Main Form Card -->
                <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-slate-200 backdrop-blur-sm">
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Current Coupon Info Header -->
                        <div class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-blue-900">
                                        Editing: <span class="font-mono uppercase">{{ coupon.code }}</span>
                                    </h3>
                                    <p class="text-sm text-blue-700">
                                        Current uses: <span class="font-semibold">{{ coupon.current_uses }}</span>
                                        {{ coupon.max_uses ? ` / ${coupon.max_uses}` : ' / Unlimited' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="space-y-8">
                            <!-- Coupon Code -->
                            <div class="space-y-3">
                                <label for="code" class="block text-sm font-semibold text-slate-700">
                                    Coupon Code <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    required
                                    class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-4 font-mono uppercase text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-blue-500 focus:shadow-md focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                    placeholder="e.g., SAVE20"
                                />
                                <div v-if="form.errors.code" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.code }}
                                </div>
                            </div>

                            <!-- Discount Row - 2 Columns -->
                            <div class="grid grid-cols-2 gap-6">
                                <!-- Discount Amount -->
                                <div class="space-y-3">
                                    <label for="discount" class="block text-sm font-semibold text-slate-700">
                                        Discount Amount <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="discount"
                                        v-model="form.discount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-4 text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-blue-500 focus:shadow-md focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                        placeholder="e.g., 20"
                                    />
                                    <div v-if="form.errors.discount" class="flex items-center gap-2 text-sm text-red-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        {{ form.errors.discount }}
                                    </div>
                                </div>

                                <!-- Discount Type -->
                                <div class="space-y-3">
                                    <label for="discount_type" class="block text-sm font-semibold text-slate-700">
                                        Discount Type <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="discount_type"
                                        v-model="form.discount_type"
                                        required
                                        class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-4 text-slate-900 shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-blue-500 focus:shadow-md focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                    >
                                        <option value="" disabled class="text-slate-500">Select discount type</option>
                                        <option value="percent">Percentage (%)</option>
                                        <option value="flat">Flat Amount (BDT)</option>
                                    </select>
                                    <div v-if="form.errors.discount_type" class="flex items-center gap-2 text-sm text-red-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        {{ form.errors.discount_type }}
                                    </div>
                                </div>
                            </div>

                            <!-- Usage and Expiry Row - 2 Columns -->
                            <div class="grid grid-cols-2 gap-6">
                                <!-- Max Uses -->
                                <div class="space-y-3">
                                    <label for="max_uses" class="block text-sm font-semibold text-slate-700">Max Uses</label>
                                    <input
                                        id="max_uses"
                                        v-model="form.max_uses"
                                        type="number"
                                        min="1"
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-4 text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-blue-500 focus:shadow-md focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                        placeholder="e.g., 100"
                                    />
                                    <div v-if="form.errors.max_uses" class="flex items-center gap-2 text-sm text-red-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        {{ form.errors.max_uses }}
                                    </div>
                                    <p class="text-sm text-slate-500">Leave empty for unlimited uses</p>
                                </div>

                                <!-- Expiry Date -->
                                <div class="space-y-3">
                                    <label for="discount_till" class="block text-sm font-semibold text-slate-700">Expiry Date</label>
                                    <input
                                        id="discount_till"
                                        v-model="form.discount_till"
                                        type="datetime-local"
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-4 text-slate-900 shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-blue-500 focus:shadow-md focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                    />
                                    <div v-if="form.errors.discount_till" class="flex items-center gap-2 text-sm text-red-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        {{ form.errors.discount_till }}
                                    </div>
                                    <p class="text-sm text-slate-500">Leave empty for no expiry</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-10 flex justify-end gap-4 border-t border-slate-200 pt-8">
                            <Link
                                href="/coupons"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:scale-105 hover:border-slate-400 hover:bg-slate-50 hover:shadow-md focus:ring-2 focus:ring-slate-500/20 focus:outline-none"
                            >
                                <svg class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 focus:outline-none disabled:transform-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="mr-3 -ml-1 h-4 w-4 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <svg v-else class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-if="form.processing">Updating Coupon...</span>
                                <span v-else>Update Coupon</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    coupon: Object,
});

// Format datetime for input field
const formatDatetimeLocal = (date: string | null) => {
    if (!date) return '';
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const form = useForm({
    code: props.coupon.code,
    discount: props.coupon.discount,
    discount_type: props.coupon.discount_type,
    max_uses: props.coupon.max_uses,
    discount_till: formatDatetimeLocal(props.coupon.discount_till),
});

const submit = () => {
    form.put(`/coupons/${props.coupon.id}`);
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Edit Coupon',
        href: dashboard().url,
    },
];
</script>
