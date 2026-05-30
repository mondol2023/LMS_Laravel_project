<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    activePart: 'part1' | 'part2';
}

const props = defineProps<Props>();

const emit = defineEmits<{
    navigate: [part: 'part1' | 'part2'];
    exit: [];
}>();

// Real-time clock
const currentTime = ref('');
const updateTime = () => {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    currentTime.value = `${hours}:${minutes}`;
};

// Battery status
const batteryLevel = ref(100);
const isCharging = ref(false);
const updateBattery = async () => {
    try {
        if ('getBattery' in navigator) {
            const battery = await (navigator as any).getBattery();
            batteryLevel.value = Math.round(battery.level * 100);
            isCharging.value = battery.charging;

            battery.addEventListener('levelchange', () => {
                batteryLevel.value = Math.round(battery.level * 100);
            });
            battery.addEventListener('chargingchange', () => {
                isCharging.value = battery.charging;
            });
        }
    } catch (e) {
        batteryLevel.value = 85;
    }
};

let timeInterval: number | null = null;

onMounted(() => {
    updateTime();
    timeInterval = window.setInterval(updateTime, 10000);
    updateBattery();
});

onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});

const handleNavigate = (part: 'part1' | 'part2') => {
    emit('navigate', part);
};

const handleExit = () => {
    emit('exit');
};

// Navigation to previous/next part
const canGoPrevious = computed(() => props.activePart !== 'part1');
const canGoNext = computed(() => props.activePart !== 'part2');

const goToPreviousPart = () => {
    if (props.activePart === 'part2') {
        emit('navigate', 'part1');
    }
};

const goToNextPart = () => {
    if (props.activePart === 'part1') {
        emit('navigate', 'part2');
    }
};
</script>

<template>
    <!-- Previous / Next Part Navigation -->
    <div class="fixed right-10 bottom-40 z-40 flex gap-1">
        <button
            @click="goToPreviousPart"
            :disabled="!canGoPrevious"
            class="flex h-10 w-11 items-center justify-center rounded transition-all duration-200"
            :class="
                canGoPrevious
                    ? 'bg-gray-600 font-extrabold text-white hover:bg-gray-700'
                    : 'cursor-not-allowed bg-gray-400 font-extrabold text-gray-200'
            "
            :title="canGoPrevious ? 'Previous Part' : ''"
        >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
            </svg>
        </button>
        <button
            @click="goToNextPart"
            :disabled="!canGoNext"
            class="flex h-10 w-11 items-center justify-center rounded transition-all duration-200"
            :class="canGoNext ? 'bg-gray-800 text-white hover:bg-gray-900' : 'cursor-not-allowed bg-gray-400 text-gray-200'"
            :title="canGoNext ? 'Next Part' : ''"
        >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" />
            </svg>
        </button>
    </div>

    <div class="fixed right-0 bottom-0 left-0 z-30 mx-5">
        <!-- Top Section: Part Tracker -->
        <div class="bg-white">
            <div class="flex">
                <!-- Task 1 -->
                <button
                    @click="handleNavigate('part1')"
                    class="w-1/2 py-2.5 text-base transition-colors"
                    :class="[
                        activePart === 'part1'
                            ? 'border-t border-black font-semibold text-black'
                            : 'text-gray-500 hover:text-black'
                    ]"
                >
                    Task 1
                </button>

                <!-- Task 2 -->
                <button
                    @click="handleNavigate('part2')"
                    class="w-1/2 py-2.5 text-base transition-colors"
                    :class="[
                        activePart === 'part2'
                            ? 'border-t border-black font-semibold text-black'
                            : 'text-gray-500 hover:text-black'
                    ]"
                >
                    Task 2
                </button>
            </div>
        </div>

        <!-- Bottom Section: Brand Bar (Gray) -->
        <div class="bg-gray-200 px-4 py-3 text-gray-800">
            <div class="flex items-center justify-between">
                <!-- Brand Name: English (black) Therapy (red) -->
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold">
                        <span class="text-black">English</span>
                        <span class="text-red-500"> Therapy</span>
                    </span>
                </div>

                <!-- Right Section: Time, Battery, Exit -->
                <div class="flex items-center gap-5">
                    <!-- Real Time Clock -->
                    <span class="text-2xl font-bold text-gray-800 tabular-nums">{{ currentTime }}</span>

                    <!-- Battery Icon -->
                    <div class="flex items-center gap-1" :title="`Battery: ${batteryLevel}%${isCharging ? ' (Charging)' : ''}`">
                        <div class="relative">
                            <svg class="h-7 w-7 text-gray-700" fill="currentColor" viewBox="0 0 24 24">
                                <rect x="2" y="7" width="18" height="10" rx="1" stroke="currentColor" stroke-width="1.5" fill="none" />
                                <rect x="20" y="10" width="2" height="4" rx="0.5" fill="currentColor" />
                                <rect
                                    x="3"
                                    y="8"
                                    :width="Math.max(1, (batteryLevel / 100) * 16)"
                                    height="8"
                                    :fill="isCharging ? '#22c55e' : batteryLevel > 20 ? 'currentColor' : '#ef4444'"
                                />
                            </svg>
                            <!-- Charging Lightning Bolt Overlay -->
                            <svg v-if="isCharging" class="absolute inset-0 h-7 w-7" viewBox="0 0 24 24">
                                <path d="M12 6L8 12h3l-1 6 5-7h-3l1-5z" fill="#ffffff" stroke="#22c55e" stroke-width="0.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- Exit Button -->
                    <button
                        @click="handleExit"
                        class="rounded border border-gray-300 bg-white px-5 py-2 text-base font-medium text-gray-800 transition-colors hover:bg-gray-100"
                    >
                        Exit
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
