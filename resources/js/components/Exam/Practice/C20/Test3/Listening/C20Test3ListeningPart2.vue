<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Answers for questions 11-20
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
    { text: 'Questions 11-16', offset: 0 },
    { text: 'Choose the correct letter, A, B or C.', offset: 18 },
    { text: 'Who was responsible for starting the community project?', offset: 60 },
    { text: 'the castle owners', offset: 115 },
    { text: 'a national charity', offset: 135 },
    { text: 'the local council', offset: 155 },
    { text: 'How was the gold coin found?', offset: 175 },
    { text: 'Heavy rain had removed some of the soil.', offset: 205 },
    { text: 'The ground was dug up by wild rabbits.', offset: 250 },
    { text: 'A person with a metal detector searched the area.', offset: 290 },
    { text: 'What led the archaeologists to believe there was an ancient village on this site?', offset: 350 },
    { text: 'the lucky discovery of old records', offset: 430 },
    { text: 'the bases of several structures visible in the grass', offset: 470 },
    { text: 'the unusual stones found near the castle', offset: 530 },
    { text: 'What are the team still hoping to find?', offset: 580 },
    { text: 'everyday pottery', offset: 620 },
    { text: 'animal bones', offset: 640 },
    { text: 'pieces of jewellery', offset: 658 },
    { text: 'What was found on the other side of the river to the castle?', offset: 680 },
    { text: 'the remains of a large palace', offset: 740 },
    { text: 'the outline of fields', offset: 775 },
    { text: 'a number of small huts', offset: 800 },
    { text: 'What do the team plan to do after work ends this summer?', offset: 825 },
    { text: 'prepare a display for a museum', offset: 885 },
    { text: 'take part in a television programme', offset: 920 },
    { text: 'start to organise school visits', offset: 960 },
    { text: 'Questions 17-20', offset: 995 },
    { text: 'Label the map below.', offset: 1013 },
    { text: 'Drag the correct letter, A-G, next to Questions 17-20.', offset: 1035 },
    { text: 'Bidcaster Archaeological Dig', offset: 1095 },
    { text: 'bridge foundations', offset: 1128 },
    { text: 'rubbish pit', offset: 1149 },
    { text: 'meeting hall', offset: 1163 },
    { text: 'fish pond', offset: 1178 },
]);

const mcqQuestions = [
    {
        num: 11,
        question: 'Who was responsible for starting the community project?',
        options: [
            { label: 'A', text: 'the castle owners' },
            { label: 'B', text: 'a national charity' },
            { label: 'C', text: 'the local council' },
        ],
    },
    {
        num: 12,
        question: 'How was the gold coin found?',
        options: [
            { label: 'A', text: 'Heavy rain had removed some of the soil.' },
            { label: 'B', text: 'The ground was dug up by wild rabbits.' },
            { label: 'C', text: 'A person with a metal detector searched the area.' },
        ],
    },
    {
        num: 13,
        question: 'What led the archaeologists to believe there was an ancient village on this site?',
        options: [
            { label: 'A', text: 'the lucky discovery of old records' },
            { label: 'B', text: 'the bases of several structures visible in the grass' },
            { label: 'C', text: 'the unusual stones found near the castle' },
        ],
    },
    {
        num: 14,
        question: 'What are the team still hoping to find?',
        options: [
            { label: 'A', text: 'everyday pottery' },
            { label: 'B', text: 'animal bones' },
            { label: 'C', text: 'pieces of jewellery' },
        ],
    },
    {
        num: 15,
        question: 'What was found on the other side of the river to the castle?',
        options: [
            { label: 'A', text: 'the remains of a large palace' },
            { label: 'B', text: 'the outline of fields' },
            { label: 'C', text: 'a number of small huts' },
        ],
    },
    {
        num: 16,
        question: 'What do the team plan to do after work ends this summer?',
        options: [
            { label: 'A', text: 'prepare a display for a museum' },
            { label: 'B', text: 'take part in a television programme' },
            { label: 'C', text: 'start to organise school visits' },
        ],
    },
];

const mapQuestions = [
    { num: 17, text: 'bridge foundations' },
    { num: 18, text: 'rubbish pit' },
    { num: 19, text: 'meeting hall' },
    { num: 20, text: 'fish pond' },
];

const mapOptions = [
    { label: 'A', text: 'Location A (Placeholder)' },
    { label: 'B', text: 'Location B (Placeholder)' },
    { label: 'C', text: 'Location C (Placeholder)' },
    { label: 'D', text: 'Location D (Placeholder)' },
    { label: 'E', text: 'Location E (Placeholder)' },
    { label: 'F', text: 'Location F (Placeholder)' },
    { label: 'G', text: 'Location G (Placeholder)' },
];

const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    const sorted = [...overlappingHighlights].sort((a, b) => b.start_offset - a.start_offset);

    let result = segmentText;
    for (const highlight of sorted) {
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

const getAnswers = () => {
    return { ...answers.value };
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const targetId = `question-${questionNumber}`;
    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            const segmentEl = (container as Element).closest('[data-segment-text]');
            if (!segmentEl) return null;
            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) {
                console.error('Segment not found for text:', segmentText);
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

const focusNote = async (noteId: number) => {
    const note = notes.value.find((n) => n.id === noteId);
    if (!note) return;

    await nextTick();
    const element = document.querySelector(`mark[data-note-id="${noteId}"]`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-flash');
        setTimeout(() => element.classList.remove('highlight-flash'), 2000);
    }
};

const handleClickOutside = () => {
    if (showContextMenu.value) showContextMenu.value = false;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) showContextMenu.value = false;
};

onMounted(async () => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({ getAnswers, answers, scrollToQuestion, scrollToHighlight, notes, deleteNote, focusNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <div class="mb-10 flex min-h-screen w-full flex-col rounded-2xl bg-white/80 shadow-2xl backdrop-blur-lg">
            <!-- Header -->
            <div class="flex-shrink-0 border-b-2 border-dashed border-gray-200 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-600 to-pink-500 text-white shadow-lg"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-purple-800">Questions 11-20</h2>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto bg-pink-50/50 p-4 lg:p-6">
                <div class="space-y-12">
                    <!-- Section 11-16 -->
                    <div>
                        <p
                            class="mb-4 font-semibold text-gray-800"
                            :data-segment-text="'Choose the correct letter, A, B or C.'"
                            v-html="getHighlightedSegment('Choose the correct letter, A, B or C.')"
                        ></p>

                        <div class="space-y-8">
                            <div
                                v-for="q in mcqQuestions"
                                :key="q.num"
                                :id="`question-${q.num}`"
                                class="transform rounded-2xl border-2 border-purple-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-purple-300 hover:shadow-2xl"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(192, 38, 211, 0.4)"
                                    >
                                        {{ q.num }}
                                    </div>
                                    <div class="w-full">
                                        <p
                                            class="mb-4 font-semibold text-gray-800"
                                            :data-segment-text="q.question"
                                            v-html="getHighlightedSegment(q.question)"
                                        ></p>
                                        <div class="space-y-3">
                                            <label
                                                v-for="option in q.options"
                                                :key="option.label"
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/70"
                                            >
                                                <input
                                                    type="radio"
                                                    :name="`q${q.num}`"
                                                    :value="option.label"
                                                    v-model="answers['q' + q.num]"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span class="text-gray-700">
                                                    <span class="mr-2 font-bold">{{ option.label }}</span>
                                                    <span :data-segment-text="option.text" v-html="getHighlightedSegment(option.text)"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 17-20 -->
                    <div class="pt-8">
                        <h3
                            class="text-lg font-bold text-purple-800"
                            :data-segment-text="'Questions 17-20'"
                            v-html="getHighlightedSegment('Questions 17-20')"
                        ></h3>
                        <p
                            class="mt-4 font-semibold text-gray-800"
                            :data-segment-text="'Label the map below.'"
                            v-html="getHighlightedSegment('Label the map below.')"
                        ></p>
                        <p
                            class="text-sm text-gray-600"
                            :data-segment-text="'Drag the correct letter, A-G, next to Questions 17-20.'"
                            v-html="getHighlightedSegment('Drag the correct letter, A-G, next to Questions 17-20.')"
                        ></p>
                        <p
                            class="my-4 text-center font-bold text-purple-700"
                            :data-segment-text="'Bidcaster Archaeological Dig'"
                            v-html="getHighlightedSegment('Bidcaster Archaeological Dig')"
                        ></p>

                        <div class="my-6 flex items-center justify-center rounded-2xl border-2 border-dashed border-purple-200 bg-purple-50 p-6">
                            <img src="/images/listening/Practice003.png" alt="Museum Plan" class="rounded-lg shadow-md md:w-2/3" />
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="q in mapQuestions"
                                :key="q.num"
                                :id="`question-${q.num}`"
                                class="flex items-center justify-between rounded-xl bg-white p-4 shadow-md transition-shadow hover:shadow-xl"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(192, 38, 211, 0.4)"
                                    >
                                        {{ q.num }}
                                    </div>
                                    <p class="font-semibold text-gray-800" :data-segment-text="q.text" v-html="getHighlightedSegment(q.text)"></p>
                                </div>
                                <select
                                    v-model="answers['q' + q.num]"
                                    class="w-28 rounded-lg border-2 border-purple-300 bg-white p-2 text-center font-bold text-purple-800 shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/50"
                                >
                                    <option value="">Select</option>
                                    <option v-for="opt in mapOptions" :key="opt.label" :value="opt.label">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu & Note Input -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="pointer-events-auto fixed z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
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
            <div
                v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border-2 border-blue-300 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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
    background: #fdf4ff;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #a855f7, #ec4899);
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #9333ea, #db2777);
}
</style>
