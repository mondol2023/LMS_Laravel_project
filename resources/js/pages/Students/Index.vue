<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white p-6">
            <div class="mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold text-gray-900">Students</h1>
                            <p class="mt-2 text-lg text-gray-600">Manage student information and records</p>
                        </div>
                        <Link
                            href="/students/create"
                            class="inline-flex items-center bg-black px-6 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-gray-800"
                        >
                            <svg class="mr-2 -ml-0.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Student
                        </Link>
                    </div>

                    <!-- Search Bar -->
                    <div class="relative max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name, roll number, or phone..."
                            class="block w-full border border-gray-300 bg-white py-3 pr-4 pl-11 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Students Table -->
                <div class="bg-white shadow-sm ring-1 ring-gray-200">
                    <div v-if="students.data.length === 0" class="p-12 text-center">
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center bg-gray-100">
                            <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-900">No students yet</h3>
                        <p class="mb-6 text-gray-500">Get started by adding your first student.</p>
                        <Link
                            href="/students/create"
                            class="inline-flex items-center bg-black px-6 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-gray-800"
                        >
                            <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Student
                        </Link>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b border-gray-200 bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Name</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Roll Number</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Phone</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Package</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Expires</th>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-900">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="student in students.data" :key="student.id" class="transition-colors hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center bg-gray-200 text-sm font-semibold text-gray-700">
                                                {{ getInitials(student.name) }}
                                            </div>
                                            <div class="font-medium text-gray-900">{{ student.name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ student.roll_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ student.phone || '-' }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            v-if="student.active_subscription?.package"
                                            class="inline-flex items-center bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                                        >
                                            {{ student.active_subscription.package.name }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            v-if="student.active_subscription?.expires_at"
                                            :class="getExpiryClass(student.active_subscription.expires_at)"
                                            class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium"
                                        >
                                            {{ formatDate(student.active_subscription.expires_at) }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                @click="viewStudent(student)"
                                                class="inline-flex items-center border border-gray-300 bg-white p-1.5 text-gray-600 transition-all hover:bg-blue-50 hover:text-blue-600"
                                                title="View Details"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <Link
                                                :href="`/students/${student.id}/edit`"
                                                class="inline-flex items-center border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-all hover:bg-gray-50"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                @click="deleteStudent(student)"
                                                class="inline-flex items-center border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition-all hover:bg-red-50"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="students.links.length > 3" class="border-t border-gray-200 px-6 py-4">
                        <div class="flex justify-center gap-1">
                            <template v-for="link in students.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium transition-colors',
                                        link.active ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700',
                                    ]"
                                    v-html="link.label"
                                />
                                <span v-else :class="['cursor-not-allowed px-4 py-2 text-sm font-medium', 'text-gray-300']" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click="closeDeleteModal">
            <div class="mx-4 w-full max-w-md bg-white p-6 shadow-2xl ring-1 ring-gray-200" @click.stop>
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                            />
                        </svg>
                    </div>

                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-xl leading-6 font-semibold text-gray-900" id="modal-title">Delete Student</h3>
                        <div class="mt-3">
                            <p class="text-gray-600">
                                Are you sure you want to delete
                                <span class="font-semibold text-gray-900">{{ studentToDelete?.name }}</span
                                >? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button
                        type="button"
                        @click="confirmDelete"
                        class="inline-flex w-full justify-center bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-red-700 sm:w-auto"
                    >
                        Delete
                    </button>
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="mt-3 inline-flex w-full justify-center border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Student Details Modal -->
        <div v-if="showDetailsModal && selectedStudent" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click="closeDetailsModal">
            <div class="mx-4 w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white shadow-2xl ring-1 ring-gray-200" @click.stop>
                <!-- Header -->
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center bg-gray-200 text-lg font-semibold text-gray-700">
                                {{ getInitials(selectedStudent.name) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ selectedStudent.name }}</h3>
                                <p class="text-sm text-gray-500">{{ selectedStudent.roll_number }}</p>
                            </div>
                        </div>
                        <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Contact Info -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Email:</span>
                            <span class="ml-2 font-medium text-gray-900">{{ selectedStudent.email }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Phone:</span>
                            <span class="ml-2 font-medium text-gray-900">{{ selectedStudent.phone || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Institute:</span>
                            <span class="ml-2 font-medium text-gray-900">{{ selectedStudent.institute || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Passport:</span>
                            <span class="ml-2 font-medium text-gray-900">{{ selectedStudent.passport || '-' }}</span>
                        </div>
                    </div>

                    <!-- Subscription Info -->
                    <div v-if="selectedStudent.active_subscription" class="border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-gray-900">Subscription</h4>
                            <span :class="getExpiryClass(selectedStudent.active_subscription.expires_at)" class="px-3 py-1 text-xs font-medium">
                                {{ getExpiryLabel(selectedStudent.active_subscription.expires_at) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            <div>
                                <span class="text-gray-500">Package:</span>
                                <span class="ml-2 font-medium text-gray-900">{{ selectedStudent.active_subscription.package?.name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Expires:</span>
                                <span class="ml-2 font-medium text-gray-900">{{ formatDate(selectedStudent.active_subscription.expires_at) }}</span>
                            </div>
                        </div>

                        <!-- Usage Progress -->
                        <div class="space-y-3">
                            <h5 class="text-sm font-medium text-gray-700">Usage Progress</h5>

                            <!-- Full Mock Tests -->
                            <div v-if="selectedStudent.active_subscription.package?.full_mock_test_limit !== null" class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Full Mock Tests</span>
                                    <span class="font-medium">
                                        {{ selectedStudent.active_subscription.full_mock_tests_used || 0 }} /
                                        {{ selectedStudent.active_subscription.package?.full_mock_test_limit ?? 'Unlimited' }}
                                    </span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-blue-500 transition-all"
                                        :style="{ width: getProgressWidth(selectedStudent.active_subscription.full_mock_tests_used, selectedStudent.active_subscription.package?.full_mock_test_limit) }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Partial Tests -->
                            <div class="space-y-2">
                                <h6 class="text-xs font-medium text-gray-500">Partial Mock Tests</h6>
                                <div class="grid grid-cols-2 gap-3">
                                <div v-if="selectedStudent.active_subscription.package?.partial_reading_limit !== null" class="space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Reading</span>
                                        <span class="font-medium">{{ selectedStudent.active_subscription.partial_reading_used || 0 }} / {{ selectedStudent.active_subscription.package?.partial_reading_limit ?? '∞' }}</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500" :style="{ width: getProgressWidth(selectedStudent.active_subscription.partial_reading_used, selectedStudent.active_subscription.package?.partial_reading_limit) }"></div>
                                    </div>
                                </div>
                                <div v-if="selectedStudent.active_subscription.package?.partial_writing_limit !== null" class="space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Writing</span>
                                        <span class="font-medium">{{ selectedStudent.active_subscription.partial_writing_used || 0 }} / {{ selectedStudent.active_subscription.package?.partial_writing_limit ?? '∞' }}</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-yellow-500" :style="{ width: getProgressWidth(selectedStudent.active_subscription.partial_writing_used, selectedStudent.active_subscription.package?.partial_writing_limit) }"></div>
                                    </div>
                                </div>
                                <div v-if="selectedStudent.active_subscription.package?.partial_listening_limit !== null" class="space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Listening</span>
                                        <span class="font-medium">{{ selectedStudent.active_subscription.partial_listening_used || 0 }} / {{ selectedStudent.active_subscription.package?.partial_listening_limit ?? '∞' }}</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-purple-500" :style="{ width: getProgressWidth(selectedStudent.active_subscription.partial_listening_used, selectedStudent.active_subscription.package?.partial_listening_limit) }"></div>
                                    </div>
                                </div>
                                <div v-if="selectedStudent.active_subscription.package?.partial_speaking_limit !== null" class="space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Speaking</span>
                                        <span class="font-medium">{{ selectedStudent.active_subscription.partial_speaking_used || 0 }} / {{ selectedStudent.active_subscription.package?.partial_speaking_limit ?? '∞' }}</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-pink-500" :style="{ width: getProgressWidth(selectedStudent.active_subscription.partial_speaking_used, selectedStudent.active_subscription.package?.partial_speaking_limit) }"></div>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Practice Modules -->
                            <div v-if="hasPracticeModules(selectedStudent.active_subscription.package)" class="pt-2">
                                <h6 class="text-xs font-medium text-gray-500 mb-2">Practice Modules</h6>
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="selectedStudent.active_subscription.package?.practice_reading_enabled" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded">Reading</span>
                                    <span v-if="selectedStudent.active_subscription.package?.practice_listening_enabled" class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">Listening</span>
                                    <span v-if="selectedStudent.active_subscription.package?.practice_writing_enabled" class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded">
                                        Writing ({{ selectedStudent.active_subscription.practice_writing_used || 0 }}/{{ selectedStudent.active_subscription.package?.practice_writing_limit ?? '∞' }})
                                    </span>
                                    <span v-if="selectedStudent.active_subscription.package?.practice_speaking_enabled" class="px-2 py-0.5 bg-pink-100 text-pink-700 text-xs rounded">
                                        Speaking ({{ selectedStudent.active_subscription.practice_speaking_used || 0 }}/{{ selectedStudent.active_subscription.package?.practice_speaking_limit ?? '∞' }})
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Subscription -->
                    <div v-else class="border-t border-gray-200 pt-4">
                        <div class="text-center py-6 bg-gray-50 rounded">
                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No active subscription</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                    <div class="flex justify-end gap-3">
                        <Link
                            :href="`/students/${selectedStudent.id}/edit`"
                            class="inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Edit Student
                        </Link>
                        <button
                            @click="closeDetailsModal"
                            class="inline-flex items-center bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Package {
    id: number;
    name: string;
    full_mock_test_limit: number | null;
    partial_reading_limit: number | null;
    partial_writing_limit: number | null;
    partial_listening_limit: number | null;
    partial_speaking_limit: number | null;
    practice_reading_enabled: boolean;
    practice_listening_enabled: boolean;
    practice_writing_enabled: boolean;
    practice_writing_limit: number | null;
    practice_speaking_enabled: boolean;
    practice_speaking_limit: number | null;
}

interface Subscription {
    id: number;
    package_id: number;
    expires_at: string;
    full_mock_tests_used: number;
    partial_reading_used: number;
    partial_writing_used: number;
    partial_listening_used: number;
    partial_speaking_used: number;
    practice_writing_used: number;
    practice_speaking_used: number;
    package?: Package;
}

interface Student {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    roll_number: string;
    institute: string | null;
    passport: string | null;
    active_subscription?: Subscription;
}

const props = defineProps({
    students: Object,
    filters: Object,
});

const showDeleteModal = ref(false);
const showDetailsModal = ref(false);
const studentToDelete = ref<Student | null>(null);
const selectedStudent = ref<Student | null>(null);
const search = ref(props.filters?.search || '');

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const getDaysUntilExpiry = (dateStr: string) => {
    const now = new Date();
    const expiry = new Date(dateStr);
    const diffTime = expiry.getTime() - now.getTime();
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

const getExpiryClass = (dateStr: string) => {
    const days = getDaysUntilExpiry(dateStr);
    if (days <= 7) return 'bg-red-100 text-red-800'; // Expired or expiring in 7 days
    if (days <= 15) return 'bg-yellow-100 text-yellow-800'; // Expiring in 15 days
    return 'bg-blue-100 text-blue-800'; // Healthy
};

const getExpiryLabel = (dateStr: string) => {
    const days = getDaysUntilExpiry(dateStr);
    if (days < 0) return `Expired ${Math.abs(days)} days ago`;
    if (days === 0) return 'Expires today';
    if (days === 1) return 'Expires tomorrow';
    if (days <= 7) return `Expires in ${days} days`;
    if (days <= 30) return `${days} days left`;
    return 'Active';
};

const getProgressWidth = (used: number | undefined, limit: number | null | undefined) => {
    if (limit === null || limit === undefined) return '0%';
    const usedVal = used || 0;
    const percentage = Math.min((usedVal / limit) * 100, 100);
    return `${percentage}%`;
};

const hasPracticeModules = (pkg: Package | undefined) => {
    if (!pkg) return false;
    return pkg.practice_reading_enabled || pkg.practice_listening_enabled || pkg.practice_writing_enabled || pkg.practice_speaking_enabled;
};

const viewStudent = (student: Student) => {
    selectedStudent.value = student;
    showDetailsModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedStudent.value = null;
};

const deleteStudent = (student: Student) => {
    studentToDelete.value = student;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (studentToDelete.value) {
        router.delete(`/students/${studentToDelete.value.id}`, {
            onSuccess: () => {
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    studentToDelete.value = null;
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/students',
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, 300);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Students',
        href: dashboard().url,
    },
];
</script>
