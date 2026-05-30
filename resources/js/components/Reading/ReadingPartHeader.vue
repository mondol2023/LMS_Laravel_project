<script setup lang="ts">
import { BookOpen, FileText, Maximize2, Minimize2, Send, X } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface Note {
    id: number;
    text: string;
    selectedText: string;
    note: string;
    part?: string;
}

interface Props {
    testNumber: number;
    partNumber: number;
    title?: string;
    subtitle?: string;
    notes?: Note[];
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Cambridge 20 Reading',
    subtitle: '',
    notes: () => [],
});

const emit = defineEmits<{
    submit: [];
    toggleNotes: [];
    focusNote: [note: Note];
    deleteNote: [noteId: number];
}>();

// Fullscreen state
const isFullscreen = ref(false);

// Notes drawer state
const showNotesDrawer = ref(false);

// Fullscreen functionality
const toggleFullscreen = () => {
    if (!isFullscreen.value) {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        } else if ((document.documentElement as any).webkitRequestFullscreen) {
            (document.documentElement as any).webkitRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if ((document as any).webkitExitFullscreen) {
            (document as any).webkitExitFullscreen();
        }
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!(document.fullscreenElement || (document as any).webkitFullscreenElement);
};

// Notes functionality
const toggleNotesDrawer = () => {
    showNotesDrawer.value = !showNotesDrawer.value;
    emit('toggleNotes');
};

const closeNotesDrawer = () => {
    showNotesDrawer.value = false;
};

const focusNote = (note: Note) => {
    emit('focusNote', note);
    closeNotesDrawer();
};

const deleteNote = (noteId: number) => {
    emit('deleteNote', noteId);
};

const handleSubmit = () => {
    emit('submit');
};

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
});
</script>

<template>
    <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-3">
            <!-- Mobile Layout -->
            <div class="block md:hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-md"
                            >
                                <BookOpen class="h-5 w-5 text-white" />
                            </div>
                        </div>
                        <div>
                            <h1 class="text-sm font-bold text-gray-900">{{ title }}</h1>
                            <p class="text-xs text-gray-500">Test {{ testNumber }} - Part {{ partNumber }}{{ subtitle ? ` · ${subtitle}` : '' }}</p>
                        </div>
                    </div>
                    <button
                        @click="handleSubmit"
                        class="flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-rose-500 to-red-500 px-3 py-1.5 text-xs font-semibold text-white shadow-md"
                    >
                        <Send class="h-3.5 w-3.5" />
                        Submit
                    </button>
                </div>

                <!-- Actions - Mobile -->
                <div class="mt-3 flex items-center justify-end gap-2">
                    <button
                        @click="toggleNotesDrawer"
                        class="relative flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700"
                    >
                        <FileText class="h-4 w-4" />
                        Notes
                        <span
                            v-if="notes.length > 0"
                            class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-bold text-white"
                        >
                            {{ notes.length }}
                        </span>
                    </button>
                    <button
                        @click="toggleFullscreen"
                        class="flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700"
                    >
                        <Maximize2 v-if="!isFullscreen" class="h-4 w-4" />
                        <Minimize2 v-else class="h-4 w-4" />
                        {{ isFullscreen ? 'Exit' : 'Fullscreen' }}
                    </button>
                </div>
            </div>

            <!-- Desktop Layout -->
            <div class="hidden items-center justify-between gap-6 md:flex">
                <!-- Left: Title -->
                <div class="flex min-w-fit items-center gap-3">
                    <div class="relative flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg">
                            <BookOpen class="h-6 w-6 text-white" />
                        </div>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-gray-900">{{ title }}</h1>
                        <p class="text-sm text-gray-500">Test {{ testNumber }} - Part {{ partNumber }}{{ subtitle ? ` · ${subtitle}` : '' }}</p>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="flex min-w-fit items-center gap-2">
                    <button
                        @click="toggleNotesDrawer"
                        class="relative flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <FileText class="h-4 w-4" />
                        <span>Notes</span>
                        <span
                            v-if="notes.length > 0"
                            class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-bold text-white"
                        >
                            {{ notes.length }}
                        </span>
                    </button>

                    <button
                        @click="toggleFullscreen"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <Maximize2 v-if="!isFullscreen" class="h-4 w-4" />
                        <Minimize2 v-else class="h-4 w-4" />
                        <span>Fullscreen</span>
                    </button>

                    <button
                        @click="handleSubmit"
                        class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-rose-500 to-red-500 px-5 py-2 text-sm font-semibold text-white shadow-md hover:from-rose-600 hover:to-red-600"
                    >
                        <Send class="h-4 w-4" />
                        <span>Submit</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notes Drawer -->
    <Teleport to="body">
        <Transition name="slide">
            <div v-if="showNotesDrawer" class="fixed top-0 right-0 z-[9999] flex h-full w-full flex-col bg-white shadow-2xl sm:w-96">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20">
                                <FileText class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">My Notes</h3>
                                <p class="text-sm text-white/80">{{ notes.length }} note{{ notes.length !== 1 ? 's' : '' }}</p>
                            </div>
                        </div>
                        <button
                            @click="closeNotesDrawer"
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-white hover:bg-white/30"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div v-if="notes.length === 0" class="flex h-full flex-col items-center justify-center py-12 text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <FileText class="h-8 w-8 text-gray-400" />
                        </div>
                        <h4 class="mb-2 text-lg font-semibold text-gray-900">No notes yet</h4>
                        <p class="max-w-xs text-sm text-gray-600">Select text in the passage and click "Note" to add notes.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="note in notes"
                            :key="note.id"
                            class="group rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-emerald-300 hover:shadow-md"
                        >
                            <div class="mb-2 flex items-start justify-between">
                                <span
                                    v-if="note.part"
                                    class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800"
                                >
                                    {{ note.part }}
                                </span>
                                <button
                                    @click.stop="deleteNote(note.id)"
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-red-50 text-red-600 opacity-0 group-hover:opacity-100 hover:bg-red-100"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <div
                                @click="focusNote(note)"
                                class="mb-2 cursor-pointer rounded border border-yellow-200 bg-yellow-50 px-3 py-2 hover:bg-yellow-100"
                            >
                                <p class="text-sm font-medium text-gray-900 italic">"{{ note.selectedText }}"</p>
                            </div>
                            <div
                                @click="focusNote(note)"
                                class="cursor-pointer rounded border border-emerald-200 bg-emerald-50 px-3 py-2 hover:bg-emerald-100"
                            >
                                <p class="text-sm text-gray-700">{{ note.note }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
