<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    fontSize?: number;
    flaggedQuestions?: Set<number>;
}

const props = withDefaults(defineProps<Props>(), {
    fontSize: 16,
    flaggedQuestions: () => new Set<number>(),
});

const emit = defineEmits<{
    toggleFlag: [questionNumber: number];
}>();

// Track hovered question for showing flag icon
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Answers for Part 2 component
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

// Drag and drop state
const dragOverQuestion = ref<number | null>(null);
const draggingOption = ref<string | null>(null);

// Options A–I with labels
const locationOptions = [
    { value: 'A', label: 'A  Bathroom' },
    { value: 'B', label: 'B  Kitchen' },
    { value: 'C', label: 'C  Cupboard' },
    { value: 'D', label: 'D  Bedroom' },
    { value: 'E', label: 'E  Living room' },
    { value: 'F', label: 'F  Hallway' },
    { value: 'G', label: 'G  Storage room' },
    { value: 'H', label: 'H  Laundry room' },
    { value: 'I', label: 'I  Garage' },
];

// Get label for a given option value (e.g. 'A' -> 'A  Bathroom')
const getLocationOptionLabel = (value: string): string => {
    const opt = locationOptions.find((o) => o.value === value);
    return opt ? opt.label : value;
};

// Compute which option values are currently placed in a drop zone (q11–q16)
const usedOptionValues = computed(() => {
    return new Set([
        answers.value.q11,
        answers.value.q12,
        answers.value.q13,
        answers.value.q14,
        answers.value.q15,
        answers.value.q16,
    ].filter(Boolean));
});

// Available options = those not yet placed
const availableOptions = computed(() =>
    locationOptions.filter((o) => !usedOptionValues.value.has(o.value))
);

// Drag handlers
const handleDragStart = (event: DragEvent, value: string) => {
    draggingOption.value = value;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'copy';
        event.dataTransfer.setData('text/plain', value);
    }
};

const handleDragOver = (event: DragEvent, questionNum: number) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'copy';
    }
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleDrop = (event: DragEvent, questionNum: number) => {
    event.preventDefault();
    const value = event.dataTransfer?.getData('text/plain') || draggingOption.value;
    if (value) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = value;
    }
    dragOverQuestion.value = null;
    draggingOption.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Delete highlight tooltip
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note tooltip (hover)
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Text segments with their cumulative offsets in the full text
const segments = [
    'Part 2',
    'Listen and answer questions 11–20.',
    'Questions 11–16',
    'Choose SIX answers from the box and write the correct letter A–I.',
    'Where can each of the following items be found?',
    'A  Bathroom',
    'B  Kitchen',
    'C  Cupboard',
    'D  Bedroom',
    'E  Living room',
    'F  Hallway',
    'G  Storage room',
    'H  Laundry room',
    'I  Garage',
    'Alarm',
    'Garage key',
    'Laundry detergent',
    'Beach towels',
    'Bath towels',
    'Light bulbs',
    'Questions 17–20',
    'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
    'Difficult parking in town at the weekend because of so many',
    'Museum is closed on',
    'Recommended places to eat:',
    'for Chinese food',
    'Pizzeria for Italian food',
    'Phone number for takeaway pizza'
];

let offset = 0;

const textSegments = ref(
    segments.map((text, i) => {
        const segment = { id: i, text, offset };
        offset += text.length + 1;
        return segment;
    })
);

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
    }

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset,
            end: h.end_offset,
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start,
            end: n.end,
            type: 'note' as const,
            color: 'blue',
            id: n.id,
            noteText: n.note,
        })),
    ];

    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = plainText;
    for (const annotation of sorted) {
        const annotationStart = Math.max(0, annotation.start - segmentOffset);
        const annotationEnd = Math.min(plainText.length, annotation.end - segmentOffset);

        if (annotationStart < annotationEnd) {
            const before = result.substring(0, annotationStart);
            const annotated = result.substring(annotationStart, annotationEnd);
            const after = result.substring(annotationEnd);

            if (annotation.type === 'note') {
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }

    return result;
};

const getAnswers = () => {
    return answers.value;
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
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
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
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
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50;
            const menuY = rect.top - menuHeight - 8;
            const viewportWidth = window.innerWidth;
            const menuWidth = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10),
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
    notes.value = notes.value.filter((n) => {
        return !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start);
    });
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;

    const modalWidth = 320;
    const modalHeight = 180;
    const padding = 10;
    const selection = window.getSelection();
    let x: number;
    let y: number;

    if (selection && selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const rect = range.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const halfWidth = modalWidth / 2;
    if (x - halfWidth < padding) x = halfWidth + padding;
    else if (x + halfWidth > viewportWidth - padding) x = viewportWidth - halfWidth - padding;

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) y = padding;
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
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

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
};

const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(idToDelete);
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find((n) => n.id === noteIdNum);
            if (note) {
                const rect = noteMark.getBoundingClientRect();
                const tooltipHeight = 50;
                let y = rect.top - tooltipHeight - 8;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = noteIdNum;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (relatedTarget?.closest('.note-hover-tooltip')) return;
    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

onMounted(async () => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mouseover', handleNoteMouseEnter);
    document.removeEventListener('mouseout', handleNoteMouseLeave);
});

defineExpose({
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
            </div>

            <!-- Questions 11-16 Section -->
            <div class="px-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></p>
            </div>

            <!-- Scrollable Questions Content -->
            <div class=" overflow-y-auto pb-24">
                <div class="p-2 sm:p-3">
                    <div @mouseup="handleContentTextSelect" class="p-3 select-text sm:p-4">
                        <div class="space-y-6">

                            <!-- ── Q11–16: Questions (left) + Options panel (right) side-by-side ── -->
                            <div class="flex gap-4 items-start">

                                <!-- LEFT: Drop Zones Q11–16 -->
                                <div class="  min-w-0 space-y-5">

                                    <!-- Q11 -->
                                    <div id="question-11" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none min-w-[30px]">11.</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="14"
                                            v-html="getHighlightedSegmentById(14)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 11 ? 'border-blue-500 bg-blue-50' : answers.q11 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 11)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 11)" @click="clearAnswer(11)"
                                            :title="answers.q11 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q11">{{ getLocationOptionLabel(answers.q11) }}</span>
                                            <span v-else class="font-bold text-gray-900">11</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                                @click.stop="toggleFlag(11)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(11) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q12 -->
                                    <div id="question-12" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none min-w-[30px]">12.</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="15"
                                            v-html="getHighlightedSegmentById(15)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 12 ? 'border-blue-500 bg-blue-50' : answers.q12 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 12)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 12)" @click="clearAnswer(12)"
                                            :title="answers.q12 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q12">{{ getLocationOptionLabel(answers.q12) }}</span>
                                            <span v-else class="font-bold text-gray-900">12</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                                @click.stop="toggleFlag(12)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(12) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q13 -->
                                    <div id="question-13" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none min-w-[30px]">13.</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="16"
                                            v-html="getHighlightedSegmentById(16)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 13 ? 'border-blue-500 bg-blue-50' : answers.q13 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 13)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 13)" @click="clearAnswer(13)"
                                            :title="answers.q13 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q13">{{ getLocationOptionLabel(answers.q13) }}</span>
                                            <span v-else class="font-bold text-gray-900">13</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                                @click.stop="toggleFlag(13)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(13) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q14 -->
                                    <div id="question-14" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none min-w-[30px]">14.</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="17"
                                            v-html="getHighlightedSegmentById(17)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 14 ? 'border-blue-500 bg-blue-50' : answers.q14 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 14)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 14)" @click="clearAnswer(14)"
                                            :title="answers.q14 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q14">{{ getLocationOptionLabel(answers.q14) }}</span>
                                            <span v-else class="font-bold text-gray-900">14</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                                @click.stop="toggleFlag(14)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q15 -->
                                    <div id="question-15" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none min-w-[30px]">15.</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="18"
                                            v-html="getHighlightedSegmentById(18)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 15 ? 'border-blue-500 bg-blue-50' : answers.q15 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 15)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 15)" @click="clearAnswer(15)"
                                            :title="answers.q15 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q15">{{ getLocationOptionLabel(answers.q15) }}</span>
                                            <span v-else class="font-bold text-gray-900">15</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                                @click.stop="toggleFlag(15)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q16 -->
                                    <div id="question-16" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none min-w-[30px]">16.</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="19"
                                            v-html="getHighlightedSegmentById(19)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 16 ? 'border-blue-500 bg-blue-50' : answers.q16 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 16)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 16)" @click="clearAnswer(16)"
                                            :title="answers.q16 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q16">{{ getLocationOptionLabel(answers.q16) }}</span>
                                            <span v-else class="font-bold text-gray-900">16</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                                @click.stop="toggleFlag(16)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                </div><!-- end LEFT drop zones -->

                                <!-- RIGHT: Draggable Option Chips (only unused ones) -->
                                <div class="shrink-0 w-36 rounded border border-gray-200 bg-gray-50 p-2 self-start sticky top-4">
                                    <p class="mb-2 text-xs font-semibold text-gray-500 leading-tight">Options</p>
                                    <div class="flex flex-col gap-1.5">
                                        <div
                                            v-for="opt in availableOptions"
                                            :key="opt.value"
                                            draggable="true"
                                            @dragstart="handleDragStart($event, opt.value)"
                                            class="cursor-grab select-none rounded border border-gray-900 bg-white px-2 py-1 text-sm font-medium text-gray-900 shadow-sm transition-all hover:bg-gray-100 active:cursor-grabbing active:opacity-60"
                                        >
                                            <span class="font-bold">{{ opt.value }}</span>
                                            <span class="ml-1 text-gray-600 text-xs">{{ opt.label.slice(2).trim() }}</span>
                                        </div>
                                        <p v-if="availableOptions.length === 0" class="text-xs text-gray-400 italic text-center py-1">All used</p>
                                    </div>
                                </div><!-- end RIGHT options panel -->

                            </div><!-- end side-by-side flex -->

                            <!-- Questions 17-20 -->
                            <div class="mt-8 border-t pt-6">
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="20"
                                    v-html="getHighlightedSegmentById(20)"></p>
                                <p class="text-sm text-gray-700 select-text mb-4" data-segment-id="21"
                                    v-html="getHighlightedSegmentById(21)"></p>

                                <div class="space-y-4 text-base leading-relaxed text-gray-800">
                                    <!-- Question 17 -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-gray-900">•</span>
                                        <span class="text-gray-800" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                        <span id="question-17" class="group relative inline-flex items-center gap-0.5"
                                            @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q17" type="text" spellcheck="false" autocomplete="off"
                                                class="question-input mx-1 min-w-[120px] text-center border border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none"
                                                placeholder="17" @focus="hoveredQuestion = 17" @blur="hoveredQuestion = null" />
                                            <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                                @click.stop="toggleFlag(17)"
                                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- Question 18 -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-gray-900">•</span>
                                        <span class="text-gray-800" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                        <span id="question-18" class="group relative inline-flex items-center gap-0.5"
                                            @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q18" type="text" spellcheck="false" autocomplete="off"
                                                class="question-input mx-1 min-w-[120px] text-center border border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none"
                                                placeholder="18" @focus="hoveredQuestion = 18" @blur="hoveredQuestion = null" />
                                            <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                                @click.stop="toggleFlag(18)"
                                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- Question 19 -->
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-gray-900">•</span>
                                            <span class="text-gray-800" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 mt-1 ml-6">
                                            <span class="text-gray-800 italic" data-segment-id="25"
                                                v-html="getHighlightedSegmentById(25)"></span>
                                            <span id="question-19" class="group relative inline-flex items-center gap-0.5"
                                                @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
                                                <input v-model="answers.q19" type="text" spellcheck="false" autocomplete="off"
                                                    class="question-input mx-1 min-w-[120px] text-center border border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none"
                                                    placeholder="19" @focus="hoveredQuestion = 19" @blur="hoveredQuestion = null" />
                                                <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                                    @click.stop="toggleFlag(19)"
                                                    class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 mt-1 ml-12">
                                            <span class="text-gray-800" data-segment-id="26"
                                                v-html="getHighlightedSegmentById(26)"></span>
                                        </div>
                                    </div>

                                    <!-- Question 20 -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-gray-900">•</span>
                                        <span class="text-gray-800" data-segment-id="27"
                                            v-html="getHighlightedSegmentById(27)"></span>
                                        <span id="question-20" class="group relative inline-flex items-center gap-0.5"
                                            @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q20" type="text" spellcheck="false" autocomplete="off"
                                                class="question-input mx-1 min-w-[120px] text-center border border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none"
                                                placeholder="20" @focus="hoveredQuestion = 20" @blur="hoveredQuestion = null" />
                                            <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                                @click.stop="toggleFlag(20)"
                                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
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
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                        <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Hover Tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop
            >
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm wrap-break-word text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div v-if="showNoteInput"
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.highlight-question {
    background-color: rgba(0, 0, 0, 0.08);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightFade 1.5s ease-out;
}

@keyframes highlightFade {
    0% { background-color: rgba(0, 0, 0, 0.15); }
    100% { background-color: rgba(0, 0, 0, 0.08); }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #000000; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #374151; }

.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
}

.highlight-nocolor { background-color: transparent; }
.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

/* Highlight Tooltip */
.highlight-tooltip .tooltip-arrow {
    position: absolute;
    left: 50%;
    bottom: -8px;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: '';
    position: absolute;
    left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

/* Delete Tooltip Arrow UP */
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: '';
    position: absolute;
    left: -9px; top: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db;
    z-index: -1;
}

/* Note highlight */
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
}

/* Note Hover Tooltip Arrow DOWN */
.note-hover-tooltip .tooltip-arrow-down {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: '';
    position: absolute;
    left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}
.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-delete-btn:hover { color: #ef4444; }

/* Theme: white-on-black */
[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-content { background: #1f2937 !important; border-color: #4b5563 !important; }
[data-theme='white-on-black'] .note-hover-tooltip .tooltip-arrow-down { border-top-color: #1f2937 !important; }
[data-theme='white-on-black'] .note-hover-tooltip .tooltip-arrow-down::before { border-top-color: #4b5563 !important; }
[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-text { color: white !important; }
[data-theme='white-on-black'] .note-hover-tooltip .note-delete-btn:hover { color: #ef4444 !important; background: #374151 !important; }

/* Theme: yellow-on-black */
[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-content { background: #1f2937 !important; border-color: #4b5563 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .tooltip-arrow-down { border-top-color: #1f2937 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .tooltip-arrow-down::before { border-top-color: #4b5563 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-text { color: #facc15 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .note-delete-btn { color: #facc15 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .note-delete-btn:hover { color: #ef4444 !important; background: #374151 !important; }

/* Input placeholders */
.question-input::placeholder { font-weight: 700; color: #374151; }

.min-w-\[30px\] { min-width: 30px; }
</style>