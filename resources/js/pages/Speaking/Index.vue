<script setup lang="ts">
import AppFooter from '@/components/Common/AppFooter.vue';
import { Button } from '@/components/ui/button';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

defineOptions({
    layout: PracticeLayout,
});

interface Subscription {
    practice_speaking_enabled: boolean;
    practice_speaking_limit: number | null;
    practice_speaking_used: number;
    practice_speaking_remaining: number | null;
    is_expired: boolean;
}

interface Props {
    subscription?: Subscription | null;
    accessError?: string | null;
}

const props = defineProps<Props>();

// Reactive subscription state
const currentSubscription = ref(props.subscription);
const currentAccessError = ref(props.accessError);

// Track if limit was reached after submission (separate from initial access error)
const limitReachedAfterSubmission = ref(false);

interface Message {
    role: 'examiner' | 'candidate';
    content: string;
}

interface Evaluation {
    fluency_coherence: number;
    lexical_resource: number;
    grammatical_range: number;
    pronunciation: number;
    overall_band: number;
    strengths: string;
    weaknesses: string;
    feedback: string;
}

const showModal = ref(false);
const selectedPart = ref<'1' | '2' | '3' | 'full' | null>(null);
const conversation = ref<Message[]>([]);
const userInput = ref('');
const isLoading = ref(false);
const isRecording = ref(false);
const sessionFinished = ref(false);
const evaluation = ref<Evaluation | null>(null);
const showEvaluation = ref(false);
const currentQuestion = ref('');
const error = ref('');

// Audio recording
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recognition = ref<any>(null);
const isListening = ref(false);
const transcript = ref('');
const interimTranscript = ref('');

// Timer and submit control
const recordingTime = ref(0);
const canSubmit = ref(false);
const timerInterval = ref<any>(null);

// Speech synthesis
const synth = window.speechSynthesis;

// Initialize speech recognition
const initSpeechRecognition = () => {
    const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;

    if (!SpeechRecognition) {
        error.value = 'Speech recognition is not supported in your browser. Please use Chrome, Edge, or Safari.';
        return false;
    }

    recognition.value = new SpeechRecognition();
    recognition.value.continuous = true;
    recognition.value.interimResults = true;
    recognition.value.lang = 'en-US';

    recognition.value.onresult = (event: any) => {
        let interim = '';
        let final = '';

        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcriptPart = event.results[i][0].transcript;
            if (event.results[i].isFinal) {
                final += transcriptPart + ' ';
            } else {
                interim += transcriptPart;
            }
        }

        if (final) {
            transcript.value += final;
            userInput.value = transcript.value.trim();
        }
        interimTranscript.value = interim;
    };

    recognition.value.onerror = (event: any) => {
        console.error('Speech recognition error:', event.error);
        if (event.error === 'no-speech') {
            error.value = 'No speech detected. Please try again.';
        } else if (event.error === 'not-allowed') {
            error.value = 'Microphone access denied. Please allow microphone access.';
        } else {
            error.value = `Speech recognition error: ${event.error}`;
        }
        stopListening();
    };

    recognition.value.onend = () => {
        if (isListening.value) {
            // Restart if we're still supposed to be listening
            try {
                recognition.value.start();
            } catch (e) {
                console.error('Failed to restart recognition:', e);
                stopListening();
            }
        }
    };

    return true;
};

// Get required speaking time based on part (in seconds)
const getRequiredSpeakingTime = (part: string): number => {
    switch (part) {
        case '1':
            return 5; // Part 1: 30 seconds minimum
        case '2':
            return 30; // Part 2: 60 seconds minimum (1-2 minutes)
        case '3':
            return 10; // Part 3: 30 seconds minimum
        case 'full':
            return 15; // Full test: 30 seconds per question
        default:
            return 15;
    }
};

// Start timer to enable submit button after required time
const startTimer = () => {
    recordingTime.value = 0;
    canSubmit.value = false;

    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }

    timerInterval.value = setInterval(() => {
        recordingTime.value++;
        const requiredTime = getRequiredSpeakingTime(selectedPart.value || '1');
        if (recordingTime.value >= requiredTime) {
            canSubmit.value = true;
        }
    }, 1000);
};

// Stop timer
const stopTimer = () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
    recordingTime.value = 0;
    canSubmit.value = false;
};

const startPractice = async (part: '1' | '2' | '3' | 'full') => {
    // Check if user still has access before starting
    if (currentAccessError.value) {
        // User has run out of attempts - do nothing
        return;
    }

    selectedPart.value = part;
    showModal.value = true;
    conversation.value = [];
    sessionFinished.value = false;
    evaluation.value = null;
    showEvaluation.value = false;
    error.value = '';
    isLoading.value = true;
    transcript.value = '';
    interimTranscript.value = '';

    // Initialize speech recognition
    if (!initSpeechRecognition()) {
        isLoading.value = false;
        return;
    }

    try {
        const response = await axios.post('/api/speaking/start-session', {
            part,
        });

        if (response.data.success) {
            conversation.value = response.data.data.conversation;
            currentQuestion.value = response.data.data.question;

            // Speak the question first, then auto-start recording
            speakText(response.data.data.question);

            // Auto-start recording after a short delay (let the AI finish speaking)
            setTimeout(() => {
                if (!sessionFinished.value) {
                    startRecording();
                    startTimer();
                }
            }, 2000);
        }
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to start session';
        console.error('Error starting session:', err);
    } finally {
        isLoading.value = false;
    }
};

const closeModal = () => {
    showModal.value = false;
    stopTimer();
    stopListening();
    stopRecording();
    synth.cancel();
    conversation.value = [];
    userInput.value = '';
    transcript.value = '';
    interimTranscript.value = '';
    sessionFinished.value = false;
    evaluation.value = null;
    showEvaluation.value = false;
    error.value = '';
};

const submitResponse = async () => {
    if (!userInput.value.trim()) {
        error.value = 'Please speak your response or record your answer';
        return;
    }

    // Stop timer, listening and recording while processing
    stopTimer();
    stopRecording();
    error.value = '';
    isLoading.value = true;

    try {
        const response = await axios.post('/api/speaking/next-question', {
            part: selectedPart.value,
            conversation: conversation.value,
            user_response: userInput.value,
        });

        if (response.data.success) {
            const data = response.data.data;

            if (data.finished) {
                sessionFinished.value = true;
                conversation.value = data.conversation;
                await evaluateSession();
            } else {
                conversation.value = data.conversation;
                currentQuestion.value = data.question;
                userInput.value = '';
                transcript.value = '';
                interimTranscript.value = '';
                speakText(data.question);

                // Auto-restart recording for next question
                setTimeout(() => {
                    if (!sessionFinished.value && !isLoading.value) {
                        startRecording();
                        startTimer();
                    }
                }, 2000);
            }
        }
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to get next question';
        console.error('Error getting next question:', err);
    } finally {
        isLoading.value = false;
    }
};

const evaluateSession = async () => {
    stopTimer();
    isLoading.value = true;

    try {
        const response = await axios.post('/api/speaking/evaluate', {
            part: selectedPart.value,
            conversation: conversation.value,
        });

        if (response.data.success) {
            evaluation.value = response.data.data;
            showEvaluation.value = true;

            // Update subscription data if returned
            if (response.data.subscription) {
                currentSubscription.value = response.data.subscription;

                // Check if limit is now reached - set flag but don't block results view
                if (response.data.subscription.practice_speaking_remaining === 0) {
                    limitReachedAfterSubmission.value = true;
                }
            }
        }
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to evaluate session';
        console.error('Error evaluating session:', err);
    } finally {
        isLoading.value = false;
    }
};

const speakText = (text: string) => {
    if (!synth) return;

    synth.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = 0.9;
    utterance.pitch = 1;
    utterance.volume = 1;

    // Try to use a British English voice
    const voices = synth.getVoices();
    const gbVoice = voices.find((voice) => voice.lang.includes('en-GB') || voice.lang.includes('en-UK'));
    if (gbVoice) {
        utterance.voice = gbVoice;
    }

    synth.speak(utterance);
};

const startListening = () => {
    if (!recognition.value) {
        if (!initSpeechRecognition()) {
            return;
        }
    }

    error.value = '';
    transcript.value = '';
    interimTranscript.value = '';
    userInput.value = '';

    try {
        recognition.value.start();
        isListening.value = true;
    } catch (err) {
        console.error('Error starting speech recognition:', err);
        error.value = 'Failed to start speech recognition. Please try again.';
    }
};

const stopListening = () => {
    if (recognition.value && isListening.value) {
        try {
            recognition.value.stop();
        } catch (err) {
            console.error('Error stopping speech recognition:', err);
        }
        isListening.value = false;
    }
};

const startRecording = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder.value = new MediaRecorder(stream);
        audioChunks.value = [];

        mediaRecorder.value.ondataavailable = (event) => {
            audioChunks.value.push(event.data);
        };

        mediaRecorder.value.onstop = () => {
            const audioBlob = new Blob(audioChunks.value, { type: 'audio/webm' });
            console.log('Audio recorded:', audioBlob);
        };

        mediaRecorder.value.start();
        isRecording.value = true;

        // Also start speech recognition
        startListening();
    } catch (err) {
        console.error('Error accessing microphone:', err);
        error.value = 'Failed to access microphone. Please allow microphone access in your browser.';
    }
};

const stopRecording = () => {
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop();
        mediaRecorder.value.stream.getTracks().forEach((track) => track.stop());
        isRecording.value = false;
    }
    stopListening();
};

const getBandColor = (band: number): string => {
    if (band >= 7) return 'text-green-600';
    if (band >= 5.5) return 'text-yellow-600';
    return 'text-red-600';
};
</script>

<template>
    <Head title="IELTS Speaking Practice" />

    <!-- Access Blocked Warning -->
    <div v-if="currentAccessError" class="min-h-screen bg-gray-50 p-8">
        <div class="mx-auto max-w-4xl">
            <div class="overflow-hidden border-2 border-orange-200 bg-white shadow-xl">
                <!-- Warning Header -->
                <div class="bg-gradient-to-r from-orange-500 to-red-500 px-8 py-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/20">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Access Restricted</h1>
                            <p class="mt-1 text-orange-100">Speaking Practice Module</p>
                        </div>
                    </div>
                </div>

                <!-- Warning Content -->
                <div class="p-8">
                    <div class="mb-6 rounded-xl border-2 border-orange-200 bg-orange-50 p-6">
                        <div class="flex items-start gap-4">
                            <svg class="mt-1 h-6 w-6 shrink-0 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <div>
                                <h3 class="mb-2 text-lg font-bold text-orange-900">Unable to Access Speaking Practice</h3>
                                <p class="text-orange-800">{{ currentAccessError }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Info (if available) -->
                    <div v-if="currentSubscription" class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Your Subscription Status</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">Speaking Practice Module:</span>
                                <span :class="currentSubscription.practice_speaking_enabled ? 'text-green-600' : 'text-red-600'" class="font-medium">
                                    {{ currentSubscription.practice_speaking_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <div v-if="currentSubscription.practice_speaking_enabled" class="flex items-center justify-between">
                                <span class="text-gray-700">Usage:</span>
                                <span class="font-medium text-gray-900">
                                    {{ currentSubscription.practice_speaking_used }} /
                                    {{ currentSubscription.practice_speaking_limit || 'Unlimited' }}
                                </span>
                            </div>
                            <div v-if="currentSubscription.practice_speaking_enabled && currentSubscription.practice_speaking_limit" class="mt-2">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :style="{
                                            width:
                                                ((currentSubscription.practice_speaking_used / currentSubscription.practice_speaking_limit) * 100).toFixed(0) + '%',
                                        }"
                                        class="h-full bg-orange-500 transition-all duration-300"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- What to do next -->
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-6">
                        <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-blue-900">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            What You Can Do
                        </h3>
                        <ul class="space-y-2 text-blue-800">
                            <li class="flex items-start gap-2">
                                <span class="mt-1">•</span>
                                <span>Contact your administrator to request access or upgrade your subscription plan</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-1">•</span>
                                <span>Explore other available practice modules on your dashboard</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-1">•</span>
                                <span>Check your subscription expiry date and renew if needed</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex justify-center gap-4">
                        <Link href="/student/dashboard" class="rounded-lg bg-gray-900 px-6 py-3 font-semibold text-white transition-colors hover:bg-gray-800">
                            Go to Dashboard
                        </Link>
                        <Link href="/practice" class="rounded-lg border-2 border-gray-300 bg-white px-6 py-3 font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                            Browse Practice Modules
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Normal Content (when access is granted) -->
    <div v-else class="bg-gray-50">
        <!-- Hero Section -->
        <div class="relative bg-gradient-to-r from-orange-50 via-white to-red-50 px-4 py-8 text-center sm:px-6 lg:px-8">
            <div class="absolute top-0 left-0 h-full w-full bg-white/30 backdrop-blur-sm"></div>
            <div class="relative mx-auto max-w-4xl">
                <h1 class="animate-fade-in-down text-2xl font-extrabold tracking-tight text-gray-900 md:text-6xl">IELTS Speaking Practice</h1>
                <p class="animate-fade-in-up animation-delay-300 mx-auto mt-6 max-w-2xl text-base text-gray-600 md:text-base">
                    Improve your speaking skills with structured practice for all three parts of the IELTS Speaking test. Record your responses and
                    get AI-powered feedback to achieve your target band score.
                </p>
            </div>
        </div>

        <!-- Practice Sections -->
        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Limit Reached Warning Banner -->
                <div v-if="limitReachedAfterSubmission" class="mb-8 rounded-lg border-2 border-red-300 bg-red-50 p-6">
                    <div class="flex items-start gap-4">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-red-900">Practice Limit Reached</h3>
                            <p class="mt-2 text-sm text-red-800">
                                You have used all your speaking practice attempts. Please contact your administrator to upgrade your subscription.
                            </p>
                            <div class="mt-4 flex gap-3">
                                <Link href="/student/dashboard" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                                    Go to Dashboard
                                </Link>
                                <Link href="/practice" class="rounded-lg border-2 border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Browse Other Practices
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6 text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Speaking Test Parts</h2>
                    <p class="mt-4 text-base text-gray-600">Practice each part of the IELTS Speaking test separately.</p>
                </div>
                <div class="grid gap-8 md:grid-cols-3">
                    <div
                        class="translateY(20px) animate-fade-in-up transform rounded-xl border border-gray-100 bg-white p-8 opacity-0 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="text-center">
                            <div class="mb-4 text-5xl">👋</div>
                            <h3 class="mb-4 text-xl font-bold text-gray-900">Part 1: Introduction</h3>
                            <p class="mb-6 leading-relaxed text-gray-600">
                                General questions about yourself, your home, family, work, studies, and interests. Duration: 4-5 minutes.
                            </p>
                            <Button
                                @click="startPractice('1')"
                                :disabled="limitReachedAfterSubmission"
                                :class="[
                                    'w-full rounded-xl px-6 py-3 text-white transition-all duration-200',
                                    limitReachedAfterSubmission
                                        ? 'cursor-not-allowed bg-gray-400'
                                        : 'bg-orange-600 hover:bg-orange-700',
                                ]"
                            >
                                Start Part 1
                            </Button>
                        </div>
                    </div>

                    <div
                        class="translateY(20px) animate-fade-in-up animation-delay-300 transform rounded-xl border border-gray-100 bg-white p-8 opacity-0 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="text-center">
                            <div class="mb-4 text-5xl">💭</div>
                            <h3 class="mb-4 text-xl font-bold text-gray-900">Part 2: Long Turn</h3>
                            <p class="mb-6 leading-relaxed text-gray-600">
                                Speak for 1-2 minutes on a given topic after 1 minute of preparation time. Test your ability to speak at length.
                            </p>
                            <Button
                                @click="startPractice('2')"
                                :disabled="limitReachedAfterSubmission"
                                :class="[
                                    'w-full rounded-xl px-6 py-3 text-white transition-all duration-200',
                                    limitReachedAfterSubmission
                                        ? 'cursor-not-allowed bg-gray-400'
                                        : 'bg-orange-600 hover:bg-orange-700',
                                ]"
                            >
                                Start Part 2
                            </Button>
                        </div>
                    </div>

                    <div
                        class="translateY(20px) animate-fade-in-up animation-delay-600 transform rounded-xl border border-gray-100 bg-white p-8 opacity-0 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="text-center">
                            <div class="mb-4 text-5xl">🎯</div>
                            <h3 class="mb-4 text-xl font-bold text-gray-900">Part 3: Discussion</h3>
                            <p class="mb-6 leading-relaxed text-gray-600">
                                In-depth discussion on abstract ideas related to Part 2 topic. Duration: 4-5 minutes.
                            </p>
                            <Button
                                @click="startPractice('3')"
                                :disabled="limitReachedAfterSubmission"
                                :class="[
                                    'w-full rounded-xl px-6 py-3 text-white transition-all duration-200',
                                    limitReachedAfterSubmission
                                        ? 'cursor-not-allowed bg-gray-400'
                                        : 'bg-orange-600 hover:bg-orange-700',
                                ]"
                            >
                                Start Part 3
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Full Mock Speaking Test -->
                <div class="mt-12">
                    <div
                        class="translateY(20px) animate-fade-in-up animation-delay-900 transform rounded-xl border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-red-50 p-10 text-center opacity-0 shadow-lg"
                    >
                        <div class="mb-4 text-6xl">🎤</div>
                        <h3 class="mb-4 text-2xl font-bold text-gray-900">Full Speaking Mock Test</h3>
                        <p class="mx-auto mb-6 max-w-2xl leading-relaxed text-gray-600">
                            Take a complete IELTS Speaking test simulation covering all three parts. Experience real exam conditions and get
                            comprehensive feedback on your performance.
                        </p>
                        <Button
                            @click="startPractice('full')"
                            :disabled="limitReachedAfterSubmission"
                            :class="[
                                'rounded-xl px-10 py-4 text-lg font-semibold text-white shadow-lg transition-all duration-200',
                                limitReachedAfterSubmission
                                    ? 'cursor-not-allowed bg-gray-400'
                                    : 'bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700',
                            ]"
                        >
                            Start Full Mock Test
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="bg-white py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Why Practice Speaking With Us?</h2>
                    <p class="mt-4 text-lg text-gray-600">Advanced features to help you excel in the IELTS Speaking test.</p>
                </div>
                <div class="grid gap-12 text-center md:grid-cols-3">
                    <div class="translateY(20px) animate-fade-in-up animation-delay-500 transform opacity-0">
                        <div class="mb-4 text-4xl">🎙️</div>
                        <h3 class="mb-2 text-xl font-bold">Record & Review</h3>
                        <p class="text-gray-600">
                            Record your responses and listen back to identify areas for improvement. Track your progress over time.
                        </p>
                    </div>
                    <div class="translateY(20px) animate-fade-in-up animation-delay-600 transform opacity-0">
                        <div class="mb-4 text-4xl">🤖</div>
                        <h3 class="mb-2 text-xl font-bold">AI-Powered Feedback</h3>
                        <p class="text-gray-600">
                            Get instant feedback on fluency, coherence, vocabulary, grammar, and pronunciation using advanced AI technology.
                        </p>
                    </div>
                    <div class="translateY(20px) animate-fade-in-up animation-delay-700 transform opacity-0">
                        <div class="mb-4 text-4xl">📊</div>
                        <h3 class="mb-2 text-xl font-bold">Detailed Analytics</h3>
                        <p class="text-gray-600">
                            Analyze your speaking patterns, common mistakes, and get personalized tips to boost your band score.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips Section -->
        <div class="bg-gradient-to-r from-orange-50 to-red-50 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Speaking Test Tips</h2>
                    <p class="mt-4 text-lg text-gray-600">Essential strategies for success.</p>
                </div>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-white p-6 shadow-md">
                        <div class="mb-3 text-3xl">⏱️</div>
                        <h4 class="mb-2 font-bold text-gray-900">Time Management</h4>
                        <p class="text-sm text-gray-600">Use your preparation time wisely in Part 2. Make quick notes and organize your thoughts.</p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-md">
                        <div class="mb-3 text-3xl">🗣️</div>
                        <h4 class="mb-2 font-bold text-gray-900">Speak Naturally</h4>
                        <p class="text-sm text-gray-600">Don't memorize answers. The examiner can tell. Speak naturally and be yourself.</p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-md">
                        <div class="mb-3 text-3xl">📚</div>
                        <h4 class="mb-2 font-bold text-gray-900">Expand Answers</h4>
                        <p class="text-sm text-gray-600">Give detailed responses with examples and explanations. Avoid yes/no answers.</p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-md">
                        <div class="mb-3 text-3xl">✨</div>
                        <h4 class="mb-2 font-bold text-gray-900">Use Varied Language</h4>
                        <p class="text-sm text-gray-600">Demonstrate your vocabulary range and grammatical accuracy throughout the test.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Speaking Practice Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-[70vw] transform rounded-3xl bg-white shadow-2xl transition-all">
                        <!-- Header -->
                        <div class="rounded-t-3xl border-b border-gray-200 bg-gradient-to-r from-orange-600 to-red-600 px-8 py-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-2xl font-bold">
                                        IELTS Speaking Practice - Part {{ selectedPart === 'full' ? 'Full Test' : selectedPart }}
                                    </h3>
                                    <p class="text-orange-100">Interactive conversation with AI examiner</p>
                                </div>
                                <button @click="closeModal" class="rounded-full p-2 text-white transition-colors hover:bg-white/20">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-8">
                            <!-- Error Message -->
                            <div v-if="error" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                                {{ error }}
                            </div>

                            <!-- Evaluation Results -->
                            <div v-if="showEvaluation && evaluation" class="space-y-6">
                                <div class="text-center">
                                    <h4 class="mb-4 text-3xl font-bold text-gray-900">Your Speaking Assessment</h4>
                                    <div class="mb-8 inline-block rounded-full bg-gradient-to-r from-orange-600 to-red-600 px-8 py-4">
                                        <p class="text-sm font-medium text-white">Overall Band Score</p>
                                        <p class="text-5xl font-bold text-white">{{ evaluation.overall_band }}</p>
                                    </div>
                                </div>

                                <!-- Individual Scores -->
                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                    <div class="rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 p-6 text-center">
                                        <p class="mb-2 text-sm font-medium text-blue-900">Fluency & Coherence</p>
                                        <p :class="['text-3xl font-bold', getBandColor(evaluation.fluency_coherence)]">
                                            {{ evaluation.fluency_coherence }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-gradient-to-br from-green-50 to-green-100 p-6 text-center">
                                        <p class="mb-2 text-sm font-medium text-green-900">Lexical Resource</p>
                                        <p :class="['text-3xl font-bold', getBandColor(evaluation.lexical_resource)]">
                                            {{ evaluation.lexical_resource }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 p-6 text-center">
                                        <p class="mb-2 text-sm font-medium text-purple-900">Grammatical Range</p>
                                        <p :class="['text-3xl font-bold', getBandColor(evaluation.grammatical_range)]">
                                            {{ evaluation.grammatical_range }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 text-center">
                                        <p class="mb-2 text-sm font-medium text-yellow-900">Pronunciation</p>
                                        <p :class="['text-3xl font-bold', getBandColor(evaluation.pronunciation)]">
                                            {{ evaluation.pronunciation }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Strengths & Weaknesses -->
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div class="rounded-xl border-2 border-green-200 bg-green-50 p-6">
                                        <h5 class="mb-3 flex items-center gap-2 text-lg font-bold text-green-900">
                                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Strengths
                                        </h5>
                                        <p class="whitespace-pre-line text-gray-700">{{ evaluation.strengths }}</p>
                                    </div>
                                    <div class="rounded-xl border-2 border-orange-200 bg-orange-50 p-6">
                                        <h5 class="mb-3 flex items-center gap-2 text-lg font-bold text-orange-900">
                                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Areas for Improvement
                                        </h5>
                                        <p class="whitespace-pre-line text-gray-700">{{ evaluation.weaknesses }}</p>
                                    </div>
                                </div>

                                <!-- Detailed Feedback -->
                                <div class="rounded-xl border-2 border-blue-200 bg-blue-50 p-6">
                                    <h5 class="mb-3 text-lg font-bold text-blue-900">Detailed Feedback</h5>
                                    <p class="leading-relaxed whitespace-pre-line text-gray-700">{{ evaluation.feedback }}</p>
                                </div>

                                <!-- Close Button -->
                                <div class="text-center">
                                    <Button
                                        @click="closeModal"
                                        class="rounded-xl bg-gradient-to-r from-orange-600 to-red-600 px-10 py-3 text-lg font-semibold text-white"
                                    >
                                        Close
                                    </Button>
                                </div>
                            </div>

                            <!-- Conversation Interface -->
                            <div v-else class="grid gap-6 lg:grid-cols-2">
                                <!-- Left Side: AI Examiner -->
                                <div class="rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-6">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white">
                                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-blue-900">IELTS Examiner</h4>
                                            <p class="text-sm text-blue-700">AI-Powered</p>
                                        </div>
                                    </div>

                                    <!-- Current Question -->
                                    <div v-if="currentQuestion && !sessionFinished" class="rounded-xl bg-white p-6 shadow-sm">
                                        <p class="mb-4 text-lg leading-relaxed text-gray-800">{{ currentQuestion }}</p>
                                        <button
                                            @click="speakText(currentQuestion)"
                                            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm text-white transition-colors hover:bg-blue-700"
                                        >
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Listen Again
                                        </button>
                                    </div>

                                    <!-- Conversation History -->
                                    <div
                                        v-if="conversation.length > 1"
                                        class="mt-4 max-h-96 space-y-3 overflow-y-auto rounded-xl bg-white p-4 shadow-sm"
                                    >
                                        <div v-for="(msg, index) in conversation.slice(0, -1)" :key="index" class="text-sm">
                                            <p class="mb-1 font-semibold" :class="msg.role === 'examiner' ? 'text-blue-700' : 'text-green-700'">
                                                {{ msg.role === 'examiner' ? 'Examiner:' : 'You:' }}
                                            </p>
                                            <p class="text-gray-700">{{ msg.content }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Side: User Response -->
                                <div class="rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-green-100 p-6">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-600 text-white">
                                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-green-900">Your Response</h4>
                                            <p class="text-sm text-green-700">Test Candidate</p>
                                        </div>
                                    </div>

                                    <div v-if="!sessionFinished" class="space-y-4">
                                        <!-- Voice Recording Interface -->
                                        <div class="rounded-xl bg-white p-6 shadow-sm">
                                            <div class="text-center">
                                                <!-- Recording Indicator -->
                                                <div
                                                    class="group relative mx-auto mb-6 flex h-32 w-32 animate-pulse items-center justify-center rounded-full bg-red-500 shadow-lg shadow-red-500/50"
                                                >
                                                    <div class="absolute inset-0 animate-ping rounded-full border-4 border-red-300"></div>
                                                    <svg class="relative z-10 h-16 w-16 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </div>

                                                <!-- Status & Timer -->
                                                <div class="mb-4">
                                                    <p class="mb-2 text-lg font-semibold text-red-600">🎙️ Recording... Speak now!</p>
                                                    <div class="flex items-center justify-center gap-2">
                                                        <div class="flex items-center gap-2 rounded-full bg-gray-100 px-4 py-2">
                                                            <svg class="h-4 w-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                                <path
                                                                    fill-rule="evenodd"
                                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                                    clip-rule="evenodd"
                                                                />
                                                            </svg>
                                                            <span class="font-mono text-sm font-semibold text-gray-700"
                                                                >{{ Math.floor(recordingTime / 60) }}:{{
                                                                    String(recordingTime % 60).padStart(2, '0')
                                                                }}</span
                                                            >
                                                        </div>
                                                        <div
                                                            v-if="!canSubmit"
                                                            class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800"
                                                        >
                                                            Minimum {{ getRequiredSpeakingTime(selectedPart || '1') }}s required
                                                        </div>
                                                        <div v-else class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                                            ✓ Ready to submit
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Live Transcription Display -->
                                                <div
                                                    v-if="userInput || interimTranscript"
                                                    class="mb-4 min-h-32 rounded-lg border-2 border-green-200 bg-green-50 p-4 text-left"
                                                >
                                                    <p class="mb-1 text-xs font-semibold text-green-700">Your Response:</p>
                                                    <p class="text-gray-800">
                                                        {{ userInput }}
                                                        <span class="text-gray-400">{{ interimTranscript }}</span>
                                                    </p>
                                                </div>
                                                <div v-else class="mb-4 min-h-32 rounded-lg border-2 border-gray-200 bg-gray-50 p-4 text-left">
                                                    <p class="text-sm text-gray-400 italic">Your transcribed speech will appear here...</p>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="mt-4">
                                                    <button
                                                        @click="submitResponse"
                                                        :disabled="isLoading || !canSubmit"
                                                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-6 py-4 text-lg font-semibold text-white transition-all hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                        :class="canSubmit && !isLoading ? 'shadow-lg shadow-green-500/30' : ''"
                                                    >
                                                        <svg v-if="isLoading" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle
                                                                class="opacity-25"
                                                                cx="12"
                                                                cy="12"
                                                                r="10"
                                                                stroke="currentColor"
                                                                stroke-width="4"
                                                            ></circle>
                                                            <path
                                                                class="opacity-75"
                                                                fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                            ></path>
                                                        </svg>
                                                        <span v-if="isLoading">Processing...</span>
                                                        <span v-else-if="!canSubmit"
                                                            >Speak for {{ getRequiredSpeakingTime(selectedPart || '1') - recordingTime }}s
                                                            more...</span
                                                        >
                                                        <span v-else>✓ Submit Response</span>
                                                    </button>
                                                </div>

                                                <!-- Instructions -->
                                                <div class="mt-4 rounded-lg bg-blue-50 p-3 text-left">
                                                    <p class="text-xs text-blue-800">
                                                        <strong>📝 Instructions:</strong> Microphone is auto-recording. Speak clearly and naturally.
                                                        The submit button will activate after
                                                        {{ getRequiredSpeakingTime(selectedPart || '1') }} seconds. Your speech is being transcribed
                                                        in real-time.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="rounded-xl bg-white p-6 text-center shadow-sm">
                                        <svg class="mx-auto mb-4 h-16 w-16 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <h4 class="mb-2 text-xl font-bold text-gray-900">Session Complete!</h4>
                                        <p class="text-gray-600">Evaluating your performance...</p>
                                        <div v-if="isLoading" class="mt-4">
                                            <div class="mx-auto h-2 w-64 overflow-hidden rounded-full bg-gray-200">
                                                <div class="h-full animate-pulse bg-green-600" style="width: 70%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>

    <AppFooter />
</template>

<style scoped>
@keyframes fade-in-down {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-down {
    animation: fade-in-down 0.6s ease-out forwards;
}

.animate-fade-in-up {
    animation: fade-in-up 0.6s ease-out forwards;
}

.animation-delay-300 {
    animation-delay: 0.3s;
}

.animation-delay-500 {
    animation-delay: 0.5s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.animation-delay-700 {
    animation-delay: 0.7s;
}

.animation-delay-900 {
    animation-delay: 0.9s;
}
</style>
