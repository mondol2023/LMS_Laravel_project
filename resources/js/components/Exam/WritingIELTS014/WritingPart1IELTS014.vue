<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
    phone?: string;
    examId?: string;
}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

const emit = defineEmits<{
    wordCountChange: [count: number];
    notesChange: [
        notes: Array<{
            id: number;
            text: string;
            selectedText: string;
            note: string;
            start: number;
            end: number;
        }>,
    ];
}>();

const answer = ref('');
const wordCount = ref(0);
const questionTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const questionText = ref(`
Plan A below shows a health centre in 2005.
Plan B shows the same place in the present day.

Summarise the information by selecting and reporting the main features, and make comparisons where relevant.

Write at least 150 words`);

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<
    Array<{
        id: number;
        text: string;
        selectedText: string;
        note: string;
        start: number;
        end: number;
    }>
>([]);

// Expose methods for parent component
const getAnswers = () => {
    return {
        part1: answer.value || null,
    };
};

// Focus on a specific note's text
const focusNote = (noteData: { selectedText: string; start: number; end: number }) => {
    // First try to find existing highlight mark
    const marks = document.querySelectorAll('mark[data-highlight-id]');
    let foundMark: Element | null = null;

    for (const mark of marks) {
        if (mark.textContent?.trim() === noteData.selectedText.trim()) {
            foundMark = mark;
            break;
        }
    }

    if (foundMark) {
        foundMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
        foundMark.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2', 'transition-all');
        setTimeout(() => {
            foundMark?.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2');
        }, 2500);
        return;
    }

    // Search in questionTextRef
    if (!questionTextRef.value) return;

    const contentAreas = questionTextRef.value.querySelectorAll('.select-text, [class*="text-"]');
    const searchText = noteData.selectedText.trim();

    // Also search directly in questionTextRef
    const walker = document.createTreeWalker(questionTextRef.value, NodeFilter.SHOW_TEXT, null);
    let node;

    while ((node = walker.nextNode())) {
        const text = node.textContent || '';
        const index = text.indexOf(searchText);

        if (index !== -1 && node.parentElement) {
            const range = document.createRange();
            range.setStart(node, index);
            range.setEnd(node, Math.min(index + searchText.length, text.length));

            const span = document.createElement('span');
            span.className = 'bg-blue-200 ring-4 ring-blue-400 ring-offset-2 transition-all';

            try {
                range.surroundContents(span);
                span.scrollIntoView({ behavior: 'smooth', block: 'center' });

                setTimeout(() => {
                    const parent = span.parentNode;
                    if (parent) {
                        parent.replaceChild(document.createTextNode(searchText), span);
                    }
                }, 2500);
                return;
            } catch (e) {
                console.warn('Could not surround contents:', e);
            }
        }
    }
};

defineExpose({
    getAnswers,
    deleteNote: (id: number) => {
        notes.value = notes.value.filter((note) => note.id !== id);
    },
    focusNote,
    notes,
});

const calculateWordCount = (text: string): number => {
    if (!text.trim()) return 0;
    return text.trim().split(/\s+/).length;
};

// Computed property to render highlighted text
const highlightedQuestionText = computed(() => {
    return applyHighlightsToText(questionText.value);
});

watch(answer, (newValue) => {
    const count = calculateWordCount(newValue);
    wordCount.value = count;
    emit('wordCountChange', count);

    // Auto-save to draft
    if (autoSaver.value) {
        autoSaver.value.save('part1', newValue);
    }
});

// Initialize highlight functionality
const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, applyHighlightsToText } = useHighlight();

// Initialize a draft system and load saved answers
onMounted(async () => {
    const userPhone = props.phone || draftService.getUserPhone();
    const examIdValue = props.examId || '1234567';

    // Initialize auto-saver
    autoSaver.value = draftService.createAutoSaver(userPhone, examIdValue, 'writing', 'part1');

    // Load saved drafts
    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part1) {
            const savedAnswer = draftsResponse.data.part1.part1;
            if (savedAnswer) {
                answer.value = savedAnswer;
                console.log('Loaded Writing Part 1 draft:', savedAnswer.substring(0, 50) + '...');
            }
        }
    } catch (error) {
        console.error('Failed to load Writing Part 1 draft:', error);
    }

    // Emit initial word count
    const count = calculateWordCount(answer.value);
    wordCount.value = count;
    emit('wordCountChange', count);

    // Add event listeners for context menu
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

const handleQuestionTextSelect = (event: MouseEvent) => {
    // Small delay to ensure selection is complete
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        // Get selection range and position
        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && questionTextRef.value) {
            // Calculate viewport dimensions
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const menuWidth = 300;
            const menuHeight = 60;
            const padding = 10;
            const bottomReserve = 120;

            // Calculate initial position
            let menuX = rect.left + rect.width / 2;
            let menuY = rect.bottom + window.scrollY + 10;

            // Ensure menu stays within horizontal bounds
            const halfMenuWidth = menuWidth / 2;
            if (menuX - halfMenuWidth < padding) {
                menuX = halfMenuWidth + padding;
            } else if (menuX + halfMenuWidth > viewportWidth - padding) {
                menuX = viewportWidth - halfMenuWidth - padding;
            }

            // Check vertical positioning
            if (rect.bottom + menuHeight > viewportHeight - bottomReserve) {
                menuY = rect.top + window.scrollY - menuHeight - 10;
                if (rect.top - menuHeight < padding) {
                    menuY = padding + window.scrollY;
                }
            }

            contextMenuPosition.value = { x: menuX, y: menuY };
            showContextMenu.value = true;

            // Store selection info for later use
            if (selection) {
                const preSelectionRange = range!.cloneRange();
                const container = questionTextRef.value;
                preSelectionRange.selectNodeContents(container);
                preSelectionRange.setEnd(range!.startContainer, range!.startOffset);

                // Get the plain text offset (ignoring HTML tags)
                const plainTextBefore = preSelectionRange.toString();
                const startOffset = plainTextBefore.length;
                const endOffset = startOffset + selected.length;

                selectedText.value = selected;
                selectionRange.value = { start: startOffset, end: endOffset };
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);

    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;

    // Position note input below the context menu
    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    // Focus the input after a small delay
    setTimeout(() => {
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: selectionRange.value.start,
        end: selectionRange.value.end,
    });

    // Clear and close
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => {
    noteInputText.value = '';
    showNoteInput.value = false;
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

const closeContextMenu = () => {
    showContextMenu.value = false;
};

// Watch notes and emit changes
watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

const handleDeleteHighlight = (id: number) => {
    deleteHighlight(id);
};

// Close context menu when clicking outside or pressing Escape
const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) {
        showContextMenu.value = false;
    }
};

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
});

// Resizable pane functionality
const leftPaneWidth = ref(50); // percentage
const isResizing = ref(false);

const startResize = () => {
    isResizing.value = true;
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
};

const handleMouseMove = (event: MouseEvent) => {
    if (!isResizing.value) return;

    const container = document.querySelector('.resizable-container') as HTMLElement;
    if (!container) return;

    const containerRect = container.getBoundingClientRect();
    const newWidth = ((event.clientX - containerRect.left) / containerRect.width) * 100;

    // Constrain between 20% and 80%
    if (newWidth >= 20 && newWidth <= 80) {
        leftPaneWidth.value = newWidth;
    }
};

const handleMouseUp = () => {
    isResizing.value = false;
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
};
</script>

<template>
    <div class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <!-- Mobile: Stack vertically -->
            <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:hidden">
                <!-- Question Section -->
                <div class="max-h-screen overflow-y-auto rounded-xl border border-gray-200 bg-white p-4 shadow-lg sm:p-6">
                    <div class="mb-4 sm:mb-6">
                        <div class="mb-3 flex items-center justify-between gap-2 sm:mb-4 sm:gap-3">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-600 sm:h-8 sm:w-8">
                                    <svg class="h-3 w-3 text-white sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 4 0 01-2 2h-2a2 2 0 01-2-2z"
                                        ></path>
                                    </svg>
                                </div>
                                <h1 class="text-lg font-bold text-gray-900 sm:text-xl lg:text-2xl">Writing Task 1</h1>
                            </div>
                            <button
                                @click="() => {}"
                                class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200 sm:text-sm"
                                title="Select text to highlight"
                            >
                                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div ref="questionTextRef" @mouseup="handleQuestionTextSelect" class="space-y-3 select-text sm:space-y-4">
                        <div class="text-base leading-relaxed whitespace-pre-wrap text-gray-800 sm:text-lg" v-html="highlightedQuestionText"></div>

                        <div class="p-2 text-center sm:p-4">
                            <img src="/images/writing/IELTS014.png" alt="Water usage charts by country" class="mx-auto h-full rounded-lg shadow-md" />
                        </div>
                    </div>
                </div>

                <!-- Answer Section -->
                <div class="max-h-screen overflow-y-auto rounded-xl border border-gray-200 bg-white p-4 shadow-lg sm:p-6">
                    <textarea
                        v-model="answer"
                        class="h-[40vh] w-full resize-none rounded-lg border-2 border-gray-300 bg-white p-3 text-base text-gray-900 transition-all duration-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none sm:h-[50vh] sm:p-4 sm:text-lg"
                        placeholder="Write your answer here..."
                    ></textarea>
                </div>
            </div>

            <!-- Desktop: Resizable layout -->
            <div class="resizable-container relative hidden gap-0 lg:flex" :class="{ 'select-none': isResizing }">
                <!-- Left Pane (Question) -->
                <div
                    class="max-h-screen overflow-y-auto rounded-l-xl border border-gray-200 bg-white p-6 shadow-lg"
                    :style="{ width: `${leftPaneWidth}%` }"
                >
                    <div class="mb-6">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 4 0 01-2 2h-2a2 2 0 01-2-2z"
                                        ></path>
                                    </svg>
                                </div>
                                <h1 class="text-2xl font-bold text-gray-900">Writing Task 1</h1>
                            </div>
                            <button
                                @click="() => {}"
                                class="rounded bg-gray-100 px-2 py-1 text-sm text-gray-700 hover:bg-gray-200"
                                title="Select text to highlight"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div ref="questionTextRef" @mouseup="handleQuestionTextSelect" class="space-y-4 select-text">
                        <div class="text-lg leading-relaxed whitespace-pre-wrap text-gray-800" v-html="highlightedQuestionText"></div>

                        <div class="p-2 text-center sm:p-4">
                            <img src="/images/writing/IELTS014.png" alt="Village development diagrams" class="mx-auto h-full rounded-lg shadow-md" />
                        </div>
                    </div>
                </div>

                <!-- Resizer -->
                <div
                    @mousedown="startResize"
                    class="group relative w-2 flex-shrink-0 cursor-col-resize bg-gray-200 transition-colors hover:bg-blue-400"
                    :class="{ 'bg-blue-500': isResizing }"
                >
                    <div
                        class="absolute inset-y-0 left-1/2 w-1 -translate-x-1/2 bg-gray-400 group-hover:bg-blue-600"
                        :class="{ 'bg-blue-700': isResizing }"
                    ></div>
                    <div
                        class="absolute top-1/2 left-1/2 flex h-12 w-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-lg border-2 border-gray-300 bg-white shadow-md group-hover:border-blue-500"
                        :class="{ 'border-blue-600': isResizing }"
                    >
                        <svg class="h-3 w-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                        </svg>
                    </div>
                </div>

                <!-- Right Pane (Answer) -->
                <div
                    class="max-h-screen overflow-y-auto rounded-r-xl border border-gray-200 bg-white p-6 shadow-lg"
                    :style="{ width: `${100 - leftPaneWidth}%` }"
                >
                    <textarea
                        v-model="answer"
                        class="h-[70vh] w-full resize-none rounded-lg border-2 border-gray-300 bg-white p-4 text-lg text-gray-900 transition-all duration-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        placeholder="Write your answer here..."
                    ></textarea>
                </div>
            </div>
        </div>

        <!-- Context Menu for Highlighting -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="fixed inset-0 z-[9998]">
                <div
                    class="absolute z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
                    :style="{
                        left: `${contextMenuPosition.x}px`,
                        top: `${contextMenuPosition.y}px`,
                        transform: 'translateX(-50%)',
                    }"
                    @click.stop
                >
                    <div class="flex items-center gap-2">
                        <!-- Highlight Color Buttons -->
                        <button
                            v-for="color in colors"
                            :key="color.name"
                            @click="applyHighlight(color.name)"
                            :class="[color.class, 'h-8 w-8 rounded-md border border-gray-300 transition-transform hover:scale-110']"
                            :title="`Highlight ${color.name}`"
                        ></button>

                        <!-- Divider -->
                        <div class="h-8 w-px bg-gray-300"></div>

                        <!-- Note Button -->
                        <button
                            @click="openNoteInput"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-indigo-50 transition-all hover:scale-110 hover:bg-indigo-100"
                            title="Add Note"
                        >
                            <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </button>

                        <!-- Cancel Button -->
                        <button
                            @click="closeContextMenu"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 hover:bg-gray-100"
                            title="Cancel"
                        >
                            <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Note Input Modal -->
        <Teleport to="body">
            <div v-if="showNoteInput" class="fixed inset-0 z-[9998]" @click="cancelNote">
                <div
                    class="absolute z-[9999] w-80 rounded-lg border-2 border-indigo-300 bg-white p-4 shadow-2xl"
                    :style="{
                        left: `${noteInputPosition.x}px`,
                        top: `${noteInputPosition.y}px`,
                        transform: 'translateX(-50%)',
                    }"
                    @click.stop
                >
                    <div class="mb-3">
                        <div class="mb-2 flex items-center gap-2">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            <h3 class="text-base font-semibold text-gray-800 sm:text-lg">Add Note</h3>
                        </div>
                        <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                        <input
                            v-model="noteInputText"
                            type="text"
                            placeholder="Write your note here..."
                            class="note-input-field w-full rounded-lg border-2 border-indigo-200 px-3 py-2 text-base focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none sm:text-lg"
                            @keyup.enter="saveNote"
                            @keyup.escape="cancelNote"
                        />
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="saveNote"
                            class="flex-1 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                            :disabled="!noteInputText.trim()"
                            :class="{ 'cursor-not-allowed opacity-50': !noteInputText.trim() }"
                        >
                            Save Note
                        </button>
                        <button
                            @click="cancelNote"
                            class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

.resizable-container {
    height: calc(100vh - 200px);
}

.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: col-resize;
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
}

.highlight-yellow {
    background-color: rgba(254, 240, 138, 0.5);
}

.highlight-green {
    background-color: rgba(187, 247, 208, 0.5);
}

.highlight-blue {
    background-color: rgba(191, 219, 254, 0.5);
}

.highlight-pink {
    background-color: rgba(251, 207, 232, 0.5);
}

.highlight-orange {
    background-color: rgba(254, 215, 170, 0.5);
}
</style>
