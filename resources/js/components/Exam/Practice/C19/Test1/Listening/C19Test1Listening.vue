<script setup lang="ts">
import { ref } from 'vue';
import C19Test1ListeningPart1 from './C19Test1ListeningPart1.vue';
import C19Test1ListeningPart2 from './C19Test1ListeningPart2.vue';
import C19Test1ListeningPart3 from './C19Test1ListeningPart3.vue';
import C19Test1ListeningPart4 from './C19Test1ListeningPart4.vue';

const activePart = ref<'part1' | 'part2' | 'part3' | 'part4'>('part1');
</script>

<template>
    <div class="listening-practice-wrapper">
        <!-- Part Navigation -->
        <div class="mb-4 flex gap-2">
            <button
                class="rounded px-4 py-2"
                :class="activePart === 'part1' ? 'bg-purple-600 text-white' : 'bg-gray-200'"
                @click="activePart = 'part1'"
            >
                Part 1
            </button>
            <button
                class="rounded px-4 py-2"
                :class="activePart === 'part2' ? 'bg-purple-600 text-white' : 'bg-gray-200'"
                @click="activePart = 'part2'"
            >
                Part 2
            </button>
            <button
                class="rounded px-4 py-2"
                :class="activePart === 'part3' ? 'bg-purple-600 text-white' : 'bg-gray-200'"
                @click="activePart = 'part3'"
            >
                Part 3
            </button>
            <button
                class="rounded px-4 py-2"
                :class="activePart === 'part4' ? 'bg-purple-600 text-white' : 'bg-gray-200'"
                @click="activePart = 'part4'"
            >
                Part 4
            </button>
        </div>

        <!-- Parts -->
        <C19Test1ListeningPart1 v-show="activePart === 'part1'" :correct-answers="{}" />
        <C19Test1ListeningPart2 v-show="activePart === 'part2'" :correct-answers="{}" />
        <C19Test1ListeningPart3 v-show="activePart === 'part3'" :correct-answers="{}" />
        <C19Test1ListeningPart4 v-show="activePart === 'part4'" :correct-answers="{}" />
    </div>
</template>
