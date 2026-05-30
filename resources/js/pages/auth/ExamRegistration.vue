<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const examId = ref('');
const name = ref('');
const email = ref('');
const phone = ref('');
const passport = ref('');
const isProcessing = ref(false);
const errors = ref({});

const handleSubmit = async () => {
    const userExamId = examId.value.trim();
    console.log('Submitting exam registration for Exam ID:', userExamId);
    router.visit(`/exam/${userExamId}`);
};
</script>

<template>
    <Head title="Exam Registration - IELTS Mock Test" />

    <div class="flex min-h-screen flex-col justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 px-4 py-6 sm:px-6 sm:py-12 lg:px-8">
        <div class="mx-auto w-full max-w-2xl">
            <div class="rounded-3xl border border-gray-100 bg-white px-8 py-12 shadow-2xl backdrop-blur-sm">
                <!-- Header -->
                <div class="mb-10 text-center">
                    <Link
                        href="/"
                        class="group mb-8 inline-flex items-center gap-3 text-2xl font-bold text-gray-900 transition-all duration-200 hover:text-gray-700"
                    >
                        <div class="text-4xl transition-transform duration-200 group-hover:scale-110">📝</div>
                        <span>IELTS Mock Test</span>
                    </Link>

                    <div class="space-y-4">
                        <h1 class="text-4xl font-bold text-gray-900">Start Your IELTS Exam</h1>
                        <p class="text-lg leading-relaxed text-gray-600">Enter your exam details and personal information to begin</p>
                    </div>
                </div>

                <!-- Registration Form -->
                <form @submit.prevent="handleSubmit" class="space-y-8">
                    <!-- General Error Message -->
                    <div v-if="errors.general" class="rounded-2xl border-2 border-red-200 bg-red-50 p-4 text-center">
                        <p class="text-sm font-medium text-red-800">{{ errors.general }}</p>
                    </div>
                    <div class="grid gap-6">
                        <!-- Exam ID -->
                        <div class="space-y-3">
                            <Label for="exam_id" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase"> Exam ID * </Label>
                            <Input
                                id="exam_id"
                                v-model="examId"
                                type="text"
                                required
                                autofocus
                                placeholder="e.g. ACAD001, GEN001, PREP001"
                                class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 font-mono text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <p v-if="errors.exam_id" class="text-sm text-red-500">{{ errors.exam_id }}</p>
                        </div>

                        <!-- Name -->
                        <div class="space-y-3">
                            <Label for="name" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase"> Full Name * </Label>
                            <Input
                                id="name"
                                v-model="name"
                                type="text"
                                required
                                placeholder="Enter your full name"
                                class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <p v-if="errors.name" class="text-sm text-red-500">{{ errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-3">
                            <Label for="email" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase"> Email Address * </Label>
                            <Input
                                id="email"
                                v-model="email"
                                type="email"
                                required
                                placeholder="Enter your email address"
                                class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <p v-if="errors.email" class="text-sm text-red-500">{{ errors.email }}</p>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Phone -->
                            <div class="space-y-3">
                                <Label for="phone" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase"> Phone Number * </Label>
                                <Input
                                    id="phone"
                                    v-model="phone"
                                    type="tel"
                                    required
                                    placeholder="+1 (555) 123-4567"
                                    class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                />
                                <p v-if="errors.phone" class="text-sm text-red-500">{{ errors.phone }}</p>
                            </div>

                            <!-- Passport -->
                            <div class="space-y-3">
                                <Label for="passport" class="block text-sm font-semibold tracking-wide text-gray-700 uppercase">
                                    Passport Number *
                                </Label>
                                <Input
                                    id="passport"
                                    v-model="passport"
                                    type="text"
                                    required
                                    placeholder="AB123456"
                                    class="w-full rounded-2xl border-2 border-gray-200 bg-gray-50 px-6 py-4 text-lg text-gray-900 placeholder-gray-400 shadow-sm transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                />
                                <p v-if="errors.passport" class="text-sm text-red-500">{{ errors.passport }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Submit Button -->
                    <div class="pt-4">
                        <Button
                            type="submit"
                            :disabled="isProcessing || !examId.trim() || !name.trim() || !email.trim() || !phone.trim() || !passport.trim()"
                            class="flex w-full justify-center rounded-2xl border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5 text-xl font-bold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="isProcessing">Starting Exam...</span>
                            <span v-else>Start My Exam</span>
                        </Button>
                    </div>

                    <!-- Back Link -->
                    <div class="pt-4 text-center">
                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 font-medium text-gray-600 transition-colors duration-200 hover:text-gray-900"
                        >
                            <span>←</span>
                            <span>Back to Home</span>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
