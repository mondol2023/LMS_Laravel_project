<script setup>
import C20Test2Reading from '@/components/Exam/Practice/C20/Test2/Reading/C20Test2Reading.vue';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: PracticeLayout,
});
</script>

<template>
    <Head title="C20 Test 2 Reading" />
    <C20Test2Reading />
</template>
