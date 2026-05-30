<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface LeaderboardEntry {
    rank: number;
    user_name: string;
    user_country: string;
    test_title: string;
    test_type: string;
    overall_band: number;
    completion_time: string;
    created_at: string;
}

interface Test {
    id: number;
    title: string;
    type: string;
}

interface Props {
    leaderboard: {
        data: LeaderboardEntry[];
        links: any[];
        meta: any;
    };
    tests: Test[];
    filters: {
        test_id: number | null;
        type: string;
    };
}

const props = defineProps<Props>();

const selectedTest = ref(props.filters.test_id?.toString() || 'all');
const selectedType = ref(props.filters.type);

const applyFilters = () => {
    const params = new URLSearchParams();

    if (selectedTest.value !== 'all') {
        params.set('test_id', selectedTest.value);
    }

    if (selectedType.value !== 'overall') {
        params.set('type', selectedType.value);
    }

    router.get('/leaderboard?' + params.toString());
};

const getRankBadge = (rank: number) => {
    if (rank === 1) return { variant: 'default', text: '🥇 1st', class: 'bg-yellow-500 text-white' };
    if (rank === 2) return { variant: 'secondary', text: '🥈 2nd', class: 'bg-gray-400 text-white' };
    if (rank === 3) return { variant: 'outline', text: '🥉 3rd', class: 'bg-amber-600 text-white' };
    return { variant: 'outline', text: `#${rank}`, class: '' };
};

const getBandColor = (band: number) => {
    if (!band || band === 0) return 'text-gray-500 font-bold';
    if (band >= 8) return 'text-green-600 font-bold';
    if (band >= 7) return 'text-blue-600 font-bold';
    if (band >= 6) return 'text-yellow-600 font-bold';
    if (band >= 5) return 'text-orange-600 font-bold';
    return 'text-red-600 font-bold';
};

const formatBandScore = (band: number) => {
    if (!band || band === 0) return 'N/A';
    return Number(band).toFixed(1);
};

const formatCompletionTime = (timeString: string) => {
    if (!timeString) return 'N/A';
    // If it's already formatted, return as is
    if (timeString.includes('min') || timeString.includes('sec') || timeString.includes('hr')) {
        return timeString;
    }
    // If it's a number (seconds), format it
    const time = parseInt(timeString);
    if (isNaN(time)) return timeString;

    const hours = Math.floor(time / 3600);
    const minutes = Math.floor((time % 3600) / 60);
    const seconds = time % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    if (minutes > 0) {
        return `${minutes}m ${seconds}s`;
    }
    return `${seconds}s`;
};
</script>

<template>
    <Head title="IELTS Leaderboard - Top Performers" />

    <div class="min-h-screen bg-background">
        <!-- Navigation -->
        <nav class="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="text-2xl">📝</div>
                        <h1 class="text-xl font-bold">IELTS Mock Test</h1>
                    </Link>

                    <div class="flex items-center gap-4">
                        <Link href="/">
                            <Button variant="ghost">Home</Button>
                        </Link>
                        <Link href="/tests">
                            <Button variant="ghost">Browse Tests</Button>
                        </Link>
                        <Link href="/login">
                            <Button variant="ghost">Log In</Button>
                        </Link>
                        <Link href="/register">
                            <Button>Get Started</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Header -->
        <section class="bg-muted/30 py-12">
            <div class="container mx-auto px-4 text-center">
                <h1 class="mb-4 text-4xl font-bold">Global Leaderboard</h1>
                <p class="mb-6 text-xl text-muted-foreground">See how top performers scored on IELTS practice tests</p>
                <div class="flex justify-center gap-4">
                    <Link href="/register">
                        <Button size="lg"> Join the Competition </Button>
                    </Link>
                    <Link href="/tests">
                        <Button variant="outline" size="lg"> Browse Tests </Button>
                    </Link>
                </div>
            </div>
        </section>

        <div class="container mx-auto px-4 py-8">
            <!-- Filters -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription>Filter the leaderboard by test or type</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <label class="text-sm font-medium">Test</label>
                            <Select v-model="selectedTest">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select test" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Tests</SelectItem>
                                    <SelectItem v-for="test in tests" :key="test.id" :value="test.id.toString()">
                                        {{ test.title }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex-1">
                            <label class="text-sm font-medium">Type</label>
                            <Select v-model="selectedType">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="overall">All Types</SelectItem>
                                    <SelectItem value="academic">Academic</SelectItem>
                                    <SelectItem value="general_training">General Training</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Button @click="applyFilters">Apply Filters</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Leaderboard -->
            <Card>
                <CardHeader>
                    <CardTitle>Top Performers</CardTitle>
                    <CardDescription> {{ leaderboard.meta.total }} total entries </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="entry in leaderboard.data"
                            :key="`${entry.rank}-${entry.user_name}`"
                            class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-center gap-4">
                                <Badge :variant="getRankBadge(entry.rank).variant" :class="getRankBadge(entry.rank).class">
                                    {{ getRankBadge(entry.rank).text }}
                                </Badge>

                                <div>
                                    <div class="mb-1 flex items-center gap-2">
                                        <p class="font-medium">{{ entry.user_name }}</p>
                                        <Badge variant="outline" class="text-xs">
                                            {{ entry.user_country || 'Global' }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm font-medium text-muted-foreground">
                                        {{ entry.test_title }}
                                    </p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <Badge :variant="entry.test_type === 'academic' ? 'default' : 'secondary'" class="text-xs">
                                            {{ entry.test_type.replace('_', ' ').toUpperCase() }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            {{ new Date(entry.created_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <div :class="['text-2xl font-bold', getBandColor(entry.overall_band)]">
                                    {{ formatBandScore(entry.overall_band) }}
                                </div>
                                <p class="text-xs text-muted-foreground">Band Score</p>
                                <p class="mt-1 text-xs text-muted-foreground">⏱️ {{ formatCompletionTime(entry.completion_time) }}</p>
                            </div>
                        </div>

                        <div v-if="leaderboard.data.length === 0" class="py-8 text-center">
                            <h3 class="text-lg font-medium text-muted-foreground">No entries found</h3>
                            <p class="text-muted-foreground">Try adjusting your filters or check back later!</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="leaderboard.links.length > 3" class="mt-6 flex justify-center gap-2">
                        <Button
                            v-for="link in leaderboard.links"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url)"
                            size="sm"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- CTA Section -->
            <div class="mt-12 rounded-lg bg-muted/50 p-8 text-center">
                <h3 class="mb-4 text-2xl font-bold">Want to see your name here?</h3>
                <p class="mb-6 text-muted-foreground">Register for free and start taking IELTS practice tests to compete with students worldwide</p>
                <div class="flex justify-center gap-4">
                    <Link href="/register">
                        <Button size="lg"> Register Free Account </Button>
                    </Link>
                    <Link href="/tests">
                        <Button variant="outline" size="lg"> View Practice Tests </Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
