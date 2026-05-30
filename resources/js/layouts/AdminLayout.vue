<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Admin Navigation -->
        <nav class="border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <div class="flex-shrink-0">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">IELTS Admin</h1>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <Link
                                :href="route('admin.dashboard')"
                                :class="[
                                    route().current('admin.dashboard')
                                        ? 'border-indigo-500 text-gray-900 dark:text-white'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium',
                                ]"
                            >
                                Dashboard
                            </Link>
                            <Link
                                :href="route('admin.exams.index')"
                                :class="[
                                    route().current('admin.exams.*')
                                        ? 'border-indigo-500 text-gray-900 dark:text-white'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium',
                                ]"
                            >
                                Exams
                            </Link>
                            <Link
                                :href="route('admin.results.index')"
                                :class="[
                                    route().current('admin.results.*')
                                        ? 'border-indigo-500 text-gray-900 dark:text-white'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium',
                                ]"
                            >
                                Results
                            </Link>
                            <Link
                                href="/billing"
                                :class="[
                                    $page.url.startsWith('/billing')
                                        ? 'border-indigo-500 text-gray-900 dark:text-white'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                    'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium',
                                ]"
                            >
                                Billing
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex rounded-full bg-white text-sm focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:bg-gray-800"
                            >
                                <span class="sr-only">Open user menu</span>
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500">
                                    <span class="text-sm font-medium text-white">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                            </button>

                            <!-- User dropdown -->
                            <div
                                v-show="showUserMenu"
                                @click.away="showUserMenu = false"
                                class="ring-opacity-5 absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black focus:outline-none dark:bg-gray-800"
                            >
                                <div class="border-b border-gray-200 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                                    {{ $page.props.auth.user.name }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Admin</div>
                                </div>
                                <Link
                                    :href="route('admin.logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    Sign out
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Page header -->
                <div v-if="title" class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
                </div>

                <!-- Page content -->
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    title: String,
});

const showUserMenu = ref(false);
</script>
