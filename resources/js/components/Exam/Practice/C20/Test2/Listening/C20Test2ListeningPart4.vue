<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Listening Part 4 component

// Answers for questions 31-40
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
    { text: 'Questions 31-40', offset: 0 },
    { text: 'Complete the notes below.', offset: 18 },
    { text: 'Write ONE WORD ONLY for each answer.', offset: 45 },
    { text: 'Developing food trends', offset: 85 },
    { text: 'The growth in interest in food fashions started with', offset: 110 },
    { text: 'of food being shared on social media.', offset: 165 },
    { text: 'The UK food industry is constantly developing products which are new or different.', offset: 205 },
    { text: 'Influencers on social media become ‘ambassadors’ for a brand.', offset: 285 },
    { text: 'Sales of', offset: 350 },
    { text: 'food brands have grown rapidly this way.', offset: 360 },
    { text: 'Supermarkets track demand for ingredients on social media.', offset: 405 },
    { text: 'Famous', offset: 465 },
    { text: 'are influential.', offset: 475 },
    { text: 'Marketing campaigns', offset: 495 },
    { text: 'The avocado:', offset: 518 },
    { text: 'were invited to visit growers in South Africa.', offset: 535 },
    { text: 'Advertising focused on its', offset: 585 },
    { text: 'benefits.', offset: 615 },
    { text: 'Oat milk:', offset: 625 },
    { text: 'A Swedish brand’s media campaign received publicity by upsetting competitors.', offset: 638 },
    { text: 'Promotion in the USA through', offset: 715 },
    { text: 'shops reduced the need for advertising.', offset: 745 },
    { text: 'It appealed to consumers who are concerned about the', offset: 790 },
    { text: 'Norwegian skrei:', offset: 845 },
    { text: 'has helped strengthen the', offset: 865 },
    { text: 'of Norwegian seafood.', offset: 890 },
    { text: 'Ethical concerns', offset: 915 },
    { text: 'Quinoa:', offset: 935 },
    { text: 'Its success led to an increase in its', offset: 945 },
    { text: 'Overuse of resources resulted in poor quality', offset: 985 },
]);

// Helper to get a highlighted version of a specific text segment
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

// Initialize answers and load drafts

const getAnswers = () => {
    return { ...answers.value };
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const targetId = `question-${questionNumber}`;
    const element = document.getElementById(targetId);
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
            const segmentEl = (container as Element).closest('[data-segment-text]');

            if (!segmentEl) {
                return null;
            }

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

onMounted(async () => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
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
        <div class="mb-10 flex min-h-screen w-full flex-col rounded-2xl bg-white/80 shadow-2xl backdrop-blur-lg">
            <!-- Header -->
            <div class="flex-shrink-0 border-b-2 border-dashed border-gray-200 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-lg"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2
                            class="text-lg font-bold text-orange-800"
                            :data-segment-text="'Questions 31-40'"
                            v-html="getHighlightedSegment('Questions 31-40')"
                        ></h2>
                        <p
                            class="text-sm text-gray-600"
                            :data-segment-text="'Complete the notes below.'"
                            v-html="getHighlightedSegment('Complete the notes below.')"
                        ></p>
                        <p
                            class="font-semibold text-orange-700 italic"
                            :data-segment-text="'Write ONE WORD ONLY for each answer.'"
                            v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.')"
                        ></p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto bg-orange-50/30 p-4 lg:p-6">
                <div class="space-y-8">
                    <div class="mb-10 text-center">
                        <h3
                            class="text-2xl font-bold text-orange-900"
                            :data-segment-text="'Developing food trends'"
                            v-html="getHighlightedSegment('Developing food trends')"
                        ></h3>
                    </div>

                    <ul class="list-none space-y-5">
                        <li class="flex items-start gap-4">
                            <span
                                :data-segment-text="'The growth in interest in food fashions started with'"
                                v-html="getHighlightedSegment('The growth in interest in food fashions started with')"
                            ></span>
                            <div id="question-31" class="inline-flex items-center gap-2">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                    >31</span
                                >
                                <input
                                    v-model="answers.q31"
                                    type="text"
                                    class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                />
                            </div>
                            <span
                                :data-segment-text="'of food being shared on social media.'"
                                v-html="getHighlightedSegment('of food being shared on social media.')"
                            ></span>
                        </li>
                        <li>
                            <span
                                :data-segment-text="'The UK food industry is constantly developing products which are new or different.'"
                                v-html="getHighlightedSegment('The UK food industry is constantly developing products which are new or different.')"
                            ></span>
                        </li>
                        <li>
                            <span
                                :data-segment-text="'Influencers on social media become ‘ambassadors’ for a brand.'"
                                v-html="getHighlightedSegment('Influencers on social media become ‘ambassadors’ for a brand.')"
                            ></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span :data-segment-text="'Sales of'" v-html="getHighlightedSegment('Sales of')"></span>
                            <div id="question-32" class="inline-flex items-center gap-2">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                    >32</span
                                >
                                <input
                                    v-model="answers.q32"
                                    type="text"
                                    class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                />
                            </div>
                            <span
                                :data-segment-text="'food brands have grown rapidly this way.'"
                                v-html="getHighlightedSegment('food brands have grown rapidly this way.')"
                            ></span>
                        </li>
                        <li>
                            <span
                                :data-segment-text="'Supermarkets track demand for ingredients on social media.'"
                                v-html="getHighlightedSegment('Supermarkets track demand for ingredients on social media.')"
                            ></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span :data-segment-text="'Famous'" v-html="getHighlightedSegment('Famous')"></span>
                            <div id="question-33" class="inline-flex items-center gap-2">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                    >33</span
                                >
                                <input
                                    v-model="answers.q33"
                                    type="text"
                                    class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                />
                            </div>
                            <span :data-segment-text="'are influential.'" v-html="getHighlightedSegment('are influential.')"></span>
                        </li>
                    </ul>

                    <div class="mt-8 border-t-2 border-dashed border-orange-200 pt-8">
                        <h4
                            class="mb-4 text-xl font-bold text-orange-800"
                            :data-segment-text="'Marketing campaigns'"
                            v-html="getHighlightedSegment('Marketing campaigns')"
                        ></h4>
                        <div class="space-y-6">
                            <div class="rounded-lg bg-white/60 p-4">
                                <h5
                                    class="text-lg font-semibold text-orange-700"
                                    :data-segment-text="'The avocado:'"
                                    v-html="getHighlightedSegment('The avocado:')"
                                ></h5>
                                <ul class="mt-2 list-none space-y-4 pl-5">
                                    <li class="flex items-center gap-3">
                                        <div id="question-34" class="inline-flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                                >34</span
                                            >
                                            <input
                                                v-model="answers.q34"
                                                type="text"
                                                class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                            />
                                        </div>
                                        <span
                                            :data-segment-text="'were invited to visit growers in South Africa.'"
                                            v-html="getHighlightedSegment('were invited to visit growers in South Africa.')"
                                        ></span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <span
                                            :data-segment-text="'Advertising focused on its'"
                                            v-html="getHighlightedSegment('Advertising focused on its')"
                                        ></span>
                                        <div id="question-35" class="inline-flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                                >35</span
                                            >
                                            <input
                                                v-model="answers.q35"
                                                type="text"
                                                class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                            />
                                        </div>
                                        <span :data-segment-text="'benefits.'" v-html="getHighlightedSegment('benefits.')"></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="rounded-lg bg-white/60 p-4">
                                <h5
                                    class="text-lg font-semibold text-orange-700"
                                    :data-segment-text="'Oat milk:'"
                                    v-html="getHighlightedSegment('Oat milk:')"
                                ></h5>
                                <ul class="mt-2 list-none space-y-4 pl-5">
                                    <li>
                                        <span
                                            :data-segment-text="'A Swedish brand’s media campaign received publicity by upsetting competitors.'"
                                            v-html="
                                                getHighlightedSegment('A Swedish brand’s media campaign received publicity by upsetting competitors.')
                                            "
                                        ></span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <span
                                            :data-segment-text="'Promotion in the USA through'"
                                            v-html="getHighlightedSegment('Promotion in the USA through')"
                                        ></span>
                                        <div id="question-36" class="inline-flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                                >36</span
                                            >
                                            <input
                                                v-model="answers.q36"
                                                type="text"
                                                class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                            />
                                        </div>
                                        <span
                                            :data-segment-text="'shops reduced the need for advertising.'"
                                            v-html="getHighlightedSegment('shops reduced the need for advertising.')"
                                        ></span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <span
                                            :data-segment-text="'It appealed to consumers who are concerned about the'"
                                            v-html="getHighlightedSegment('It appealed to consumers who are concerned about the')"
                                        ></span>
                                        <div id="question-37" class="inline-flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                                >37</span
                                            >
                                            <input
                                                v-model="answers.q37"
                                                type="text"
                                                class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                            />
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="rounded-lg bg-white/60 p-4">
                                <h5
                                    class="text-lg font-semibold text-orange-700"
                                    :data-segment-text="'Norwegian skrei:'"
                                    v-html="getHighlightedSegment('Norwegian skrei:')"
                                ></h5>
                                <ul class="mt-2 list-none space-y-4 pl-5">
                                    <li class="flex items-center gap-3">
                                        <span
                                            :data-segment-text="'has helped strengthen the'"
                                            v-html="getHighlightedSegment('has helped strengthen the')"
                                        ></span>
                                        <div id="question-38" class="inline-flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                                >38</span
                                            >
                                            <input
                                                v-model="answers.q38"
                                                type="text"
                                                class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                            />
                                        </div>
                                        <span
                                            :data-segment-text="'of Norwegian seafood.'"
                                            v-html="getHighlightedSegment('of Norwegian seafood.')"
                                        ></span>
                                    </li>
                                    <li><span :data-segment-text="'Ethical concerns'" v-html="getHighlightedSegment('Ethical concerns')"></span></li>
                                </ul>
                            </div>
                            <div class="rounded-lg bg-white/60 p-4">
                                <h5
                                    class="text-lg font-semibold text-orange-700"
                                    :data-segment-text="'Quinoa:'"
                                    v-html="getHighlightedSegment('Quinoa:')"
                                ></h5>
                                <ul class="mt-2 list-none space-y-4 pl-5">
                                    <li class="flex items-center gap-3">
                                        <span
                                            :data-segment-text="'Its success led to an increase in its'"
                                            v-html="getHighlightedSegment('Its success led to an increase in its')"
                                        ></span>
                                        <div id="question-39" class="inline-flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                                >39</span
                                            >
                                            <input
                                                v-model="answers.q39"
                                                type="text"
                                                class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                            />
                                        </div>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <span
                                            :data-segment-text="'Overuse of resources resulted in poor quality'"
                                            v-html="getHighlightedSegment('Overuse of resources resulted in poor quality')"
                                        ></span>
                                        <div id="question-40" class="inline-flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-sm font-bold text-white shadow-[0_4px_12px_rgba(249,115,22,0.3)]"
                                                >40</span
                                            >
                                            <input
                                                v-model="answers.q40"
                                                type="text"
                                                class="w-40 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500/50 sm:text-sm"
                                            />
                                        </div>
                                    </li>
                                </ul>
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
    background: #fff7ed;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f97316, #ea580c);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ea580c, #c2410c);
}
</style>
