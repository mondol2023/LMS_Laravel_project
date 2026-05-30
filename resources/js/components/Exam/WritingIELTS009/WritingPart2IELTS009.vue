<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const emit = defineEmits<{
    wordCountChange: [count: number];
    notesChange: [notes: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>];
}>();

// Expose methods for parent component
const getAnswers = () => {
    return {
        part2: answer.value || null,
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

    const searchText = noteData.selectedText.trim();

    // Search directly in questionTextRef
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
                // Could not surround contents
            }
        }
    }
};

const answer = ref('');
const wordCount = ref(0);
const questionTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const questionText = ref(`You should spend about 40 minutes on this task

Write about the following topic:

People living in large cites today face many problems in their everyday life.

What are these problems?

Should governments encourage people to move to smaller regional towns?

Give reasons for your answer and include any relevant examples from your own knowledge or experience.
Write at least 250 words.`);

// Resize functionality
const PANEL_WIDTH_KEY = 'writing-part2-ielts002-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

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
});

// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

// Initialize highlight functionality
const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, applyHighlightsToText } = useHighlight();

// Initialize component
onMounted(() => {
    // Emit initial word count
    const count = calculateWordCount(answer.value);
    wordCount.value = count;
    emit('wordCountChange', count);

    // Add event listeners for context menu
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);

    // Add resize event listeners
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
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
            // Calculate position for context menu (below the selection)
            contextMenuPosition.value = {
                x: rect.left + rect.width / 2,
                y: rect.bottom + window.scrollY + 5,
            };
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

defineExpose({
    getAnswers,
    deleteNote,
    focusNote,
    notes,
});

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

// Resize handlers
const startResize = (event: MouseEvent) => {
    isResizing.value = true;
    event.preventDefault();
};

const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;

    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;

    // Set minimum and maximum widths (20% to 80%)
    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
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
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
});
</script>
<template>
    <div class="pb-20 sm:pb-24 md:pb-32">
        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Question Section -->
                <div
                    class="question-panel mb-4 max-h-screen overflow-y-auto rounded-xl border border-gray-200 bg-white p-4 shadow-lg sm:p-6 lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="mb-4 sm:mb-6">
                        <div class="mb-3 flex items-center justify-between gap-2 sm:mb-4 sm:gap-3">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-purple-600 sm:h-8 sm:w-8">
                                    <svg class="h-3 w-3 text-white sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                        ></path>
                                    </svg>
                                </div>
                                <h1 class="text-lg font-bold text-gray-900 sm:text-xl lg:text-2xl">Writing Task 2</h1>
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
                        <div class="rounded-lg bg-gray-50 p-3 sm:p-4">
                            <div
                                class="text-sm leading-relaxed whitespace-pre-wrap text-gray-800 sm:text-base"
                                v-html="highlightedQuestionText"
                            ></div>
                        </div>
                    </div>

                    <!-- Highlights List -->
                    <div v-if="highlights.length > 0" class="mt-4 border-t border-gray-200 pt-4">
                        <h3 class="mb-2 text-sm font-semibold text-gray-700">Your Highlights</h3>
                        <div class="space-y-2">
                            <div
                                v-for="highlight in highlights"
                                :key="highlight.id"
                                class="flex items-start gap-2 rounded border border-gray-200 p-2"
                            >
                                <span
                                    class="mt-0.5 inline-block h-4 w-4 rounded"
                                    :class="{
                                        'bg-yellow-200': highlight.color === 'yellow',
                                        'bg-green-200': highlight.color === 'green',
                                        'bg-blue-200': highlight.color === 'blue',
                                        'bg-pink-200': highlight.color === 'pink',
                                        'bg-orange-200': highlight.color === 'orange',
                                    }"
                                ></span>
                                <p class="flex-1 text-xs text-gray-700 sm:text-sm">{{ highlight.text }}</p>
                                <button @click="handleDeleteHighlight(highlight.id)" class="text-red-500 hover:text-red-700" title="Delete highlight">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notes List -->
                    <div v-if="notes.length > 0" class="mt-4 border-t border-gray-200 pt-4">
                        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            Your Notes
                        </h3>
                        <div class="space-y-3">
                            <div v-for="note in notes" :key="note.id" class="relative rounded-lg border-2 border-indigo-100 bg-indigo-50/30 p-3">
                                <div class="mb-2 border-b border-indigo-200 pb-2">
                                    <p class="text-xs text-indigo-600 italic">"{{ note.selectedText }}"</p>
                                </div>
                                <p class="text-sm font-medium text-gray-800">{{ note.note }}</p>
                                <button
                                    @click="deleteNote(note.id)"
                                    class="absolute top-2 right-2 text-red-500 hover:text-red-700"
                                    title="Delete note"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-purple-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-purple-500"></div>
                    </div>
                    <!-- Visual indicators (dots) -->
                    <div class="absolute flex flex-col gap-1 opacity-40 transition-opacity group-hover:opacity-100">
                        <div class="h-1 w-1 rounded-full bg-gray-400 group-hover:bg-purple-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 group-hover:bg-purple-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 group-hover:bg-purple-600"></div>
                    </div>
                </div>

                <!-- Answer Section -->
                <div
                    class="answer-panel max-h-screen overflow-y-auto rounded-xl border border-gray-200 bg-white p-4 shadow-lg sm:p-6"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <textarea
                        v-model="answer"
                        class="h-[40vh] w-full resize-none rounded-lg border-2 border-gray-300 bg-white p-3 text-sm text-gray-900 transition-all duration-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 focus:outline-none sm:h-[50vh] sm:p-4 sm:text-base lg:h-[70vh]"
                        placeholder="Write your answer here..."
                        spellcheck="false"
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
                        <button
                            v-for="color in colors"
                            :key="color.name"
                            @click="applyHighlight(color.name)"
                            :class="[color.class, 'h-8 w-8 rounded-md border border-gray-300 transition-transform hover:scale-110']"
                            :title="`Highlight ${color.name}`"
                        ></button>
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

            <!-- Note Input Modal -->
            <div
                v-if="showNoteInput"
                class="absolute z-[9999] w-80 rounded-lg border-2 border-indigo-300 bg-white p-4 shadow-2xl"
                :style="{
                    left: `${noteInputPosition.x}px`,
                    top: `${noteInputPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded-lg border-2 border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        @click="cancelNote"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveNote"
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        Save Note
                    </button>
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

.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: col-resize;
}

/* Responsive panel widths */
.question-panel {
    width: 100%;
}

.answer-panel {
    width: 100%;
}

@media (min-width: 1024px) {
    .question-panel {
        width: calc(var(--panel-width) - 0.5rem);
    }

    .answer-panel {
        width: calc(100% - var(--panel-width) - 0.5rem);
    }
}
</style>
