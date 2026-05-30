<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, AlertTriangle, Clock, IndianRupee, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const showDropdown = ref(false);

const notifications = computed(() => page.props.billingNotifications as {
    unpaid: Array<{ id: number; invoice_number: string; total: string; due_date: string; status: string }>;
    overdue: Array<{ id: number; invoice_number: string; total: string; due_date: string; status: string }>;
    total_count: number;
} | null);

const hasNotifications = computed(() => notifications.value && notifications.value.total_count > 0);

function formatCurrency(amount: string | number) {
    return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0 }).format(Number(amount));
}

function formatDate(date: string | null) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <header
        class="relative z-50 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-sidebar-border/30 bg-white/80 px-6 shadow-sm backdrop-blur-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-3">
            <SidebarTrigger class="-ml-1 rounded-xl p-2 transition-all duration-200 hover:scale-105 hover:bg-slate-100" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Notification Bell -->
        <div v-if="notifications" class="relative">
            <button
                @click="showDropdown = !showDropdown"
                class="relative rounded-xl p-2 text-gray-500 transition-all hover:bg-slate-100 hover:text-gray-700"
            >
                <Bell class="h-5 w-5" />
                <span
                    v-if="hasNotifications"
                    class="absolute -top-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                >
                    {{ notifications.total_count }}
                </span>
            </button>

            <!-- Dropdown -->
            <div
                v-show="showDropdown"
                @click.away="showDropdown = false"
                class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-2xl bg-white shadow-xl ring-1 ring-gray-200"
            >
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800">Billing Notifications</h3>
                    <button @click="showDropdown = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div v-if="!hasNotifications" class="px-4 py-6 text-center text-sm text-gray-400">
                    No pending notifications
                </div>

                <div v-else class="max-h-72 overflow-y-auto">
                    <!-- Overdue invoices -->
                    <template v-if="notifications.overdue.length > 0">
                        <div class="px-4 pt-2 pb-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-red-500">Overdue</span>
                        </div>
                        <Link
                            v-for="inv in notifications.overdue"
                            :key="'o-' + inv.id"
                            :href="`/billing/${inv.id}`"
                            @click="showDropdown = false"
                            class="flex items-start gap-3 px-4 py-2.5 transition hover:bg-red-50"
                        >
                            <div class="mt-0.5 rounded-lg bg-red-100 p-1.5 text-red-600">
                                <AlertTriangle class="h-3.5 w-3.5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ inv.invoice_number }}</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span class="font-semibold text-red-600">{{ formatCurrency(inv.total) }}</span>
                                    <span>Due {{ formatDate(inv.due_date) }}</span>
                                </div>
                            </div>
                        </Link>
                    </template>

                    <!-- Unpaid invoices -->
                    <template v-if="notifications.unpaid.length > 0">
                        <div class="px-4 pt-2 pb-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-500">Unpaid</span>
                        </div>
                        <Link
                            v-for="inv in notifications.unpaid"
                            :key="'u-' + inv.id"
                            :href="`/billing/${inv.id}`"
                            @click="showDropdown = false"
                            class="flex items-start gap-3 px-4 py-2.5 transition hover:bg-amber-50"
                        >
                            <div class="mt-0.5 rounded-lg bg-amber-100 p-1.5 text-amber-600">
                                <Clock class="h-3.5 w-3.5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ inv.invoice_number }}</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span class="font-semibold text-amber-600">{{ formatCurrency(inv.total) }}</span>
                                    <span>Due {{ formatDate(inv.due_date) }}</span>
                                </div>
                            </div>
                        </Link>
                    </template>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-100 px-4 py-2.5">
                    <Link
                        href="/billing"
                        @click="showDropdown = false"
                        class="block text-center text-xs font-medium text-indigo-600 hover:text-indigo-800"
                    >
                        View all billing
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>
