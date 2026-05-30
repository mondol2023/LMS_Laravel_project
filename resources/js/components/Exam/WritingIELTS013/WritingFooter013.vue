<script setup lang="ts">
const props = defineProps<{
    activePart: 'part1' | 'part2';
}>();

const emit = defineEmits<{
    (e: 'navigate', part: 'part1' | 'part2'): void;
}>();
</script>

<template>
    <nav class="fixed right-0 bottom-0 left-0 z-50 border-t border-gray-200 bg-white/95 shadow-lg backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-center gap-6 py-4">
                <!-- Part 1 Button -->
                <button
                    @click="emit('navigate', 'part1')"
                    :class="['task-button', props.activePart === 'part1' ? 'task-button-active task-1-active' : 'task-button-inactive']"
                >
                    Part 1
                </button>
                <!-- Part 2 Button -->
                <button
                    @click="emit('navigate', 'part2')"
                    :class="['task-button', props.activePart === 'part2' ? 'task-button-active task-2-active' : 'task-button-inactive']"
                >
                    Part 2
                </button>
            </div>
        </div>
    </nav>
</template>

<style scoped>
/* Task Navigation Buttons */
.task-button {
    padding: 1rem 1.5rem;
    border-radius: 16px;
    background: white;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    width: 100%;
    cursor: pointer;
}

.task-button-active {
    border-color: transparent;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.task-button-inactive {
    border-color: #e5e7eb;
    background: #f9fafb;
}

.task-button-inactive:hover {
    border-color: #d1d5db;
    background: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.task-1-active {
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
}

.task-2-active {
    background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
    color: white;
}

.task-content {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    text-align: left;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.task-title {
    font-size: 1rem;
    font-weight: 700;
}

.task-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.task-type {
    font-size: 0.75rem;
    opacity: 0.9;
    font-weight: 500;
}

.requirement {
    font-size: 0.75rem;
    font-weight: 600;
    opacity: 0.8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .task-button {
        min-width: 180px;
        padding: 0.875rem 1.25rem;
    }

    .task-title {
        font-size: 0.875rem;
    }

    .task-type,
    .requirement {
        font-size: 0.625rem;
    }
}

@media (max-width: 640px) {
    .task-button {
        min-width: 150px;
        padding: 0.75rem 1rem;
    }

    .flex {
        gap: 1rem;
    }
}
</style>
