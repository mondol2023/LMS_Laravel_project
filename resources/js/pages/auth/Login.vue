<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form, Head, Link } from '@inertiajs/vue3';
import { BookOpen, Eye, EyeOff, Headphones, LoaderCircle, MessageSquare, PenTool } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const showPassword = ref(false);
</script>

<template>
    <Head title="Log in - English Therapy" />

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
                        <img
                            src="/logo.png"
                            alt="English Therapy"
                            class="h-14 w-14 rounded-2xl shadow-2xl transition-transform duration-300 group-hover:scale-110"
                        />
                        <div class="flex flex-col">
                            <span class="text-2xl font-bold text-white">English Therapy</span>
                            <span class="text-sm text-white/80">IELTS Success Partner</span>
                        </div>
                    </div>
                </Link>

                <!-- Main Content -->
                <div class="mb-8 max-w-md text-center">
                    <h1 class="mb-4 text-3xl font-bold text-white">Welcome Back!</h1>
                    <p class="text-lg leading-relaxed text-blue-100">
                        Continue your journey to IELTS excellence with expert guidance and comprehensive practice.
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

        <!-- Right Side - Login Form -->
        <div class="flex w-full flex-col justify-center overflow-y-auto bg-white px-6 py-8 sm:px-12 lg:w-1/2 lg:px-16 xl:px-20">
            <div class="mx-auto w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="mb-8 text-center lg:hidden">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <img
                            src="/logo.png"
                            alt="English Therapy"
                            class="h-10 w-10 rounded-lg shadow-lg"
                        />
                        <div class="text-left">
                            <div class="text-lg font-bold text-gray-900">English Therapy</div>
                            <div class="text-xs text-gray-500">IELTS Success Partner</div>
                        </div>
                    </Link>
                </div>

                <!-- Header -->
                <div class="mb-8">
                    <h2 class="mb-2 text-3xl font-bold text-gray-900">Sign in</h2>
                    <p class="text-gray-600">Enter your credentials to access your account</p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3">
                    <div class="flex items-center gap-2 text-sm font-medium text-green-800">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{ status }}
                    </div>
                </div>

                <!-- Login Form -->
                <Form
                    v-bind="AuthenticatedSessionController.store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-5"
                >
                    <!-- Email Field -->
                    <div>
                        <Label for="email" class="mb-2 block text-sm font-semibold text-gray-700"> Email address </Label>
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
                        <div class="mb-2 flex items-center justify-between">
                            <Label for="password" class="block text-sm font-semibold text-gray-700"> Password </Label>
                            <Link
                                v-if="canResetPassword"
                                href="/forgot-password"
                                class="text-sm font-medium text-blue-600 transition-colors hover:text-blue-700"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-12 text-gray-900 placeholder-gray-400 shadow-sm transition-all focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600 focus:outline-none"
                            >
                                <EyeOff v-if="showPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="errors.password" class="mt-2 text-xs" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <Checkbox id="remember" name="remember" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <Label for="remember" class="ml-2.5 block text-sm text-gray-700"> Remember me for 30 days </Label>
                    </div>

                    <!-- Login Button -->
                    <Button
                        type="submit"
                        :disabled="processing"
                        class="flex w-full items-center justify-center rounded-xl border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3.5 text-base font-semibold text-white shadow-lg transition-all hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <LoaderCircle v-if="processing" class="mr-2 h-5 w-5 animate-spin" />
                        <span v-if="processing">Signing in...</span>
                        <span v-else>Sign in to your account</span>
                    </Button>

                    <!-- Divider -->
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-white px-4 text-gray-500">New to English Therapy?</span>
                        </div>
                    </div>

                    <!-- Sign Up Link -->
                    <Link
                        href="/register"
                        class="flex w-full items-center justify-center rounded-xl border-2 border-gray-200 bg-white px-4 py-3.5 text-base font-semibold text-gray-700 transition-all hover:border-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Create a free account
                    </Link>
                </Form>

                <!-- Footer -->
                <p class="mt-6 text-center text-sm text-gray-500">
                    By signing in, you agree to our
                    <Link href="/terms-and-conditions" class="font-medium text-blue-600 hover:text-blue-700">Terms</Link>
                    and
                    <Link href="/privacy-policy" class="font-medium text-blue-600 hover:text-blue-700">Privacy Policy</Link>
                </p>
            </div>
        </div>
    </div>
</template>
