<script setup lang="ts">
import { provide, ref } from 'vue';

interface Props {
    modelValue?: string;
    placeholder?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Select an option',
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const isOpen = ref(false);
const selectedValue = ref(props.modelValue);
const selectedLabel = ref('');

const selectValue = (value: string, label: string) => {
    selectedValue.value = value;
    selectedLabel.value = label;
    emit('update:modelValue', value);
    isOpen.value = false;
};

provide('selectContext', {
    isOpen,
    selectedValue,
    selectedLabel,
    selectValue,
    placeholder: props.placeholder,
    disabled: props.disabled,
});
</script>

<template>
    <div class="relative">
        <slot />
    </div>
</template>