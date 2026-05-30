<script setup lang="ts">
import C19Test3ListeningPart2 from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningPart2.vue';
import ListeningPartHeader from '@/components/Listening/ListeningPartHeader.vue';
import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const testNumber = 7;
const partNumber = 2;
const audioSrc = '/audio/listening17.mp3'; // C19 Test 3 full audio

const correctAnswers: Record<number, string> = {
    11: 'F',
    12: 'D',
    13: 'E',
    14: 'C',
    15: 'A',
    16: 'G',
    17: 'A/E',
    18: 'A/E',
    19: 'B/D',
    20: 'B/D',
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
    <Head title="Listening Test 7 - Part 2" />
    <div class="min-h-screen bg-gray-50">
        <ListeningPartHeader
            :test-number="testNumber"
            :part-number="partNumber"
            :audio-src="audioSrc"
            :notes="notes"
            title="Cambridge 19 Listening"
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
                    <li>• Q11-16: Match workshops with information A-H</li>
                    <li>• Q17-20: Choose TWO letters for each question pair</li>
                </ul>
            </div>

            <div class="rounded-lg bg-white shadow">
                <C19Test3ListeningPart2 ref="listeningPartRef" />
            </div>
        </div>
    </div>
</template>
