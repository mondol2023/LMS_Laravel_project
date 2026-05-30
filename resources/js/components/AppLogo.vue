<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link href="/" class="flex items-center gap-2">
        <div class="relative flex size-11 items-center justify-center">
            <!-- Spinning border only -->
            <div class="spinning-border absolute inset-0 rounded-full"></div>
            <!-- Static white background -->
            <div class="absolute inset-[2px] rounded-full bg-white dark:bg-gray-800"></div>
            <!-- Static logo -->
            <img src="/logo.png" alt="English Therapy" class="relative z-10 size-9 rounded-full object-contain" />
        </div>
        <div class="grid flex-1 text-left">
            <span class="truncate text-lg leading-tight font-bold text-gray-900">English Therapy</span>
            <span class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">IELTS Success Partner</span>
        </div>
    </Link>
</template>

<style scoped>
.spinning-border {
    background: conic-gradient(from 0deg, #ef4444, #f97316, #ef4444);
    animation: spin-border 2s linear infinite;
}

@keyframes spin-border {
    to {
        transform: rotate(360deg);
    }
}
</style>
