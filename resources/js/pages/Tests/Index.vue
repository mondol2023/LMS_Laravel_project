<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { LogOut, Settings, User } from 'lucide-vue-next';

interface Test {
    id: number;
    title: string;
    type: string;
    difficulty: string;
    attempts_count: number;
    average_score: number;
    completed_attempts: number;
}

interface Props {
    tests: Test[];
    auth: {
        user: {
            name: string;
            email: string;
        };
    };
}

const props = defineProps<Props>();

const getDifficultyColor = (difficulty: string) => {
    switch (difficulty) {
        case 'easy':
            return 'text-green-600 bg-green-50 border-green-200';
        case 'medium':
            return 'text-yellow-600 bg-yellow-50 border-yellow-200';
        case 'hard':
            return 'text-red-600 bg-red-50 border-red-200';
        default:
            return 'text-gray-600 bg-gray-50 border-gray-200';
    }
};

const getTypeColor = (type: string) => {
    return type === 'academic' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-green-100 text-green-800 border-green-200';
};
</script>

<template>
    <Head title="IELTS Mock Tests" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur-sm">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Link href="/" class="flex items-center gap-2">
                            <div class="text-2xl">📝</div>
                            <h1 class="text-xl font-bold text-gray-900">IELTS Mock Test</h1>
                        </Link>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- User Profile Dropdown -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2">
                                <User class="h-4 w-4 text-gray-600" />
                                <span class="text-sm font-medium text-gray-900">{{ auth.user.name }}</span>
                            </div>

                            <Link href="/settings/profile">
                                <Button variant="ghost" size="sm">
                                    <Settings class="h-4 w-4" />
                                </Button>
                            </Link>

                            <Link href="/logout" method="post">
                                <Button variant="ghost" size="sm" class="text-red-600 hover:text-red-700">
                                    <LogOut class="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <div class="mb-8">
                <h1 class="mb-4 text-4xl font-bold text-gray-900">IELTS Mock Tests</h1>
                <p class="text-xl text-gray-600">Practice with authentic IELTS test formats and improve your band score</p>
            </div>

            <!-- Test Cards Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Link
                    v-for="test in tests"
                    :key="test.id"
                    :href="`/tests/${test.id}`"
                    class="block overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                >
                    <!-- Test Image -->
                    <div class="flex h-48 items-center justify-center bg-gradient-to-br from-blue-400 to-indigo-600">
                        <div class="text-6xl text-white opacity-90">📝</div>
                    </div>

                    <!-- Test Content -->
                    <div class="p-6">
                        <h3 class="mb-2 text-center text-xl font-bold text-gray-900">{{ test.title }}</h3>
                        <div class="flex justify-center">
                            <span :class="['rounded-full border px-3 py-1 text-xs font-medium', getTypeColor(test.type)]">
                                {{ test.type.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-if="tests.length === 0" class="py-16 text-center">
                <div class="mb-4 text-6xl">📚</div>
                <h3 class="mb-2 text-2xl font-bold text-gray-900">No tests available</h3>
                <p class="text-gray-600">Check back later for new practice tests</p>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-16 border-t border-gray-200 bg-white py-12">
            <div class="container mx-auto px-4">
                <div class="grid gap-8 md:grid-cols-4">
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <div class="text-2xl">📝</div>
                            <h3 class="text-lg font-bold text-gray-900">IELTS Mock Test</h3>
                        </div>
                        <p class="text-gray-600">Professional IELTS practice platform with realistic test conditions and instant feedback.</p>
                    </div>

                    <div>
                        <h4 class="mb-4 font-semibold text-gray-900">Practice Tests</h4>
                        <ul class="space-y-2 text-gray-600">
                            <li><Link href="/tests" class="transition-colors hover:text-blue-600">All Tests</Link></li>
                            <li><Link href="/leaderboard" class="transition-colors hover:text-blue-600">Leaderboard</Link></li>
                            <li><Link href="/dashboard" class="transition-colors hover:text-blue-600">Dashboard</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 font-semibold text-gray-900">Account</h4>
                        <ul class="space-y-2 text-gray-600">
                            <li><Link href="/settings/profile" class="transition-colors hover:text-blue-600">Profile</Link></li>
                            <li><Link href="/settings/password" class="transition-colors hover:text-blue-600">Settings</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 font-semibold text-gray-900">Support</h4>
                        <ul class="space-y-2 text-gray-600">
                            <li><a href="#" class="transition-colors hover:text-blue-600">Help Center</a></li>
                            <li><a href="#" class="transition-colors hover:text-blue-600">Contact Us</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 border-t border-gray-200 pt-8 text-center text-gray-500">
                    <p>&copy; 2024 IELTS Mock Test. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
