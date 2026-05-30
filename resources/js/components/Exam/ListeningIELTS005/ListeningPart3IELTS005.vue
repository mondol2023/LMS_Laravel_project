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

// Listening Part 1 component
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

const textSegments = ref([
    // Part header box text segments
    { id: 'part1-title', text: 'Part 3', offset: 0 },
    { id: 'part1-desc', text: 'Listen and answer questions 21–30.', offset: 6 },
    // Instructions text segments
    { id: 's3_h_1', text: 'QUESTIONS 21-30', offset: 0 },
    { id: 's3_h_2', text: 'Choose the correct answer for each question.', offset: 16 },
    { id: 's3_q_1', text: 'Questions 21–23', offset: 59 },
    { id: 's3_q_2', text: 'Answer the questions below.', offset: 76 },
    { id: 's3_q_3', text: 'Write NO MORE THAN THREE WORDS for each answer.', offset: 104 },
    { id: 's3_q_4', text: 'What do Sharon and Xiao Li agree was the strongest aspect of their presentation?', offset: 155 },
    { id: 's3_q_5', text: 'Which part of their presentation was Xiao Li least happy with?', offset: 239 },
    { id: 's3_q_6', text: 'Which section does Sharon feel they should have discussed in more depth?', offset: 305 },
    { id: 's3_q_7', text: 'Presentation Feedback', offset: 381 },
    { id: 's3_q_8', text: 'Questions 24 – 26', offset: 403 },
    { id: 's3_q_9', text: 'Choose the correct letter,', offset: 422 },
    { id: 's3_q_10', text: 'A, B, or C', offset: 450 },
    { id: 's3_q_11', text: 'Write your answers in boxes 24 – 26 on your answer sheet.', offset: 461 },
    { id: 's3_q_12', text: '24. Sharon and Xiao Li were surprised when the class said', offset: 521 },
    { id: 's3_q_13', text: 'A. they spoke too quickly.', offset: 577 },
    { id: 's3_q_14', text: 'B. they included too much information.', offset: 604 },
    { id: 's3_q_15', text: 'C. their talk was not well organised.', offset: 642 },
    { id: 's3_q_16', text: '25. The class gave Sharon and Xiao Li conflicting feedback on their', offset: 681 },
    { id: 's3_q_17', text: 'A. timing.', offset: 746 },
    { id: 's3_q_18', text: 'B. use of visuals.', offset: 757 },
    { id: 's3_q_19', text: 'C. use of eye contact.', offset: 777 },
    { id: 's3_q_20', text: '26. The class thought that the presentation was different from the others because', offset: 801 },
    { id: 's3_q_21', text: 'A. the analysis was more detailed.', offset: 880 },
    { id: 's3_q_22', text: 'B. the data collection was more wide-ranging.', offset: 915 },
    { id: 's3_q_23', text: 'C. the background reading was more extensive.', offset: 962 },
    { id: 's3_q_24', text: 'Questions 27', offset: 1009 },
    { id: 's3_q_25', text: 'Which bar chart represents the marks given by the tutor?', offset: 1022 },
    { id: 's3_q_26', text: 'A. content.', offset: 1081 },
    { id: 's3_q_27', text: 'B. structure.', offset: 1093 },
    { id: 's3_q_28', text: 'C. technique.', offset: 1107 },
    { id: 's3_q_29', text: 'Questions 28–30', offset: 1120 },
    { id: 's3_q_30', text: 'Complete the sentences below.', offset: 1137 },
    { id: 's3_q_31', text: 'Write ONE WORD ONLY for each answer.', offset: 1167 },
    { id: 's3_q_32', text: 'The tutor says that the', offset: 1205 },
    { id: 's3_q_33', text: 'of the presentation seemed rather sudden.', offset: 1230 },
    { id: 's3_q_34', text: 'The tutor praises the students’ discussion of the', offset: 1272 },
    { id: 's3_q_35', text: 'of their results.', offset: 1324 },
    { id: 's3_q_36', text: 'The tutor suggests that they could extend the', offset: 1344 },
    { id: 's3_q_37', text: 'review in their report.', offset: 1389 },
]);

// Helper to apply highlights to text that may contain HTML
// Maps plain text positions to HTML positions, preserving tags
const applyHighlightsToHtml = (
    htmlText: string,
    plainText: string,
    highlightsToApply: Array<{ id: number; color: string; start: number; end: number }>,
): string => {
    if (highlightsToApply.length === 0) return htmlText;

    // Build a mapping from plain text index to HTML index
    // plainToHtml[i] = the HTML index where plain text character i starts
    const plainToHtml: number[] = [];
    let plainIndex = 0;
    let inTag = false;

    for (let htmlIndex = 0; htmlIndex < htmlText.length; htmlIndex++) {
        const char = htmlText[htmlIndex];
        if (char === '<') {
            inTag = true;
        } else if (char === '>') {
            inTag = false;
        } else if (!inTag) {
            plainToHtml[plainIndex] = htmlIndex;
            plainIndex++;
        }
    }
    // Add end position
    plainToHtml[plainIndex] = htmlText.length;

    // Sort highlights by start position descending to apply from end to start
    const sorted = [...highlightsToApply].sort((a, b) => b.start - a.start);

    let result = htmlText;

    for (const highlight of sorted) {
        const startHtml = plainToHtml[highlight.start];
        const endHtml = plainToHtml[highlight.end] !== undefined ? plainToHtml[highlight.end] : plainToHtml[plainIndex];

        if (startHtml !== undefined && endHtml !== undefined && startHtml < endHtml) {
            const markStart = `<mark class="highlight-${highlight.color}" data-highlight-id="${highlight.id}">`;
            const markEnd = '</mark>';

            result = result.substring(0, startHtml) + markStart + result.substring(startHtml, endHtml) + markEnd + result.substring(endHtml);
        }
    }

    return result;
};

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId) as { id: number | string; text: string; html?: string; offset: number } | undefined;
    if (!segment) return '';

    const plainText = segment.text;
    const htmlText = segment.html || segment.text; // Use html property if available
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    // If no highlights or notes, return the HTML version (with line breaks)
    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return htmlText;
    }

    // Combine highlights and notes into a single list of annotations
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

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    // If there's HTML content, use the applyHighlightsToHtml function
    if (segment.html) {
        const highlightData = sorted.map((a) => ({
            id: a.id,
            color: a.color,
            start: Math.max(0, a.start - segmentOffset),
            end: Math.min(plainText.length, a.end - segmentOffset),
            type: a.type,
            noteText: a.type === 'note' ? (a as any).noteText : undefined,
        }));

        // Apply highlights to HTML, handling tags properly
        let result = htmlText;
        for (const highlight of highlightData) {
            if (highlight.start < highlight.end) {
                result = applyHighlightsToHtml(result, plainText, [
                    {
                        id: highlight.id,
                        color: highlight.color,
                        start: highlight.start,
                        end: highlight.end,
                    },
                ]);
            }
        }
        return result;
    }

    // Fallback for plain text segments
    let result = plainText;
    for (const annotation of sorted) {
        // Calculate the position within this segment
        const annotationStart = Math.max(0, annotation.start - segmentOffset);
        const annotationEnd = Math.min(plainText.length, annotation.end - segmentOffset);

        if (annotationStart < annotationEnd) {
            const before = result.substring(0, annotationStart);
            const annotated = result.substring(annotationStart, annotationEnd);
            const after = result.substring(annotationEnd);

            if (annotation.type === 'note') {
                // Just render the mark with data attributes - tooltip will be rendered via Teleport
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }

    return result;
};

// Initialize answers and load drafts
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
        // Add highlight effect
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
            // Handle both string and number segment IDs
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
            // Position tooltip ABOVE the selection with arrow pointing down
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50; // Approximate tooltip height
            const menuY = rect.top - menuHeight - 8; // 8px gap above selection

            const viewportWidth = window.innerWidth;
            const menuWidth = 140; // Smaller width for new design

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10), // Ensure it doesn't go above viewport
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

    // Remove overlapping notes (last action wins - highlight overwrites note)
    notes.value = notes.value.filter((n) => {
        return !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start);
    });

    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);

    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;

    const modalWidth = 320; // w-80 = 20rem = 320px
    const modalHeight = 180; // Approximate height of the modal
    const padding = 10; // Minimum distance from viewport edges

    // Get fresh position based on current selection
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

    // Viewport boundary checking
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Check horizontal bounds (modal is centered, so check half-width on each side)
    const halfWidth = modalWidth / 2;
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    // Check vertical bounds - if modal goes below viewport, show above selection
    if (y + modalHeight > viewportHeight - padding) {
        // Try to position above the selection
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        // If still outside viewport (too close to top), position at top with padding
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

    // Remove overlapping highlights (last action wins - note overwrites highlight)
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    // Remove any existing notes that overlap with this range
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

// Close delete tooltip
const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

// Handle click on content area - check if clicking on a highlight mark
const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        // Clicked on a highlight - show delete tooltip
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
        // Clicked outside highlight - close tooltips
        if (showDeleteTooltip.value) {
            closeDeleteTooltip();
        }
        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
    }
};

// Delete highlight - simple and direct
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        // Close tooltip and clear state first
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        // Then delete
        deleteHighlight(idToDelete);
    }
};

// Handle mouse enter on noted text to show tooltip
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

                // Position tooltip ABOVE the noted text with arrow pointing DOWN
                const tooltipHeight = 50; // Approximate tooltip height
                let y = rect.top - tooltipHeight - 8; // 8px gap above text

                // If not enough space above, show below
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

// Handle mouse leave from noted text
const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    // Check if we're moving to the tooltip itself
    if (relatedTarget?.closest('.note-hover-tooltip')) {
        return;
    }

    // Check if leaving a note mark
    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

// Handle mouse leave from the tooltip itself
const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

// Delete note from hover tooltip
const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};


const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
};

// Load saved answers when component mounts
onMounted(() => {
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
        class="mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">

        <div class="flex min-h-screen w-full flex-col bg-white px-6">

            <!-- Header -->
            <div class="flex-shrink-0 bg-white p-2 sm:p-1 md:p-2 lg:p-2">
                            <div class="flex flex-col gap-1 sm:gap-1">
                                <div class="flex-shrink-0 bg-white p-1 sm:p-1 lg:p-2">
                                    <div class="mb-1 rounded border  mr-1 border-gray-200 part-header-box  px-1 py-3">
                                        <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part1-title"
                                            v-html="getHighlightedSegmentById('part1-title')"></h3>
                                        <p class="text-sm text-gray-700 select-text" data-segment-id="part1-desc"
                                            v-html="getHighlightedSegmentById   ('part1-desc')"></p>
                                    </div>

                                </div>
                            </div>
                        </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">

                        <!-- Q21-23: Short answer -->
                        <div class=" bg-white p-6">
                            <div>
                                <div class="mb-4 flex items-center space-x-2">
                                    <h3 class="text-base font-bold text-black" data-segment-id="s3_q_1"
                                        v-html="getHighlightedSegmentById('s3_q_1')"></h3>
                                </div>
                                <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                    <strong data-segment-id="s3_q_3"
                                        v-html="getHighlightedSegmentById('s3_q_3')"></strong>
                                </p>
                            </div>

                            <div class="space-y-6 text-sm leading-relaxed text-gray-800">
                                <!-- Q21 -->
                                <div id="question-21"
                                    @mouseenter="hoveredQuestion = 21"
                                    @mouseleave="hoveredQuestion = null">
                                    <div class="mb-2 flex items-center space-x-2">

                                        <span data-segment-id="s3_q_4"
                                            v-html="getHighlightedSegmentById('s3_q_4')"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input v-model="answers.q21" type="text"
                                            class="block w-1/2 border border-gray-900 bg-white px-4 py-2 text-center text-sm font-medium focus:border-black focus:outline-none sm:w-2/3"
                                            placeholder="21" />
                                        <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                            @click.stop="emit('toggleFlag', 21)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q22 -->
                                <div id="question-22"
                                    @mouseenter="hoveredQuestion = 22"
                                    @mouseleave="hoveredQuestion = null">
                                    <div class="mb-2 flex items-center space-x-2">

                                        <span data-segment-id="s3_q_5"
                                            v-html="getHighlightedSegmentById('s3_q_5')"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input v-model="answers.q22" type="text"
                                            class="block w-1/2 border border-gray-900 bg-white px-4 py-2 text-center text-sm font-medium focus:border-black focus:outline-none sm:w-2/3"
                                            placeholder="22" />
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="emit('toggleFlag', 22)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q23 -->
                                <div id="question-23"
                                    @mouseenter="hoveredQuestion = 23"
                                    @mouseleave="hoveredQuestion = null">
                                    <div class="mb-2 flex items-center space-x-2">

                                        <span data-segment-id="s3_q_6"
                                            v-html="getHighlightedSegmentById('s3_q_6')"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input v-model="answers.q23" type="text"
                                            class="block w-1/2 border border-gray-900 bg-white px-4 py-2 text-center text-sm font-medium focus:border-black focus:outline-none sm:w-2/3"
                                            placeholder="23" />
                                        <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                            @click.stop="emit('toggleFlag', 23)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Q24-26: MCQ -->
                        <div class="border border-gray-200 bg-white p-6">
                            <h1 class="text-center text-base font-bold" data-segment-id="s3_q_7"
                                v-html="getHighlightedSegmentById('s3_q_7')"></h1>

                            <div class="mb-6">
                                <div class="mb-4 flex items-center space-x-2">
                                    <h3 class="text-base font-bold text-black" data-segment-id="s3_q_8"
                                        v-html="getHighlightedSegmentById('s3_q_8')"></h3>
                                </div>
                                <div class="border border-gray-200 bg-white p-6">
                                    <p class="mb-4 text-base leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span data-segment-id="s3_q_9"
                                            v-html="getHighlightedSegmentById('s3_q_9')"></span>
                                        <strong class="text-black" data-segment-id="s3_q_10"
                                            v-html="getHighlightedSegmentById('s3_q_10')"></strong><br />
                                        <span data-segment-id="s3_q_11"
                                            v-html="getHighlightedSegmentById('s3_q_11')"></span>
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- Q24 -->
                                <div id="question-24"
                                    class="flex gap-3  border-gray-900 bg-white p-3"
                                    @mouseenter="hoveredQuestion = 24"
                                    @mouseleave="hoveredQuestion = null">

                                    <div class="flex-1 text-base sm:text-base">
                                        <p class="mb-3 font-bold text-black" data-segment-id="s3_q_12"
                                            v-html="getHighlightedSegmentById('s3_q_12')"></p>
                                        <div class="space-y-3">
                                            <label class="flex items-center space-x-3"><input v-model="answers.q24"
                                                    type="radio" value="A"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_13"
                                                    v-html="getHighlightedSegmentById('s3_q_13')"></span></label>
                                            <label class="flex items-center space-x-3"><input v-model="answers.q24"
                                                    type="radio" value="B"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_14"
                                                    v-html="getHighlightedSegmentById('s3_q_14')"></span></label>
                                            <label class="flex items-center space-x-3"><input v-model="answers.q24"
                                                    type="radio" value="C"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_15"
                                                    v-html="getHighlightedSegmentById('s3_q_15')"></span></label>
                                        </div>
                                    </div>
                                    <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                        @click.stop="emit('toggleFlag', 24)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(24) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        title="Bookmark">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q25 -->
                                <div id="question-25"
                                    class="flex gap-3  border-gray-900 bg-white p-3"
                                    @mouseenter="hoveredQuestion = 25"
                                    @mouseleave="hoveredQuestion = null">

                                    <div class="flex-1 text-base sm:text-base">
                                        <p class="mb-3 font-bold text-black" data-segment-id="s3_q_16"
                                            v-html="getHighlightedSegmentById('s3_q_16')"></p>
                                        <div class="space-y-3">
                                            <label class="flex items-center space-x-3"><input v-model="answers.q25"
                                                    type="radio" value="A"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_17"
                                                    v-html="getHighlightedSegmentById('s3_q_17')"></span></label>
                                            <label class="flex items-center space-x-3"><input v-model="answers.q25"
                                                    type="radio" value="B"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_18"
                                                    v-html="getHighlightedSegmentById('s3_q_18')"></span></label>
                                            <label class="flex items-center space-x-3"><input v-model="answers.q25"
                                                    type="radio" value="C"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_19"
                                                    v-html="getHighlightedSegmentById('s3_q_19')"></span></label>
                                        </div>
                                    </div>
                                    <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="emit('toggleFlag', 25)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(25) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        title="Bookmark">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q26 -->
                                <div id="question-26"
                                    class="flex gap-3  border-gray-900 bg-white p-3"
                                    @mouseenter="hoveredQuestion = 26"
                                    @mouseleave="hoveredQuestion = null">

                                    <div class="flex-1 text-base ">
                                        <p class="mb-3 font-bold text-black" data-segment-id="s3_q_20"
                                            v-html="getHighlightedSegmentById('s3_q_20')"></p>
                                        <div class="space-y-3">
                                            <label class="flex items-center space-x-3"><input v-model="answers.q26"
                                                    type="radio" value="A"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_21"
                                                    v-html="getHighlightedSegmentById('s3_q_21')"></span></label>
                                            <label class="flex items-center space-x-3"><input v-model="answers.q26"
                                                    type="radio" value="B"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_22"
                                                    v-html="getHighlightedSegmentById('s3_q_22')"></span></label>
                                            <label class="flex items-center space-x-3"><input v-model="answers.q26"
                                                    type="radio" value="C"
                                                    class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                    data-segment-id="s3_q_23"
                                                    v-html="getHighlightedSegmentById('s3_q_23')"></span></label>
                                        </div>
                                    </div>
                                    <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="emit('toggleFlag', 26)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        title="Bookmark">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Q27: Image MCQ -->
                        <div id="question-27" class="group/q27 border border-gray-200 bg-white p-6">
                            <div class="mb-6 flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-black" data-segment-id="s3_q_24"
                                        v-html="getHighlightedSegmentById('s3_q_24')"></h3>
                                    <p data-segment-id="s3_q_25" v-html="getHighlightedSegmentById('s3_q_25')"></p>
                                </div>
                                <button
                                    @click.stop="emit('toggleFlag', 27)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                        opacity-5 group-hover/q27:opacity-100"
                                    :class="isQuestionFlagged(27) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    title="Bookmark">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="border border-gray-200 bg-white p-3 sm:p-4 md:p-6">
                                <img src="/images/listening/IELTS005.PNG" alt="" />
                            </div>
                            <div class="mt-4 space-y-3">
                                <label class="flex items-center space-x-3"><input v-model="answers.q27" type="radio"
                                        value="A"
                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                        data-segment-id="s3_q_26"
                                        v-html="getHighlightedSegmentById('s3_q_26')"></span></label>
                                <label class="flex items-center space-x-3"><input v-model="answers.q27" type="radio"
                                        value="B"
                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                        data-segment-id="s3_q_27"
                                        v-html="getHighlightedSegmentById('s3_q_27')"></span></label>
                                <label class="flex items-center space-x-3"><input v-model="answers.q27" type="radio"
                                        value="C"
                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                        data-segment-id="s3_q_28"
                                        v-html="getHighlightedSegmentById('s3_q_28')"></span></label>
                            </div>
                        </div>

                        <!-- Q28-30: Sentence completion -->
                        <div class="border border-gray-200 bg-white p-6">
                            <div>
                                <div class="mb-4 flex items-center space-x-2">
                                    <h3 class="text-base font-bold text-black" data-segment-id="s3_q_29"
                                        v-html="getHighlightedSegmentById('s3_q_29')"></h3>
                                </div>
                                <p class="mb-4  leading-relaxed text-black">
                                    <span data-segment-id="s3_q_30"
                                        v-html="getHighlightedSegmentById('s3_q_30')"></span><br />
                                    <strong data-segment-id="s3_q_31"
                                        v-html="getHighlightedSegmentById('s3_q_31')"></strong>
                                </p>
                            </div>

                            <div class="space-y-4  leading-relaxed text-black">
                                <!-- Q28 -->
                                <p id="question-28"
                                    class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 28"
                                    @mouseleave="hoveredQuestion = null">
                                    <span data-segment-id="s3_q_32"
                                        v-html="getHighlightedSegmentById('s3_q_32')"></span>
                                    <span class="inline-flex items-center gap-1">

                                        <input v-model="answers.q28" type="text"
                                            class="w-36 border border-gray-900 bg-white px-3 py-1 text-center text-sm font-medium focus:border-black focus:outline-none"
                                            placeholder="28" />
                                             <span data-segment-id="s3_q_33"
                                        v-html="getHighlightedSegmentById('s3_q_33')"></span>
                                        <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                            @click.stop="emit('toggleFlag', 28)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(28) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>

                                </p>

                                <!-- Q29 -->
                                <p id="question-29"
                                    class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 29"
                                    @mouseleave="hoveredQuestion = null">
                                    <span data-segment-id="s3_q_34"
                                        v-html="getHighlightedSegmentById('s3_q_34')"></span>
                                    <span class="inline-flex items-center gap-1">

                                        <input v-model="answers.q29" type="text"
                                            class="w-36 border border-gray-900 bg-white px-3 py-1 text-center text-sm font-medium focus:border-black focus:outline-none"
                                            placeholder="29" />
                                             <span data-segment-id="s3_q_35"
                                        v-html="getHighlightedSegmentById('s3_q_35')"></span>
                                        <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                            @click.stop="emit('toggleFlag', 29)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(29) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>

                                </p>

                                <!-- Q30 -->
                                <p id="question-30"
                                    class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 30"
                                    @mouseleave="hoveredQuestion = null">
                                    <span data-segment-id="s3_q_36"
                                        v-html="getHighlightedSegmentById('s3_q_36')"></span>
                                    <span class="inline-flex items-center gap-1">

                                        <input v-model="answers.q30" type="text"
                                            class="w-36 border border-gray-900 bg-white px-3 py-1 text-center text-sm font-medium focus:border-black focus:outline-none"
                                            placeholder="30" />

                                            <span data-segment-id="s3_q_37"
                                        v-html="getHighlightedSegmentById('s3_q_37')"></span>
                                        <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                            @click.stop="emit('toggleFlag', 30)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(30) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>

                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
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
            <div class="delete-highlight-tooltip fixed z-9999"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                    @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path
                                d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2">
                            </path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Delete</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Hover Tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div
                    class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText
                        }}</span>
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
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"</p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100">Cancel</button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Part Header Box */
.part-header-box {
    background-color: #F1F2EC;
}

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(0, 0, 0, 0.1);
    }
    50% {
        background-color: rgba(0, 0, 0, 0.25);
    }
    100% {
        background-color: rgba(0, 0, 0, 0.1);
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

/* Tooltip arrow styles */
.highlight-tooltip {
    position: fixed;
    z-index: 9999;
}

.tooltip-arrow {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.tooltip-arrow-up {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.1));
}

.tooltip-arrow-down {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
<style>
.note-highlight {
    border-bottom: 2px solid rgba(0, 0, 0, 0.4);
    cursor: help;
    position: relative;
    display: inline;
}

.note-highlight::after {
    content: '📝';
    display: inline-block;
    margin-left: 2px;
    font-size: 0.7em;
    vertical-align: super;
    line-height: 0;
    opacity: 0.9;
    pointer-events: none;
}

.note-highlight:hover {
    border-bottom-color: #000;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
