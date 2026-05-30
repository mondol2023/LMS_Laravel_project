<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white p-6">
            <div class="mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="mb-2 text-4xl font-bold text-gray-900">Edit Student</h1>
                    <p class="text-lg text-gray-600">Update student information and details</p>
                </div>

                <!-- Main Form Card -->
                <div class="bg-white p-8 shadow-sm ring-1 ring-gray-200">
                    <form @submit.prevent="submit">
                        <!-- Current Student Info Header -->
                        <div class="mb-6 border border-gray-200 bg-gray-50 p-6">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center bg-gray-200 text-lg font-semibold text-gray-700">
                                    {{ getInitials(student.name) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Editing: {{ student.name }}</h3>
                                    <p class="text-sm text-gray-600">
                                        Roll: <span class="font-mono font-semibold">{{ student.roll_number }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="grid grid-cols-2 gap-6">
                            <!-- Name -->
                            <div class="space-y-2">
                                <label for="name" class="block text-sm font-semibold text-gray-700">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="Enter student's full name"
                                />
                                <div v-if="form.errors.name" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Roll Number -->
                            <div class="space-y-2">
                                <label for="roll_number" class="block text-sm font-semibold text-gray-700">
                                    Roll Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="roll_number"
                                    v-model="form.roll_number"
                                    type="text"
                                    required
                                    class="block w-full border border-gray-300 bg-white px-4 py-3 font-mono text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="e.g., ROLL2024001"
                                />
                                <div v-if="form.errors.roll_number" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.roll_number }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-semibold text-gray-700">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="block w-full border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="student@example.com"
                                />
                                <div v-if="form.errors.email" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <label for="password" class="block text-sm font-semibold text-gray-700">
                                    Password
                                    <span class="ml-1 text-xs font-normal text-gray-500">(Leave blank to keep current)</span>
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="block w-full border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="Enter new password"
                                />
                                <div v-if="form.errors.password" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="space-y-2">
                                <label for="phone" class="block text-sm font-semibold text-gray-700">
                                    Phone Number
                                    <span class="ml-1 text-xs font-normal text-gray-500">(Optional)</span>
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="block w-full border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="+8801712345678"
                                />
                                <div v-if="form.errors.phone" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.phone }}
                                </div>
                            </div>

                            <!-- Passport Number -->
                            <div class="space-y-2">
                                <label for="passport" class="block text-sm font-semibold text-gray-700">
                                    Passport Number
                                    <span class="ml-1 text-xs font-normal text-gray-500">(Optional)</span>
                                </label>
                                <input
                                    id="passport"
                                    v-model="form.passport"
                                    type="text"
                                    class="block w-full border border-gray-300 bg-white px-4 py-3 font-mono text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="e.g., A12345678"
                                />
                                <div v-if="form.errors.passport" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.passport }}
                                </div>
                            </div>

                            <!-- Institute (Full Width) -->
                            <div class="col-span-2 space-y-2">
                                <label for="institute" class="block text-sm font-semibold text-gray-700">
                                    Institute
                                    <span class="ml-1 text-xs font-normal text-gray-500">(Optional)</span>
                                </label>
                                <input
                                    id="institute"
                                    v-model="form.institute"
                                    type="text"
                                    class="block w-full border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="Enter institute name"
                                />
                                <div v-if="form.errors.institute" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.institute }}
                                </div>
                            </div>

                            <!-- Reference (Full Width) -->
                            <div class="col-span-2 space-y-2">
                                <label for="reference" class="block text-sm font-semibold text-gray-700">
                                    Reference
                                    <span class="ml-1 text-xs font-normal text-gray-500">(Optional)</span>
                                </label>
                                <textarea
                                    id="reference"
                                    v-model="form.reference"
                                    rows="3"
                                    class="block w-full resize-none border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-black focus:ring-2 focus:ring-black/20 focus:outline-none"
                                    placeholder="Enter any reference notes or additional information"
                                ></textarea>
                                <div v-if="form.errors.reference" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.reference }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="col-span-2 mt-8 flex justify-end gap-4 border-t border-gray-200 pt-6">
                            <Link
                                href="/students"
                                class="inline-flex items-center border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-gray-400 hover:bg-gray-50"
                            >
                                <svg class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center bg-black px-8 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
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
                                <span v-if="form.processing">Updating Student...</span>
                                <span v-else>Update Student</span>
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
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, useForm } from '@inertiajs/vue3';

interface Student {
    id: number;
    name: string;
    roll_number: string;
    email: string;
    phone: string | null;
    passport: string | null;
    institute: string | null;
    reference: string | null;
}

const props = defineProps<{
    student: Student;
}>();

const form = useForm({
    name: props.student.name,
    roll_number: props.student.roll_number,
    email: props.student.email,
    password: '',
    phone: props.student.phone || '',
    passport: props.student.passport || '',
    institute: props.student.institute || '',
    reference: props.student.reference || '',
});

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const submit = () => {
    form.put(`/students/${props.student.id}`);
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Edit Student',
        href: dashboard().url,
    },
];
</script>
