<script setup>
import AppFooter from '@/components/Common/AppFooter.vue';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

defineOptions({
    layout: PracticeLayout,
});

// Scroll-triggered animations
let observer = null;

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
        { threshold: 0.1, rootMargin: '0px 0px -30px 0px' },
    );
    document.querySelectorAll('.scroll-reveal').forEach((el) => observer?.observe(el));
});

onUnmounted(() => {
    observer?.disconnect();
});

// Sections data
const sections = [
    {
        href: '/listening',
        title: 'Listening',
        subtitle: '4 Sections · 40 Questions · 30 Min',
        description: 'Sharpen your ear with authentic audio, varied accents, and real-time automated scoring.',
        color: 'blue',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />`,
        tags: ['Audio Practice', 'Auto Scoring'],
    },
    {
        href: '/reading',
        title: 'Reading',
        subtitle: '3 Passages · 40 Questions · 60 Min',
        description: 'Academic and General Training passages with instant scoring and detailed answer explanations.',
        color: 'emerald',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />`,
        tags: ['Passages', 'Explanations'],
    },
    {
        href: '/writing',
        title: 'Writing',
        subtitle: 'Task 1 & Task 2 · 60 Min',
        description: 'AI-powered evaluation with professional band score assessment and improvement tips.',
        color: 'violet',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />`,
        tags: ['AI Evaluation', 'Band Score'],
    },
    {
        href: '/speaking',
        title: 'Speaking',
        subtitle: '3 Parts · 11-14 Min',
        description: 'Interactive AI conversation with real-time transcription and comprehensive feedback.',
        color: 'amber',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />`,
        tags: ['AI Conversation', 'Live Feedback'],
    },
];

const colorMap = {
    blue: {
        bg: 'bg-blue-50',
        bgHover: 'group-hover:bg-blue-600',
        text: 'text-blue-600',
        textHover: 'group-hover:text-white',
        border: 'hover:border-blue-200',
        shadow: 'hover:shadow-blue-500/10',
        tag: 'bg-blue-50 text-blue-600',
        tagHover: 'group-hover:bg-blue-500/20 group-hover:text-blue-100',
        gradient: 'from-blue-500 to-blue-600',
    },
    emerald: {
        bg: 'bg-emerald-50',
        bgHover: 'group-hover:bg-emerald-600',
        text: 'text-emerald-600',
        textHover: 'group-hover:text-white',
        border: 'hover:border-emerald-200',
        shadow: 'hover:shadow-emerald-500/10',
        tag: 'bg-emerald-50 text-emerald-600',
        tagHover: 'group-hover:bg-emerald-500/20 group-hover:text-emerald-100',
        gradient: 'from-emerald-500 to-emerald-600',
    },
    violet: {
        bg: 'bg-violet-50',
        bgHover: 'group-hover:bg-violet-600',
        text: 'text-violet-600',
        textHover: 'group-hover:text-white',
        border: 'hover:border-violet-200',
        shadow: 'hover:shadow-violet-500/10',
        tag: 'bg-violet-50 text-violet-600',
        tagHover: 'group-hover:bg-violet-500/20 group-hover:text-violet-100',
        gradient: 'from-violet-500 to-violet-600',
    },
    amber: {
        bg: 'bg-amber-50',
        bgHover: 'group-hover:bg-amber-600',
        text: 'text-amber-600',
        textHover: 'group-hover:text-white',
        border: 'hover:border-amber-200',
        shadow: 'hover:shadow-amber-500/10',
        tag: 'bg-amber-50 text-amber-600',
        tagHover: 'group-hover:bg-amber-500/20 group-hover:text-amber-100',
        gradient: 'from-amber-500 to-amber-600',
    },
};

// Tips carousel
const tips = [
    { text: 'Practice daily for at least 30 minutes to build consistency.', icon: 'clock' },
    { text: 'Focus on your weakest section first to improve overall score.', icon: 'target' },
    { text: 'Review incorrect answers carefully to learn from mistakes.', icon: 'search' },
    { text: 'Simulate real test conditions with timed practice sessions.', icon: 'timer' },
];
const activeTip = ref(0);
let tipInterval = null;

onMounted(() => {
    tipInterval = setInterval(() => {
        activeTip.value = (activeTip.value + 1) % tips.length;
    }, 4000);
});

onUnmounted(() => {
    clearInterval(tipInterval);
});
</script>

<template>
    <Head title="English Therapy - IELTS Practice Center" />

    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-700 to-purple-800 px-4 py-12 sm:px-6 sm:py-16 lg:py-20">
            <!-- Modern mesh background -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="hero-mesh"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent"></div>
            </div>

            <div class="relative z-10 container mx-auto">
                <div class="mx-auto max-w-4xl text-center">
                    <!-- Logo -->
                    <div class="hero-fade mb-6 flex flex-col items-center gap-4 sm:mb-8">
                        <img src="/logo.png" alt="English Therapy" class="h-16 w-16 rounded-2xl object-contain shadow-xl sm:h-20 sm:w-20" />
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-medium text-white backdrop-blur-md sm:px-5 sm:text-sm"
                        >
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                            </span>
                            <span class="hidden sm:inline">English Therapy Practice Center</span>
                            <span class="sm:hidden">Practice Center</span>
                        </div>
                    </div>

                    <!-- Title -->
                    <h1 class="hero-fade hero-delay-1 mb-6 text-4xl font-extrabold tracking-tight text-white sm:mb-8 sm:text-5xl lg:text-6xl">
                        Master IELTS with
                        <span class="mt-2 block bg-gradient-to-r from-emerald-300 via-cyan-300 to-blue-300 bg-clip-text text-transparent">
                            Expert Practice
                        </span>
                    </h1>

                    <!-- Subtitle -->
                    <p class="hero-fade hero-delay-2 mx-auto mb-8 max-w-2xl text-base leading-relaxed text-blue-100 sm:mb-10 sm:text-lg">
                        Master every section with AI-powered practice. Get instant feedback, detailed analytics, and personalized guidance on your
                        path to success.
                    </p>

                    <!-- Quick stats row -->
                    <div class="hero-fade hero-delay-3 flex flex-wrap items-center justify-center gap-4 text-xs sm:gap-6 sm:text-sm">
                        <div class="flex items-center gap-2 text-blue-100/80">
                            <svg class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            <span class="font-medium">4 Modules</span>
                        </div>
                        <div class="h-4 w-px bg-white/30"></div>
                        <div class="flex items-center gap-2 text-blue-100/80">
                            <svg class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="hidden font-medium sm:inline">AI-Powered Scoring</span>
                            <span class="font-medium sm:hidden">AI Scoring</span>
                        </div>
                        <div class="h-4 w-px bg-white/30"></div>
                        <div class="flex items-center gap-2 text-blue-100/80">
                            <svg class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span class="hidden font-medium sm:inline">Real Exam Timing</span>
                            <span class="font-medium sm:hidden">Exam Timing</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Practice Sections -->
        <section class="relative z-10 -mt-8 px-4 py-12 sm:-mt-12 sm:px-6 sm:py-16 lg:py-20">
            <div class="container mx-auto">
                <div class="scroll-reveal mb-10 text-center sm:mb-12">
                    <span
                        class="mb-3 inline-block rounded-full bg-blue-50 px-4 py-1.5 text-xs font-bold tracking-wider text-blue-600 uppercase sm:mb-4"
                    >
                        Practice Modules
                    </span>
                    <h2 class="mb-3 text-3xl font-extrabold text-gray-900 sm:mb-4 sm:text-4xl lg:text-5xl">Choose Your Focus</h2>
                    <p class="text-base text-gray-600 sm:text-lg">Select a section to begin your practice journey</p>
                </div>

                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                    <Link
                        v-for="(section, idx) in sections"
                        :key="section.href"
                        :href="section.href"
                        class="scroll-reveal group relative overflow-hidden rounded-xl border-2 bg-gradient-to-br from-white to-gray-50 p-6 shadow-md transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl sm:rounded-2xl sm:p-7"
                        :class="`border-${section.color}-100 hover:border-${section.color}-300`"
                        :style="{ transitionDelay: `${idx * 80}ms` }"
                    >
                        <div class="relative z-10">
                            <!-- Icon -->
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl shadow-lg transition-transform duration-500 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14 sm:rounded-2xl"
                                :class="`bg-gradient-to-br ${colorMap[section.color].gradient}`"
                            >
                                <svg
                                    class="h-6 w-6 text-white sm:h-7 sm:w-7"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    v-html="section.icon"
                                ></svg>
                            </div>

                            <!-- Title & Subtitle -->
                            <h3 class="mb-1 text-lg font-bold text-gray-900 sm:mb-2 sm:text-xl">
                                {{ section.title }}
                            </h3>
                            <p class="mb-3 text-xs font-medium text-gray-500 sm:mb-4">
                                {{ section.subtitle }}
                            </p>

                            <!-- Description -->
                            <p class="mb-4 text-sm leading-relaxed text-gray-600 sm:mb-5">
                                {{ section.description }}
                            </p>

                            <!-- Tags -->
                            <div class="mb-4 flex flex-wrap gap-2">
                                <span
                                    v-for="tag in section.tags"
                                    :key="tag"
                                    class="rounded-full px-2.5 py-1 text-[10px] font-semibold transition-colors duration-300 sm:text-xs"
                                    :class="[colorMap[section.color].tag]"
                                >
                                    {{ tag }}
                                </span>
                            </div>

                            <!-- CTA -->
                            <span
                                class="inline-flex items-center gap-1.5 text-sm font-semibold transition-all duration-300 group-hover:gap-2"
                                :class="colorMap[section.color].text"
                            >
                                Start Practice
                                <svg
                                    class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Tips Carousel -->
        <section class="bg-gradient-to-b from-white to-gray-50 px-4 py-12 sm:px-6 sm:py-16">
            <div class="container mx-auto">
                <div class="scroll-reveal mx-auto max-w-4xl">
                    <div
                        class="overflow-hidden rounded-2xl border-2 border-indigo-100 bg-gradient-to-br from-indigo-50 via-blue-50 to-purple-50 p-6 shadow-lg sm:rounded-3xl sm:p-8 lg:p-10"
                    >
                        <div class="mb-6 flex items-center gap-3 sm:mb-8 sm:gap-4">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg sm:h-12 sm:w-12"
                            >
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 sm:text-lg">Expert Study Tips</h3>
                        </div>

                        <!-- Tip content with transition -->
                        <div class="relative min-h-[60px] sm:min-h-[48px]">
                            <TransitionGroup
                                enter-active-class="transition-all duration-500 ease-out"
                                enter-from-class="translate-y-4 opacity-0"
                                enter-to-class="translate-y-0 opacity-100"
                                leave-active-class="transition-all duration-300 ease-in absolute inset-x-0"
                                leave-from-class="translate-y-0 opacity-100"
                                leave-to-class="-translate-y-4 opacity-0"
                            >
                                <p
                                    v-for="(tip, i) in tips"
                                    v-show="activeTip === i"
                                    :key="i"
                                    class="text-sm leading-relaxed text-gray-700 sm:text-base"
                                >
                                    {{ tip.text }}
                                </p>
                            </TransitionGroup>
                        </div>

                        <!-- Dots -->
                        <div class="mt-6 flex gap-2 sm:mt-8">
                            <button
                                v-for="(tip, i) in tips"
                                :key="i"
                                @click="activeTip = i"
                                class="h-2 rounded-full transition-all duration-300"
                                :class="activeTip === i ? 'w-8 bg-indigo-600' : 'w-2 bg-indigo-200 hover:bg-indigo-300'"
                                :aria-label="`Tip ${i + 1}`"
                            ></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Practice With Us -->
        <section class="px-4 py-12 sm:px-6 sm:py-16 lg:py-20">
            <div class="container mx-auto">
                <div class="scroll-reveal mb-10 text-center sm:mb-14">
                    <span
                        class="mb-3 inline-block rounded-full bg-emerald-50 px-4 py-1.5 text-xs font-bold tracking-wider text-emerald-600 uppercase sm:mb-4"
                    >
                        Why English Therapy
                    </span>
                    <h2 class="mb-3 text-3xl font-extrabold text-gray-900 sm:mb-4 sm:text-4xl lg:text-5xl">Your Success Partner</h2>
                    <p class="text-base text-gray-600 sm:text-lg">Everything you need to excel in one comprehensive platform</p>
                </div>

                <div class="mx-auto grid max-w-6xl grid-cols-1 gap-4 sm:gap-6 md:grid-cols-3">
                    <!-- Progress -->
                    <div
                        class="scroll-reveal group rounded-xl border-2 border-blue-100 bg-white p-6 text-center shadow-lg transition-all duration-500 hover:-translate-y-2 hover:border-blue-300 hover:shadow-xl sm:rounded-2xl sm:p-8"
                    >
                        <div
                            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14"
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
                        <h3 class="mb-2 text-base font-bold text-gray-900 sm:text-lg">Track Your Progress</h3>
                        <p class="text-sm leading-relaxed text-gray-600">Visualize improvement with detailed analytics and score trends.</p>
                    </div>

                    <!-- Mock Tests -->
                    <div
                        class="scroll-reveal group rounded-xl border-2 border-amber-100 bg-white p-6 text-center shadow-lg transition-all duration-500 hover:-translate-y-2 hover:border-amber-300 hover:shadow-xl sm:rounded-2xl sm:p-8"
                        style="transition-delay: 100ms"
                    >
                        <div
                            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-lg transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14"
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
                        <h3 class="mb-2 text-base font-bold text-gray-900 sm:text-lg">Realistic Mock Tests</h3>
                        <p class="text-sm leading-relaxed text-gray-600">Full-length timed tests in authentic exam conditions.</p>
                    </div>

                    <!-- Feedback -->
                    <div
                        class="scroll-reveal group rounded-xl border-2 border-purple-100 bg-white p-6 text-center shadow-lg transition-all duration-500 hover:-translate-y-2 hover:border-purple-300 hover:shadow-xl sm:rounded-2xl sm:p-8"
                        style="transition-delay: 200ms"
                    >
                        <div
                            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14"
                        >
                            <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-gray-900 sm:text-lg">AI-Expert Feedback</h3>
                        <p class="text-sm leading-relaxed text-gray-600">Detailed AI feedback with actionable improvement tips.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Banner -->
        <section class="px-4 pb-12 sm:px-6 sm:pb-16">
            <div class="container mx-auto">
                <div
                    class="scroll-reveal relative mx-auto max-w-5xl overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 p-8 text-center shadow-2xl sm:rounded-3xl sm:p-12 lg:p-16"
                >
                    <!-- Decorative elements -->
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_50%,rgba(255,255,255,0.08),transparent_50%)]"></div>

                    <div class="relative z-10">
                        <h2 class="mb-4 text-3xl font-extrabold text-white sm:mb-6 sm:text-4xl lg:text-5xl">Ready to Begin?</h2>
                        <p class="mx-auto mb-8 max-w-2xl text-base text-blue-100 sm:mb-10 sm:text-lg">
                            Start your IELTS journey with English Therapy today and achieve your target band score with confidence.
                        </p>
                        <div class="flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                            <Link
                                href="/listening"
                                class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-indigo-700 shadow-xl transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl sm:w-auto sm:px-8 sm:py-4 sm:text-base"
                            >
                                Start Practicing
                                <svg
                                    class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1 sm:h-5 sm:w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <Link
                                href="/"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-white/30 px-6 py-3.5 text-sm font-semibold text-white transition-all duration-300 hover:border-white/50 hover:bg-white/10 sm:w-auto sm:px-8 sm:py-4 sm:text-base"
                            >
                                Back to Home
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <AppFooter />
</template>

<style scoped>
/* Hero mesh background */
.hero-mesh {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(at 20% 30%, rgba(59, 130, 246, 0.3) 0px, transparent 50%),
        radial-gradient(at 80% 20%, rgba(99, 102, 241, 0.3) 0px, transparent 50%),
        radial-gradient(at 60% 80%, rgba(139, 92, 246, 0.25) 0px, transparent 50%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 100%);
    background-size:
        100% 100%,
        100% 100%,
        100% 100%,
        100% 100%;
    animation: mesh-move 20s ease-in-out infinite;
}

@keyframes mesh-move {
    0%,
    100% {
        background-position:
            0% 0%,
            100% 0%,
            50% 100%,
            0% 0%;
    }
    50% {
        background-position:
            100% 100%,
            0% 100%,
            100% 0%,
            100% 100%;
    }
}

/* Hero entrance animations */
.hero-fade {
    animation: hero-enter 0.7s ease-out forwards;
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

@keyframes hero-enter {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Scroll-triggered reveal */
.scroll-reveal {
    opacity: 0;
    transform: translateY(28px);
    transition:
        opacity 0.6s ease-out,
        transform 0.6s ease-out;
}
.scroll-reveal.animate-in {
    opacity: 1;
    transform: translateY(0);
}
</style>
