<script setup lang="ts">
import C19Test3ListeningPart1 from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningPart1.vue';
import ListeningPartHeader from '@/components/Listening/ListeningPartHeader.vue';
import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const testNumber = 7;
const partNumber = 1;
const audioSrc = '/audio/listening17.mp3'; // C19 Test 3 full audio

const correctAnswers: Record<number, string> = {
    1: 'station',
    2: 'bridge',
    3: '4',
    4: 'Harvest',
    5: 'sign',
    6: 'free',
    7: 'kelp',
    8: 'pie',
    9: 'honey',
    10: 'lemon',
};

const listeningPart1Ref = ref<any>();
const { calculateBandScore } = useIeltsScoring();

// Notes from the part component
const notes = computed(() => listeningPart1Ref.value?.notes || []);

const handleDeleteNote = (noteId: number) => {
    listeningPart1Ref.value?.deleteNote?.(noteId);
};

const handleFocusNote = (note: any) => {
    listeningPart1Ref.value?.focusNote?.(note);
};

const submitAnswers = () => {
    const userAnswers = listeningPart1Ref.value?.getAnswers?.() || {};

    // Convert q1, q2 format to 1, 2 format for results page
    const formattedUserAnswers: Record<number, string> = {};
    Object.keys(userAnswers).forEach((key) => {
        const num = parseInt(key.replace('q', ''));
        formattedUserAnswers[num] = userAnswers[key];
    });

    // Calculate score
    let correctCount = 0;
    Object.keys(correctAnswers).forEach((key) => {
        const qNum = parseInt(key);
        const userAnswer = (formattedUserAnswers[qNum] || '').trim().toLowerCase();
        const correct = correctAnswers[qNum].toLowerCase();
        // Handle multiple correct answers with /
        const possibleAnswers = correct.split('/').map((a) => a.trim().toLowerCase());
        if (possibleAnswers.includes(userAnswer)) {
            correctCount++;
        }
    });

    const totalQuestions = Object.keys(correctAnswers).length;
    const incorrectCount = totalQuestions - correctCount;
    const bandScore = calculateBandScore(correctCount, 10); // 10 questions for Part 1

    // Post results and redirect to results page
    router.post(
        `/practice/results/listening/${testNumber}`,
        {
            userAnswers: formattedUserAnswers,
            correctAnswers,
            score: {
                correct: correctCount,
                incorrect: incorrectCount,
                bandScore,
            },
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
    <Head title="Listening Test 7 - Part 1" />
    <div class="min-h-screen bg-gray-50">
        <ListeningPartHeader
            :test-number="testNumber"
            :part-number="partNumber"
            :audio-src="audioSrc"
            :notes="notes"
            title="Cambridge 19 Listening"
            subtitle="Questions 1-10"
            @submit="submitAnswers"
            @delete-note="handleDeleteNote"
            @focus-note="handleFocusNote"
        />

        <div class="mx-auto max-w-7xl px-4 py-6">
            <div class="mb-4 rounded-lg bg-white p-4 shadow">
                <h2 class="mb-2 text-lg font-semibold text-gray-800">Instructions</h2>
                <ul class="space-y-1 text-sm text-gray-600">
                    <li>• Complete all questions in Part 1 (Questions 1-10)</li>
                    <li>• Q1-6: Write ONE WORD AND/OR A NUMBER for each answer</li>
                    <li>• Q7-10: Write ONE WORD ONLY for each answer</li>
                </ul>
            </div>

            <div class="rounded-lg bg-white shadow">
                <C19Test3ListeningPart1 ref="listeningPart1Ref" />
            </div>
        </div>
    </div>
</template>
