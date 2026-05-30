<script setup lang="ts">
import C20Test1ListeningPart2 from '@/components/Exam/Practice/C20/Test1/Listening/C20Test1ListeningPart2.vue';
import ListeningPartHeader from '@/components/Listening/ListeningPartHeader.vue';
import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const testNumber = 1;
const partNumber = 2;
const audioSrc = '/audio/Practice/C20T1P2.mp3'; // C20 Test 1 full audio

const correctAnswers: Record<number, string> = {
    11: 'A',
    12: 'B',
    13: 'C',
    14: 'A',
    15: 'B',
    16: 'C',
    17: 'A/E',
    18: 'A/E',
    19: 'C/E',
    20: 'C/E',
};

const listeningPartRef = ref<any>();
const { calculateBandScore } = useIeltsScoring();

// Notes from the part component
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
    <Head title="Listening Test 1 - Part 2" />
    <div class="min-h-screen bg-gray-50">
        <ListeningPartHeader
            :test-number="testNumber"
            :part-number="partNumber"
            :audio-src="audioSrc"
            :notes="notes"
            title="Cambridge 20 Listening"
            subtitle="Questions 11-20"
            @submit="submitAnswers"
            @delete-note="handleDeleteNote"
            @focus-note="handleFocusNote"
        />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="mb-4 rounded-lg bg-white p-4 shadow">
                <h2 class="mb-2 text-lg font-semibold text-gray-800">Instructions</h2>
                <ul class="space-y-1 text-sm text-gray-600">
                    <li>• Complete all questions in Part 2 (Questions 11-20)</li>
                    <li>• Q11-16: Choose the correct letter A, B or C</li>
                    <li>• Q17-20: Choose TWO letters for each question pair</li>
                </ul>
            </div>

            <div class="rounded-lg bg-white shadow">
                <C20Test1ListeningPart2 ref="listeningPartRef" />
            </div>
        </div>
    </div>
</template>
