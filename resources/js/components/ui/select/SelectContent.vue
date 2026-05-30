<script setup lang="ts">
import { inject } from 'vue';

const selectContext = inject('selectContext') as any;
</script>

<template>
    <div
        v-if="selectContext?.isOpen.value"
        class="absolute top-full z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg dark:bg-gray-800 dark:border-gray-700"
    >
        <div class="max-h-60 overflow-auto p-1">
            <slot />
        </div>
    </div>
</template>