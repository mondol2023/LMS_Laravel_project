<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    coupons: Object,
    search: String,
});

const showDeleteModal = ref(false);
const couponToDelete = ref(null);
const copiedCode = ref<string | null>(null);

const copyCouponCode = async (code: string) => {
    try {
        await navigator.clipboard.writeText(code);
        copiedCode.value = code;
        setTimeout(() => {
            copiedCode.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const deleteCoupon = (coupon) => {
    couponToDelete.value = coupon;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (couponToDelete.value) {
        router.delete(`/coupons/${couponToDelete.value.id}`, {
            onSuccess: () => {
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    couponToDelete.value = null;
};

const isExpired = (date) => {
    if (!date) return false;
    return new Date(date) < new Date();
};

const isMaxedOut = (coupon) => {
    if (!coupon.max_uses) return false;
    return coupon.current_uses >= coupon.max_uses;
};

const getStatusBadge = (coupon) => {
    if (isExpired(coupon.discount_till)) {
        return { text: 'Expired', class: 'bg-red-100 text-red-800' };
    }
    if (isMaxedOut(coupon)) {
        return { text: 'Maxed Out', class: 'bg-orange-100 text-orange-800' };
    }
    return { text: 'Active', class: 'bg-green-100 text-green-800' };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Coupons',
        href: dashboard().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Coupons</h1>
                <Link
                    href="/coupons/create"
                    class="inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Coupon
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="coupons.data.length === 0" class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                    />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">No coupons yet</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first coupon.</p>
                <Link
                    href="/coupons/create"
                    class="mt-6 inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Coupon
                </Link>
            </div>

            <!-- Table -->
            <div v-else class="mt-6 overflow-hidden border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Discount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Usage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="coupon in coupons.data" :key="coupon.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm font-semibold uppercase text-gray-900">{{ coupon.code }}</span>
                                    <button
                                        @click="copyCouponCode(coupon.code)"
                                        class="inline-flex items-center justify-center rounded p-1 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                        :title="copiedCode === coupon.code ? 'Copied!' : 'Copy code'"
                                    >
                                        <svg
                                            v-if="copiedCode === coupon.code"
                                            class="h-4 w-4 text-green-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900">
                                    {{ coupon.discount_type === 'percent' ? `${coupon.discount}%` : `BDT ${coupon.discount}` }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ coupon.current_uses }}{{ coupon.max_uses ? ` / ${coupon.max_uses}` : ' / Unlimited' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ coupon.discount_till ? formatDate(coupon.discount_till) : 'No expiry' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 text-xs font-medium', getStatusBadge(coupon).class]">
                                    {{ getStatusBadge(coupon).text }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link :href="`/coupons/${coupon.id}/edit`" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                                        Edit
                                    </Link>
                                    <button @click="deleteCoupon(coupon)" class="text-sm font-medium text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="coupons.links.length > 3" class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                    <div class="flex justify-center gap-1">
                        <template v-for="link in coupons.links" :key="link.label">
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
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="closeDeleteModal"></div>
                <div class="relative w-full max-w-sm border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Delete Coupon</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Are you sure you want to delete coupon
                        <span class="font-mono font-semibold uppercase text-gray-900">{{ couponToDelete?.code }}</span
                        >? This cannot be undone.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="closeDeleteModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button @click="confirmDelete" class="bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
