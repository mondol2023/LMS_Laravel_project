<script setup lang="ts">
import AppFooter from '@/components/Common/AppFooter.vue';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Download, FileText, List, RotateCcw, Target, Trophy, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

defineOptions({
    layout: PracticeLayout,
});

interface Props {
    testNumber: number;
    userAnswers: Record<string | number, string>;
    correctAnswers: Record<number, string>;
    score: {
        correct: number;
        incorrect: number;
        bandScore: number;
    };
    partNumber?: number | null;
}

const props = defineProps<Props>();

// Animation states
const showScore = ref(false);
const showDetails = ref(false);
const animatedScore = ref(0);
const animatedBand = ref(0);

// Filter state
const filterMode = ref<'all' | 'correct' | 'incorrect'>('all');

// Part-wise question ranges
const getQuestionRange = (part: number) => {
    switch (part) {
        case 1:
            return { start: 1, end: 10 };
        case 2:
            return { start: 11, end: 20 };
        case 3:
            return { start: 21, end: 30 };
        case 4:
            return { start: 31, end: 40 };
        default:
            return { start: 1, end: 40 };
    }
};

// Computed values
const isPartWise = computed(() => props.partNumber !== null && props.partNumber !== undefined);
const questionRange = computed(() => (isPartWise.value ? getQuestionRange(props.partNumber!) : { start: 1, end: 40 }));
const totalQuestions = computed(() => (isPartWise.value ? 10 : 40));
const percentage = computed(() => Math.round((props.score.correct / totalQuestions.value) * 100));

// Helper to get user answer (handles both numeric and string keys)
const getUserAnswer = (questionNum: number): string => {
    // Try numeric key first, then string key with 'q' prefix
    return props.userAnswers[questionNum] || props.userAnswers[`q${questionNum}`] || '';
};

const filteredQuestions = computed(() => {
    const questions = [];
    const { start, end } = questionRange.value;
    for (let i = start; i <= end; i++) {
        const userAnswer = getUserAnswer(i);
        const correctAnswer = props.correctAnswers[i] || '';
        const isCorrect = checkAnswer(userAnswer, correctAnswer);

        if (filterMode.value === 'all' || (filterMode.value === 'correct' && isCorrect) || (filterMode.value === 'incorrect' && !isCorrect)) {
            questions.push({
                number: i,
                userAnswer,
                correctAnswer,
                isCorrect,
            });
        }
    }
    return questions;
});

// Computed counts for filters
const correctCount = computed(() => {
    let count = 0;
    const { start, end } = questionRange.value;
    for (let i = start; i <= end; i++) {
        const userAnswer = getUserAnswer(i);
        const correctAnswer = props.correctAnswers[i] || '';
        if (checkAnswer(userAnswer, correctAnswer)) count++;
    }
    return count;
});

const incorrectCount = computed(() => totalQuestions.value - correctCount.value);

// Check if answer is correct (handles multiple correct answers with /)
const checkAnswer = (userAnswer: string, correctAnswer: string): boolean => {
    if (!userAnswer || !correctAnswer) return false;
    const normalize = (str: string) =>
        str
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[.,!?;:'"()-]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    const userNormalized = normalize(userAnswer);
    if (!userNormalized) return false;
    const possibleAnswers = correctAnswer.split('/').map((a) => normalize(a.trim()));
    return possibleAnswers.some((possible) => possible === userNormalized);
};

// Get band score color
const getBandColor = (band: number) => {
    if (band >= 8) return 'from-emerald-500 to-green-600';
    if (band >= 7) return 'from-blue-500 to-indigo-600';
    if (band >= 6) return 'from-yellow-500 to-orange-500';
    if (band >= 5) return 'from-orange-500 to-red-500';
    return 'from-red-500 to-rose-600';
};

const getBandTextColor = (band: number) => {
    if (band >= 8) return 'text-emerald-600';
    if (band >= 7) return 'text-blue-600';
    if (band >= 6) return 'text-yellow-600';
    if (band >= 5) return 'text-orange-600';
    return 'text-red-600';
};

const getBandDescription = (band: number) => {
    if (band >= 8.5) return 'Expert User';
    if (band >= 8) return 'Very Good User';
    if (band >= 7) return 'Good User';
    if (band >= 6) return 'Competent User';
    if (band >= 5) return 'Modest User';
    if (band >= 4) return 'Limited User';
    return 'Developing';
};

// Animate scores on mount
onMounted(() => {
    setTimeout(() => {
        showScore.value = true;

        // Animate correct score
        const scoreInterval = setInterval(() => {
            if (animatedScore.value < props.score.correct) {
                animatedScore.value++;
            } else {
                clearInterval(scoreInterval);
            }
        }, 30);

        // Animate band score
        const bandInterval = setInterval(() => {
            if (animatedBand.value < props.score.bandScore) {
                animatedBand.value = Math.min(animatedBand.value + 0.5, props.score.bandScore);
            } else {
                clearInterval(bandInterval);
            }
        }, 100);
    }, 300);

    setTimeout(() => {
        showDetails.value = true;
    }, 800);
});

// Get part label
const getPartLabel = (questionNum: number) => {
    if (questionNum <= 10) return 'Part 1';
    if (questionNum <= 20) return 'Part 2';
    if (questionNum <= 30) return 'Part 3';
    return 'Part 4';
};

// PDF Download functionality
const isGeneratingPdf = ref(false);

const downloadPdf = async () => {
    isGeneratingPdf.value = true;

    try {
        // Dynamically import jspdf and html2canvas
        const [{ default: jsPDF }, { default: html2canvas }] = await Promise.all([import('jspdf'), import('html2canvas')]);

        const pdf = new jsPDF('p', 'mm', 'a4');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 15;
        let yPosition = margin;

        // Colors
        const primaryColor = [99, 102, 241]; // Indigo
        const successColor = [16, 185, 129]; // Emerald
        const errorColor = [239, 68, 68]; // Red
        const textColor = [31, 41, 55]; // Gray-800
        const lightGray = [156, 163, 175]; // Gray-400

        // Header with gradient effect
        pdf.setFillColor(99, 102, 241);
        pdf.rect(0, 0, pageWidth, 45, 'F');

        // Logo/Brand area
        pdf.setFillColor(255, 255, 255);
        pdf.roundedRect(margin, 10, 25, 25, 3, 3, 'F');
        pdf.setTextColor(99, 102, 241);
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('IELTS', margin + 12.5, 22, { align: 'center' });
        pdf.setFontSize(8);
        pdf.text('MOCK', margin + 12.5, 28, { align: 'center' });

        // Title
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(22);
        pdf.setFont('helvetica', 'bold');
        pdf.text(
            isPartWise.value ? `Listening Test ${props.testNumber} - Part ${props.partNumber} Results` : 'Listening Test Results',
            margin + 35,
            22,
        );

        pdf.setFontSize(11);
        pdf.setFont('helvetica', 'normal');
        const cambridgeVersion = props.testNumber <= 4 ? '20' : '19';
        pdf.text(
            isPartWise.value
                ? `Cambridge IELTS ${cambridgeVersion} - Questions ${questionRange.value.start}-${questionRange.value.end}`
                : `Cambridge IELTS ${cambridgeVersion} - Test ${props.testNumber}`,
            margin + 35,
            32,
        );

        // Date
        pdf.setFontSize(9);
        const date = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
        pdf.text(date, pageWidth - margin, 32, { align: 'right' });

        yPosition = 55;

        // Score Summary Cards
        const cardWidth = (pageWidth - margin * 2 - 10) / 3;
        const cardHeight = 35;

        // Band Score Card
        pdf.setFillColor(249, 250, 251);
        pdf.roundedRect(margin, yPosition, cardWidth, cardHeight, 3, 3, 'F');
        pdf.setDrawColor(229, 231, 235);
        pdf.roundedRect(margin, yPosition, cardWidth, cardHeight, 3, 3, 'S');

        pdf.setTextColor(...lightGray);
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'bold');
        pdf.text('BAND SCORE', margin + cardWidth / 2, yPosition + 10, { align: 'center' });

        pdf.setTextColor(...(props.score.bandScore >= 6 ? successColor : errorColor));
        pdf.setFontSize(24);
        pdf.setFont('helvetica', 'bold');
        pdf.text(props.score.bandScore.toFixed(1), margin + cardWidth / 2, yPosition + 25, { align: 'center' });

        pdf.setFontSize(8);
        pdf.text(getBandDescription(props.score.bandScore), margin + cardWidth / 2, yPosition + 32, { align: 'center' });

        // Correct Answers Card
        const card2X = margin + cardWidth + 5;
        pdf.setFillColor(240, 253, 244);
        pdf.roundedRect(card2X, yPosition, cardWidth, cardHeight, 3, 3, 'F');
        pdf.setDrawColor(187, 247, 208);
        pdf.roundedRect(card2X, yPosition, cardWidth, cardHeight, 3, 3, 'S');

        pdf.setTextColor(...lightGray);
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'bold');
        pdf.text('CORRECT', card2X + cardWidth / 2, yPosition + 10, { align: 'center' });

        pdf.setTextColor(...successColor);
        pdf.setFontSize(24);
        pdf.setFont('helvetica', 'bold');
        pdf.text(`${props.score.correct}`, card2X + cardWidth / 2, yPosition + 25, { align: 'center' });

        pdf.setFontSize(8);
        pdf.text(`${percentage.value}% Accuracy`, card2X + cardWidth / 2, yPosition + 32, { align: 'center' });

        // Incorrect Answers Card
        const card3X = margin + (cardWidth + 5) * 2;
        pdf.setFillColor(254, 242, 242);
        pdf.roundedRect(card3X, yPosition, cardWidth, cardHeight, 3, 3, 'F');
        pdf.setDrawColor(254, 202, 202);
        pdf.roundedRect(card3X, yPosition, cardWidth, cardHeight, 3, 3, 'S');

        pdf.setTextColor(...lightGray);
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'bold');
        pdf.text('INCORRECT', card3X + cardWidth / 2, yPosition + 10, { align: 'center' });

        pdf.setTextColor(...errorColor);
        pdf.setFontSize(24);
        pdf.setFont('helvetica', 'bold');
        pdf.text(`${props.score.incorrect}`, card3X + cardWidth / 2, yPosition + 25, { align: 'center' });

        pdf.setFontSize(8);
        pdf.text(`${totalQuestions.value} Total Questions`, card3X + cardWidth / 2, yPosition + 32, { align: 'center' });

        yPosition += cardHeight + 15;

        // Answer Details Header
        pdf.setFillColor(249, 250, 251);
        pdf.roundedRect(margin, yPosition, pageWidth - margin * 2, 10, 2, 2, 'F');
        pdf.setTextColor(...textColor);
        pdf.setFontSize(11);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Answer Details', margin + 5, yPosition + 7);

        yPosition += 15;

        // Table Header
        const colWidths = [15, 20, 50, 50, 30];
        const tableWidth = pageWidth - margin * 2;

        pdf.setFillColor(99, 102, 241);
        pdf.roundedRect(margin, yPosition, tableWidth, 8, 2, 2, 'F');
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'bold');

        let xPos = margin + 3;
        pdf.text('#', xPos + 5, yPosition + 5.5, { align: 'center' });
        xPos += colWidths[0];
        pdf.text('Part', xPos + 8, yPosition + 5.5, { align: 'center' });
        xPos += colWidths[1];
        pdf.text('Your Answer', xPos + 20, yPosition + 5.5, { align: 'center' });
        xPos += colWidths[2];
        pdf.text('Correct Answer', xPos + 20, yPosition + 5.5, { align: 'center' });
        xPos += colWidths[3];
        pdf.text('Status', xPos + 12, yPosition + 5.5, { align: 'center' });

        yPosition += 10;

        // Table Rows
        const { start, end } = questionRange.value;
        for (let i = start; i <= end; i++) {
            // Check if we need a new page
            if (yPosition > pageHeight - 30) {
                pdf.addPage();
                yPosition = margin;

                // Add header on new page
                pdf.setFillColor(99, 102, 241);
                pdf.roundedRect(margin, yPosition, tableWidth, 8, 2, 2, 'F');
                pdf.setTextColor(255, 255, 255);
                pdf.setFontSize(8);
                pdf.setFont('helvetica', 'bold');

                xPos = margin + 3;
                pdf.text('#', xPos + 5, yPosition + 5.5, { align: 'center' });
                xPos += colWidths[0];
                pdf.text('Part', xPos + 8, yPosition + 5.5, { align: 'center' });
                xPos += colWidths[1];
                pdf.text('Your Answer', xPos + 20, yPosition + 5.5, { align: 'center' });
                xPos += colWidths[2];
                pdf.text('Correct Answer', xPos + 20, yPosition + 5.5, { align: 'center' });
                xPos += colWidths[3];
                pdf.text('Status', xPos + 12, yPosition + 5.5, { align: 'center' });

                yPosition += 10;
            }

            const userAnswer = getUserAnswer(i);
            const correctAnswer = props.correctAnswers[i] || '';
            const isCorrect = checkAnswer(userAnswer, correctAnswer);
            const rowHeight = 7;

            // Alternating row colors
            if (i % 2 === 0) {
                pdf.setFillColor(249, 250, 251);
                pdf.rect(margin, yPosition, tableWidth, rowHeight, 'F');
            }

            // Row border
            pdf.setDrawColor(229, 231, 235);
            pdf.line(margin, yPosition + rowHeight, margin + tableWidth, yPosition + rowHeight);

            pdf.setFontSize(8);
            pdf.setFont('helvetica', 'normal');

            xPos = margin + 3;

            // Question number
            pdf.setTextColor(...textColor);
            pdf.text(`${i}`, xPos + 5, yPosition + 5, { align: 'center' });
            xPos += colWidths[0];

            // Part
            pdf.setTextColor(...lightGray);
            pdf.text(getPartLabel(i), xPos + 8, yPosition + 5, { align: 'center' });
            xPos += colWidths[1];

            // User answer
            if (isCorrect) {
                pdf.setTextColor(...successColor);
                pdf.setFont('helvetica', 'bold');
            } else if (userAnswer) {
                pdf.setTextColor(...errorColor);
            } else {
                pdf.setTextColor(...lightGray);
            }
            pdf.text(userAnswer || 'No answer', xPos + 20, yPosition + 5, { align: 'center' });
            xPos += colWidths[2];

            // Correct answer (only show if incorrect)
            pdf.setFont('helvetica', 'normal');
            if (isCorrect) {
                pdf.setTextColor(...successColor);
                pdf.text('-', xPos + 20, yPosition + 5, { align: 'center' });
            } else {
                pdf.setTextColor(...successColor);
                pdf.setFont('helvetica', 'bold');
                pdf.text(correctAnswer, xPos + 20, yPosition + 5, { align: 'center' });
            }
            xPos += colWidths[3];

            // Status
            pdf.setFont('helvetica', 'bold');
            if (isCorrect) {
                pdf.setTextColor(...successColor);
                pdf.text('CORRECT', xPos + 12, yPosition + 5, { align: 'center' });
            } else {
                pdf.setTextColor(...errorColor);
                pdf.text('WRONG', xPos + 12, yPosition + 5, { align: 'center' });
            }

            yPosition += rowHeight;
        }

        // Footer
        yPosition = pageHeight - 15;
        pdf.setDrawColor(229, 231, 235);
        pdf.line(margin, yPosition - 5, pageWidth - margin, yPosition - 5);

        pdf.setTextColor(...lightGray);
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'normal');
        pdf.text('Generated by IELTS Mock Test Platform', margin, yPosition);
        pdf.text('www.ieltsmock.com', pageWidth - margin, yPosition, { align: 'right' });

        // Save the PDF
        const filename = isPartWise.value
            ? `IELTS_Listening_Test${props.testNumber}_Part${props.partNumber}_Results.pdf`
            : `IELTS_Listening_Test${props.testNumber}_Results.pdf`;
        pdf.save(filename);
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        isGeneratingPdf.value = false;
    }
};
</script>

<template>
    <Head :title="isPartWise ? `Listening Test ${testNumber} Part ${partNumber} Results` : `Listening Test ${testNumber} Results`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <!-- Header -->
            <div class="mb-8 text-center">
                <Transition
                    enter-active-class="transition duration-500 ease-out"
                    enter-from-class="opacity-0 -translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div v-if="showScore">
                        <h1 class="mb-2 text-3xl font-bold text-gray-900 sm:text-4xl">
                            Listening Test {{ testNumber }}{{ isPartWise ? ` - Part ${partNumber}` : '' }} Results
                        </h1>
                        <p class="text-gray-600">
                            Cambridge IELTS {{ testNumber <= 4 ? '20' : '19' }} -
                            {{ isPartWise ? `Questions ${questionRange.start}-${questionRange.end}` : 'Practice Mode' }}
                        </p>
                    </div>
                </Transition>
            </div>

            <!-- Score Cards -->
            <Transition
                enter-active-class="transition duration-700 ease-out delay-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
            >
                <div v-if="showScore" class="mb-8 grid gap-6 sm:grid-cols-3">
                    <!-- Band Score Card -->
                    <div
                        class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl sm:col-span-1"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br opacity-5" :class="getBandColor(props.score.bandScore)"></div>
                        <div class="relative">
                            <div class="mb-2 flex items-center gap-2">
                                <Trophy class="h-4 w-4 text-gray-400" />
                                <p class="text-sm font-medium tracking-wider text-gray-500 uppercase">Band Score</p>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-5xl font-bold" :class="getBandTextColor(props.score.bandScore)">
                                    {{ animatedBand.toFixed(1) }}
                                </span>
                                <span class="text-lg text-gray-400">/9.0</span>
                            </div>
                            <p class="mt-2 text-sm font-medium" :class="getBandTextColor(props.score.bandScore)">
                                {{ getBandDescription(props.score.bandScore) }}
                            </p>
                        </div>
                        <!-- Decorative circle -->
                        <div
                            class="absolute -top-6 -right-6 h-24 w-24 rounded-full bg-gradient-to-br opacity-10 transition-transform duration-300 group-hover:scale-110"
                            :class="getBandColor(props.score.bandScore)"
                        ></div>
                    </div>

                    <!-- Correct Answers Card -->
                    <div
                        class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500 to-green-600 opacity-5"></div>
                        <div class="relative">
                            <div class="mb-2 flex items-center gap-2">
                                <Target class="h-4 w-4 text-gray-400" />
                                <p class="text-sm font-medium tracking-wider text-gray-500 uppercase">Correct Answers</p>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-5xl font-bold text-emerald-600">{{ animatedScore }}</span>
                                <span class="text-lg text-gray-400">/{{ totalQuestions }}</span>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-green-500 transition-all duration-1000 ease-out"
                                    :style="{ width: `${percentage}%` }"
                                ></div>
                            </div>
                            <p class="mt-2 text-sm font-medium text-emerald-600">{{ percentage }}% Accuracy</p>
                        </div>
                        <div
                            class="absolute -top-6 -right-6 h-24 w-24 rounded-full bg-gradient-to-br from-emerald-500 to-green-600 opacity-10 transition-transform duration-300 group-hover:scale-110"
                        ></div>
                    </div>

                    <!-- Incorrect Answers Card -->
                    <div
                        class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-red-500 to-rose-600 opacity-5"></div>
                        <div class="relative">
                            <div class="mb-2 flex items-center gap-2">
                                <XCircle class="h-4 w-4 text-gray-400" />
                                <p class="text-sm font-medium tracking-wider text-gray-500 uppercase">Incorrect Answers</p>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-5xl font-bold text-red-500">{{ props.score.incorrect }}</span>
                                <span class="text-lg text-gray-400">/{{ totalQuestions }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ props.score.incorrect === 0 ? 'Perfect score!' : 'Keep practicing!' }}
                            </p>
                        </div>
                        <div
                            class="absolute -top-6 -right-6 h-24 w-24 rounded-full bg-gradient-to-br from-red-500 to-rose-600 opacity-10 transition-transform duration-300 group-hover:scale-110"
                        ></div>
                    </div>
                </div>
            </Transition>

            <!-- Filter Tabs -->
            <Transition
                enter-active-class="transition duration-500 ease-out delay-500"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="showDetails" class="mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <h2 class="text-xl font-semibold text-gray-900">Answer Details</h2>
                        <div class="flex rounded-xl bg-white p-1 shadow-sm ring-1 ring-gray-200">
                            <button
                                @click="filterMode = 'all'"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200"
                                :class="
                                    filterMode === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                "
                            >
                                All ({{ totalQuestions }})
                            </button>
                            <button
                                @click="filterMode = 'correct'"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200"
                                :class="
                                    filterMode === 'correct'
                                        ? 'bg-emerald-600 text-white shadow-sm'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                "
                            >
                                Correct ({{ correctCount }})
                            </button>
                            <button
                                @click="filterMode = 'incorrect'"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200"
                                :class="
                                    filterMode === 'incorrect'
                                        ? 'bg-red-600 text-white shadow-sm'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                "
                            >
                                Incorrect ({{ incorrectCount }})
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Questions Grid -->
            <Transition enter-active-class="transition duration-500 ease-out delay-700" enter-from-class="opacity-0" enter-to-class="opacity-100">
                <div v-if="showDetails" class="mb-8 space-y-3">
                    <TransitionGroup
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 translate-x-4"
                        enter-to-class="opacity-100 translate-x-0"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                        move-class="transition duration-300 ease-out"
                    >
                        <div
                            v-for="(question, index) in filteredQuestions"
                            :key="question.number"
                            class="group relative overflow-hidden rounded-xl bg-white p-4 shadow-sm ring-1 transition-all duration-300 hover:shadow-md sm:p-5"
                            :class="question.isCorrect ? 'ring-emerald-200 hover:ring-emerald-300' : 'ring-red-200 hover:ring-red-300'"
                            :style="{ animationDelay: `${index * 50}ms` }"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <!-- Question Number & Part -->
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-lg font-bold text-white shadow-sm transition-transform duration-300 group-hover:scale-105"
                                        :class="
                                            question.isCorrect
                                                ? 'bg-gradient-to-br from-emerald-500 to-green-600'
                                                : 'bg-gradient-to-br from-red-500 to-rose-600'
                                        "
                                    >
                                        {{ question.number }}
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="inline-flex w-fit items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                        >
                                            {{ getPartLabel(question.number) }}
                                        </span>
                                        <!-- Correct Badge for correct answers -->
                                        <span
                                            v-if="question.isCorrect"
                                            class="inline-flex w-fit items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                                        >
                                            <CheckCircle class="h-3 w-3" />
                                            Correct
                                        </span>
                                    </div>
                                </div>

                                <!-- Answers -->
                                <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-6">
                                    <!-- User Answer -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium tracking-wider text-gray-400 uppercase">Your Answer:</span>
                                        <span
                                            class="inline-flex min-w-[80px] items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold"
                                            :class="
                                                question.isCorrect
                                                    ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
                                                    : question.userAnswer
                                                      ? 'bg-red-50 text-red-700 line-through decoration-2'
                                                      : 'bg-gray-100 text-gray-400 italic'
                                            "
                                        >
                                            {{ question.userAnswer || 'No answer' }}
                                        </span>
                                    </div>

                                    <!-- Correct Answer (only show if incorrect) -->
                                    <div v-if="!question.isCorrect" class="flex items-center gap-2">
                                        <span class="text-xs font-medium tracking-wider text-gray-400 uppercase">Correct:</span>
                                        <span
                                            class="inline-flex min-w-[80px] items-center justify-center rounded-lg bg-emerald-100 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200"
                                        >
                                            {{ question.correctAnswer }}
                                        </span>
                                    </div>

                                    <!-- Status Icon -->
                                    <div class="hidden sm:block">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110"
                                            :class="question.isCorrect ? 'bg-emerald-100' : 'bg-red-100'"
                                        >
                                            <CheckCircle v-if="question.isCorrect" class="h-5 w-5 text-emerald-600" />
                                            <XCircle v-else class="h-5 w-5 text-red-600" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Decorative gradient line -->
                            <div
                                class="absolute bottom-0 left-0 h-1 w-full opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                :class="
                                    question.isCorrect
                                        ? 'bg-gradient-to-r from-emerald-500 to-green-500'
                                        : 'bg-gradient-to-r from-red-500 to-rose-500'
                                "
                            ></div>
                        </div>
                    </TransitionGroup>

                    <!-- Empty state -->
                    <div v-if="filteredQuestions.length === 0" class="rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-200">
                        <FileText class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-4 text-gray-500">No questions match this filter</p>
                    </div>
                </div>
            </Transition>

            <!-- Action Buttons -->
            <Transition
                enter-active-class="transition duration-500 ease-out delay-1000"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="showDetails" class="space-y-6">
                    <!-- Download Section -->
                    <div class="rounded-2xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-6 shadow-xl">
                        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div class="text-center sm:text-left">
                                <h3 class="text-lg font-bold text-white">Download Your Results</h3>
                                <p class="text-sm text-white/80">Get a beautifully designed PDF report of your test results</p>
                            </div>
                            <button
                                @click="downloadPdf"
                                :disabled="isGeneratingPdf"
                                class="group flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-600 shadow-lg transition-all duration-300 hover:bg-gray-50 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <Download v-if="!isGeneratingPdf" class="h-5 w-5 transition-transform group-hover:-translate-y-0.5" />
                                <svg v-else class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                {{ isGeneratingPdf ? 'Generating...' : 'Download PDF' }}
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
                        <Link
                            :href="isPartWise ? `/listening/test${testNumber}/part${partNumber}` : `/exam/practice00${testNumber}/listening`"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:from-indigo-700 hover:to-purple-700 hover:shadow-xl"
                        >
                            <RotateCcw class="h-5 w-5" />
                            Try Again
                        </Link>
                        <Link
                            href="/listening"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-3 text-sm font-semibold text-gray-700 shadow-lg ring-1 ring-gray-200 transition-all duration-300 hover:bg-gray-50 hover:shadow-xl"
                        >
                            <List class="h-5 w-5" />
                            All Tests
                        </Link>
                    </div>
                </div>
            </Transition>
        </div>
    </div>

    <AppFooter />
</template>

<style scoped>
/* Smooth scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>
