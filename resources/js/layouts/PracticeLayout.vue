<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const links = [
    { href: '/practice', label: 'Learning Center', icon: 'book' },
    { href: '/listening', label: 'Listening', icon: 'headphones' },
    { href: '/reading', label: 'Reading', icon: 'file-text' },
    { href: '/writing', label: 'Writing', icon: 'edit' },
    { href: '/speaking', label: 'Speaking', icon: 'mic' },
];

const isActive = (href) => {
    if (href === '/practice') return currentUrl.value === '/practice';
    return currentUrl.value.startsWith(href);
};

// Mobile menu
const mobileOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Navbar -->
        <nav class="sticky top-0 z-50 w-full bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 shadow-lg">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo + Brand -->
                    <Link href="/" class="group flex items-center gap-3">
                        <img
                            src="/logo.png"
                            alt="English Therapy"
                            class="h-9 w-9 rounded-lg object-contain shadow-md transition-transform duration-300 group-hover:scale-105"
                        />
                        <div class="flex flex-col">
                            <span class="text-base leading-tight font-bold text-white">English Therapy</span>
                            <span class="text-[10px] leading-tight font-medium text-white/80">IELTS Success Partner</span>
                        </div>
                    </Link>

                    <!-- Desktop Nav -->
                    <div class="hidden items-center gap-1 md:flex">
                        <Link
                            v-for="link in links"
                            :key="link.href"
                            :href="link.href"
                            class="relative rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200"
                            :class="isActive(link.href) ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white'"
                        >
                            {{ link.label }}
                            <!-- Active indicator dot -->
                            <span
                                v-if="isActive(link.href)"
                                class="absolute bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-white"
                            ></span>
                        </Link>
                    </div>

                    <!-- Right side: Home + Dashboard buttons + Mobile toggle -->
                    <div class="flex items-center gap-3">
                        <Link
                            href="/"
                            class="hidden items-center gap-2 rounded-lg border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur-sm transition-all duration-200 hover:bg-white/20 hover:shadow-md sm:inline-flex"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                />
                            </svg>
                            Home
                        </Link>

                        <Link
                            href="/student/dashboard"
                            class="hidden items-center gap-2 rounded-lg border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur-sm transition-all duration-200 hover:bg-white/20 hover:shadow-md sm:inline-flex"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                            Dashboard
                        </Link>

                        <!-- Mobile hamburger -->
                        <button
                            @click="mobileOpen = !mobileOpen"
                            class="inline-flex cursor-pointer items-center justify-center rounded-lg p-2 text-white/90 transition-colors hover:bg-white/10 hover:text-white md:hidden"
                        >
                            <svg v-if="!mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="-translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-2 opacity-0"
            >
                <div v-if="mobileOpen" class="border-t border-gray-100 bg-white px-4 pt-2 pb-4 shadow-lg md:hidden">
                    <div class="flex flex-col gap-1">
                        <Link
                            v-for="link in links"
                            :key="link.href"
                            :href="link.href"
                            @click="mobileOpen = false"
                            class="rounded-lg px-4 py-3 text-sm font-medium transition-all duration-200"
                            :class="isActive(link.href) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                        >
                            {{ link.label }}
                        </Link>
                        <div class="my-1 border-t border-gray-100"></div>
                        <Link
                            href="/"
                            @click="mobileOpen = false"
                            class="flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-gray-900"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                />
                            </svg>
                            Back to Home
                        </Link>
                        <Link
                            href="/student/dashboard"
                            @click="mobileOpen = false"
                            class="flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-gray-900"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                            Dashboard
                        </Link>
                    </div>
                </div>
            </Transition>
        </nav>

        <main>
            <slot />
        </main>
    </div>
</template>
