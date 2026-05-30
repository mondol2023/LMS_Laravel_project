<script setup lang="ts">
import { inject, computed } from 'vue';

interface Props {
    placeholder?: string;
}

const props = defineProps<Props>();

const selectContext = inject('selectContext') as any;

const displayValue = computed(() => {
    return selectContext?.selectedLabel.value || props.placeholder || selectContext?.placeholder || 'Select...';
});
</script>

<template>
    <span class="truncate">{{ displayValue }}</span>
</template>