<script setup lang="ts">
interface Props {
    value?: number;
    max?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    value: 0,
    max: 100,
    class: '',
});

const percentage = Math.min(Math.max((props.value / props.max) * 100, 0), 100);
</script>

<template>
    <div :class="['w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700', props.class]">
        <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-in-out"
            :style="{ width: `${percentage}%` }"
        ></div>
    </div>
</template>