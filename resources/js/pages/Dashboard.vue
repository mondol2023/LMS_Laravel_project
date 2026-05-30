<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Package, Users, FileText, TrendingUp, UserPlus, CalendarCheck, AlertTriangle, Clock, ArrowRight, BadgeDollarSign } from 'lucide-vue-next';
import VueApexCharts from 'vue3-apexcharts';
import { computed } from 'vue';

interface Props {
    stats: {
        total_attempts: number;
        completed_tests: number;
        average_band: number;
        best_score: number;
    };
    adminStats: {
        total_exams: number;
        total_students: number;
        total_results: number;
        today_results: number;
        today_students: number;
        today_participants: number;
    };
    subscriptionStats: {
        total_packages: number;
        active_packages: number;
        total_subscriptions: number;
        active_subscriptions: number;
        expiring_soon: number;
        expired: number;
    };
    packages: Array<{
        id: number;
        name: string;
        price: string;
        duration: number;
        discount: number | null;
        discount_type: string | null;
        final_price: number;
        total_subscribers: number;
        active_subscribers: number;
    }>;
    recentAttempts: Array<{
        id: number;
        test_title: string;
        status: string;
        overall_band: number | null;
        started_at: string;
        completed_at: string | null;
    }>;
    availableTests: Array<{
        id: number;
        title: string;
        type: string;
        difficulty: string;
        user_attempts: number;
    }>;
    topPerformers: Array<{
        user_name: string;
        overall_band: number;
        completion_time: string;
    }>;
}

const props = defineProps<Props>();

const page = usePage();
const billing = computed(() => page.props.billingNotifications as {
    unpaid: Array<{ id: number; invoice_number: string; total: string; due_date: string }>;
    overdue: Array<{ id: number; invoice_number: string; total: string; due_date: string }>;
    total_count: number;
    grace_period_days: number;
    auto_suspend_after_days: number;
} | null);

const daysUntilSuspend = computed(() => {
    if (!billing.value || billing.value.overdue.length === 0) return null;
    const oldest = billing.value.overdue.reduce((a, b) => new Date(a.due_date) < new Date(b.due_date) ? a : b);
    const dueDate = new Date(oldest.due_date);
    const today = new Date();
    const daysOverdue = Math.floor((today.getTime() - dueDate.getTime()) / (1000 * 60 * 60 * 24));
    const totalAllowed = (billing.value.grace_period_days ?? 7) + (billing.value.auto_suspend_after_days ?? 15);
    return Math.max(totalAllowed - daysOverdue, 0);
});

function formatCurrency(amount: string | number) {
    return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0 }).format(Number(amount));
}

function formatDate(date: string | null) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN', { month: 'short', day: 'numeric', year: 'numeric' });
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Subscription Status Donut Chart
const subscriptionDonutOptions = computed(() => ({
    chart: {
        type: 'donut',
        height: 280,
    },
    labels: ['Active', 'Expiring Soon', 'Expired'],
    colors: ['#10B981', '#F59E0B', '#EF4444'],
    legend: {
        position: 'bottom',
        fontSize: '13px',
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        fontSize: '14px',
                        fontWeight: 600,
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: false,
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: { height: 250 },
            legend: { position: 'bottom' }
        }
    }]
}));

const subscriptionDonutSeries = computed(() => [
    props.subscriptionStats.active_subscriptions - props.subscriptionStats.expiring_soon,
    props.subscriptionStats.expiring_soon,
    props.subscriptionStats.expired
]);

// Package Subscribers Line Chart
const packageLineOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 280,
        toolbar: { show: false },
        sparkline: { enabled: false }
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.1,
            stops: [0, 90, 100]
        }
    },
    colors: ['#6366F1', '#EC4899'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.packages.slice(0, 5).map(p => p.name.substring(0, 10)),
        labels: { style: { fontSize: '11px' } }
    },
    yaxis: {
        labels: { style: { fontSize: '11px' } }
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right'
    },
    tooltip: {
        y: { formatter: (val: number) => `${val} users` }
    },
    grid: {
        borderColor: '#f1f5f9',
        strokeDashArray: 4
    }
}));

const packageLineSeries = computed(() => [
    {
        name: 'Active',
        data: props.packages.slice(0, 5).map(p => p.active_subscribers)
    },
    {
        name: 'Total',
        data: props.packages.slice(0, 5).map(p => p.total_subscribers)
    }
]);

// Today's Activity Radial Chart
const activityRadialOptions = computed(() => ({
    chart: {
        type: 'radialBar',
        height: 280,
    },
    plotOptions: {
        radialBar: {
            offsetY: 0,
            startAngle: 0,
            endAngle: 270,
            hollow: {
                margin: 5,
                size: '30%',
                background: 'transparent',
            },
            dataLabels: {
                name: { show: false },
                value: { show: false }
            }
        }
    },
    colors: ['#10B981', '#6366F1', '#F59E0B'],
    labels: ['New Students', 'Participants', 'Results'],
    legend: {
        show: true,
        floating: true,
        fontSize: '13px',
        position: 'left',
        offsetX: 10,
        offsetY: 10,
        labels: { useSeriesColors: true },
        formatter: function(seriesName: string, opts: any) {
            return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex]
        },
    },
    responsive: [{
        breakpoint: 480,
        options: {
            legend: { show: false }
        }
    }]
}));

const maxActivity = computed(() => Math.max(
    props.adminStats.today_students,
    props.adminStats.today_participants,
    props.adminStats.today_results,
    1
) * 1.2);

const activityRadialSeries = computed(() => [
    Math.round((props.adminStats.today_students / maxActivity.value) * 100),
    Math.round((props.adminStats.today_participants / maxActivity.value) * 100),
    Math.round((props.adminStats.today_results / maxActivity.value) * 100)
]);
</script>

<template>
    <Head title="English Therapy - Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-5 p-5 bg-slate-50 min-h-screen">
            <!-- Billing Alert -->
            <div v-if="billing && billing.overdue.length > 0" class="rounded-2xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-red-100 p-2.5 text-red-600">
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-semibold text-red-800">Overdue Invoice{{ billing.overdue.length > 1 ? 's' : '' }}</h3>
                            <span v-if="daysUntilSuspend !== null && daysUntilSuspend > 0" class="rounded-lg bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white">
                                Account suspension in {{ daysUntilSuspend }} day{{ daysUntilSuspend !== 1 ? 's' : '' }}
                            </span>
                            <span v-else-if="daysUntilSuspend === 0" class="rounded-lg bg-red-700 px-2 py-0.5 text-[11px] font-bold text-white animate-pulse">
                                Suspension imminent
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-red-600">
                            You have {{ billing.overdue.length }} overdue invoice{{ billing.overdue.length > 1 ? 's' : '' }}.
                            Grace period: {{ billing.grace_period_days }} days, auto-suspend after {{ billing.auto_suspend_after_days }} additional days.
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <div v-for="inv in billing.overdue" :key="inv.id" class="flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-sm ring-1 ring-red-200">
                                <BadgeDollarSign class="h-3.5 w-3.5 text-red-500" />
                                <span class="font-semibold text-red-700">{{ formatCurrency(inv.total) }}</span>
                                <span class="text-red-400">-</span>
                                <span class="text-xs text-red-500">Due {{ formatDate(inv.due_date) }}</span>
                            </div>
                            <Link href="/billing" class="text-sm font-medium text-red-700 underline underline-offset-2 hover:text-red-900">
                                View Billing
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="billing && billing.unpaid.length > 0" class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-amber-100 p-2.5 text-amber-600">
                        <Clock class="h-5 w-5" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-amber-800">Unpaid Invoice{{ billing.unpaid.length > 1 ? 's' : '' }}</h3>
                        <p class="mt-0.5 text-sm text-amber-600">
                            You have {{ billing.unpaid.length }} unpaid invoice{{ billing.unpaid.length > 1 ? 's' : '' }}.
                            Please make payment before the due date. Grace period: {{ billing.grace_period_days }} days after due date.
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <div v-for="inv in billing.unpaid" :key="inv.id" class="flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-sm ring-1 ring-amber-200">
                                <BadgeDollarSign class="h-3.5 w-3.5 text-amber-500" />
                                <span class="font-semibold text-amber-700">{{ formatCurrency(inv.total) }}</span>
                                <span class="text-amber-400">-</span>
                                <span class="text-xs text-amber-500">Due {{ formatDate(inv.due_date) }}</span>
                            </div>
                            <Link href="/billing" class="text-sm font-medium text-amber-700 underline underline-offset-2 hover:text-amber-900">
                                View Billing
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Stats Cards - Beautiful Grid -->
            <div class="grid grid-cols-3 gap-4">
                <!-- Total Students -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg hover:ring-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ adminStats.total_students }}</p>
                            <p class="text-sm text-gray-500">Students</p>
                        </div>
                        <div class="rounded-xl bg-blue-100 p-3 text-blue-600 transition-transform group-hover:scale-110">
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-blue-500 to-blue-400"></div>
                </div>

                <!-- Active Subscriptions -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg hover:ring-emerald-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ subscriptionStats.active_subscriptions }}</p>
                            <p class="text-sm text-gray-500">Active Subs</p>
                        </div>
                        <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600 transition-transform group-hover:scale-110">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-emerald-500 to-emerald-400"></div>
                </div>

                <!-- Expiring Soon -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg hover:ring-amber-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-amber-600">{{ subscriptionStats.expiring_soon }}</p>
                            <p class="text-sm text-gray-500">Expiring</p>
                        </div>
                        <div class="rounded-xl bg-amber-100 p-3 text-amber-600 transition-transform group-hover:scale-110">
                            <Clock class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-amber-500 to-orange-400"></div>
                </div>

                <!-- Expired -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg hover:ring-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-red-600">{{ subscriptionStats.expired }}</p>
                            <p class="text-sm text-gray-500">Expired</p>
                        </div>
                        <div class="rounded-xl bg-red-100 p-3 text-red-600 transition-transform group-hover:scale-110">
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-red-500 to-rose-400"></div>
                </div>

                <!-- Today New -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg hover:ring-violet-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ adminStats.today_students }}</p>
                            <p class="text-sm text-gray-500">Today New</p>
                        </div>
                        <div class="rounded-xl bg-violet-100 p-3 text-violet-600 transition-transform group-hover:scale-110">
                            <UserPlus class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-violet-500 to-purple-400"></div>
                </div>

                <!-- Today Tests -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition-all hover:shadow-lg hover:ring-indigo-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ adminStats.today_results }}</p>
                            <p class="text-sm text-gray-500">Today Tests</p>
                        </div>
                        <div class="rounded-xl bg-indigo-100 p-3 text-indigo-600 transition-transform group-hover:scale-110">
                            <CalendarCheck class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-indigo-500 to-blue-400"></div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- Subscription Status Donut -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-3 text-base font-semibold text-gray-800">Subscription Status</h3>
                    <VueApexCharts
                        type="donut"
                        height="260"
                        :options="subscriptionDonutOptions"
                        :series="subscriptionDonutSeries"
                    />
                </div>

                <!-- Package Subscribers Line Chart -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-800">Package Subscribers</h3>
                        <Link href="/subscriptions/packages" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                            View All
                        </Link>
                    </div>
                    <VueApexCharts
                        v-if="packages.length > 0"
                        type="area"
                        height="260"
                        :options="packageLineOptions"
                        :series="packageLineSeries"
                    />
                    <div v-else class="flex h-60 items-center justify-center text-gray-400">
                        No packages yet
                    </div>
                </div>

                <!-- Today's Activity Radial -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-3 text-base font-semibold text-gray-800">Today's Activity</h3>
                    <VueApexCharts
                        type="radialBar"
                        height="260"
                        :options="activityRadialOptions"
                        :series="activityRadialSeries"
                    />
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- Packages Table -->
                <div class="lg:col-span-2 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-800">Subscription Packages</h3>
                        <Link href="/subscriptions/packages" class="flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                            Manage <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <div v-if="packages.length === 0" class="py-10 text-center text-gray-400">
                        <Package class="mx-auto mb-2 h-10 w-10 opacity-50" />
                        <p class="text-sm">No packages created yet</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                                    <th class="pb-3">Package</th>
                                    <th class="pb-3 text-right">Price</th>
                                    <th class="pb-3 text-right">Discount</th>
                                    <th class="pb-3 text-right">Final</th>
                                    <th class="pb-3 text-center">Days</th>
                                    <th class="pb-3 text-center">Active</th>
                                    <th class="pb-3 text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(pkg, index) in packages" :key="pkg.id" class="group hover:bg-gray-50/80">
                                    <td class="py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold text-white"
                                                :class="[
                                                    index === 0 ? 'bg-gradient-to-br from-indigo-500 to-purple-500' :
                                                    index === 1 ? 'bg-gradient-to-br from-pink-500 to-rose-500' :
                                                    index === 2 ? 'bg-gradient-to-br from-amber-500 to-orange-500' :
                                                    index === 3 ? 'bg-gradient-to-br from-emerald-500 to-teal-500' :
                                                    'bg-gradient-to-br from-slate-500 to-slate-600'
                                                ]"
                                            >
                                                {{ pkg.name.charAt(0) }}
                                            </div>
                                            <span class="font-medium text-gray-800">{{ pkg.name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 text-right text-gray-500">{{ pkg.price }}</td>
                                    <td class="py-2.5 text-right">
                                        <span v-if="pkg.discount" class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-600">
                                            {{ pkg.discount_type === 'percent' ? `${pkg.discount}%` : `${pkg.discount}` }}
                                        </span>
                                        <span v-else class="text-gray-300">-</span>
                                    </td>
                                    <td class="py-2.5 text-right font-semibold text-gray-800">{{ pkg.final_price.toFixed(0) }}</td>
                                    <td class="py-2.5 text-center">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                            {{ pkg.duration }}d
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-center">
                                        <span class="inline-flex min-w-[2rem] justify-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-600">
                                            {{ pkg.active_subscribers }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-center">
                                        <span class="inline-flex min-w-[2rem] justify-center rounded-full bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-500">
                                            {{ pkg.total_subscribers }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Stats & Actions -->
                <div class="space-y-5">
                    <!-- Summary Cards -->
                    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 p-5 text-white shadow-lg">
                        <h3 class="mb-3 text-base font-semibold">Quick Summary</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between rounded-lg bg-white/15 px-3 py-2">
                                <span class="text-sm opacity-90">Total Packages</span>
                                <span class="text-lg font-bold">{{ subscriptionStats.total_packages }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg bg-white/15 px-3 py-2">
                                <span class="text-sm opacity-90">Active Packages</span>
                                <span class="text-lg font-bold">{{ subscriptionStats.active_packages }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg bg-white/15 px-3 py-2">
                                <span class="text-sm opacity-90">Total Exams</span>
                                <span class="text-lg font-bold">{{ adminStats.total_exams }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg bg-white/15 px-3 py-2">
                                <span class="text-sm opacity-90">Total Results</span>
                                <span class="text-lg font-bold">{{ adminStats.total_results }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <h3 class="mb-3 text-base font-semibold text-gray-800">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <Link
                                href="/students"
                                class="flex flex-col items-center gap-1.5 rounded-xl bg-blue-50 p-3 text-blue-600 transition-all hover:bg-blue-100 hover:shadow"
                            >
                                <Users class="h-5 w-5" />
                                <span class="text-xs font-medium">Students</span>
                            </Link>
                            <Link
                                href="/subscriptions/packages"
                                class="flex flex-col items-center gap-1.5 rounded-xl bg-purple-50 p-3 text-purple-600 transition-all hover:bg-purple-100 hover:shadow"
                            >
                                <Package class="h-5 w-5" />
                                <span class="text-xs font-medium">Packages</span>
                            </Link>
                            <Link
                                href="/exams"
                                class="flex flex-col items-center gap-1.5 rounded-xl bg-emerald-50 p-3 text-emerald-600 transition-all hover:bg-emerald-100 hover:shadow"
                            >
                                <FileText class="h-5 w-5" />
                                <span class="text-xs font-medium">Exams</span>
                            </Link>
                            <Link
                                href="/results"
                                class="flex flex-col items-center gap-1.5 rounded-xl bg-amber-50 p-3 text-amber-600 transition-all hover:bg-amber-100 hover:shadow"
                            >
                                <CalendarCheck class="h-5 w-5" />
                                <span class="text-xs font-medium">Results</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
