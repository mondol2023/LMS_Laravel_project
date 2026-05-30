<script setup lang="ts">
import AppFooter from '@/components/Common/AppFooter.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// Mobile menu state
const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

// Close menu on route change
watch(mobileMenuOpen, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

interface Exam {
    id: number;
    name: string;
    exam_id: string;
    description?: string;
    created_at: string;
}

interface Props {
    auth?: {
        user: any;
    };
    totalUsers: number;
    totalTests: number;
    totalAttempts: number;
    avgScore: number;
    exams: Exam[];
}

const props = defineProps<Props>();

// Scroll-triggered animation
const observedElements = ref<Set<Element>>(new Set());
let observer: IntersectionObserver | null = null;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer?.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -40px 0px' },
    );

    document.querySelectorAll('.scroll-animate').forEach((el) => {
        observer?.observe(el);
        observedElements.value.add(el);
    });
});

onUnmounted(() => {
    observer?.disconnect();
});

// Animated counters
const counters = ref({
    users: 0,
    tests: 0,
    attempts: 0,
    score: 0,
});
const countersStarted = ref(false);

const startCounters = () => {
    if (countersStarted.value) return;
    countersStarted.value = true;

    const duration = 2500;
    const steps = 80;
    const interval = duration / steps;

    // Ensure minimum display values
    const targets = {
        users: Math.max(props.totalUsers, 1000),
        tests: Math.max(props.totalTests, 100),
        attempts: Math.max(props.totalAttempts, 500),
        score: Math.max(props.avgScore, 7.5),
    };

    let step = 0;
    const timer = setInterval(() => {
        step++;
        const progress = step / steps;
        // Ease-out cubic for a smooth deceleration
        const eased = 1 - Math.pow(1 - progress, 3);

        counters.value.users = Math.round(targets.users * eased);
        counters.value.tests = Math.round(targets.tests * eased);
        counters.value.attempts = Math.round(targets.attempts * eased);
        counters.value.score = Math.round(targets.score * eased * 10) / 10;

        if (step >= steps) {
            counters.value = { ...targets };
            clearInterval(timer);
        }
    }, interval);
};

onMounted(() => {
    const statsSection = document.getElementById('stats-section');
    if (statsSection) {
        const statsObserver = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) {
                    startCounters();
                    statsObserver.disconnect();
                }
            },
            { threshold: 0.3 },
        );
        statsObserver.observe(statsSection);
    }
});

// Modal state
const modalType = ref<'full' | 'partial' | null>(null);
const selectedExam = ref<Exam | null>(null);

const registrationForm = useForm({
    local_exam_id: '',
    roll_number: '',
    modules: [] as string[],
});

const openExamModal = () => {
    const isCoachingMode = usePage().props.isCoachingMode;

    // If not coaching mode and user is not logged in, redirect to login
    if (!isCoachingMode && !props.auth?.user) {
        router.visit('/login');
        return;
    }

    // Auto-fill roll number if user is logged in
    if (props.auth?.user?.roll_number) {
        registrationForm.roll_number = props.auth.user.roll_number;
    }

    modalType.value = 'full';
};

const openPartialExamModal = () => {
    const isCoachingMode = usePage().props.isCoachingMode;

    // If not coaching mode and user is not logged in, redirect to login
    if (!isCoachingMode && !props.auth?.user) {
        router.visit('/login');
        return;
    }

    // Auto-fill roll number if user is logged in
    if (props.auth?.user?.roll_number) {
        registrationForm.roll_number = props.auth.user.roll_number;
    }

    modalType.value = 'partial';
};

const openPracticeModal = () => {
    router.visit('/practice');
};

const closeModal = () => {
    modalType.value = null;
    selectedExam.value = null;
    registrationForm.reset();
};

const isPartialFormValid = computed(() => {
    if (modalType.value !== 'partial') return true;
    return registrationForm.modules.length > 0;
});

const enterFullscreen = async () => {
    try {
        if (document.documentElement.requestFullscreen) {
            await document.documentElement.requestFullscreen();
        }
    } catch (error) {
        console.error('Failed to enter fullscreen:', error);
    }
};

const submitRegistration = () => {
    if (!registrationForm.local_exam_id) return;

    if (modalType.value === 'full') {
        registrationForm.post(`/exam/${registrationForm.local_exam_id}/register`, {
            onSuccess: () => {
                enterFullscreen();
                closeModal();
            },
            onError: () => {},
        });
    } else if (modalType.value === 'partial') {
        registrationForm.post(`/partial-exam/${registrationForm.local_exam_id}/register`, {
            onSuccess: () => {
                enterFullscreen();
                closeModal();
            },
        });
    }
};
</script>

<template>
    <Head title="English Therapy - Master IELTS with Expert Guidance" />
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <!-- Navigation -->
        <nav class="fixed top-0 z-50 w-full">
            <div class="mx-auto px-4 py-3 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl rounded-2xl border border-white/20 bg-gradient-to-r from-slate-800/50 via-blue-900/50 to-indigo-900/50 px-4 py-3 shadow-2xl backdrop-blur-xs sm:px-6">
                    <div class="flex items-center justify-between">
                        <!-- Logo -->
                        <a href="/" class="group flex items-center gap-3">
                            <div class="relative">
                                <div class="absolute -inset-1 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 opacity-0 blur transition-all duration-300 group-hover:opacity-60"></div>
                                <img src="/logo.png" alt="English Therapy" class="relative h-10 w-10 rounded-xl object-contain shadow-lg sm:h-11 sm:w-11" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-lg font-bold leading-tight text-white sm:text-xl">English Therapy</span>
                                <span class="hidden text-[10px] font-medium tracking-wider text-blue-300/70 uppercase sm:block">IELTS Preparation</span>
                            </div>
                        </a>

                        <!-- Desktop Navigation -->
                        <div class="hidden items-center gap-2 md:flex">
                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="openPracticeModal"
                                    class="cursor-pointer rounded-xl border border-white/20 bg-white/5 px-4 py-2.5 text-sm font-medium text-white transition-all duration-300 hover:border-white/30 hover:bg-white/10"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        Practice
                                    </span>
                                </button>
                                <a
                                    v-if="!props.auth?.user"
                                    href="/login"
                                    class="group relative cursor-pointer overflow-hidden rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl"
                                >
                                    <span class="absolute inset-0 bg-gradient-to-r from-blue-500 to-cyan-500"></span>
                                    <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                                    <span class="relative flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                        </svg>
                                        Login
                                    </span>
                                </a>
                                <a
                                    v-else
                                    :href="props.auth.user.role === 'admin' ? '/dashboard' : '/student/dashboard'"
                                    class="group relative cursor-pointer overflow-hidden rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl"
                                >
                                    <span class="absolute inset-0 bg-gradient-to-r from-blue-500 to-cyan-500"></span>
                                    <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                                    <span class="relative flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        Dashboard
                                    </span>
                                </a>
                            </div>
                        </div>

                        <!-- Mobile Hamburger Button -->
                        <button
                            @click="toggleMobileMenu"
                            class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white transition-all duration-300 hover:bg-white/10 md:hidden"
                        >
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <span
                                    class="block h-0.5 w-5 rounded-full bg-white transition-all duration-300"
                                    :class="mobileMenuOpen ? 'translate-y-2 rotate-45' : ''"
                                ></span>
                                <span
                                    class="block h-0.5 w-5 rounded-full bg-white transition-all duration-300"
                                    :class="mobileMenuOpen ? 'opacity-0' : ''"
                                ></span>
                                <span
                                    class="block h-0.5 w-5 rounded-full bg-white transition-all duration-300"
                                    :class="mobileMenuOpen ? '-translate-y-2 -rotate-45' : ''"
                                ></span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <Transition name="mobile-menu">
                <div
                    v-if="mobileMenuOpen"
                    class="absolute top-full left-0 w-full px-4 pt-2 md:hidden"
                >
                    <div class="rounded-2xl border border-white/10 bg-slate-900/95 p-4 shadow-2xl backdrop-blur-xl">
                        <div class="flex flex-col gap-2">
                            <button
                                @click="openPracticeModal(); closeMobileMenu();"
                                class="flex items-center justify-center gap-2 rounded-xl bg-white/5 px-4 py-3.5 text-sm font-medium text-white transition-all duration-300 hover:bg-white/10"
                            >
                                <svg class="h-5 w-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                Practice Center
                            </button>

                            <button
                                @click="openPartialExamModal(); closeMobileMenu();"
                                class="flex items-center justify-center gap-2 rounded-xl bg-white/5 px-4 py-3.5 text-sm font-medium text-white transition-all duration-300 hover:bg-white/10"
                            >
                                <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                                </svg>
                                Partial Test
                            </button>

                            <a
                                v-if="!props.auth?.user"
                                href="/login"
                                class="mt-2 flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 px-4 py-3.5 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Login
                            </a>
                            <a
                                v-else
                                :href="props.auth.user.role === 'admin' ? '/dashboard' : '/student/dashboard'"
                                class="mt-2 flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 px-4 py-3.5 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- Mobile Menu Overlay -->
        <Transition name="overlay">
            <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm md:hidden" @click="closeMobileMenu"></div>
        </Transition>

        <!-- Hero Section -->
        <section
            class="relative flex min-h-[90vh] items-center overflow-hidden bg-gradient-to-br from-slate-900 via-blue-950 to-indigo-950 px-4 sm:min-h-screen sm:px-6 lg:px-8"
        >
            <!-- Modern mesh background -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="hero-mesh"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>
            </div>

            <div class="relative z-10 container mx-auto py-20 sm:py-24 lg:py-32">
                <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-2 lg:gap-12">
                    <!-- Left Content -->
                    <div class="text-center lg:text-left">
                        <!-- Badge -->
                        <div
                            class="hero-fade-up mb-6 inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-500/10 px-4 py-2 text-xs font-medium text-blue-200 backdrop-blur-md sm:mb-8 sm:px-5 sm:text-sm"
                        >
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                            </span>
                            <span class="hidden sm:inline">Empowering 10,000+ Students Worldwide</span>
                            <span class="sm:hidden">10,000+ Students Empowered</span>
                        </div>

                        <!-- Headline -->
                        <h1
                            class="hero-fade-up hero-delay-1 mb-6 text-4xl leading-tight font-extrabold tracking-tight text-white sm:mb-8 sm:text-5xl md:text-6xl lg:text-7xl"
                        >
                            Master IELTS with
                            <span class="relative inline-block">
                                <span class="relative z-10 bg-gradient-to-r from-blue-400 via-cyan-300 to-emerald-400 bg-clip-text text-transparent">
                                    English Therapy
                                </span>
                                <svg class="hero-underline absolute -bottom-2 left-0 w-full" viewBox="0 0 300 12" fill="none">
                                    <path d="M2 8C50 3 150 3 298 8" stroke="url(#gradient)" stroke-width="4" stroke-linecap="round"/>
                                    <defs>
                                        <linearGradient id="gradient" x1="0" y1="0" x2="300" y2="0">
                                            <stop offset="0%" stop-color="#60A5FA"/>
                                            <stop offset="50%" stop-color="#22D3EE"/>
                                            <stop offset="100%" stop-color="#34D399"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                            </span>
                        </h1>

                        <!-- Subheadline -->
                        <p
                            class="hero-fade-up hero-delay-2 mx-auto mb-8 max-w-xl text-base leading-relaxed text-blue-100/90 sm:mb-10 sm:text-lg lg:mx-0 lg:text-xl"
                        >
                            Your complete IELTS preparation platform with AI-powered scoring, real exam conditions, and personalized guidance to achieve your target band score.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="hero-fade-up hero-delay-3 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-center sm:gap-4 lg:justify-start">
                            <!-- Start Full Mock Test -->
                            <button
                                @click="openExamModal"
                                class="group relative cursor-pointer overflow-hidden rounded-xl px-6 py-4 text-sm font-bold shadow-xl transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl sm:rounded-2xl sm:px-8 sm:py-5 sm:text-base sm:hover:scale-[1.03]"
                            >
                                <span class="absolute inset-0 bg-gradient-to-r from-blue-500 to-cyan-500"></span>
                                <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                                <span class="relative z-10 flex items-center justify-center gap-2 text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Start Full Mock Test
                                    <svg
                                        class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>

                            <!-- Partial Test -->
                            <button
                                @click="openPartialExamModal"
                                class="group relative cursor-pointer overflow-hidden rounded-xl border-2 border-white/20 px-6 py-4 text-sm font-semibold text-white backdrop-blur-md transition-all duration-300 hover:scale-[1.02] hover:border-white/40 hover:bg-white/5 sm:rounded-2xl sm:px-8 sm:py-5 sm:text-base sm:hover:scale-[1.03]"
                            >
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                                    </svg>
                                    Practice Individual Modules
                                </span>
                            </button>
                        </div>

                        <!-- Trust indicators -->
                        <div
                            class="hero-fade-up hero-delay-4 mt-10 flex flex-wrap items-center justify-center gap-4 text-xs text-blue-200/70 sm:mt-12 sm:gap-6 sm:text-sm lg:justify-start lg:gap-8"
                        >
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span class="hidden sm:inline">Free Practice Tests</span>
                                <span class="sm:hidden">Free Tests</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                AI Scoring
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span class="hidden sm:inline">Real Exam Conditions</span>
                                <span class="sm:hidden">Real Exams</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content - Banner Images -->
                    <div class="hero-fade-up hero-delay-2 relative hidden lg:block">
                        <div class="relative">
                            <!-- Decorative elements -->
                            <div class="absolute -top-8 -left-8 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl"></div>
                            <div class="absolute -bottom-8 -right-8 h-72 w-72 rounded-full bg-cyan-500/20 blur-3xl"></div>

                            <!-- Main image card -->
                            <div class="relative z-10 overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-4 shadow-2xl backdrop-blur-sm">
                                <img
                                    src="/images/Banner/b1.png"
                                    alt="IELTS Practice"
                                    class="min-h-[300px] w-full rounded-2xl object-contain shadow-lg"
                                />
                            </div>

                            <!-- Floating secondary image -->
                            <div class="absolute -bottom-6 -left-12 z-20 w-48 overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-2 shadow-xl backdrop-blur-sm">
                                <img
                                    src="/images/Banner/b2.png"
                                    alt="IELTS Success"
                                    class="h-auto w-full rounded-xl object-cover"
                                />
                            </div>

                            <!-- Floating stats card -->
                            <div class="absolute -right-6 -top-4 z-20 rounded-2xl border border-white/10 bg-white/10 px-5 py-4 shadow-xl backdrop-blur-md">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-400">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-white">Band 7.5+</div>
                                        <div class="text-xs text-blue-200/70">Average Score</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll indicator - hidden on mobile -->
            <div class="absolute bottom-8 left-1/2 hidden -translate-x-1/2 sm:flex">
                <div class="flex flex-col items-center gap-2 text-white/40">
                    <span class="text-xs font-medium tracking-widest uppercase">Scroll</span>
                    <div class="h-8 w-5 rounded-full border-2 border-white/30 p-1">
                        <div class="scroll-dot h-1.5 w-1.5 rounded-full bg-white/60"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section id="stats-section" class="relative z-20 -mt-8 px-4 sm:-mt-16 sm:px-6 lg:px-8">
            <div class="container mx-auto">
                <div class="scroll-animate mx-auto grid max-w-6xl grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4 lg:gap-6">
                    <div
                        class="group rounded-xl border border-blue-100 bg-white p-4 text-center shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl sm:rounded-2xl sm:p-6 lg:p-8"
                    >
                        <div class="mb-2 flex items-center justify-center sm:mb-3">
                            <svg class="mb-2 h-6 w-6 text-blue-500 sm:h-8 sm:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                        </div>
                        <div class="mb-1 text-2xl font-extrabold text-blue-600 sm:text-3xl lg:text-4xl">{{ counters.users.toLocaleString() }}+</div>
                        <div class="text-xs font-medium text-gray-600 sm:text-sm">Active Students</div>
                    </div>
                    <div
                        class="group rounded-xl border border-indigo-100 bg-white p-4 text-center shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl sm:rounded-2xl sm:p-6 lg:p-8"
                    >
                        <div class="mb-2 flex items-center justify-center sm:mb-3">
                            <svg class="mb-2 h-6 w-6 text-indigo-500 sm:h-8 sm:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <div class="mb-1 text-2xl font-extrabold text-indigo-600 sm:text-3xl lg:text-4xl">{{ counters.tests }}+</div>
                        <div class="text-xs font-medium text-gray-600 sm:text-sm">Mock Tests</div>
                    </div>
                    <div
                        class="group rounded-xl border border-purple-100 bg-white p-4 text-center shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-purple-200 hover:shadow-xl sm:rounded-2xl sm:p-6 lg:p-8"
                    >
                        <div class="mb-2 flex items-center justify-center sm:mb-3">
                            <svg class="mb-2 h-6 w-6 text-purple-500 sm:h-8 sm:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                />
                            </svg>
                        </div>
                        <div class="mb-1 text-2xl font-extrabold text-purple-600 sm:text-3xl lg:text-4xl">
                            {{ counters.attempts.toLocaleString() }}+
                        </div>
                        <div class="text-xs font-medium text-gray-600 sm:text-sm">Tests Taken</div>
                    </div>
                    <div
                        class="group rounded-xl border border-emerald-100 bg-white p-4 text-center shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl sm:rounded-2xl sm:p-6 lg:p-8"
                    >
                        <div class="mb-2 flex items-center justify-center sm:mb-3">
                            <svg class="mb-2 h-6 w-6 text-emerald-500 sm:h-8 sm:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                />
                            </svg>
                        </div>
                        <div class="mb-1 text-2xl font-extrabold text-emerald-600 sm:text-3xl lg:text-4xl">{{ counters.score }}</div>
                        <div class="text-xs font-medium text-gray-600 sm:text-sm">Avg. Band Score</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="relative overflow-hidden bg-white px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28">
            <!-- Animated floating dots -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="floating-dot absolute top-[10%] left-[5%] h-2 w-2 rounded-full bg-blue-400/50"></div>
                <div class="floating-dot-2 absolute top-[20%] left-[15%] h-1.5 w-1.5 rounded-full bg-indigo-400/40"></div>
                <div class="floating-dot-3 absolute top-[15%] left-[25%] h-2.5 w-2.5 rounded-full bg-cyan-400/50"></div>
                <div class="floating-dot absolute top-[30%] left-[8%] h-1 w-1 rounded-full bg-violet-400/40"></div>
                <div class="floating-dot-2 absolute top-[5%] left-[40%] h-2 w-2 rounded-full bg-blue-400/30"></div>
                <div class="floating-dot-3 absolute top-[25%] left-[50%] h-1.5 w-1.5 rounded-full bg-emerald-400/40"></div>
                <div class="floating-dot absolute top-[12%] left-[60%] h-2 w-2 rounded-full bg-indigo-400/50"></div>
                <div class="floating-dot-2 absolute top-[8%] left-[75%] h-1 w-1 rounded-full bg-cyan-400/40"></div>
                <div class="floating-dot-3 absolute top-[22%] left-[85%] h-2.5 w-2.5 rounded-full bg-violet-400/50"></div>
                <div class="floating-dot absolute top-[18%] left-[92%] h-1.5 w-1.5 rounded-full bg-blue-400/40"></div>
                <div class="floating-dot-2 absolute top-[50%] left-[3%] h-2 w-2 rounded-full bg-emerald-400/50"></div>
                <div class="floating-dot-3 absolute top-[60%] left-[12%] h-1 w-1 rounded-full bg-indigo-400/40"></div>
                <div class="floating-dot absolute top-[55%] left-[22%] h-1.5 w-1.5 rounded-full bg-cyan-400/50"></div>
                <div class="floating-dot-2 absolute top-[70%] left-[35%] h-2 w-2 rounded-full bg-violet-400/40"></div>
                <div class="floating-dot-3 absolute top-[65%] left-[55%] h-2.5 w-2.5 rounded-full bg-blue-400/50"></div>
                <div class="floating-dot absolute top-[75%] left-[70%] h-1 w-1 rounded-full bg-emerald-400/40"></div>
                <div class="floating-dot-2 absolute top-[58%] left-[80%] h-2 w-2 rounded-full bg-indigo-400/50"></div>
                <div class="floating-dot-3 absolute top-[80%] left-[90%] h-1.5 w-1.5 rounded-full bg-cyan-400/40"></div>
                <div class="floating-dot absolute top-[85%] left-[45%] h-2 w-2 rounded-full bg-violet-400/50"></div>
                <div class="floating-dot-2 absolute top-[90%] left-[65%] h-1 w-1 rounded-full bg-blue-400/40"></div>
            </div>
            <div class="relative z-10 container mx-auto">
                <div class="scroll-animate mb-12 text-center sm:mb-16">
                    <span
                        class="mb-3 inline-block rounded-full bg-blue-50 px-4 py-1.5 text-xs font-bold tracking-wider text-blue-600 uppercase sm:mb-4"
                    >
                        Practice Modules
                    </span>
                    <h2 class="mb-3 text-3xl font-extrabold text-gray-900 sm:mb-4 sm:text-4xl lg:text-5xl">Complete IELTS Preparation</h2>
                    <p class="mx-auto max-w-2xl text-base text-gray-600 sm:text-lg">
                        Master all four essential skills with English Therapy's expert-guided practice
                    </p>
                </div>

                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                    <!-- Listening -->
                    <a
                        href="/listening"
                        class="module-card scroll-animate group relative overflow-hidden rounded-xl border-2 border-blue-100 bg-gradient-to-br from-blue-50 to-white p-6 shadow-md transition-all duration-500 ease-out hover:-translate-y-3 hover:border-blue-300 hover:shadow-2xl sm:rounded-2xl sm:p-8"
                    >
                        <!-- Shimmer effect -->
                        <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"></div>
                        <!-- Glow effect -->
                        <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 opacity-0 blur-xl transition-opacity duration-500 group-hover:opacity-20"></div>
                        <div class="relative z-10">
                            <div
                                class="icon-bounce mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-blue-500/40 sm:mb-5 sm:h-14 sm:w-14 sm:rounded-2xl"
                            >
                                <svg class="h-6 w-6 text-white transition-transform duration-300 group-hover:scale-110 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
                                    />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-blue-700 sm:text-xl">Listening</h3>
                            <p class="mb-4 text-sm leading-relaxed text-gray-600">Authentic audio with varied accents and instant scoring.</p>
                            <span
                                class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 transition-all duration-300 group-hover:gap-3"
                            >
                                Practice Now
                                <svg
                                    class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Reading -->
                    <a
                        href="/reading"
                        class="module-card scroll-animate group relative overflow-hidden rounded-xl border-2 border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-md transition-all duration-500 ease-out hover:-translate-y-3 hover:border-emerald-300 hover:shadow-2xl sm:rounded-2xl sm:p-8"
                        style="animation-delay: 100ms"
                    >
                        <!-- Shimmer effect -->
                        <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"></div>
                        <!-- Glow effect -->
                        <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 opacity-0 blur-xl transition-opacity duration-500 group-hover:opacity-20"></div>
                        <div class="relative z-10">
                            <div
                                class="icon-bounce mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-emerald-500/40 sm:mb-5 sm:h-14 sm:w-14 sm:rounded-2xl"
                            >
                                <svg class="h-6 w-6 text-white transition-transform duration-300 group-hover:scale-110 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-emerald-700 sm:text-xl">Reading</h3>
                            <p class="mb-4 text-sm leading-relaxed text-gray-600">Academic passages with detailed explanations and strategies.</p>
                            <span
                                class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 transition-all duration-300 group-hover:gap-3"
                            >
                                Practice Now
                                <svg
                                    class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Writing -->
                    <a
                        href="/writing"
                        class="module-card scroll-animate group relative overflow-hidden rounded-xl border-2 border-violet-100 bg-gradient-to-br from-violet-50 to-white p-6 shadow-md transition-all duration-500 ease-out hover:-translate-y-3 hover:border-violet-300 hover:shadow-2xl sm:rounded-2xl sm:p-8"
                        style="animation-delay: 200ms"
                    >
                        <!-- Shimmer effect -->
                        <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"></div>
                        <!-- Glow effect -->
                        <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-violet-400 to-violet-600 opacity-0 blur-xl transition-opacity duration-500 group-hover:opacity-20"></div>
                        <div class="relative z-10">
                            <div
                                class="icon-bounce mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-violet-500/40 sm:mb-5 sm:h-14 sm:w-14 sm:rounded-2xl"
                            >
                                <svg class="h-6 w-6 text-white transition-transform duration-300 group-hover:scale-110 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-violet-700 sm:text-xl">Writing</h3>
                            <p class="mb-4 text-sm leading-relaxed text-gray-600">AI evaluation for Task 1 & 2 with detailed band scores.</p>
                            <span
                                class="inline-flex items-center gap-1 text-sm font-semibold text-violet-600 transition-all duration-300 group-hover:gap-3"
                            >
                                Practice Now
                                <svg
                                    class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Speaking -->
                    <a
                        href="/speaking"
                        class="module-card scroll-animate group relative overflow-hidden rounded-xl border-2 border-amber-100 bg-gradient-to-br from-amber-50 to-white p-6 shadow-md transition-all duration-500 ease-out hover:-translate-y-3 hover:border-amber-300 hover:shadow-2xl sm:rounded-2xl sm:p-8"
                        style="animation-delay: 300ms"
                    >
                        <!-- Shimmer effect -->
                        <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"></div>
                        <!-- Glow effect -->
                        <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 opacity-0 blur-xl transition-opacity duration-500 group-hover:opacity-20"></div>
                        <div class="relative z-10">
                            <div
                                class="icon-bounce mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-amber-500/40 sm:mb-5 sm:h-14 sm:w-14 sm:rounded-2xl"
                            >
                                <svg class="h-6 w-6 text-white transition-transform duration-300 group-hover:scale-110 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                    />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-bold text-gray-900 sm:text-xl">Speaking</h3>
                            <p class="mb-4 text-sm leading-relaxed text-gray-600">AI conversation with real-time transcription & feedback.</p>
                            <span
                                class="inline-flex items-center gap-1 text-sm font-semibold text-amber-600 transition-all duration-300 group-hover:gap-2"
                            >
                                Practice Now
                                <svg
                                    class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="relative overflow-hidden bg-gradient-to-b from-gray-900 to-gray-950 px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28">
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-blue-900/20 via-transparent to-transparent"
            ></div>

            <div class="relative z-10 container mx-auto">
                <div class="scroll-animate mb-12 text-center sm:mb-16">
                    <span
                        class="mb-3 inline-block rounded-full border border-blue-400/20 bg-blue-500/10 px-4 py-1.5 text-xs font-bold tracking-wider text-blue-400 uppercase sm:mb-4"
                    >
                        Why English Therapy
                    </span>
                    <h2 class="mb-3 text-3xl font-extrabold text-white sm:mb-4 sm:text-4xl lg:text-5xl">Your Path to IELTS Excellence</h2>
                    <p class="mx-auto max-w-2xl text-base text-gray-400 sm:text-lg">
                        Comprehensive tools and expert guidance for your IELTS success journey
                    </p>
                </div>

                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                    <div
                        class="scroll-animate group rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-all duration-500 hover:scale-[1.02] hover:border-blue-400/30 hover:bg-white/10 sm:rounded-2xl sm:p-8"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg sm:mb-5 sm:h-14 sm:w-14"
                        >
                            <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-white sm:text-lg">Instant Results</h3>
                        <p class="text-sm leading-relaxed text-gray-300">Immediate scores and detailed feedback for all sections.</p>
                    </div>

                    <div
                        class="scroll-animate group rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-all duration-500 hover:scale-[1.02] hover:border-purple-400/30 hover:bg-white/10 sm:rounded-2xl sm:p-8"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg sm:mb-5 sm:h-14 sm:w-14"
                        >
                            <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-white sm:text-lg">AI-Powered Evaluation</h3>
                        <p class="text-sm leading-relaxed text-gray-300">Advanced AI for accurate band score predictions.</p>
                    </div>

                    <div
                        class="scroll-animate group rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-all duration-500 hover:scale-[1.02] hover:border-emerald-400/30 hover:bg-white/10 sm:rounded-2xl sm:p-8"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg sm:mb-5 sm:h-14 sm:w-14"
                        >
                            <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-white sm:text-lg">Performance Analytics</h3>
                        <p class="text-sm leading-relaxed text-gray-300">Track progress and identify areas for improvement.</p>
                    </div>

                    <div
                        class="scroll-animate group rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-all duration-500 hover:scale-[1.02] hover:border-amber-400/30 hover:bg-white/10 sm:rounded-2xl sm:p-8"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-lg sm:mb-5 sm:h-14 sm:w-14"
                        >
                            <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-white sm:text-lg">Real Exam Conditions</h3>
                        <p class="text-sm leading-relaxed text-gray-300">Authentic formats and timing matching actual IELTS.</p>
                    </div>

                    <div
                        class="scroll-animate group rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-all duration-500 hover:scale-[1.02] hover:border-cyan-400/30 hover:bg-white/10 sm:rounded-2xl sm:p-8"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-cyan-600 text-white shadow-lg sm:mb-5 sm:h-14 sm:w-14"
                        >
                            <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-white sm:text-lg">Global Community</h3>
                        <p class="text-sm leading-relaxed text-gray-300">Compare with thousands of students worldwide.</p>
                    </div>

                    <div
                        class="scroll-animate group rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-all duration-500 hover:scale-[1.02] hover:border-rose-400/30 hover:bg-white/10 sm:rounded-2xl sm:p-8"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 text-white shadow-lg sm:mb-5 sm:h-14 sm:w-14"
                        >
                            <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-white sm:text-lg">Practice Anywhere</h3>
                        <p class="text-sm leading-relaxed text-gray-300">Access on any device - desktop, tablet, or mobile.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Getting Started Section -->
        <section class="bg-gradient-to-b from-white to-gray-50 px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28">
            <div class="container mx-auto">
                <div class="scroll-animate mb-12 text-center sm:mb-16">
                    <span
                        class="mb-3 inline-block rounded-full bg-indigo-50 px-4 py-1.5 text-xs font-bold tracking-wider text-indigo-600 uppercase sm:mb-4"
                    >
                        Getting Started
                    </span>
                    <h2 class="mb-3 text-3xl font-extrabold text-gray-900 sm:mb-4 sm:text-4xl lg:text-5xl">How It Works</h2>
                    <p class="mx-auto max-w-2xl text-base text-gray-600 sm:text-lg">
                        Begin your IELTS journey with English Therapy in three simple steps
                    </p>
                </div>

                <div class="mx-auto max-w-6xl">
                    <!-- Steps container -->
                    <div class="relative grid grid-cols-1 gap-6 sm:gap-8 md:grid-cols-3">
                        <!-- Dashed connector line (md+) -->
                        <div class="absolute top-16 right-[16.67%] left-[16.67%] hidden h-px md:block">
                            <div class="h-full w-full border-t-2 border-dashed border-indigo-200"></div>
                        </div>

                        <!-- Step 1 -->
                        <div class="scroll-animate group text-center">
                            <div class="relative mx-auto mb-6 flex flex-col items-center sm:mb-8">
                                <!-- Number circle -->
                                <div
                                    class="relative z-10 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-lg font-extrabold text-white shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:shadow-xl sm:h-16 sm:w-16 sm:text-xl"
                                >
                                    1
                                </div>
                            </div>
                            <!-- Card -->
                            <div
                                class="rounded-xl border-2 border-blue-100 bg-white p-6 transition-all duration-500 group-hover:border-blue-300 group-hover:shadow-xl sm:rounded-2xl sm:p-8"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-all duration-300 group-hover:bg-blue-100 sm:h-14 sm:w-14"
                                >
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                        />
                                    </svg>
                                </div>
                                <h3 class="mb-2 text-base font-bold text-gray-900 sm:text-lg">Choose Your Test</h3>
                                <p class="text-sm leading-relaxed text-gray-600">Pick a full mock test or focus on individual sections.</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="scroll-animate group text-center" style="transition-delay: 120ms">
                            <div class="relative mx-auto mb-6 flex flex-col items-center sm:mb-8">
                                <div
                                    class="relative z-10 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 text-lg font-extrabold text-white shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:shadow-xl sm:h-16 sm:w-16 sm:text-xl"
                                >
                                    2
                                </div>
                            </div>
                            <div
                                class="rounded-xl border-2 border-indigo-100 bg-white p-6 transition-all duration-500 group-hover:border-indigo-300 group-hover:shadow-xl sm:rounded-2xl sm:p-8"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition-all duration-300 group-hover:bg-indigo-100 sm:h-14 sm:w-14"
                                >
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="mb-2 text-base font-bold text-gray-900 sm:text-lg">Take the Test</h3>
                                <p class="text-sm leading-relaxed text-gray-600">Experience real exam conditions with timed sections.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="scroll-animate group text-center" style="transition-delay: 240ms">
                            <div class="relative mx-auto mb-6 flex flex-col items-center sm:mb-8">
                                <div
                                    class="relative z-10 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-purple-600 text-lg font-extrabold text-white shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:shadow-xl sm:h-16 sm:w-16 sm:text-xl"
                                >
                                    3
                                </div>
                            </div>
                            <div
                                class="rounded-xl border-2 border-purple-100 bg-white p-6 transition-all duration-500 group-hover:border-purple-300 group-hover:shadow-xl sm:rounded-2xl sm:p-8"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 transition-all duration-300 group-hover:bg-purple-100 sm:h-14 sm:w-14"
                                >
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="mb-2 text-base font-bold text-gray-900 sm:text-lg">Get Instant Feedback</h3>
                                <p class="text-sm leading-relaxed text-gray-600">Detailed band scores and personalized recommendations.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->

        <AppFooter />

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="modal-backdrop">
                <div v-if="modalType" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="fixed inset-0 bg-black/70 backdrop-blur-md" @click="closeModal"></div>

                    <div class="flex min-h-full items-center justify-center p-4">
                        <Transition name="modal-content" appear>
                            <div
                                class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl sm:max-w-lg sm:rounded-3xl"
                            >
                                <!-- Header -->
                                <div
                                    class="px-6 py-6 text-white sm:px-8 sm:py-8"
                                    :class="{
                                        'bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-700': modalType === 'full',
                                        'bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-700': modalType === 'partial',
                                    }"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex flex-1 items-start gap-3 sm:gap-4">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm sm:h-12 sm:w-12"
                                            >
                                                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-bold sm:text-lg">
                                                    {{ modalType === 'full' ? 'Full Mock Test' : 'Partial Mock Test' }}
                                                </h3>
                                                <p class="mt-1 text-xs text-white/80 sm:text-sm">
                                                    {{ modalType === 'full' ? 'Enter your details to begin' : 'Select modules and enter details' }}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            @click="closeModal"
                                            class="cursor-pointer rounded-lg p-1.5 text-white/70 transition-colors hover:bg-white/10 hover:text-white sm:p-2"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Form -->
                                <div class="p-8">
                                    <form
                                        v-if="modalType === 'full' || modalType === 'partial'"
                                        @submit.prevent="submitRegistration"
                                        class="space-y-5"
                                    >
                                        <div>
                                            <label for="modal-exam-id" class="mb-1.5 block text-sm font-semibold text-gray-700">
                                                Exam ID <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                id="modal-exam-id"
                                                v-model="registrationForm.local_exam_id"
                                                type="text"
                                                required
                                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 placeholder-gray-400 transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                                :class="{
                                                    'border-red-400 focus:border-red-500 focus:ring-red-500/20':
                                                        registrationForm.errors.local_exam_id,
                                                }"
                                                placeholder="Enter your exam ID"
                                            />
                                            <p v-if="registrationForm.errors.local_exam_id" class="mt-1.5 text-sm text-red-600">
                                                {{ registrationForm.errors.local_exam_id }}
                                            </p>
                                        </div>

                                        <div>
                                            <label for="modal-roll-number" class="mb-1.5 block text-sm font-semibold text-gray-700">
                                                Roll Number <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                id="modal-roll-number"
                                                v-model="registrationForm.roll_number"
                                                type="text"
                                                required
                                                :disabled="!!props.auth?.user?.roll_number"
                                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 placeholder-gray-400 transition-all duration-200 hover:border-gray-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 disabled:bg-gray-100"
                                                :class="{
                                                    'border-red-400 focus:border-red-500 focus:ring-red-500/20': registrationForm.errors.roll_number,
                                                }"
                                                placeholder="Enter your roll number"
                                            />
                                            <p v-if="registrationForm.errors.roll_number" class="mt-1.5 text-sm text-red-600">
                                                {{ registrationForm.errors.roll_number }}
                                            </p>
                                        </div>

                                        <!-- Module Selection (Partial only) -->
                                        <div v-if="modalType === 'partial'" class="space-y-3">
                                            <label class="block text-sm font-semibold text-gray-700">
                                                Select Modules <span class="text-red-500">*</span>
                                            </label>
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                                <label
                                                    v-for="mod in ['listening', 'reading', 'writing']"
                                                    :key="mod"
                                                    class="flex cursor-pointer items-center gap-2.5 rounded-xl border-2 px-4 py-3 transition-all duration-200"
                                                    :class="
                                                        registrationForm.modules.includes(mod)
                                                            ? 'border-purple-500 bg-purple-50 text-purple-700'
                                                            : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                                    "
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :value="mod"
                                                        v-model="registrationForm.modules"
                                                        class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                    />
                                                    <span class="text-sm font-medium capitalize">{{ mod }}</span>
                                                </label>
                                            </div>
                                            <p v-if="registrationForm.errors.modules" class="text-sm text-red-600">
                                                {{ registrationForm.errors.modules }}
                                            </p>
                                            <p v-if="!isPartialFormValid" class="flex items-center gap-1.5 text-sm text-amber-600">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                                Select at least one module
                                            </p>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-col gap-3 pt-4 sm:flex-row">
                                            <button
                                                @click="closeModal"
                                                type="button"
                                                class="order-2 cursor-pointer rounded-xl border-2 border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 sm:order-1 sm:flex-1"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                type="submit"
                                                :disabled="registrationForm.processing || !isPartialFormValid"
                                                class="order-1 cursor-pointer rounded-xl px-5 py-3 text-sm font-bold text-white shadow-lg transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50 sm:order-2 sm:flex-1"
                                                :class="{
                                                    'bg-gradient-to-r from-blue-600 to-indigo-600 hover:shadow-xl hover:brightness-110 focus:ring-4 focus:ring-blue-500/20':
                                                        modalType === 'full',
                                                    'bg-gradient-to-r from-purple-600 to-indigo-600 hover:shadow-xl hover:brightness-110 focus:ring-4 focus:ring-purple-500/20':
                                                        modalType === 'partial',
                                                }"
                                            >
                                                <svg
                                                    v-if="registrationForm.processing"
                                                    class="mr-2 -ml-1 inline h-4 w-4 animate-spin text-white"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path
                                                        class="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                    ></path>
                                                </svg>
                                                {{
                                                    registrationForm.processing
                                                        ? 'Starting...'
                                                        : modalType === 'full'
                                                          ? 'Start Exam'
                                                          : 'Start Partial Exam'
                                                }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
/* Hero mesh background */
.hero-mesh {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(at 10% 20%, rgba(59, 130, 246, 0.4) 0px, transparent 50%),
        radial-gradient(at 90% 10%, rgba(34, 211, 238, 0.3) 0px, transparent 50%),
        radial-gradient(at 70% 80%, rgba(52, 211, 153, 0.25) 0px, transparent 50%),
        radial-gradient(at 30% 70%, rgba(99, 102, 241, 0.3) 0px, transparent 50%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, transparent 100%);
    background-size:
        100% 100%,
        100% 100%,
        100% 100%,
        100% 100%,
        100% 100%;
    animation: mesh-move 25s ease-in-out infinite;
}

@keyframes mesh-move {
    0%,
    100% {
        background-position:
            0% 0%,
            100% 0%,
            80% 100%,
            20% 70%,
            0% 0%;
    }
    33% {
        background-position:
            50% 50%,
            50% 50%,
            20% 50%,
            80% 30%,
            50% 50%;
    }
    66% {
        background-position:
            100% 100%,
            0% 100%,
            100% 0%,
            0% 100%,
            100% 100%;
    }
}

/* Hero entrance animations */
.hero-fade-up {
    animation: hero-fade-up 0.8s ease-out forwards;
    opacity: 0;
}
.hero-delay-1 {
    animation-delay: 0.15s;
}
.hero-delay-2 {
    animation-delay: 0.3s;
}
.hero-delay-3 {
    animation-delay: 0.45s;
}
.hero-delay-4 {
    animation-delay: 0.6s;
}

@keyframes hero-fade-up {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Underline draw animation */
.hero-underline {
    stroke-dasharray: 400;
    stroke-dashoffset: 400;
    animation: draw-underline 1.2s ease-out 0.6s forwards;
}
@keyframes draw-underline {
    to {
        stroke-dashoffset: 0;
    }
}

/* Float animation for images */
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

/* Floating dots animation */
.floating-dot {
    animation: float-dot 8s ease-in-out infinite;
}

.floating-dot-2 {
    animation: float-dot-2 10s ease-in-out infinite;
}

.floating-dot-3 {
    animation: float-dot-3 12s ease-in-out infinite;
}

@keyframes float-dot {
    0%, 100% {
        transform: translateY(0) translateX(0);
        opacity: 0.5;
    }
    50% {
        transform: translateY(-20px) translateX(10px);
        opacity: 0.8;
    }
}

@keyframes float-dot-2 {
    0%, 100% {
        transform: translateY(0) translateX(0);
        opacity: 0.4;
    }
    33% {
        transform: translateY(-15px) translateX(-8px);
        opacity: 0.7;
    }
    66% {
        transform: translateY(10px) translateX(12px);
        opacity: 0.5;
    }
}

@keyframes float-dot-3 {
    0%, 100% {
        transform: translateY(0) translateX(0);
        opacity: 0.5;
    }
    25% {
        transform: translateY(-12px) translateX(15px);
        opacity: 0.7;
    }
    50% {
        transform: translateY(8px) translateX(-10px);
        opacity: 0.4;
    }
    75% {
        transform: translateY(-18px) translateX(-5px);
        opacity: 0.8;
    }
}

/* Module card animations */
.module-card {
    animation: module-fade-in 0.6s ease-out forwards;
    animation-play-state: paused;
}

.module-card.animate-in {
    animation-play-state: running;
}

@keyframes module-fade-in {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Icon bounce animation on hover */
.icon-bounce {
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.group:hover .icon-bounce {
    animation: icon-pulse 0.6s ease-out;
}

@keyframes icon-pulse {
    0% {
        transform: scale(1) rotate(0deg);
    }
    25% {
        transform: scale(1.15) rotate(3deg);
    }
    50% {
        transform: scale(1.1) rotate(-2deg);
    }
    75% {
        transform: scale(1.12) rotate(1deg);
    }
    100% {
        transform: scale(1.1) rotate(3deg);
    }
}

/* Scroll dot */
.scroll-dot {
    animation: scroll-bounce 2s ease-in-out infinite;
}
@keyframes scroll-bounce {
    0%,
    100% {
        transform: translateY(0);
        opacity: 0.6;
    }
    50% {
        transform: translateY(10px);
        opacity: 0;
    }
}

/* Scroll-triggered animations */
.scroll-animate {
    opacity: 0;
    transform: translateY(32px);
    transition:
        opacity 0.6s ease-out,
        transform 0.6s ease-out;
}
.scroll-animate.animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Modal transitions */
.modal-backdrop-enter-active {
    transition: opacity 0.2s ease;
}
.modal-backdrop-leave-active {
    transition: opacity 0.15s ease;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
    opacity: 0;
}

.modal-content-enter-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-content-leave-active {
    transition: all 0.15s ease;
}
.modal-content-enter-from {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
}
.modal-content-leave-to {
    opacity: 0;
    transform: scale(0.97);
}

/* Mobile menu transitions */
.mobile-menu-enter-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.mobile-menu-leave-active {
    transition: all 0.2s ease-in;
}
.mobile-menu-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}
.mobile-menu-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Overlay transitions */
.overlay-enter-active {
    transition: opacity 0.3s ease;
}
.overlay-leave-active {
    transition: opacity 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
    opacity: 0;
}
</style>
