<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';

interface Props {
    examId: string;
    message?: string;
}

const { examId, message = 'Exam not found' } = defineProps<Props>();
</script>

<template>
    <Head title="Exam Not Found - IELTS Mock Test" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-lg">
            <div class="overflow-hidden rounded-lg bg-white shadow-lg">
                <!-- Header -->
                <div class="bg-red-600 px-6 py-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-500">
                        <span class="text-3xl">❌</span>
                    </div>
                    <h1 class="text-2xl font-bold text-white">Exam Not Found</h1>
                </div>

                <!-- Content -->
                <div class="px-6 py-8">
                    <div class="mb-6 text-center">
                        <p class="mb-4 text-lg text-gray-600">No corresponding exam found for exam ID:</p>
                        <div class="inline-block rounded-lg border bg-gray-100 px-4 py-3">
                            <span class="font-mono text-lg font-semibold text-gray-800">{{ examId }}</span>
                        </div>
                    </div>

                    <div class="mb-6 border-l-4 border-blue-400 bg-blue-50 p-4">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> Please check your exam ID and try again. If you continue to experience issues, contact your exam
                            coordinator.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <Link href="/exam-registration" class="block">
                            <Button class="w-full bg-blue-600 py-3 text-white hover:bg-blue-700"> Try Different Exam ID </Button>
                        </Link>

                        <Link href="/" class="block">
                            <Button variant="outline" class="w-full border-gray-300 py-3 hover:bg-gray-50"> Back to Home </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
