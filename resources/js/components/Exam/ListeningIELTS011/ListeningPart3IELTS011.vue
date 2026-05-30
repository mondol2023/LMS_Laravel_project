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

// Listening Section 3 answers (Q21–30)
const answers = ref({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
    q27: '',
    q28: '',
    q29: '',
    q30: '',
});

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
const textSegments = ref([
    // Part header box
    { id: 'part3-title', text: 'Part 3', offset: 0 },
    { id: 'part3-desc', text: 'Listen and answer questions 21–30.', offset: 9 },
    // Q21-22 instruction
    { id: 's3-q-range1', text: 'Questions 21 and 22', offset: 44 },
    { id: 's3-inst1', text: 'Choose the correct letter, A, B or C.', offset: 64 },
    // Q21
    { id: 's3-q21-stem', text: '21. In her home country, Kira had', offset: 102 },
    { id: 's3-q21-a', text: 'A completed a course', offset: 136 },
    { id: 's3-q21-b', text: 'B done two years of a course', offset: 157 },
    { id: 's3-q21-c', text: 'C found her course difficult', offset: 186 },
    // Q22
    { id: 's3-q22-stem', text: '22. To succeed with assignments, Kira had to', offset: 215 },
    { id: 's3-q22-a', text: 'A read faster', offset: 260 },
    { id: 's3-q22-b', text: 'B write faster', offset: 274 },
    { id: 's3-q22-c', text: 'C change her way of thinking', offset: 289 },
    // Q23-25 instruction
    { id: 's3-q-range2', text: 'Questions 23–25', offset: 318 },
    { id: 's3-inst2', text: 'Complete the sentences below.', offset: 334 },
    { id: 's3-inst3', text: 'Write ', offset: 364 },
    { id: 's3-inst3b', text: 'ONE WORD ONLY', offset: 370 },
    { id: 's3-inst3c', text: ' for each answer.', offset: 383 },
    // Q23
    { id: 's3-q23-pre', text: '• Kira says that lecturers are easier to', offset: 401 },
    { id: 's3-q23-post', text: 'than those in her home country.', offset: 443 },
    // Q24
    { id: 's3-q24-pre', text: '• Paul suggests that Kira may be more', offset: 475 },
    { id: 's3-q24-post', text: 'than when she was studying before.', offset: 515 },
    // Q25
    { id: 's3-q25-pre', text: '• Kira says that students want to discuss things that worry them or that', offset: 550 },
    { id: 's3-q25-post', text: 'them very much.', offset: 624 },
    // Q26-30 instruction
    { id: 's3-q-range3', text: 'Questions 26–30', offset: 640 },
    { id: 's3-inst4', text: 'Answer the questions below.', offset: 656 },
    { id: 's3-inst5', text: 'Write ', offset: 684 },
    { id: 's3-inst5b', text: 'NO MORE THAN THREE WORDS AND/OR A NUMBER', offset: 690 },
    { id: 's3-inst5c', text: ' for each answer.', offset: 730 },
    // Q26
    { id: 's3-q26-stem', text: '• How did the students do their practical sessions?', offset: 748 },
    // Q27
    { id: 's3-q27-stem', text: '• In the second semester how often did Kira work in a hospital?', offset: 801 },
    // Q28
    { id: 's3-q28-stem', text: '• How much full-time work did Kira do during the year?', offset: 867 },
    // Q29
    { id: 's3-q29-stem', text: '• Having completed the year, how does Kira feel?', offset: 924 },
    // Q30
    { id: 's3-q30-stem', text: '• In addition to the language, what do overseas students need to become familiar with?', offset: 975 },
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
    return answers.value;
};

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 rounded border border-gray-200 px-4 py-3" style="background-color: #F1F2EC;"
                @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"></p>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto pb-24">
                <div class="p-2 sm:p-3">
                    <div @mouseup="handleContentTextSelect" class="p-3 select-text sm:p-4">
                        <div class="space-y-6">

                            <!-- ===== Q21–22: MCQ Section ===== -->
                            <!-- Instructions -->
                            <div class="shrink-0 px-0 pb-1" @mouseup="handleContentTextSelect">
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="s3-q-range1"
                                    v-html="getHighlightedSegmentById('s3-q-range1')"></p>
                                <p class="text-sm text-gray-700 select-text">
                                    <span data-segment-id="s3-inst1"
                                        v-html="getHighlightedSegmentById('s3-inst1')"></span>
                                </p>
                            </div>
                            <!-- Q21 - Fixed Version -->
                            <div class="space-y-2" @mouseenter="hoveredQuestion = 21"
                                @mouseleave="hoveredQuestion = null">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900 select-text flex-1">
                                        <span data-segment-id="s3-q21-stem"
                                            v-html="getHighlightedSegmentById('s3-q21-stem')"></span>
                                    </p>
                                    <div class="relative inline-block w-7 h-7 flex-shrink-0">
                                        <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                            @click.stop="toggleFlag(21)"
                                            class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-1 pl-4">
                                    <!-- Option A -->
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input v-model="answers.q21" type="radio" value="A"
                                            class="h-4 w-4 border-gray-400 text-gray-900 focus:ring-gray-500 cursor-pointer" />
                                        <span class="text-sm text-gray-800 select-text" data-segment-id="s3-q21-a"
                                            v-html="getHighlightedSegmentById('s3-q21-a')"></span>
                                    </label>
                                    <!-- Option B -->
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input v-model="answers.q21" type="radio" value="B"
                                            class="h-4 w-4 border-gray-400 text-gray-900 focus:ring-gray-500 cursor-pointer" />
                                        <span class="text-sm text-gray-800 select-text" data-segment-id="s3-q21-b"
                                            v-html="getHighlightedSegmentById('s3-q21-b')"></span>
                                    </label>
                                    <!-- Option C -->
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input v-model="answers.q21" type="radio" value="C"
                                            class="h-4 w-4 border-gray-400 text-gray-900 focus:ring-gray-500 cursor-pointer" />
                                        <span class="text-sm text-gray-800 select-text" data-segment-id="s3-q21-c"
                                            v-html="getHighlightedSegmentById('s3-q21-c')"></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Q22 - Fixed Version -->
                            <div class="space-y-2" @mouseenter="hoveredQuestion = 22"
                                @mouseleave="hoveredQuestion = null">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900 select-text flex-1">
                                        <span data-segment-id="s3-q22-stem"
                                            v-html="getHighlightedSegmentById('s3-q22-stem')"></span>
                                    </p>
                                    <div class="relative inline-block w-7 h-7 flex-shrink-0">
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-1 pl-4">
                                    <!-- Option A -->
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input v-model="answers.q22" type="radio" value="A"
                                            class="h-4 w-4 border-gray-400 text-gray-900 focus:ring-gray-500 cursor-pointer" />
                                        <span class="text-sm text-gray-800 select-text" data-segment-id="s3-q22-a"
                                            v-html="getHighlightedSegmentById('s3-q22-a')"></span>
                                    </label>
                                    <!-- Option B -->
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input v-model="answers.q22" type="radio" value="B"
                                            class="h-4 w-4 border-gray-400 text-gray-900 focus:ring-gray-500 cursor-pointer" />
                                        <span class="text-sm text-gray-800 select-text" data-segment-id="s3-q22-b"
                                            v-html="getHighlightedSegmentById('s3-q22-b')"></span>
                                    </label>
                                    <!-- Option C -->
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input v-model="answers.q22" type="radio" value="C"
                                            class="h-4 w-4 border-gray-400 text-gray-900 focus:ring-gray-500 cursor-pointer" />
                                        <span class="text-sm text-gray-800 select-text" data-segment-id="s3-q22-c"
                                            v-html="getHighlightedSegmentById('s3-q22-c')"></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Q23 - Fixed Version -->
                            <div class="flex flex-wrap items-center gap-1 text-sm leading-relaxed text-gray-800"
                                @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                <span class="font-semibold" data-segment-id="s3-q23-pre"
                                    v-html="getHighlightedSegmentById('s3-q23-pre')"></span>
                                <input v-model="answers.q23" type="text" spellcheck="false" autocomplete="off"
                                    class="question-input mx-1 min-w-[100px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[140px]"
                                    placeholder="23" @focus="hoveredQuestion = 23" @blur="hoveredQuestion = null" />
                                <span data-segment-id="s3-q23-post"
                                    v-html="getHighlightedSegmentById('s3-q23-post')"></span>
                                <div class="relative inline-block w-7 h-7">
                                    <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                        @click.stop="toggleFlag(23)"
                                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Q24 - Fixed Version -->
                            <div class="flex flex-wrap items-center gap-1 text-sm leading-relaxed text-gray-800"
                                @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                <span class="font-semibold" data-segment-id="s3-q24-pre"
                                    v-html="getHighlightedSegmentById('s3-q24-pre')"></span>
                                <input v-model="answers.q24" type="text" spellcheck="false" autocomplete="off"
                                    class="question-input mx-1 min-w-[100px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[140px]"
                                    placeholder="24" @focus="hoveredQuestion = 24" @blur="hoveredQuestion = null" />
                                <span data-segment-id="s3-q24-post"
                                    v-html="getHighlightedSegmentById('s3-q24-post')"></span>
                                <div class="relative inline-block w-7 h-7">
                                    <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                        @click.stop="toggleFlag(24)"
                                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Q25 - Fixed Version -->
                            <div class="flex flex-wrap items-center gap-1 text-sm leading-relaxed text-gray-800"
                                @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                <span class="font-semibold" data-segment-id="s3-q25-pre"
                                    v-html="getHighlightedSegmentById('s3-q25-pre')"></span>
                                <input v-model="answers.q25" type="text" spellcheck="false" autocomplete="off"
                                    class="question-input mx-1 min-w-[100px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[140px]"
                                    placeholder="25" @focus="hoveredQuestion = 25" @blur="hoveredQuestion = null" />
                                <span data-segment-id="s3-q25-post"
                                    v-html="getHighlightedSegmentById('s3-q25-post')"></span>
                                <div class="relative inline-block w-7 h-7">
                                    <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="toggleFlag(25)"
                                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- ===== Q26–30: Short Answer ===== -->
                            <!-- Instructions -->
                            <div class="shrink-0 px-0 pb-1 pt-2" @mouseup="handleContentTextSelect">
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="s3-q-range3"
                                    v-html="getHighlightedSegmentById('s3-q-range3')"></p>
                                <p class="text-sm text-gray-700 select-text">
                                    <span data-segment-id="s3-inst4"
                                        v-html="getHighlightedSegmentById('s3-inst4')"></span>
                                </p>
                                <p class="text-sm text-gray-700 select-text">
                                    <span data-segment-id="s3-inst5"
                                        v-html="getHighlightedSegmentById('s3-inst5')"></span><span class="font-bold"
                                        data-segment-id="s3-inst5b"
                                        v-html="getHighlightedSegmentById('s3-inst5b')"></span><span
                                        data-segment-id="s3-inst5c"
                                        v-html="getHighlightedSegmentById('s3-inst5c')"></span>
                                </p>
                            </div>

                            <!-- Q26 -->
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-900 select-text">
                                    <span data-segment-id="s3-q26-stem"
                                        v-html="getHighlightedSegmentById('s3-q26-stem')"></span>
                                </p>
                                <div class="flex items-center gap-1 pl-4">
                                    <span id="question-26" class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <input v-model="answers.q26" type="text" spellcheck="false" autocomplete="off"
                                            class="question-input mx-1 min-w-[160px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[200px]"
                                            placeholder="26" @focus="hoveredQuestion = 26"
                                            @blur="hoveredQuestion = null" />
                                        <div class="relative inline-block w-7 h-7 ml-2">
                                            <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                                @click.stop="toggleFlag(26)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </span>
                                </div>
                            </div>

                            <!-- Q27 -->
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-900 select-text">
                                    <span data-segment-id="s3-q27-stem"
                                        v-html="getHighlightedSegmentById('s3-q27-stem')"></span>
                                </p>
                                <div class="flex items-center gap-1 pl-4">
                                    <span id="question-27" class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                        <input v-model="answers.q27" type="text" spellcheck="false" autocomplete="off"
                                            class="question-input mx-1 min-w-[160px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[200px]"
                                            placeholder="27" @focus="hoveredQuestion = 27"
                                            @blur="hoveredQuestion = null" />
                                        <div class="relative inline-block w-7 h-7 ml-2">
                                            <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                                @click.stop="toggleFlag(27)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </span>
                                </div>
                            </div>

                            <!-- Q28 -->
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-900 select-text">
                                    <span data-segment-id="s3-q28-stem"
                                        v-html="getHighlightedSegmentById('s3-q28-stem')"></span>
                                </p>
                                <div class="flex items-center gap-1 pl-4">
                                    <span id="question-28" class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                        <input v-model="answers.q28" type="text" spellcheck="false" autocomplete="off"
                                            class="question-input mx-1 min-w-[160px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[200px]"
                                            placeholder="28" @focus="hoveredQuestion = 28"
                                            @blur="hoveredQuestion = null" />
                                        <div class="relative inline-block w-7 h-7 ml-2">
                                            <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                                @click.stop="toggleFlag(28)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </span>
                                </div>
                            </div>

                            <!-- Q29 -->
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-900 select-text">
                                    <span data-segment-id="s3-q29-stem"
                                        v-html="getHighlightedSegmentById('s3-q29-stem')"></span>
                                </p>
                                <div class="flex items-center gap-1 pl-4">
                                    <span id="question-29" class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                        <input v-model="answers.q29" type="text" spellcheck="false" autocomplete="off"
                                            class="question-input mx-1 min-w-[160px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[200px]"
                                            placeholder="29" @focus="hoveredQuestion = 29"
                                            @blur="hoveredQuestion = null" />
                                        <div class="relative inline-block w-7 h-7 ml-2">
                                            <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                                @click.stop="toggleFlag(29)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </span>
                                </div>
                            </div>

                            <!-- Q30 -->
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-900 select-text">
                                    <span data-segment-id="s3-q30-stem"
                                        v-html="getHighlightedSegmentById('s3-q30-stem')"></span>
                                </p>
                                <div class="flex items-center gap-1 pl-4">
                                    <span id="question-30" class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                        <input v-model="answers.q30" type="text" spellcheck="false" autocomplete="off"
                                            class="question-input mx-1 min-w-[160px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[200px]"
                                            placeholder="30" @focus="hoveredQuestion = 30"
                                            @blur="hoveredQuestion = null" />
                                        <div class="relative inline-block w-7 h-7 ml-2">
                                            <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                                @click.stop="toggleFlag(30)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </span>
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
            <div class="highlight-tooltip pointer-events-auto fixed z-9999" :style="{
                left: `${contextMenuPosition.x}px`,
                top: `${contextMenuPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @click.stop>
                <!-- Tooltip Content -->
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <!-- Note Button -->
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <!-- Highlight Button -->
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
                <!-- Arrow pointing down -->
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-9999" :style="{
                left: `${deleteTooltipPosition.x}px`,
                top: `${deleteTooltipPosition.y}px`,
                transform: 'translateX(-50%)',
            }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                    @click.stop>
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
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{
                left: `${noteTooltipPosition.x}px`,
                top: `${noteTooltipPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div
                    class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm wrap-break-word text-gray-700">{{
                        hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                        title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div v-if="showNoteInput"
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl" :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @click.stop>
            <div class="mb-3">
                <p
                    class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
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