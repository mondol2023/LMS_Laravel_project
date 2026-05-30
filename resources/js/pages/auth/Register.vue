<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form, Head, Link } from '@inertiajs/vue3';
import { BookOpen, Eye, EyeOff, Headphones, LoaderCircle, MessageSquare, PenTool } from 'lucide-vue-next';
import { ref } from 'vue';

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
</script>

<template>
    <Head title="Create Account - English Therapy" />

    <div class="flex min-h-screen overflow-hidden bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <!-- Left Side - Branding -->
        <div class="relative hidden overflow-hidden lg:flex lg:w-1/2">
            <!-- Gradient Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-700">
                <!-- Mesh Background -->
                <div class="absolute inset-0 opacity-30">
                    <div class="absolute top-0 left-0 h-96 w-96 animate-pulse rounded-full bg-purple-500 mix-blend-multiply blur-3xl filter"></div>
                    <div
                        class="absolute right-0 bottom-0 h-96 w-96 animate-pulse rounded-full bg-indigo-500 mix-blend-multiply blur-3xl filter"
                        style="animation-delay: 2s"
                    ></div>
                    <div
                        class="absolute top-1/2 left-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 animate-pulse rounded-full bg-blue-400 mix-blend-multiply blur-3xl filter"
                        style="animation-delay: 4s"
                    ></div>
                </div>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex w-full flex-col items-center justify-center p-12">
                <!-- Logo -->
                <Link href="/" class="group mb-8">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-xl font-bold text-blue-600 shadow-2xl transition-transform duration-300 group-hover:scale-110"
                        >
                            EP
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xl font-bold text-white">English Therapy</span>
                            <span class="text-sm text-white/80">IELTS Success Partner</span>
                        </div>
                    </div>
                </Link>

                <!-- Main Content -->
                <div class="mb-8 max-w-md text-center">
                    <h1 class="mb-4 text-3xl font-bold text-white">Start Your Journey</h1>
                    <p class="text-lg leading-relaxed text-blue-100">
                        Join thousands of students achieving their dream IELTS scores with expert guidance.
                    </p>
                </div>

                <!-- Feature Cards -->
                <div class="grid w-full max-w-md grid-cols-2 gap-4">
                    <div class="rounded-xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="mb-2 w-fit rounded-lg bg-green-400 p-2">
                            <Headphones class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="mb-1 text-sm font-semibold text-white">Listening</h3>
                        <p class="text-xs text-white/70">Practice tests</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="mb-2 w-fit rounded-lg bg-purple-400 p-2">
                            <BookOpen class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="mb-1 text-sm font-semibold text-white">Reading</h3>
                        <p class="text-xs text-white/70">Comprehension</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="mb-2 w-fit rounded-lg bg-orange-400 p-2">
                            <PenTool class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="mb-1 text-sm font-semibold text-white">Writing</h3>
                        <p class="text-xs text-white/70">Essay practice</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="mb-2 w-fit rounded-lg bg-pink-400 p-2">
                            <MessageSquare class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="mb-1 text-sm font-semibold text-white">Speaking</h3>
                        <p class="text-xs text-white/70">Conversation</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="mt-8 flex justify-center gap-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">10K+</div>
                        <div class="text-sm text-blue-200">Students</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">500+</div>
                        <div class="text-sm text-blue-200">Tests</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">7.5</div>
                        <div class="text-sm text-blue-200">Avg. Score</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="flex w-full flex-col justify-center overflow-y-auto bg-white px-6 py-8 sm:px-12 lg:w-1/2 lg:px-16 xl:px-20">
            <div class="mx-auto w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="mb-8 text-center lg:hidden">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 text-base font-bold text-white shadow-lg"
                        >
                            EP
                        </div>
                        <div class="text-left">
                            <div class="text-lg font-bold text-gray-900">English Therapy</div>
                            <div class="text-xs text-gray-500">IELTS Success Partner</div>
                        </div>
                    </Link>
                </div>

                <!-- Header -->
                <div class="mb-8">
                    <h2 class="mb-2 text-3xl font-bold text-gray-900">Create account</h2>
                    <p class="text-gray-600">Join thousands of students preparing for IELTS success</p>
                </div>

                <!-- Registration Form -->
                <Form
                    v-bind="RegisteredUserController.store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="space-y-5"
                >
                    <!-- Name Field -->
                    <div>
                        <Label for="name" class="mb-2 block text-sm font-semibold text-gray-700"> Full Name </Label>
                        <Input
                            id="name"
                            type="text"
                            name="name"
                            autocomplete="name"
                            placeholder="John Doe"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                        <InputError :message="errors.name" class="mt-2 text-xs" />
                    </div>

                    <!-- Email Field -->
                    <div>
                        <Label for="email" class="mb-2 block text-sm font-semibold text-gray-700"> Email Address </Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                        <InputError :message="errors.email" class="mt-2 text-xs" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <Label for="password" class="mb-2 block text-sm font-semibold text-gray-700"> Password </Label>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                autocomplete="new-password"
                                placeholder="Create password"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-11 text-gray-900 placeholder-gray-400 shadow-sm transition-all focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600 focus:outline-none"
                            >
                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">Must be at least 8 characters</p>
                        <InputError :message="errors.password" class="mt-1.5 text-xs" />
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <Label for="password_confirmation" class="mb-2 block text-sm font-semibold text-gray-700"> Confirm Password </Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-11 text-gray-900 placeholder-gray-400 shadow-sm transition-all focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600 focus:outline-none"
                            >
                                <Eye v-if="!showPasswordConfirmation" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="errors.password_confirmation" class="mt-2 text-xs" />
                    </div>

                    <!-- Phone & Passport Row -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <Label for="phone" class="mb-2 block text-sm font-semibold text-gray-700">
                                Phone <span class="font-normal text-gray-400">(Optional)</span>
                            </Label>
                            <Input
                                id="phone"
                                type="tel"
                                name="phone"
                                autocomplete="tel"
                                placeholder="+1 555 123 4567"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <InputError :message="errors.phone" class="mt-2 text-xs" />
                        </div>
                        <div>
                            <Label for="passport" class="mb-2 block text-sm font-semibold text-gray-700">
                                Passport <span class="font-normal text-gray-400">(Optional)</span>
                            </Label>
                            <Input
                                id="passport"
                                type="text"
                                name="passport"
                                placeholder="AB123456"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm transition-all focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <InputError :message="errors.passport" class="mt-2 text-xs" />
                        </div>
                    </div>

                    <!-- Create Account Button -->
                    <Button
                        type="submit"
                        :disabled="processing"
                        class="flex w-full items-center justify-center rounded-xl border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3.5 text-base font-semibold text-white shadow-lg transition-all hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <LoaderCircle v-if="processing" class="mr-2 h-5 w-5 animate-spin" />
                        <span v-if="processing">Creating account...</span>
                        <span v-else>Create your account</span>
                    </Button>

                    <!-- Sign In Link -->
                    <div class="flex items-center justify-center gap-2 pt-2">
                        <span class="text-sm text-gray-500">Already have an account?</span>
                        <Link href="/login" class="text-sm font-semibold text-blue-600 transition-colors hover:text-blue-700"> Sign in </Link>
                    </div>
                </Form>

                <!-- Footer -->
                <p class="mt-6 text-center text-sm text-gray-500">
                    By creating an account, you agree to our
                    <Link href="/terms-and-conditions" class="font-medium text-blue-600 hover:text-blue-700">Terms</Link>
                    and
                    <Link href="/privacy-policy" class="font-medium text-blue-600 hover:text-blue-700">Privacy Policy</Link>
                </p>
            </div>
        </div>
    </div>
</template>
