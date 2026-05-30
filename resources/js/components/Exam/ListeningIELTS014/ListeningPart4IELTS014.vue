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

// Listening Part 4 component
const answers = ref({
    q31: '',
    q32: '',
    q33: '',
    q34: '',
    q35: '',
    q36: '',
    q37: '',
    q38: '',
    q39: '',
    q40: '',
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
    { text: 'Section 4', offset: 0 },
    { text: 'Questions 31-40', offset: 9 },
    { text: 'Complete the notes below. Write ONE WORD ONLY for each answer.', offset: 25 },
    { text: 'The Tawny Owl', offset: 89 },
    { text: 'Most  ............ owl species in UK', offset: 104 },
    { text: 'Strongly nocturnal', offset: 142 },
    { text: 'Habitat:', offset: 161 },
    { text: 'Mainly lives in  ............ but can also be seen in urban areas, e.g. parks.', offset: 170 },
    { text: 'Adaptations:', offset: 247 },
    { text: '• Short wings and  ............ , for navigation', offset: 260 },
    { text: '• Brown and  ............ feathers, for camouflage', offset: 310 },
    { text: '• Large eyes (more effective than those of  ............ ), for good night vision', offset: 361 },
    { text: '• Very good spatial  ............ for predicting where prey might be found', offset: 440 },
    { text: '• Excellent  ............ for locating prey from a perch', offset: 512 },
    { text: 'Diet', offset: 569 },
    { text: 'Main food is small mammals.', offset: 575 },
    { text: 'Owls in urban areas eat more  ............', offset: 604 },
    { text: 'Survival', offset: 647 },
    { text: 'Two thirds of young owls die within a  ............', offset: 657 },
    { text: 'Owls don’t disperse over long distances.', offset: 709 },
    { text: 'Owls seem to dislike flying over large areas of  ............', offset: 751 },
]);

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentIndex: number) => {
    const segment = textSegments.value[segmentIndex];
    if (!segment) return '';

    const segmentText = segment.text;
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

// Initialize answers and load drafts
const loadSavedAnswers = async () => {
    const userPhone = props.phone || draftService.getUserPhone();
    const examId = props.examId || '1234567';

    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part4');

    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part4) {
            Object.assign(answers.value, draftsResponse.data.part4);
            console.log('Loaded Listening Part 4 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 4 drafts:', error);
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
    return answers.value;
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
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
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
                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-r from-orange-500 to-red-600 sm:h-7 sm:w-7 md:h-8 md:w-8"
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
            <div class="flex-1 overflow-y-auto bg-orange-50 pb-32">
                <div class="p-4 sm:p-6 lg:p-8">
                    <div class="rounded-2xl border border-orange-200 bg-white p-6 shadow-lg">
                        <h3 class="mb-6 text-2xl font-bold text-orange-800" :data-segment-index="3" v-html="getHighlightedSegment(3)"></h3>

                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >31</span
                                >
                                <div class="flex-1 text-gray-700">
                                    <span :data-segment-index="4" v-html="getHighlightedSegment(4).split('............')[0]"></span>
                                    <input
                                        v-model="answers.q31"
                                        type="text"
                                        class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                    />
                                    <span :data-segment-index="4" v-html="getHighlightedSegment(4).split('............')[1]"></span>
                                </div>
                            </div>
                            <p class="mt-2 pl-12 text-gray-700" :data-segment-index="5" v-html="getHighlightedSegment(5)"></p>

                            <div>
                                <h4 class="mt-4 text-lg font-semibold text-orange-700" :data-segment-index="6" v-html="getHighlightedSegment(6)"></h4>
                                <div class="mt-2 flex items-center gap-4">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >32</span
                                    >
                                    <div class="flex-1 text-gray-700">
                                        <span :data-segment-index="7" v-html="getHighlightedSegment(7).split('............')[0]"></span>
                                        <input
                                            v-model="answers.q32"
                                            type="text"
                                            class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                        />
                                        <span :data-segment-index="7" v-html="getHighlightedSegment(7).split('............')[1]"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="mt-4 text-lg font-semibold text-orange-700" :data-segment-index="8" v-html="getHighlightedSegment(8)"></h4>
                                <ul class="mt-2 list-inside list-disc space-y-4 pl-6">
                                    <li class="flex items-center gap-4">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >33</span
                                        >
                                        <div class="flex-1 text-gray-700">
                                            <span :data-segment-index="9" v-html="getHighlightedSegment(9).split('............')[0]"></span>
                                            <input
                                                v-model="answers.q33"
                                                type="text"
                                                class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                            />
                                            <span :data-segment-index="9" v-html="getHighlightedSegment(9).split('............')[1]"></span>
                                        </div>
                                    </li>
                                    <li class="flex items-center gap-4">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >34</span
                                        >
                                        <div class="flex-1 text-gray-700">
                                            <span :data-segment-index="10" v-html="getHighlightedSegment(10).split('............')[0]"></span>
                                            <input
                                                v-model="answers.q34"
                                                type="text"
                                                class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                            />
                                            <span :data-segment-index="10" v-html="getHighlightedSegment(10).split('............')[1]"></span>
                                        </div>
                                    </li>
                                    <li class="flex items-center gap-4">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >35</span
                                        >
                                        <div class="flex-1 text-gray-700">
                                            <span :data-segment-index="11" v-html="getHighlightedSegment(11).split('............')[0]"></span>
                                            <input
                                                v-model="answers.q35"
                                                type="text"
                                                class="w-24w-32 mx-1 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                            />
                                            <span :data-segment-index="11" v-html="getHighlightedSegment(11).split('............')[1]"></span>
                                        </div>
                                    </li>
                                    <li class="flex items-center gap-4">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >36</span
                                        >
                                        <div class="flex-1 text-gray-700">
                                            <span :data-segment-index="12" v-html="getHighlightedSegment(12).split('............')[0]"></span>
                                            <input
                                                v-model="answers.q36"
                                                type="text"
                                                class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                            />
                                            <span :data-segment-index="12" v-html="getHighlightedSegment(12).split('............')[1]"></span>
                                        </div>
                                    </li>
                                    <li class="flex items-center gap-4">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >37</span
                                        >
                                        <div class="flex-1 text-gray-700">
                                            <span :data-segment-index="13" v-html="getHighlightedSegment(13).split('............')[0]"></span>
                                            <input
                                                v-model="answers.q37"
                                                type="text"
                                                class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                            />
                                            <span :data-segment-index="13" v-html="getHighlightedSegment(13).split('............')[1]"></span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h4
                                    class="mt-4 text-lg font-semibold text-orange-700"
                                    :data-segment-index="14"
                                    v-html="getHighlightedSegment(14)"
                                ></h4>
                                <p class="mt-2 pl-12 text-gray-700" :data-segment-index="15" v-html="getHighlightedSegment(15)"></p>
                                <div class="mt-2 flex items-center gap-4">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >38</span
                                    >
                                    <div class="flex-1 text-gray-700">
                                        <span :data-segment-index="16" v-html="getHighlightedSegment(16).split('............')[0]"></span>
                                        <input
                                            v-model="answers.q38"
                                            type="text"
                                            class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                        />
                                        <span :data-segment-index="16" v-html="getHighlightedSegment(16).split('............')[1]"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4
                                    class="mt-4 text-lg font-semibold text-orange-700"
                                    :data-segment-index="17"
                                    v-html="getHighlightedSegment(17)"
                                ></h4>
                                <div class="mt-2 flex items-center gap-4">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >39</span
                                    >
                                    <div class="flex-1 text-gray-700">
                                        <span :data-segment-index="18" v-html="getHighlightedSegment(18).split('............')[0]"></span>
                                        <input
                                            v-model="answers.q39"
                                            type="text"
                                            class="mx-1 w-32 rounded-2xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                        />
                                        <span :data-segment-index="18" v-html="getHighlightedSegment(18).split('............')[1]"></span>
                                    </div>
                                </div>
                                <p class="mt-2 pl-12 text-gray-700" :data-segment-index="19" v-html="getHighlightedSegment(19)"></p>
                                <div class="mt-2 flex items-center gap-4">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-semibold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >40</span
                                    >
                                    <div class="flex-1 text-gray-700">
                                        <span :data-segment-index="20" v-html="getHighlightedSegment(20).split('............')[0]"></span>
                                        <input
                                            v-model="answers.q40"
                                            type="text"
                                            class="mx-1 w-32 rounded-xl border border-orange-300 px-2 py-1 text-sm shadow focus:border-orange-500 focus:ring-orange-500"
                                        />
                                        <span :data-segment-index="20" v-html="getHighlightedSegment(20).split('............')[1]"></span>
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
        background-color: rgba(249, 115, 22, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(249, 115, 22, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(249, 115, 22, 0.1);
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
    background: linear-gradient(to bottom, #f97316, #ea580c);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ea580c, #c2410c);
}
</style>
