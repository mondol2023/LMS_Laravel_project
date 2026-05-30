<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import draftService from '@/services/draftService';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {
    phone?: string;
    examId?: string;
}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Listening Part 2 component

// Single choice answers for questions 11-20
const answers = ref({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
});

// Draft auto-saver
const autoSaver = ref<any>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const textSegments = ref([
    { text: 'Section 2', offset: 0 },
    { text: 'Questions 11-20', offset: 9 },
    { text: 'Choose the correct letter, A, B or C.', offset: 24 },
    { text: 'Museum work placement', offset: 62 },
    { text: 'On Monday what will be the students’ working day?', offset: 85 },
    { text: 'A 9 am – 5 pm', offset: 139 },
    { text: 'B 8.45 am – 5 pm', offset: 151 },
    { text: 'C 9 am – 4.45 pm', offset: 166 },
    { text: 'While working in the museum students are encouraged to wear', offset: 181 },
    { text: 'A formal clothing such as a suit', offset: 245 },
    { text: 'B cap with the museum logo', offset: 276 },
    { text: 'C their own casual clothes', offset: 302 },
    { text: 'If students are ill or going to be late they must inform', offset: 328 },
    { text: 'A the museum receptionist', offset: 389 },
    { text: 'B their museum supervisor', offset: 414 },
    { text: 'C their school placement tutor', offset: 439 },
    { text: 'The most popular task while on work placement is usually', offset: 468 },
    { text: 'A making presentations in local primary schools', offset: 531 },
    { text: 'B talking to elderly people in care homes', offset: 576 },
    { text: 'C conducting workshops in the museum', offset: 615 },
    { text: 'The best form of preparation before starting their work placement is to read', offset: 651 },
    { text: 'A the history of the museum on the website', offset: 732 },
    { text: 'B the museum regulations and safety guidance', offset: 773 },
    { text: 'C notes made by previous work placement students', offset: 815 },
    { text: 'Questions 16-20', offset: 862 },
    { text: 'Label the plan below. Write the correct letter A-I next to questions 16-20.', offset: 877 },
    { text: 'Where in the museum are the following places?', offset: 952 },
    { text: 'Sign-in office', offset: 997 },
    { text: 'Gallery 1', offset: 1015 },
    { text: 'Key box', offset: 1028 },
    { text: 'Kitchen area', offset: 1039 },
    { text: 'Staff noticeboard', offset: 1054 },
]);

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentIndex: number) => {
    const segment = textSegments.value[segmentIndex];
    if (!segment) return '';

    const segmentText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    // Apply highlights to this segment
    // Sort by start offset descending
    const sorted = [...overlappingHighlights].sort((a, b) => b.start_offset - a.start_offset);

    let result = segmentText;
    for (const highlight of sorted) {
        // Calculate the position within this segment
        const highlightStart = Math.max(0, highlight.start_offset - segmentOffset);
        const highlightEnd = Math.min(segmentText.length, highlight.end_offset - segmentOffset);

        if (highlightStart < highlightEnd) {
            const before = result.substring(0, highlightStart);
            const highlighted = result.substring(highlightStart, highlightEnd);
            const after = result.substring(highlightEnd);

            result = `${before}<mark class="highlight-${highlight.color}" data-highlight-id="${highlight.id}">${highlighted}</mark>${after}`;
        }
    }

    return result;
};

// Initialize answers and load drafts
const loadSavedAnswers = async () => {
    const userPhone = props.phone || draftService.getUserPhone();
    const examId = props.examId || '1234567';

    // Initialize auto-saver
    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part2');

    // Load saved drafts
    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part2) {
            // Load answers
            Object.keys(answers.value).forEach((key) => {
                if (draftsResponse.data.part2[key]) {
                    answers.value[key as keyof typeof answers.value] = draftsResponse.data.part2[key];
                }
            });
            console.log('Loaded Listening Part 2 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 2 drafts:', error);
    }
};

// Save answers to drafts
const saveAnswers = () => {
    if (autoSaver.value) {
        autoSaver.value.saveBatch(answers.value);
    }
};

// Expose methods for parent component
const getAnswers = () => {
    return { ...answers.value };
};

// Watch for changes and auto-save
watch(
    answers,
    () => {
        saveAnswers();
    },
    { deep: true },
);

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const targetId = `question-${questionNumber}`;

    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Add highlight effect
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

const scrollToHighlight = async (highlightId: number) => {
    await nextTick();
    const element = document.querySelector(`mark[data-highlight-id="${highlightId}"]`);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Optional: Add a temporary visual effect to the scrolled highlight
        element.classList.add('highlight-flash');
        setTimeout(() => {
            element.classList.remove('highlight-flash');
        }, 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-index]');

            if (!segmentEl) {
                return null;
            }

            const segmentIndex = parseInt(segmentEl.getAttribute('data-segment-index') || '-1');
            if (segmentIndex === -1) return null;

            const segment = textSegments.value[segmentIndex];
            if (!segment) {
                console.error('Segment not found for index:', segmentIndex);
                return null;
            }

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) {
                    offsetInSegment += offsetInNode;
                    break;
                } else {
                    offsetInSegment += currentNode.textContent?.length || 0;
                }
            }

            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);

        if (startAbsOffset === null || endAbsOffset === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }

        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.bottom + 5;

            const viewportWidth = window.innerWidth;
            const menuWidth = 250;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2), viewportWidth - menuWidth / 2),
                y: menuY,
            };
            showContextMenu.value = true;

            selectedText.value = selection.toString();
            selectionRange.value = { start: startAbsOffset, end: endAbsOffset };

            console.log('Selection:', { selected: selectedText.value, startOffset: startAbsOffset, endOffset: endAbsOffset });
        } else {
            showContextMenu.value = false;
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

    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

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

const handleClickOutside = () => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) {
        showContextMenu.value = false;
    }
};

// Load saved answers when component mounts
onMounted(async () => {
    await loadSavedAnswers();
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    if (autoSaver.value) {
        autoSaver.value.stop();
    }
});

defineExpose({
    getAnswers,
    answers: answers.value,
    scrollToQuestion,
    scrollToHighlight,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
            <!-- Questions Header (Fixed) -->
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-2 sm:p-3 md:p-4 lg:p-6">
                <div class="flex flex-col gap-2 sm:gap-3">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600 sm:h-7 sm:w-7 md:h-8 md:w-8"
                        >
                            <svg class="h-3 w-3 text-white sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                ></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm">
                                <span :data-segment-index="0" v-html="getHighlightedSegment(0)"></span> -
                                <span :data-segment-index="1" v-html="getHighlightedSegment(1)"></span>
                            </p>
                            <p class="text-xs text-gray-600"><span :data-segment-index="2" v-html="getHighlightedSegment(2)"></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto bg-pink-50 pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-8">
                        <div class="rounded-2xl border border-pink-200 bg-gradient-to-br from-purple-50 to-pink-100 p-6 shadow-lg">
                            <h3 class="mb-4 text-lg font-semibold text-pink-800" :data-segment-index="3" v-html="getHighlightedSegment(3)"></h3>

                            <!-- Questions 11-15 -->
                            <div class="space-y-6">
                                <div
                                    v-for="i in 5"
                                    :key="i"
                                    class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-md transition-shadow hover:shadow-lg"
                                >
                                    <div class="flex items-start gap-4">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-semibold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >{{ 10 + i }}</span
                                        >
                                        <div class="flex-1">
                                            <p
                                                class="mb-3 font-medium text-gray-800"
                                                :data-segment-index="4 + (i - 1) * 4"
                                                v-html="getHighlightedSegment(4 + (i - 1) * 4)"
                                            ></p>
                                            <div class="space-y-2">
                                                <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-pink-100">
                                                    <input
                                                        type="radio"
                                                        :name="`q${10 + i}`"
                                                        v-model="answers[`q${10 + i}`]"
                                                        value="A"
                                                        class="h-5 w-5 accent-pink-600"
                                                    />
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-index="5 + (i - 1) * 4"
                                                        v-html="getHighlightedSegment(5 + (i - 1) * 4)"
                                                    ></span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-pink-100">
                                                    <input
                                                        type="radio"
                                                        :name="`q${10 + i}`"
                                                        v-model="answers[`q${10 + i}`]"
                                                        value="B"
                                                        class="h-5 w-5 accent-pink-600"
                                                    />
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-index="6 + (i - 1) * 4"
                                                        v-html="getHighlightedSegment(6 + (i - 1) * 4)"
                                                    ></span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-pink-100">
                                                    <input
                                                        type="radio"
                                                        :name="`q${10 + i}`"
                                                        v-model="answers[`q${10 + i}`]"
                                                        value="C"
                                                        class="h-5 w-5 accent-pink-600"
                                                    />
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-index="7 + (i - 1) * 4"
                                                        v-html="getHighlightedSegment(7 + (i - 1) * 4)"
                                                    ></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 16-20 -->
                        <div class="rounded-2xl border border-pink-200 bg-gradient-to-br from-purple-50 to-pink-100 p-6 shadow-lg">
                            <h3
                                class="mb-2 bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-xl font-bold text-transparent"
                                :data-segment-index="24"
                                v-html="getHighlightedSegment(24)"
                            ></h3>
                            <p class="text-gray-700" :data-segment-index="25" v-html="getHighlightedSegment(25)"></p>
                            <p class="mt-4 text-gray-700" :data-segment-index="26" v-html="getHighlightedSegment(26)"></p>

                            <div class="mt-4 flex flex-col gap-6 md:flex-row">
                                <div class="md:w-1/2">
                                    <img src="/images/listening/IELTS014.png" alt="Museum Plan" class="rounded-lg shadow-md md:w-full" />
                                </div>
                                <div class="space-y-4 md:w-1/2">
                                    <div v-for="i in 5" :key="i" class="flex items-center gap-4 rounded-lg bg-white p-3 shadow-sm">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-semibold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >{{ 15 + i }}</span
                                        >
                                        <label
                                            :for="`q${15 + i}`"
                                            class="flex-1 font-medium text-gray-800"
                                            :data-segment-index="27 + (i - 1)"
                                            v-html="getHighlightedSegment(27 + (i - 1))"
                                        ></label>
                                        <select
                                            :id="`q${15 + i}`"
                                            v-model="answers[`q${15 + i}`]"
                                            class="w-20 rounded-md border-2 border-pink-200 bg-pink-50 px-3 py-1 text-sm focus:border-pink-500 focus:bg-white focus:outline-none"
                                        >
                                            <option disabled value="">Select A-I</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                            <option value="I">I</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Context Menu for Highlighting -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="pointer-events-auto fixed z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
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
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-blue-50 transition-all hover:scale-110 hover:bg-blue-100"
                            title="Add Note"
                        >
                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div
                v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border-2 border-blue-300 bg-white p-4 shadow-2xl"
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
                        class="note-input-field w-full rounded-lg border-2 border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
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
                        class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        Save Note
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
}

.highlight-nocolor {
    background-color: transparent;
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
.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(168, 85, 247, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(168, 85, 247, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(168, 85, 247, 0.1);
        transform: scale(1);
    }
}

.highlight-flash {
    animation: flashHighlight 1.5s ease-out;
}

@keyframes flashHighlight {
    0% {
        background-color: yellow;
    }
    100% {
        background-color: transparent;
    }
}

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #a855f7, #ec4899);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #9333ea, #db2777);
}
</style>
