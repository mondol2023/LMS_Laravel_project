<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
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
    is_current_user: boolean;
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
    userRank: number | null;
    userBestScore: number | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leaderboard', href: '/leaderboard' },
];

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
    if (band >= 8) return 'text-green-600 font-bold';
    if (band >= 6.5) return 'text-blue-600 font-bold';
    if (band >= 5.5) return 'text-yellow-600 font-bold';
    return 'text-red-600 font-bold';
};
</script>

<template>
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Leaderboard</h1>
                    <p class="text-muted-foreground">See how you rank against other test takers</p>
                </div>
            </div>

            <!-- User Stats -->
            <div v-if="userRank || userBestScore" class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Your Best Rank</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">
                            {{ userRank ? `#${userRank}` : 'Not ranked yet' }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Your Best Score</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div :class="['text-3xl font-bold', getBandColor(userBestScore || 0)]">
                            {{ userBestScore?.toFixed(1) || 'No scores yet' }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
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
                    <CardTitle>Rankings</CardTitle>
                    <CardDescription> {{ leaderboard.meta.total }} total entries </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="entry in leaderboard.data"
                            :key="`${entry.rank}-${entry.user_name}`"
                            :class="[
                                'flex items-center justify-between rounded-lg border p-4',
                                entry.is_current_user ? 'border-primary bg-primary/5' : '',
                            ]"
                        >
                            <div class="flex items-center gap-4">
                                <Badge :variant="getRankBadge(entry.rank).variant" :class="getRankBadge(entry.rank).class">
                                    {{ getRankBadge(entry.rank).text }}
                                </Badge>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">
                                            {{ entry.user_name }}
                                            <span v-if="entry.is_current_user" class="text-primary">(You)</span>
                                        </p>
                                        <Badge variant="outline" class="text-xs">
                                            {{ entry.user_country }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ entry.test_title }} ({{ entry.test_type.replace('_', ' ') }})</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ new Date(entry.created_at).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-right">
                                <div :class="['text-2xl font-bold', getBandColor(entry.overall_band)]">
                                    {{ entry.overall_band.toFixed(1) }}
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ entry.completion_time }}
                                </p>
                            </div>
                        </div>

                        <div v-if="leaderboard.data.length === 0" class="py-8 text-center">
                            <h3 class="text-lg font-medium text-muted-foreground">No entries found</h3>
                            <p class="text-muted-foreground">Try adjusting your filters or take a test to get on the leaderboard!</p>
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
        </div>
    </AppLayout>
</template>
