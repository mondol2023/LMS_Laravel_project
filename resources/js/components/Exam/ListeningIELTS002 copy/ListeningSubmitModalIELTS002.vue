<script setup lang="ts">
interface Props {
    isVisible: boolean;
    answeredCount: number;
    totalQuestions: number;
    isTimeUp?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    totalQuestions: 40,
    isTimeUp: false,
});

const emit = defineEmits<{
    close: [];
    submit: [];
}>();

const unansweredCount = () => props.totalQuestions - props.answeredCount;

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    emit('submit');
};

const handleOverlayClick = (event: MouseEvent) => {
    if (props.isTimeUp) return;
    if (event.target === event.currentTarget) {
        handleClose();
    }
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isVisible" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4" @click="handleOverlayClick">
            <div class="w-full max-w-sm border shadow rounded border-black bg-white p-6 text-center">
                <!-- Icon -->
                <div class="mb-4 flex justify-center">
                    <div class="flex h-12 w-12 items-center rounded justify-center bg-black">
                        <svg v-if="isTimeUp" class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <svg v-else class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="mb-2 text-lg font-bold text-black">
                    {{ isTimeUp ? 'Time is Up!' : 'Submit Test?' }}
                </h2>

                <!-- Stats -->
                <p class="mb-4 text-sm text-gray-600">
                    <span class="font-bold text-black">{{ answeredCount }}</span
                    >/{{ totalQuestions }} answered
                    <span v-if="unansweredCount() > 0" class="text-gray-500"> ({{ unansweredCount() }} left) </span>
                </p>

                <!-- Buttons -->
                <div class="flex gap-2">
                    <button
                        v-if="!isTimeUp"
                        @click="handleClose"
                        class="flex-1 border border-black py-2 text-sm font-semibold text-black transition-colors hover:bg-gray-100"
                    >
                        Go Back
                    </button>
                    <button
                        @click="handleSubmit"
                        :class="[
                            'bg-black py-2 text-sm font-semibold text-white transition-colors hover:bg-gray-800',
                            isTimeUp ? 'w-full' : 'flex-1',
                        ]"
                    >
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.fixed {
    animation: fadeIn 0.15s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>
