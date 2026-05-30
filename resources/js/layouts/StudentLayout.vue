<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { BarChart3, BookOpen, ChevronDown, FileText, Headphones, Home, LayoutDashboard, LogOut, Menu, User, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    title?: string;
}

defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const showMobileMenu = ref(false);
const showUserMenu = ref(false);

const navItems = [
    { title: 'Dashboard', href: '/student/dashboard', icon: LayoutDashboard },
    { title: 'My Results', href: '/student/my-results', icon: FileText },
    { title: 'Reports', href: '/student/reports', icon: BarChart3 },
    { title: 'Profile', href: '/student/profile', icon: User },
];

const practiceItems = [
    { title: 'Listening Practice', href: '/listening', icon: Headphones },
    { title: 'Reading Practice', href: '/reading', icon: BookOpen },
];

const isActive = (href: string) => {
    return page.url.startsWith(href);
};

const handleLogout = () => {
    router.post('/logout');
};

const closeUserMenu = () => {
    showUserMenu.value = false;
};
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50">
        <!-- Top Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-200/50 bg-white/80 shadow-sm backdrop-blur-xl">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <!-- Logo & Mobile Menu Button -->
                    <div class="flex items-center gap-4">
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="rounded-xl p-2 text-gray-600 transition-colors hover:bg-gray-100 lg:hidden"
                        >
                            <Menu v-if="!showMobileMenu" class="h-5 w-5" />
                            <X v-else class="h-5 w-5" />
                        </button>
                        <Link href="/" class="flex items-center gap-3">
                            <img src="/logo.png" alt="English Therapy" class="h-9 w-9 rounded-xl object-contain shadow-lg shadow-blue-500/20" />
                            <span class="bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-lg font-bold text-transparent">
                                English Therapy
                            </span>
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden items-center space-x-1 lg:flex">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                isActive(item.href) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200',
                            ]"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>

                        <!-- Practice Dropdown -->
                        <div class="group relative">
                            <button
                                class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-gray-900"
                            >
                                <BookOpen class="h-4 w-4" />
                                Practice
                                <ChevronDown class="h-3 w-3" />
                            </button>
                            <div
                                class="invisible absolute top-full left-0 mt-1 w-48 rounded-xl border border-gray-100 bg-white opacity-0 shadow-lg transition-all duration-200 group-hover:visible group-hover:opacity-100"
                            >
                                <div class="py-2">
                                    <Link
                                        v-for="item in practiceItems"
                                        :key="item.href"
                                        :href="item.href"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        <component :is="item.icon" class="h-4 w-4 text-gray-400" />
                                        {{ item.title }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-3 rounded-xl px-3 py-2 transition-colors hover:bg-gray-100"
                            >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-md"
                                >
                                    <span class="text-sm font-semibold text-white">
                                        {{ user?.name?.charAt(0)?.toUpperCase() || 'S' }}
                                    </span>
                                </div>
                                <div class="hidden text-left sm:block">
                                    <p class="text-sm font-medium text-gray-900">{{ user?.name || 'Student' }}</p>
                                    <p class="text-xs text-gray-500">{{ user?.roll_number || user?.email }}</p>
                                </div>
                                <ChevronDown class="hidden h-4 w-4 text-gray-400 sm:block" />
                            </button>

                            <!-- Dropdown -->
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div
                                    v-show="showUserMenu"
                                    @click.away="closeUserMenu"
                                    class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-black/5"
                                >
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-4 py-4">
                                        <p class="text-sm font-semibold text-white">{{ user?.name }}</p>
                                        <p class="text-xs text-blue-100">{{ user?.email }}</p>
                                        <p v-if="user?.roll_number" class="mt-1 text-xs text-blue-100">Roll: {{ user?.roll_number }}</p>
                                    </div>
                                    <div class="py-2">
                                        <Link
                                            href="/"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
                                            @click="closeUserMenu"
                                        >
                                            <Home class="h-4 w-4 text-gray-400" />
                                            Back to Home
                                        </Link>
                                        <Link
                                            href="/student/profile"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
                                            @click="closeUserMenu"
                                        >
                                            <User class="h-4 w-4 text-gray-400" />
                                            Profile Settings
                                        </Link>
                                        <hr class="my-2 border-gray-100" />
                                        <button
                                            @click="handleLogout"
                                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50"
                                        >
                                            <LogOut class="h-4 w-4" />
                                            Sign out
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-show="showMobileMenu" class="border-t border-gray-200 bg-white lg:hidden">
                    <div class="space-y-1 px-3 py-4">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                isActive(item.href) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50',
                                'flex items-center gap-3 rounded-xl px-4 py-3 text-base font-medium',
                            ]"
                            @click="showMobileMenu = false"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                            {{ item.title }}
                        </Link>

                        <div class="mt-2 border-t border-gray-100 pt-2">
                            <p class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-400 uppercase">Practice</p>
                            <Link
                                v-for="item in practiceItems"
                                :key="item.href"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-xl px-4 py-3 text-base font-medium text-gray-600 hover:bg-gray-50"
                                @click="showMobileMenu = false"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                                {{ item.title }}
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- Page Content -->
        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <div v-if="title" class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ title }}</h1>
            </div>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-gray-200 bg-white/50">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                    <img src="/logo.png" alt="English Therapy" class="h-5 w-5 rounded object-contain" />
                    <p>English Therapy - IELTS Success Partner. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
