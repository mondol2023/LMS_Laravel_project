<script setup lang="ts">
import { inject } from 'vue';
import { Button } from '@/components/ui/button';

const selectContext = inject('selectContext') as any;
</script>

<template>
    <Button
        variant="outline"
        role="combobox"
        :aria-expanded="selectContext?.isOpen.value"
        class="w-full justify-between"
        @click="selectContext && (selectContext.isOpen.value = !selectContext.isOpen.value)"
        :disabled="selectContext?.disabled"
    >
        <slot />
        <svg
            class="ml-2 h-4 w-4 shrink-0 opacity-50"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m6 9 6 6 6-6"
            />
        </svg>
    </Button>
</template>