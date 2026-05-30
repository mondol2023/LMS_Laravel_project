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

// Listening to Part 1 component
const answers = ref({
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q9: '',
    q10: '',
});

// Multiple choice answers for Q7 & Q8 (choose TWO from A-E)
const multipleAnswers = ref<{ q7_8: string[] }>({
    q7_8: [],
});

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);
    if (index > -1) {
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 2) {
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string): boolean => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string): boolean => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    return currentAnswers.length >= 2 && !currentAnswers.includes(option);
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
// Each segment covers the static text visible in the UI
const textSegments = ref([
    // Part header
    { id: 'part1-title', text: 'Part 1', offset: 0 },
    { id: 'part1-desc', text: 'Listen and answer questions 1–10.', offset: 7 },

    // Section 1 instructions
    { id: 'sec1-heading', text: 'Questions 1–6', offset: 41 },
    { id: 'sec1-inst1', text: 'Complete the form below.', offset: 55 },
    { id: 'sec1-inst2', text: 'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.', offset: 80 },
    { id: 'sec1-form-title', text: 'Application Form for use of Library Internet Service', offset: 142 },

    // Form field labels
    { id: 'lbl-family', text: 'Family name:', offset: 195 },
    { id: 'val-family', text: 'Milton', offset: 208 },
    { id: 'lbl-first', text: 'First names:', offset: 215 },
    { id: 'q1-suffix', text: 'Jayne', offset: 228 },
    { id: 'lbl-address', text: 'Address:', offset: 234 },
    { id: 'q2-suffix', text: '35 Maximilian Way Whitfield', offset: 243 },
    { id: 'lbl-postcode', text: 'Post Code:', offset: 271 },
    { id: 'lbl-occupation', text: 'Occupation:', offset: 282 },
    { id: 'val-occupation', text: 'Nurse', offset: 294 },
    { id: 'q4-prefix', text: '(works the', offset: 300 },
    { id: 'q4-suffix', text: ')', offset: 311 },
    { id: 'lbl-homephone', text: 'Home phone:', offset: 313 },
    { id: 'val-homephone', text: 'N/A', offset: 325 },
    { id: 'lbl-mobile', text: 'Mobile:', offset: 329 },
    { id: 'val-mobile', text: '0412 214 418', offset: 337 },
    { id: 'lbl-typeid', text: 'Type of ID:', offset: 350 },
    { id: 'lbl-idnum', text: 'ID number:', offset: 362 },
    { id: 'val-idnum', text: 'AZ 1985331', offset: 373 },
    { id: 'lbl-dob', text: 'Date of Birth:', offset: 384 },
    { id: 'q6-prefix', text: '25th', offset: 399 },

    // Section 2 - MCQ
    { id: 'sec2-heading', text: 'Questions 7 and 8', offset: 404 },
    { id: 'sec2-inst', text: 'Choose TWO letters, A–E.', offset: 422 },
    { id: 'sec2-question', text: 'What will the woman use the internet for?', offset: 447 },
    { id: 'opt-A', text: 'trade & exchange', offset: 489 },
    { id: 'opt-B', text: 'research', offset: 506 },
    { id: 'opt-C', text: 'email', offset: 515 },
    { id: 'opt-D', text: 'social networking', offset: 521 },
    { id: 'opt-E', text: 'job vacancies', offset: 539 },

    // Section 3 - Short answer
    { id: 'sec3-heading', text: 'Questions 9 and 10', offset: 568 },
    { id: 'sec3-inst', text: 'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.', offset: 587 },
    { id: 'q9-label', text: '9  How much does it cost to register as an internet user?', offset: 649 },
    { id: 'q10-label', text: '10  What is the maximum amount of time allowed per single daily internet session?', offset: 707 },
]);

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

// Expose methods for parent component
const getAnswers = () => {
    const allAnswers: Record<string, string> = { ...answers.value };
    allAnswers.q7 = multipleAnswers.value.q7_8[0] || '';
    allAnswers.q8 = multipleAnswers.value.q7_8[1] || '';
    return allAnswers;
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 7 || questionNumber === 8) {
        targetId = 'question-7-8';
    }
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

            if (!segmentEl) {
                return null;
            }

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) {
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
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) {
            y = padding;
        }
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

    notes.value = notes.value.filter((n) => {
        const overlaps = n.start < newEnd && n.end > newStart;
        return !overlaps;
    });

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
            deleteTooltipPosition.value = {
                x: rect.left + rect.width / 2,
                y: rect.bottom + 8,
            };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) {
            closeDeleteTooltip();
        }
        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
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

                if (y < 10) {
                    y = rect.bottom + 8;
                }

                noteTooltipPosition.value = {
                    x: rect.left + rect.width / 2,
                    y: y,
                };

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

    if (relatedTarget?.closest('.note-hover-tooltip')) {
        return;
    }

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
        <!-- Questions Section - Full Width -->
        <div class="flex  min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part1-title"
                    v-html="getHighlightedSegmentById('part1-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part1-desc"
                    v-html="getHighlightedSegmentById('part1-desc')"
                ></p>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto pb-24">
                <div class="p-2 sm:p-3">
                    <div @mouseup="handleContentTextSelect" class="p-3 select-text sm:p-4">
                        <div class="space-y-6">

                            <!-- ===== SECTION 1: Form Completion Q1-Q6 ===== -->
                            <div>
                                <!-- Section Heading -->
                                <p
                                    class="text-base font-bold text-gray-900"
                                    data-segment-id="sec1-heading"
                                    v-html="getHighlightedSegmentById('sec1-heading')"
                                ></p>
                                <p class="text-sm text-gray-700 mt-1">
                                    <span data-segment-id="sec1-inst1" v-html="getHighlightedSegmentById('sec1-inst1')"></span>
                                </p>
                                <p class="text-sm text-gray-700">
                                    <span data-segment-id="sec1-inst2" v-html="getHighlightedSegmentById('sec1-inst2')"></span>
                                </p>

                                <!-- Form Box -->
                                <div class="mt-3 ">
                                    <!-- Form Title -->
                                    <div class="bg-gray-100  px-4 py-2 text-center">
                                        <span
                                            class="font-bold text-gray-900 text-sm"
                                            data-segment-id="sec1-form-title"
                                            v-html="getHighlightedSegmentById('sec1-form-title')"
                                        ></span>
                                    </div>

                                    <!-- Form Rows -->
                                    <div class="">

                                        <!-- Family name -->
                                        <div class="flex items-center px-4 py-2.5 gap-3">
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-family"
                                                v-html="getHighlightedSegmentById('lbl-family')"
                                            ></span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="val-family"
                                                v-html="getHighlightedSegmentById('val-family')"
                                            ></span>
                                        </div>

                                        <!-- First names - Q1 -->
                                        <div
                                            class="flex flex-wrap items-center px-4 py-2.5 gap-2"
                                            @mouseenter="hoveredQuestion = 1"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-first"
                                                v-html="getHighlightedSegmentById('lbl-first')"
                                            ></span>
                                            <span id="question-1" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q1"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                    placeholder="1"
                                                    @focus="hoveredQuestion = 1"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="q1-suffix"
                                                v-html="getHighlightedSegmentById('q1-suffix')"
                                            ></span>
                                            <button
                                                v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                                @click.stop="toggleFlag(1)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Address - Q2 -->
                                        <div
                                            class="flex flex-wrap items-center px-4 py-2.5 gap-2"
                                            @mouseenter="hoveredQuestion = 2"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-address"
                                                v-html="getHighlightedSegmentById('lbl-address')"
                                            ></span>
                                            <span id="question-2" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q2"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                                    placeholder="2"
                                                    @focus="hoveredQuestion = 2"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="q2-suffix"
                                                v-html="getHighlightedSegmentById('q2-suffix')"
                                            ></span>
                                            <button
                                                v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                                @click.stop="toggleFlag(2)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Post Code - Q3 -->
                                        <div
                                            class="flex flex-wrap items-center px-4 py-2.5 gap-2"
                                            @mouseenter="hoveredQuestion = 3"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-postcode"
                                                v-html="getHighlightedSegmentById('lbl-postcode')"
                                            ></span>
                                            <span id="question-3" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q3"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                    placeholder="3"
                                                    @focus="hoveredQuestion = 3"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                                @click.stop="toggleFlag(3)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Occupation / Q4 -->
                                        <div
                                            class="flex flex-wrap items-center px-4 py-2.5 gap-2"
                                            @mouseenter="hoveredQuestion = 4"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-occupation"
                                                v-html="getHighlightedSegmentById('lbl-occupation')"
                                            ></span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="val-occupation"
                                                v-html="getHighlightedSegmentById('val-occupation')"
                                            ></span>
                                            <span
                                                class="text-sm text-gray-800 ml-1"
                                                data-segment-id="q4-prefix"
                                                v-html="getHighlightedSegmentById('q4-prefix')"
                                            ></span>
                                            <span id="question-4" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q4"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                    placeholder="4"
                                                    @focus="hoveredQuestion = 4"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="q4-suffix"
                                                v-html="getHighlightedSegmentById('q4-suffix')"
                                            ></span>
                                            <button
                                                v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                                @click.stop="toggleFlag(4)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Home phone -->
                                        <div class="flex items-center px-4 py-2.5 gap-3">
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-homephone"
                                                v-html="getHighlightedSegmentById('lbl-homephone')"
                                            ></span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="val-homephone"
                                                v-html="getHighlightedSegmentById('val-homephone')"
                                            ></span>
                                        </div>

                                        <!-- Mobile -->
                                        <div class="flex items-center px-4 py-2.5 gap-3">
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-mobile"
                                                v-html="getHighlightedSegmentById('lbl-mobile')"
                                            ></span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="val-mobile"
                                                v-html="getHighlightedSegmentById('val-mobile')"
                                            ></span>
                                        </div>

                                        <!-- Type of ID - Q5 -->
                                        <div
                                            class="flex flex-wrap items-center px-4 py-2.5 gap-2"
                                            @mouseenter="hoveredQuestion = 5"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-typeid"
                                                v-html="getHighlightedSegmentById('lbl-typeid')"
                                            ></span>
                                            <span id="question-5" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q5"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                                    placeholder="5"
                                                    @focus="hoveredQuestion = 5"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                                @click.stop="toggleFlag(5)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- ID number -->
                                        <div class="flex items-center px-4 py-2.5 gap-3">
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-idnum"
                                                v-html="getHighlightedSegmentById('lbl-idnum')"
                                            ></span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="val-idnum"
                                                v-html="getHighlightedSegmentById('val-idnum')"
                                            ></span>
                                        </div>

                                        <!-- Date of Birth - Q6 -->
                                        <div
                                            class="flex flex-wrap items-center px-4 py-2.5 gap-2"
                                            @mouseenter="hoveredQuestion = 6"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="w-36 shrink-0 text-sm font-semibold text-gray-700"
                                                data-segment-id="lbl-dob"
                                                v-html="getHighlightedSegmentById('lbl-dob')"
                                            ></span>
                                            <span
                                                class="text-sm text-gray-800"
                                                data-segment-id="q6-prefix"
                                                v-html="getHighlightedSegmentById('q6-prefix')"
                                            ></span>
                                            <span id="question-6" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q6"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                    placeholder="6"
                                                    @focus="hoveredQuestion = 6"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                                @click.stop="toggleFlag(6)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- ===== SECTION 2: Multiple Choice Q7 & Q8 ===== -->
                            <div
                                id="question-7-8"
                                class="relative p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 7"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <!-- Flag button — exact Part 3 style -->
                                <button
                                    v-show="hoveredQuestion === 7 || isQuestionFlagged(7) || isQuestionFlagged(8)"
                                    @click.stop="toggleFlag(7); toggleFlag(8);"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(7) || isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(7) || isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>

                                <!-- Heading + instruction + question — exact Part 3 nesting -->
                                <div class="mb-2">
                                    <div class="mb-2">
                                        <h3
                                            class="text-base font-bold text-gray-900 select-text"
                                            data-segment-id="sec2-heading"
                                            v-html="getHighlightedSegmentById('sec2-heading')"
                                        ></h3>
                                    </div>
                                    <div class="p-2">
                                        <p
                                            class="mb-2 text-base leading-relaxed text-gray-900 select-text"
                                            data-segment-id="sec2-inst"
                                            v-html="getHighlightedSegmentById('sec2-inst')"
                                        ></p>
                                        <p
                                            class="text-lg leading-tight font-bold text-gray-900 select-text"
                                            data-segment-id="sec2-question"
                                            v-html="getHighlightedSegmentById('sec2-question')"
                                        ></p>
                                    </div>
                                </div>

                                <!-- Checkbox options A–E — exact Part 3 style -->
                                <div class="space-y-1">
                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected('q7_8', 'A')"
                                            :disabled="isDisabled('q7_8', 'A')"
                                            @change="handleMultipleChoice('q7_8', 'A')"
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                        <span
                                            class="text-base leading-relaxed text-gray-900 select-text"
                                            data-segment-id="opt-A"
                                            v-html="getHighlightedSegmentById('opt-A')"
                                        ></span>
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected('q7_8', 'B')"
                                            :disabled="isDisabled('q7_8', 'B')"
                                            @change="handleMultipleChoice('q7_8', 'B')"
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                        <span
                                            class="text-base leading-relaxed text-gray-900 select-text"
                                            data-segment-id="opt-B"
                                            v-html="getHighlightedSegmentById('opt-B')"
                                        ></span>
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected('q7_8', 'C')"
                                            :disabled="isDisabled('q7_8', 'C')"
                                            @change="handleMultipleChoice('q7_8', 'C')"
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                        <span
                                            class="text-base leading-relaxed text-gray-900 select-text"
                                            data-segment-id="opt-C"
                                            v-html="getHighlightedSegmentById('opt-C')"
                                        ></span>
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected('q7_8', 'D')"
                                            :disabled="isDisabled('q7_8', 'D')"
                                            @change="handleMultipleChoice('q7_8', 'D')"
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                        <span
                                            class="text-base leading-relaxed text-gray-900 select-text"
                                            data-segment-id="opt-D"
                                            v-html="getHighlightedSegmentById('opt-D')"
                                        ></span>
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected('q7_8', 'E')"
                                            :disabled="isDisabled('q7_8', 'E')"
                                            @change="handleMultipleChoice('q7_8', 'E')"
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                        <span
                                            class="text-base leading-relaxed text-gray-900 select-text"
                                            data-segment-id="opt-E"
                                            v-html="getHighlightedSegmentById('opt-E')"
                                        ></span>
                                    </label>
                                </div>

                                <!-- Selected counter — exact Part 3 style -->
                                <div class="mt-2 p-2">
                                    <p class="text-sm font-medium text-gray-900">Selected: {{ multipleAnswers.q7_8.length }}/2 answers</p>
                                </div>
                            </div>

                            <!-- ===== SECTION 3: Short Answer Q9 & Q10 ===== -->
                            <div class=" pt-4">
                                <p
                                    class="text-base font-bold text-gray-900"
                                    data-segment-id="sec3-heading"
                                    v-html="getHighlightedSegmentById('sec3-heading')"
                                ></p>
                                <p class="text-sm text-gray-700 mt-1">
                                    <span data-segment-id="sec3-inst" v-html="getHighlightedSegmentById('sec3-inst')"></span>
                                </p>

                                <div class="mt-3 space-y-4">
                                    <!-- Q9 -->
                                    <div class="space-y-1.5">
                                        <p
                                            class="text-sm text-gray-800 font-medium"
                                            data-segment-id="q9-label"
                                            v-html="getHighlightedSegmentById('q9-label')"
                                        ></p>
                                        <div
                                            class="flex items-center gap-2"
                                            @mouseenter="hoveredQuestion = 9"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span id="question-9" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q9"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[160px]"
                                                    placeholder="9"
                                                    @focus="hoveredQuestion = 9"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                                @click.stop="toggleFlag(9)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q10 -->
                                    <div class="space-y-1.5">
                                        <p
                                            class="text-sm text-gray-800 font-medium"
                                            data-segment-id="q10-label"
                                            v-html="getHighlightedSegmentById('q10-label')"
                                        ></p>
                                        <div
                                            class="flex items-center gap-2"
                                            @mouseenter="hoveredQuestion = 10"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span id="question-10" class="inline-flex items-center">
                                                <input
                                                    v-model="answers.q10"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[160px]"
                                                    placeholder="10"
                                                    @focus="hoveredQuestion = 10"
                                                    @blur="hoveredQuestion = null"
                                                />
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                                @click.stop="toggleFlag(10)"
                                                class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Context Menu for Highlighting - Speech Bubble Style -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <!-- Tooltip Content -->
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <!-- Note Button -->
                    <button
                        @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note"
                    >
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"
                            />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <!-- Highlight Button -->
                    <button
                        @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight"
                    >
                        <svg
                            class="h-5 w-5 text-gray-900"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                        >
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <!-- Arrow pointing down -->
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button
                        @click="confirmDeleteHighlight"
                        type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                    >
                        <svg
                            class="h-5 w-5 text-gray-700"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
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
                :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @mouseleave="handleTooltipMouseLeave"
                @click.stop
            >
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            ></path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm wrap-break-word text-gray-700">{{ hoveredNoteText }}</span>
                    <button
                        @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                        title="Delete note"
                    >
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            ></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }"
            @click.stop
        >
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input
                    v-model="noteInputText"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote"
                    @keyup.escape="cancelNote"
                />
            </div>
            <div class="flex justify-end gap-2">
                <button
                    @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                >
                    Cancel
                </button>
                <button @click="saveNote" class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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
    0% {
        background-color: rgba(0, 0, 0, 0.15);
    }
    100% {
        background-color: rgba(0, 0, 0, 0.08);
    }
}

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #000000;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #374151;
}

/* Highlight functionality styles */
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

/* Highlight Tooltip Styles */
.highlight-tooltip .tooltip-arrow {
    position: absolute;
    left: 50%;
    bottom: -8px;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.highlight-tooltip .tooltip-arrow::before {
    content: '';
    position: absolute;
    left: -9px;
    bottom: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

/* Delete Highlight Tooltip - Arrow pointing UP */
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.1));
}

.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: '';
    position: absolute;
    left: -9px;
    top: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db;
    z-index: -1;
}

/* Note Highlight Styles */
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
}

/* Note Hover Tooltip - Arrow pointing DOWN */
.note-hover-tooltip .tooltip-arrow-down {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.note-hover-tooltip .tooltip-arrow-down::before {
    content: '';
    position: absolute;
    left: -9px;
    bottom: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

.note-hover-tooltip .note-tooltip-content {
    max-width: 240px;
}

.note-hover-tooltip .note-tooltip-icon {
    color: #6b7280;
}

.note-hover-tooltip .note-tooltip-text {
    line-height: 1.4;
}

.note-hover-tooltip .note-delete-btn {
    color: #9ca3af;
}

.note-hover-tooltip .note-delete-btn:hover {
    color: #ef4444;
}

/* Theme Support for Note Hover Tooltip - White on Black */
[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-content {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
}

[data-theme='white-on-black'] .note-hover-tooltip .tooltip-arrow-down {
    border-top-color: #1f2937 !important;
}

[data-theme='white-on-black'] .note-hover-tooltip .tooltip-arrow-down::before {
    border-top-color: #4b5563 !important;
}

[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-icon {
    color: #9ca3af !important;
}

[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-text {
    color: white !important;
}

[data-theme='white-on-black'] .note-hover-tooltip .note-delete-btn {
    color: #9ca3af !important;
}

[data-theme='white-on-black'] .note-hover-tooltip .note-delete-btn:hover {
    color: #ef4444 !important;
    background: #374151 !important;
}

/* Theme Support for Note Hover Tooltip - Yellow on Black */
[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-content {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
}

[data-theme='yellow-on-black'] .note-hover-tooltip .tooltip-arrow-down {
    border-top-color: #1f2937 !important;
}

[data-theme='yellow-on-black'] .note-hover-tooltip .tooltip-arrow-down::before {
    border-top-color: #4b5563 !important;
}

[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-icon {
    color: #facc15 !important;
}

[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-text {
    color: #facc15 !important;
}

[data-theme='yellow-on-black'] .note-hover-tooltip .note-delete-btn {
    color: #facc15 !important;
}

[data-theme='yellow-on-black'] .note-hover-tooltip .note-delete-btn:hover {
    color: #ef4444 !important;
    background: #374151 !important;
}

/* Bold placeholder styling for question inputs */
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>
