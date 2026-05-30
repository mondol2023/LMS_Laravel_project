<script setup lang="ts">
import C20Test4ListeningPart3 from '@/components/Exam/Practice/C20/Test4/Listening/C20Test4ListeningPart3.vue';
import ListeningPartHeader from '@/components/Listening/ListeningPartHeader.vue';
import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const testNumber = 4;
const partNumber = 3;
const audioSrc = '/audio/Practice/C20T4P3.mp3'; // C20 Test 4 full audio

const correctAnswers: Record<number, string> = {
    21: 'C/E',
    22: 'C/E',
    23: 'A/C',
    24: 'A/C',
    25: 'C',
    26: 'A',
    27: 'A',
    28: 'B',
    29: 'B',
    30: 'C',
};

const listeningPartRef = ref<any>();
const { calculateBandScore } = useIeltsScoring();

const notes = computed(() => listeningPartRef.value?.notes || []);

const handleDeleteNote = (noteId: number) => {
    listeningPartRef.value?.deleteNote?.(noteId);
};

const handleFocusNote = (note: any) => {
    listeningPartRef.value?.focusNote?.(note);
};

const submitAnswers = () => {
    const userAnswers = listeningPartRef.value?.getAnswers?.() || {};

    const formattedUserAnswers: Record<number, string> = {};
    Object.keys(userAnswers).forEach((key) => {
        const num = parseInt(key.replace('q', ''));
        formattedUserAnswers[num] = userAnswers[key];
    });

    let correctCount = 0;
    Object.keys(correctAnswers).forEach((key) => {
        const qNum = parseInt(key);
        const userAnswer = (formattedUserAnswers[qNum] || '').trim().toUpperCase();
        const correct = correctAnswers[qNum].toUpperCase();
        const possibleAnswers = correct.split('/').map((a) => a.trim().toUpperCase());
        if (possibleAnswers.includes(userAnswer)) {
            correctCount++;
        }
    });

    const totalQuestions = Object.keys(correctAnswers).length;
    const incorrectCount = totalQuestions - correctCount;
    const bandScore = calculateBandScore(correctCount, 10);

    router.post(
        `/practice/results/listening/${testNumber}`,
        {
            userAnswers: formattedUserAnswers,
            correctAnswers,
            score: { correct: correctCount, incorrect: incorrectCount, bandScore },
            partNumber,
        },
        {
            preserveState: false,
            onSuccess: () => {
                router.visit(`/practice/results/listening/${testNumber}?part=${partNumber}`);
            },
        },
    );
};
</script>

<template>
    <Head title="Listening Test 4 - Part 3" />
    <div class="min-h-screen bg-gray-50">
        <ListeningPartHeader
            :test-number="testNumber"
            :part-number="partNumber"
            :audio-src="audioSrc"
            :notes="notes"
            title="Cambridge 20 Listening"
            subtitle="Questions 21-30"
            @submit="submitAnswers"
            @delete-note="handleDeleteNote"
            @focus-note="handleFocusNote"
        />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="mb-4 rounded-lg bg-white p-4 shadow">
                <h2 class="mb-2 text-lg font-semibold text-gray-800">Instructions</h2>
                <ul class="space-y-1 text-sm text-gray-600">
                    <li>• Complete all questions in Part 3 (Questions 21-30)</li>
                    <li>• Q21-24: Choose TWO letters for each question pair</li>
                    <li>• Q25-30: Choose the correct letter A, B or C</li>
                </ul>
            </div>

            <div class="rounded-lg bg-white shadow">
                <C20Test4ListeningPart3 ref="listeningPartRef" />
            </div>
        </div>
    </div>
</template>
