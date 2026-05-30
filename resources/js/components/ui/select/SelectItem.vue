<script setup lang="ts">
import { inject } from 'vue';

interface Props {
    value: string;
}

const props = defineProps<Props>();

const selectContext = inject('selectContext') as any;

const handleSelect = () => {
    const label = props.value; // You can customize this to use slot content if needed
    selectContext?.selectValue(props.value, label);
};
</script>

<template>
    <div
        class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-gray-100 focus:bg-gray-100 dark:hover:bg-gray-700 dark:focus:bg-gray-700"
        @click="handleSelect"
    >
        <slot />
    </div>
</template>