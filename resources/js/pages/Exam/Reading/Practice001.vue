<script setup>
import C20Test1Reading from '@/components/Exam/Practice/C20/Test1/Reading/C20Test1Reading.vue';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: PracticeLayout,
});
</script>

<template>
    <Head title="C20 Test 1 Reading" />
    <C20Test1Reading />
</template>
