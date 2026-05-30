<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Exam {
    id: number;
    name: string;
    exam_id: string;
    description?: string;
    created_at: string;
}

interface Props {
    auth?: {
        user: any;
    };
    exams: Exam[];
}

defineProps<Props>();

// Modal state
const showModal = ref(false);
const selectedExam = ref<Exam | null>(null);

// Registration form
interface RegistrationForm {
    name: string;
    phone: string;
    email: string;
    passport: string;
    local_exam_id: string;
}

const registrationForm = useForm<RegistrationForm>({
    name: '',
    phone: '',
    email: '',
    passport: '',
    local_exam_id: '',
});

const openExamModal = (exam: Exam) => {
    selectedExam.value = exam;
    registrationForm.local_exam_id = exam.exam_id;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedExam.value = null;
    registrationForm.reset();
};

const submitRegistration = () => {
    console.log(selectedExam.value);
    if (!selectedExam.value) return;

    registrationForm.post(`/exam/${selectedExam.value.exam_id}/register`, {
        onSuccess: () => {
            // save user phone to local storage
            // normalize phone if +88 prefix remove it
            if (registrationForm.phone.startsWith('+88')) {
                registrationForm.phone = registrationForm.phone.slice(3);
            }
            if (registrationForm.phone.startsWith('88')) {
                registrationForm.phone = registrationForm.phone.slice(2);
            }
            localStorage.setItem('userPhone', registrationForm.phone);
            closeModal();
        },
        onError: () => {
            // Keep modal open if there are errors
        },
    });
};
</script>

<template>
    <Head title="Available IELTS Mock Exams" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur-sm">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Link href="/" class="flex items-center gap-3">
                            <div class="text-3xl">📝</div>
                            <h1 class="text-xl font-bold text-gray-900">IELTS Mock Test</h1>
                        </Link>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link href="/">
                            <Button
                                variant="outline"
                                class="rounded-full border-gray-300 px-6 py-2 font-medium text-gray-700 transition-all duration-200 hover:bg-gray-50"
                            >
                                Back to Home
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Header Section -->
        <section class="container mx-auto px-4 py-12">
            <div class="text-center">
                <div class="space-y-6">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700"
                    >
                        🎯 Choose Your IELTS Practice Test
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 md:text-5xl">
                        Available
                        <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Mock Exams</span>
                    </h1>
                    <p class="mx-auto max-w-2xl text-lg leading-relaxed text-gray-600">
                        Select from our comprehensive collection of IELTS practice exams designed to simulate real test conditions and help you
                        achieve your target band score.
                    </p>
                </div>
            </div>
        </section>

        <!-- Available Exams Section -->
        <section class="container mx-auto px-4 py-12">
            <div v-if="exams && exams.length > 0" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="exam in exams"
                    :key="exam.id"
                    class="group cursor-pointer rounded-2xl border border-gray-100 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-xl"
                    @click="openExamModal(exam)"
                >
                    <div class="text-center">
                        <div
                            class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-r from-blue-100 to-indigo-100 shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:from-blue-200 group-hover:to-indigo-200"
                        >
                            <span class="text-3xl">📝</span>
                        </div>
                        <div class="mb-4">
                            <div class="mb-3 inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                ID: {{ exam.exam_id }}
                            </div>
                        </div>
                        <h3 class="mb-4 line-clamp-2 text-xl font-bold text-gray-900 group-hover:text-blue-900">
                            {{ exam.name }}
                        </h3>
                        <p v-if="exam.description" class="mb-6 line-clamp-3 leading-relaxed text-gray-600">
                            {{ exam.description }}
                        </p>
                        <div class="pt-4">
                            <Button
                                class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-lg"
                            >
                                Register for Exam
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-16 text-center">
                <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100">
                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
                <h3 class="mb-4 text-2xl font-bold text-gray-900">No Exams Available</h3>
                <p class="mb-8 text-lg text-gray-600">There are currently no mock exams available. Please check back later.</p>
                <Link href="/">
                    <Button class="rounded-xl bg-blue-600 px-8 py-3 font-semibold text-white transition-all duration-200 hover:bg-blue-700">
                        Back to Home
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Features Section -->
        <section class="bg-gradient-to-r from-blue-50 to-indigo-50 py-16">
            <div class="container mx-auto px-4">
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">What's Included in Each Exam</h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600">
                        Every mock exam includes all four IELTS sections with realistic test conditions
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="text-center">
                            <div class="mb-4 text-4xl">🎧</div>
                            <h3 class="mb-3 text-lg font-bold text-gray-900">Listening</h3>
                            <p class="text-sm leading-relaxed text-gray-600">30 minutes of authentic audio materials with automatic scoring</p>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="text-center">
                            <div class="mb-4 text-4xl">📖</div>
                            <h3 class="mb-3 text-lg font-bold text-gray-900">Reading</h3>
                            <p class="text-sm leading-relaxed text-gray-600">60 minutes with 3 passages and instant scoring</p>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="text-center">
                            <div class="mb-4 text-4xl">✍️</div>
                            <h3 class="mb-3 text-lg font-bold text-gray-900">Writing</h3>
                            <p class="text-sm leading-relaxed text-gray-600">60 minutes for Task 1 and Task 2 with detailed feedback</p>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="text-center">
                            <div class="mb-4 text-4xl">🎤</div>
                            <h3 class="mb-3 text-lg font-bold text-gray-900">Speaking</h3>
                            <p class="text-sm leading-relaxed text-gray-600">11-14 minutes recording all three parts for evaluation</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Exam Registration Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50" @click="closeModal"></div>

                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white shadow-2xl transition-all">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="bg-opacity-20 flex h-12 w-12 items-center justify-center rounded-full bg-white">
                                        <span class="text-2xl">📝</span>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold">Register for Exam</h3>
                                        <p class="text-blue-100">{{ selectedExam?.name }}</p>
                                        <p class="text-sm text-blue-200">Exam ID: {{ selectedExam?.exam_id }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="closeModal"
                                    class="hover:bg-opacity-20 focus:ring-opacity-50 cursor-pointer rounded-full p-2 text-white hover:bg-black focus:ring-2 focus:ring-white focus:outline-none"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <div class="p-8">
                            <form @submit.prevent="submitRegistration" class="space-y-6">
                                <!-- Name -->
                                <div class="space-y-2">
                                    <label for="modal-name" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="modal-name"
                                        v-model="registrationForm.name"
                                        type="text"
                                        required
                                        class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': registrationForm.errors.name }"
                                        placeholder="Enter your full name"
                                    />
                                    <p v-if="registrationForm.errors.name" class="text-sm text-red-600">
                                        {{ registrationForm.errors.name }}
                                    </p>
                                </div>

                                <!-- Email -->
                                <div class="space-y-2">
                                    <label for="modal-email" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="modal-email"
                                        v-model="registrationForm.email"
                                        type="email"
                                        required
                                        class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': registrationForm.errors.email }"
                                        placeholder="Enter your email address"
                                    />
                                    <p v-if="registrationForm.errors.email" class="text-sm text-red-600">
                                        {{ registrationForm.errors.email }}
                                    </p>
                                </div>

                                <div class="grid gap-6 md:grid-cols-2">
                                    <!-- Phone -->
                                    <div class="space-y-2">
                                        <label for="modal-phone" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase">
                                            Phone Number <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="modal-phone"
                                            v-model="registrationForm.phone"
                                            type="tel"
                                            required
                                            class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': registrationForm.errors.phone }"
                                            placeholder="+1 (555) 123-4567"
                                        />
                                        <p v-if="registrationForm.errors.phone" class="text-sm text-red-600">
                                            {{ registrationForm.errors.phone }}
                                        </p>
                                    </div>

                                    <!-- Passport -->
                                    <div class="space-y-2">
                                        <label for="modal-passport" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase">
                                            Passport Number <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="modal-passport"
                                            v-model="registrationForm.passport"
                                            type="text"
                                            required
                                            class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': registrationForm.errors.passport }"
                                            placeholder="AB123456"
                                        />
                                        <p v-if="registrationForm.errors.passport" class="text-sm text-red-600">
                                            {{ registrationForm.errors.passport }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="flex gap-4 pt-6">
                                    <button
                                        @click="closeModal"
                                        type="button"
                                        class="flex-1 cursor-pointer rounded-2xl border-2 border-gray-300 bg-white px-6 py-4 text-lg font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:outline-none"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="registrationForm.processing"
                                        class="flex-1 cursor-pointer rounded-2xl border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-lg font-bold text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-indigo-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <svg
                                            v-if="registrationForm.processing"
                                            class="mr-3 -ml-1 inline h-5 w-5 animate-spin text-white"
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
                                        {{ registrationForm.processing ? 'Starting Exam...' : 'Start My Exam' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
