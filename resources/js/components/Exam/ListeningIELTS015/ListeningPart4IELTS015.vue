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
    { text: 'Questions 31-40', offset: 10 },
    { text: 'Choose the correct letter, A, B or C.', offset: 26 },
    { text: 'During the first week of term, students are invited to', offset: 65 },
    { text: 'A be shown round the library by the librarian', offset: 120 },
    { text: 'B listen to descriptions of library resources', offset: 164 },
    { text: 'C do an intensive course in the computer centre', offset: 207 },
    { text: 'The speaker warns the students that', offset: 254 },
    { text: 'A internet materials can be unreliable', offset: 289 },
    { text: 'B downloaded information must be acknowledged', offset: 327 },
    { text: 'C computer access may be limited at times', offset: 372 },
    { text: 'The library is acquiring more CDs as a resource because', offset: 415 },
    { text: 'A they are a cheap source of information', offset: 471 },
    { text: 'B they take up very little space', offset: 510 },
    { text: 'C they are more up to date than the reference books', offset: 541 },
    { text: 'Students are encouraged to use journals online because', offset: 593 },
    { text: 'A the articles do not need to be returned to the shelves', offset: 647 },
    { text: 'B reading online is cheaper than photocopying articles', offset: 704 },
    { text: 'C the stock of printed articles is to be reduced', offset: 758 },
    { text: 'Why might some students continue to use reference books?', offset: 808 },
    { text: 'A they can be taken away from the library', offset: 865 },
    { text: 'B they provide information unavailable anywhere else', offset: 906 },
    { text: 'C they can be borrowed for an extended loan period', offset: 957 },
    { text: 'What is the responsibility of the training supervisor?', offset: 1008 },
    { text: 'A to supervise and support library staff', offset: 1062 },
    { text: 'B to provide orientation to the library facilities', offset: 1101 },
    { text: 'C to identify needs and inform section managers', offset: 1151 },
    { text: 'Questions 37-40', offset: 1198 },
    { text: 'Which section of the university will help postgraduate students with their dissertations in the following ways?', offset: 1214 },
    { text: "A the postgraduate's own department or tutor", offset: 1326 },
    { text: 'B library staff', offset: 1371 },
    { text: 'C another section of the university', offset: 1387 },
    { text: 'Write the correct letter, A, B or C, next to questions 37-40.', offset: 1422 },
    { text: 'training in specialised computer programs', offset: 1486 },
    { text: 'advising on bibliography presentation', offset: 1527 },
    { text: 'checking the draft of the dissertation', offset: 1564 },
    { text: 'providing language support', offset: 1603 },
]);

const questions = ref([
    {
        num: 31,
        text: 'During the first week of term, students are invited to',
        options: [
            { label: 'A', text: 'be shown round the library by the librarian' },
            { label: 'B', text: 'listen to descriptions of library resources' },
            { label: 'C', text: 'do an intensive course in the computer centre' },
        ],
    },
    {
        num: 32,
        text: 'The speaker warns the students that',
        options: [
            { label: 'A', text: 'internet materials can be unreliable' },
            { label: 'B', text: 'downloaded information must be acknowledged' },
            { label: 'C', text: 'computer access may be limited at times' },
        ],
    },
    {
        num: 33,
        text: 'The library is acquiring more CDs as a resource because',
        options: [
            { label: 'A', text: 'they are a cheap source of information' },
            { label: 'B', text: 'they take up very little space' },
            { label: 'C', text: 'they are more up to date than the reference books' },
        ],
    },
    {
        num: 34,
        text: 'Students are encouraged to use journals online because',
        options: [
            { label: 'A', text: 'the articles do not need to be returned to the shelves' },
            { label: 'B', text: 'reading online is cheaper than photocopying articles' },
            { label: 'C', text: 'the stock of printed articles is to be reduced' },
        ],
    },
    {
        num: 35,
        text: 'Why might some students continue to use reference books?',
        options: [
            { label: 'A', text: 'they can be taken away from the library' },
            { label: 'B', text: 'they provide information unavailable anywhere else' },
            { label: 'C', text: 'they can be borrowed for an extended loan period' },
        ],
    },
    {
        num: 36,
        text: 'What is the responsibility of the training supervisor?',
        options: [
            { label: 'A', text: 'to supervise and support library staff' },
            { label: 'B', text: 'to provide orientation to the library facilities' },
            { label: 'C', text: 'to identify needs and inform section managers' },
        ],
    },
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

watch(answers, saveAnswers, { deep: true });

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
        if (!selection || selection.rangeCount === 0 || !selection.toString().trim()) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            const container = node.nodeType === Node.ELEMENT_NODE ? node : node.parentNode;
            const segmentEl = (container as Element).closest('[data-segment-text]');

            if (!segmentEl) return null;

            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) return null;

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
        if (rect.width > 0 || rect.height > 0) {
            contextMenuPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 5 };
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
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    notes.value.push({
        id: Date.now(),
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
    if (showContextMenu.value) showContextMenu.value = false;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) showContextMenu.value = false;
};

onMounted(async () => {
    await loadSavedAnswers();
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    if (autoSaver.value) autoSaver.value.stop();
});

defineExpose({ getAnswers, scrollToQuestion, scrollToHighlight, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <div class="mb-20 flex min-h-screen w-full flex-col rounded-2xl bg-white shadow-2xl">
            <div class="sticky top-0 z-10 flex-shrink-0 border-b border-gray-200 bg-white p-3 sm:p-4 md:p-5 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-orange-500 to-red-600 sm:h-9 sm:w-9 md:h-10 md:w-10"
                    >
                        <svg class="h-4 w-4 text-white sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm">
                            <span :data-segment-text="'Section 4'" v-html="getHighlightedSegment('Section 4')"></span>:
                            <span :data-segment-text="'Questions 31-40'" v-html="getHighlightedSegment('Questions 31-40')"></span>
                        </p>
                        <p class="text-xs text-gray-600">
                            <span
                                :data-segment-text="'Choose the correct letter, A, B or C.'"
                                v-html="getHighlightedSegment('Choose the correct letter, A, B or C.')"
                            ></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto bg-orange-50/50 p-4 sm:p-6 md:p-8">
                <div class="mx-auto space-y-8">
                    <section
                        v-for="q in questions"
                        :key="q.num"
                        :id="`question-${q.num}`"
                        class="rounded-2xl border-2 border-orange-200 bg-white p-6 shadow-lg transition-shadow hover:shadow-xl"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-[0_4px_15px_rgba(249,115,22,0.4)]"
                            >
                                {{ q.num }}
                            </div>
                            <div class="flex-1">
                                <p class="text-lg font-semibold text-gray-800" :data-segment-text="q.text" v-html="getHighlightedSegment(q.text)"></p>
                                <div class="mt-4 space-y-3">
                                    <label
                                        v-for="option in q.options"
                                        :key="option.label"
                                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-transparent p-3 transition-all hover:border-orange-200 hover:bg-orange-50"
                                    >
                                        <input
                                            type="radio"
                                            :name="`q${q.num}`"
                                            :value="option.label"
                                            v-model="answers[`q${q.num}`]"
                                            class="h-5 w-5 border-gray-300 text-orange-600 focus:ring-orange-500"
                                        />
                                        <span
                                            class="font-medium"
                                            :data-segment-text="`${option.label} ${option.text}`"
                                            v-html="getHighlightedSegment(`${option.label} ${option.text}`)"
                                        ></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        id="questions-37-40"
                        class="rounded-2xl border-2 border-orange-200 bg-white p-6 shadow-lg transition-shadow hover:shadow-xl"
                    >
                        <h3
                            class="text-lg font-semibold text-gray-800"
                            :data-segment-text="'Questions 37-40'"
                            v-html="getHighlightedSegment('Questions 37-40')"
                        ></h3>
                        <p
                            class="mt-2 text-gray-600"
                            :data-segment-text="'Which section of the university will help postgraduate students with their dissertations in the following ways?'"
                            v-html="
                                getHighlightedSegment(
                                    'Which section of the university will help postgraduate students with their dissertations in the following ways?',
                                )
                            "
                        ></p>

                        <div class="mt-4 rounded-lg border border-orange-200 bg-orange-50/50 p-4">
                            <ul class="space-y-2">
                                <li
                                    :data-segment-text="'A the postgraduate\'s own department or tutor'"
                                    v-html="getHighlightedSegment('A the postgraduate\'s own department or tutor')"
                                ></li>
                                <li :data-segment-text="'B library staff'" v-html="getHighlightedSegment('B library staff')"></li>
                                <li
                                    :data-segment-text="'C another section of the university'"
                                    v-html="getHighlightedSegment('C another section of the university')"
                                ></li>
                            </ul>
                        </div>

                        <p
                            class="mt-4 text-sm font-semibold text-gray-800"
                            :data-segment-text="'Write the correct letter, A, B or C, next to questions 37-40.'"
                            v-html="getHighlightedSegment('Write the correct letter, A, B or C, next to questions 37-40.')"
                        ></p>

                        <div class="mt-4 space-y-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-[0_4px_15px_rgba(249,115,22,0.4)]"
                                >
                                    37
                                </div>
                                <p
                                    class="flex-1 font-medium"
                                    :data-segment-text="'training in specialised computer programs'"
                                    v-html="getHighlightedSegment('training in specialised computer programs')"
                                ></p>
                                <select
                                    v-model="answers.q37"
                                    class="w-24 rounded-md border-2 border-orange-300 bg-white px-3 py-2 text-center font-bold focus:border-orange-500 focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-[0_4px_15px_rgba(249,115,22,0.4)]"
                                >
                                    38
                                </div>
                                <p
                                    class="flex-1 font-medium"
                                    :data-segment-text="'advising on bibliography presentation'"
                                    v-html="getHighlightedSegment('advising on bibliography presentation')"
                                ></p>
                                <select
                                    v-model="answers.q38"
                                    class="w-24 rounded-md border-2 border-orange-300 bg-white px-3 py-2 text-center font-bold focus:border-orange-500 focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-[0_4px_15px_rgba(249,115,22,0.4)]"
                                >
                                    39
                                </div>
                                <p
                                    class="flex-1 font-medium"
                                    :data-segment-text="'checking the draft of the dissertation'"
                                    v-html="getHighlightedSegment('checking the draft of the dissertation')"
                                ></p>
                                <select
                                    v-model="answers.q39"
                                    class="w-24 rounded-md border-2 border-orange-300 bg-white px-3 py-2 text-center font-bold focus:border-orange-500 focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-[0_4px_15px_rgba(249,115,22,0.4)]"
                                >
                                    40
                                </div>
                                <p
                                    class="flex-1 font-medium"
                                    :data-segment-text="'providing language support'"
                                    v-html="getHighlightedSegment('providing language support')"
                                ></p>
                                <select
                                    v-model="answers.q40"
                                    class="w-24 rounded-md border-2 border-orange-300 bg-white px-3 py-2 text-center font-bold focus:border-orange-500 focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

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
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-orange-50 transition-all hover:scale-110 hover:bg-orange-100"
                            title="Add Note"
                        >
                            <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="fixed z-[9999] w-80 rounded-lg border-2 border-orange-300 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded-lg border-2 border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500 focus:outline-none"
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
                        class="rounded-md bg-orange-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-orange-700"
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
</style>
