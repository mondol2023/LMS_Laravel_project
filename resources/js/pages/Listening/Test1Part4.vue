<script setup lang="ts">
import C20Test1ListeningPart4 from '@/components/Exam/Practice/C20/Test1/Listening/C20Test1ListeningPart4.vue';
import ListeningPartHeader from '@/components/Listening/ListeningPartHeader.vue';
import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const testNumber = 1;
const partNumber = 4;
const audioSrc = '/audio/Practice/C20T1P4.mp3'; // C20 Test 1 full audio

const correctAnswers: Record<number, string> = {
    31: 'Factories',
    32: 'Dead',
    33: 'Whale',
    34: 'Apartments',
    35: 'Park',
    36: 'Art',
    37: 'Beaches',
    38: 'Ferry',
    39: 'Bikes',
    40: 'Drone',
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
        const userAnswer = (formattedUserAnswers[qNum] || '').trim().toLowerCase();
        const correct = correctAnswers[qNum].toLowerCase();
        const possibleAnswers = correct.split('/').map((a) => a.trim().toLowerCase());
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
    <Head title="Listening Test 1 - Part 4" />
    <div class="min-h-screen bg-gray-50">
        <ListeningPartHeader
            :test-number="testNumber"
            :part-number="partNumber"
            :audio-src="audioSrc"
            :notes="notes"
            title="Cambridge 20 Listening"
            subtitle="Questions 31-40"
            @submit="submitAnswers"
            @delete-note="handleDeleteNote"
            @focus-note="handleFocusNote"
        />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="mb-4 rounded-lg bg-white p-4 shadow">
                <h2 class="mb-2 text-lg font-semibold text-gray-800">Instructions</h2>
                <ul class="space-y-1 text-sm text-gray-600">
                    <li>• Complete all questions in Part 4 (Questions 31-40)</li>
                    <li>• Write ONE WORD ONLY for each answer</li>
                </ul>
            </div>

            <div class="rounded-lg bg-white shadow">
                <C20Test1ListeningPart4 ref="listeningPartRef" />
            </div>
        </div>
    </div>
</template>
