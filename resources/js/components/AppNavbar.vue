<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    // Props for the main brand/logo link
    brandHref: {
        type: String,
        default: '/',
    },
    brandText: {
        type: String,
        default: 'IELTS Mock Test',
    },
    // Props for the main navigation links (e.g., for practice pages)
    navLinks: {
        type: Array,
        default: () => [],
    },
    currentUrl: {
        type: String,
        default: '/',
    },
    // Prop to determine if it's the practice navbar style
    isPracticeNavbar: {
        type: Boolean,
        default: false,
    },
});

const navbarClass = computed(() => {
    return props.isPracticeNavbar
        ? 'bg-gray-800 shadow-lg' // Dark background for practice navbar
        : 'border-b border-gray-200 bg-white/95 backdrop-blur-sm shadow-sm'; // Light background for welcome navbar
});

// Brand icon is now the logo.png image
</script>

<template>
    <nav :class="['sticky top-0 z-50', navbarClass]">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="/logo.png" alt="English Therapy" class="h-9 w-9 rounded-lg object-contain" />
                    <Link
                        :href="brandHref"
                        :class="[
                            'text-xl font-bold',
                            isPracticeNavbar ? 'text-white' : 'text-gray-900',
                            'transition-colors duration-200 hover:text-red-300',
                        ]"
                    >
                        {{ brandText }}
                    </Link>
                </div>

                <!-- Main Navigation Links (for practice pages) -->
                <div v-if="navLinks.length > 0" class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            :class="[
                                'rounded-md px-3 py-2 text-sm font-medium transition-all duration-200',
                                currentUrl.startsWith(link.href)
                                    ? 'transform bg-red-600 text-white hover:scale-105'
                                    : 'transform text-gray-300 hover:translate-y-[-2px] hover:bg-gray-700 hover:text-white',
                            ]"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </div>

                <!-- Right-aligned slot for buttons/login/etc. -->
                <div class="flex items-center gap-3">
                    <slot></slot>
                </div>
            </div>
        </div>
    </nav>
</template>
