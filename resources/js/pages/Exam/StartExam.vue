<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';

interface Exam {
    id: number;
    name: string;
    exam_id: string;
    // Add other relevant exam properties here if needed
}

interface Section {
    name: string;
    duration: number;
    questions: number;
    description: string;
}

interface Props {
    slug: string;
    exam: Exam;
    sections: Section[];
}

const props = defineProps<Props>();
</script>

<template>
    <Head :title="`Start ${exam.name}`" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 p-6">
        <div class="container mx-auto max-w-4xl rounded-xl bg-white p-8 shadow-lg">
            <h1 class="mb-6 text-center text-4xl font-bold text-gray-900">Start Your Exam: {{ exam.name }}</h1>
            <p class="mb-8 text-center text-lg text-gray-700">
                Prepare yourself for the {{ exam.name }} mock test. Below are the sections you will be attempting.
            </p>

            <div class="space-y-6">
                <div
                    v-for="section in sections"
                    :key="section.name"
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-5 shadow-sm"
                >
                    <div>
                        <h2 class="text-2xl font-semibold text-blue-800">{{ section.name }}</h2>
                        <p class="text-gray-600">{{ section.description }}</p>
                        <p class="mt-1 text-sm text-gray-500">Duration: {{ section.duration }} mins | Questions: {{ section.questions }}</p>
                    </div>
                    <Link :href="route('exam.section', { slug: slug, section: section.name.toLowerCase() })">
                        <Button class="rounded-lg bg-blue-600 px-6 py-2 font-bold text-white hover:bg-blue-700"> Go to {{ section.name }} </Button>
                    </Link>
                </div>
            </div>

            <div class="mt-10 text-center">
                <p class="text-gray-500">Good luck with your exam!</p>
            </div>
        </div>
    </div>
</template>
