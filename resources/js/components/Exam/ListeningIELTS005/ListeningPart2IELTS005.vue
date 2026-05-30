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
    { id: 'part1-title', text: 'Part 2', offset: 0 },
    { id: 'part1-desc', text: 'Listen and answer questions 11–20.', offset: 6 },
    // Instructions text segments
    { id: 's2_h_3', text: 'Choose the correct answer for each question.', offset: 25 },
    { id: 's2_q_1', text: 'Questions 11-16', offset: 68 },
    { id: 's2_q_2', text: 'Complete the notes below.', offset: 83 },
    { id: 's2_q_3', text: 'Write', offset: 108 },
    { id: 's2_q_4', text: 'NO MORE THAN THREE WORDS OR A NUMBER.', offset: 113 },
    { id: 's2_q_5', text: '11. Jack says that in London these days many people', offset: 149 },
    { id: 's2_q_6', text: 'A. see cycling as a foolish activity', offset: 197 },
    { id: 's2_q_7', text: 'B. have no experience of cycling', offset: 232 },
    { id: 's2_q_8', text: 'C. take too many risks when cycling', offset: 263 },
    { id: 's2_q_9', text: '12. If people want to cycle to school or work, CitiCyclist helps them by', offset: 297 },
    { id: 's2_q_10', text: 'A. giving cycling lessons on the route they take.', offset: 366 },
    { id: 's2_q_11', text: 'B. advising them on the safest route to choose.', offset: 413 },
    { id: 's2_q_12', text: 'C. teaching them basic skills on quiet roads first.', offset: 459 },
    { id: 's2_q_13', text: '13. Jack works with some advanced cyclists who want to develop', offset: 510 },
    { id: 's2_q_14', text: 'A. international competitive riding skills.', offset: 568 },
    { id: 's2_q_15', text: 'B. knowledge of advanced equipment.', offset: 608 },
    { id: 's2_q_16', text: 'C. confidence in complex road systems.', offset: 641 },
    { id: 's2_q_17', text: '14. CitiCyclist supports the view that cyclists should', offset: 678 },
    { id: 's2_q_18', text: 'A. have separate sections of the road from motor traffic.', offset: 728 },
    { id: 's2_q_19', text: 'B. always wear protective clothing when cycling.', offset: 786 },
    { id: 's2_q_20', text: 'C. know how to ride confidently on busy roads.', offset: 833 },
    { id: 's2_q_21', text: 'Questions 15–17', offset: 879 },
    { id: 's2_q_22', text: 'List THREE types of organizations for which CitiCyclist provides services.', offset: 895 },
    { id: 's2_q_23', text: 'Write NO MORE THAN THREE WORDS for each answer.', offset: 969 },
    { id: 's2_q_24', text: 'Questions 18-20', offset: 1017 },
    { id: 's2_q_25', text: 'Complete the notes below.', offset: 1032 },
    { id: 's2_q_26', text: 'Write NO MORE THAN THREE WORDS for each answer.', offset: 1057 },
    { id: 's2_q_27', text: 'Website address:', offset: 1105 },
    { id: 's2_q_28', text: 'citicyclist.co.uk', offset: 1121 },
    { id: 's2_q_29', text: 'Phone:', offset: 1139 },
    { id: 's2_q_30', text: 'Cost (single person):', offset: 1145 },
    { id: 's2_q_31', text: 'per person', offset: 1166 },
    { id: 's2_q_32', text: 'Usual length of course:', offset: 1176 },
    { id: 's2_q_33', text: '(except complete beginners)', offset: 1200 },
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

        <div class="flex min-h-screen w-full flex-col bg-white px-1">

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
                        <div>
                            <!-- Q11-14 header -->
                            <h3 class="pb-4 text-xl font-bold text-black" data-segment-id="s2_q_1"
                                v-html="getHighlightedSegmentById('s2_q_1')"></h3>

                            <!-- Q11-14: MCQ Section -->
                            <section>
                                <p class="mb-2 text-xs text-zinc-800 sm:text-sm">
                                    <span data-segment-id="s2_q_2" v-html="getHighlightedSegmentById('s2_q_2')"></span><br />
                                    <span data-segment-id="s2_q_3" v-html="getHighlightedSegmentById('s2_q_3')"></span>
                                    <span class="font-semibold" data-segment-id="s2_q_4"
                                        v-html="getHighlightedSegmentById('s2_q_4')"></span>
                                </p>

                                <div class="space-y-4">
                                    <!-- Q11 -->
                                    <div id="question-11"
                                        class="flex gap-2 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 11"
                                        @mouseleave="hoveredQuestion = null">

                                        <div class="flex-1 text-base sm:text-lg">
                                            <p class="mb-3 font-bold text-gray-700" data-segment-id="s2_q_5"
                                                v-html="getHighlightedSegmentById('s2_q_5')"></p>
                                            <div class="space-y-3">
                                                <label class="flex items-center space-x-3"><input v-model="answers.q11"
                                                        type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_6"
                                                        v-html="getHighlightedSegmentById('s2_q_6')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q11"
                                                        type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_7"
                                                        v-html="getHighlightedSegmentById('s2_q_7')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q11"
                                                        type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_8"
                                                        v-html="getHighlightedSegmentById('s2_q_8')"></span></label>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="emit('toggleFlag', 11)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q12 -->
                                    <div id="question-12"
                                        class="flex gap-2 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 12"
                                        @mouseleave="hoveredQuestion = null">

                                        <div class="flex-1 text-base sm:text-lg">
                                            <p class="mb-3 font-bold text-gray-700" data-segment-id="s2_q_9"
                                                v-html="getHighlightedSegmentById('s2_q_9')"></p>
                                            <div class="space-y-3">
                                                <label class="flex items-center space-x-3"><input v-model="answers.q12"
                                                        type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_10"
                                                        v-html="getHighlightedSegmentById('s2_q_10')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q12"
                                                        type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_11"
                                                        v-html="getHighlightedSegmentById('s2_q_11')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q12"
                                                        type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_12"
                                                        v-html="getHighlightedSegmentById('s2_q_12')"></span></label>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="emit('toggleFlag', 12)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q13 -->
                                    <div id="question-13"
                                        class="flex gap-2 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null">

                                        <div class="flex-1 text-base sm:text-lg">
                                            <p class="mb-3 font-bold text-gray-700" data-segment-id="s2_q_13"
                                                v-html="getHighlightedSegmentById('s2_q_13')"></p>
                                            <div class="space-y-3">
                                                <label class="flex items-center space-x-3"><input v-model="answers.q13"
                                                        type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_14"
                                                        v-html="getHighlightedSegmentById('s2_q_14')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q13"
                                                        type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_15"
                                                        v-html="getHighlightedSegmentById('s2_q_15')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q13"
                                                        type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_16"
                                                        v-html="getHighlightedSegmentById('s2_q_16')"></span></label>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="emit('toggleFlag', 13)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q14 -->
                                    <div id="question-14"
                                        class="flex gap-2 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 14"
                                        @mouseleave="hoveredQuestion = null">

                                        <div class="flex-1 text-base sm:text-lg">
                                            <p class="mb-3 font-bold text-gray-700" data-segment-id="s2_q_17"
                                                v-html="getHighlightedSegmentById('s2_q_17')"></p>
                                            <div class="space-y-3">
                                                <label class="flex items-center space-x-3"><input v-model="answers.q14"
                                                        type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_18"
                                                        v-html="getHighlightedSegmentById('s2_q_18')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q14"
                                                        type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_19"
                                                        v-html="getHighlightedSegmentById('s2_q_19')"></span></label>
                                                <label class="flex items-center space-x-3"><input v-model="answers.q14"
                                                        type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-black focus:ring-gray-500" /><span
                                                        data-segment-id="s2_q_20"
                                                        v-html="getHighlightedSegmentById('s2_q_20')"></span></label>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="emit('toggleFlag', 14)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </section>

                            <!-- Q15-17: List three organisations -->
                            <section class="my-4 md:py-8">
                                <p class="mb-2 text-[18px]">
                                    <span class="font-semibold text-black" data-segment-id="s2_q_21"
                                        v-html="getHighlightedSegmentById('s2_q_21')"></span><br />
                                    <strong data-segment-id="s2_q_22"
                                        v-html="getHighlightedSegmentById('s2_q_22')"></strong><br />
                                    <span data-segment-id="s2_q_23"
                                        v-html="getHighlightedSegmentById('s2_q_23')"></span>
                                </p>

                                <div class="mt-4 space-y-4 text-[18px] leading-relaxed text-gray-800">
                                    <p id="question-15"
                                        class="flex items-center gap-2"
                                        @mouseenter="hoveredQuestion = 15"
                                        @mouseleave="hoveredQuestion = null">

                                        <input v-model="answers.q15" type="text"
                                            class="w-36 border-2 border-gray-900 px-3 py-1 text-center text-sm font-medium transition-colors"
                                            placeholder="15" />
                                        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="emit('toggleFlag', 15)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>
                                    <p id="question-16"
                                        class="flex items-center gap-2"
                                        @mouseenter="hoveredQuestion = 16"
                                        @mouseleave="hoveredQuestion = null">

                                        <input v-model="answers.q16" type="text"
                                            class="w-36 border-2 border-gray-900 px-3 py-1 text-center text-sm font-medium transition-colors"
                                            placeholder="16" />
                                        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="emit('toggleFlag', 16)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>
                                    <p id="question-17"
                                        class="flex items-center gap-2"
                                        @mouseenter="hoveredQuestion = 17"
                                        @mouseleave="hoveredQuestion = null">

                                        <input v-model="answers.q17" type="text"
                                            class="w-36 border-2 border-gray-900 px-3 py-1 text-center text-sm font-medium transition-colors"
                                            placeholder="17" />
                                        <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="emit('toggleFlag', 17)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            title="Bookmark">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>
                                </div>
                            </section>

                            <!-- Q18-20: Table -->
                            <section class="my-4 md:py-8">
                                <p class="mb-2 text-[18px] text-zinc-800">
                                    <span class="font-semibold text-black" data-segment-id="s2_q_24"
                                        v-html="getHighlightedSegmentById('s2_q_24')"></span><br />
                                    <span data-segment-id="s2_q_25"
                                        v-html="getHighlightedSegmentById('s2_q_25')"></span><br />
                                    <span data-segment-id="s2_q_26"
                                        v-html="getHighlightedSegmentById('s2_q_26')"></span>
                                </p>

                                <div class="overflow-x-auto">
                                    <table class="w-full border border-gray-900 text-base text-black">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-900 px-3 py-2 text-left font-medium"
                data-segment-id="s2_q_27"
                v-html="getHighlightedSegmentById('s2_q_27')"></th>
            <th class="border border-gray-900 px-3 py-2 text-left font-medium"
                data-segment-id="s2_q_28"
                v-html="getHighlightedSegmentById('s2_q_28')"></th>
        </tr>
    </thead>
    <tbody>
        <!-- Q18 -->
        <tr>
            <td class="border border-gray-900 px-3 py-2"
                data-segment-id="s2_q_29"
                v-html="getHighlightedSegmentById('s2_q_29')"></td>
            <td class="border border-gray-900 px-3 py-2">
                <span
                    id="question-18"
                    class="flex items-center gap-1"
                    @mouseenter="hoveredQuestion = 18"
                    @mouseleave="hoveredQuestion = null">
                    <input v-model="answers.q18" type="text"
                        class="w-3/5 border border-gray-900 bg-transparent px-1 py-1 text-center focus:border-black focus:outline-none sm:px-2"
                        placeholder="18" />
                    <button
                        @click.stop="emit('toggleFlag', 18)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                        :class="[
                            hoveredQuestion === 18 || isQuestionFlagged(18) ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none',
                            isQuestionFlagged(18)
                                ? 'border-red-300 text-red-500'
                                : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'
                        ]"
                        title="Bookmark">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </td>
        </tr>

        <!-- Q19 -->
        <tr>
            <td class="border border-gray-900 px-3 py-2"
                data-segment-id="s2_q_30"
                v-html="getHighlightedSegmentById('s2_q_30')"></td>
            <td class="border border-gray-900 px-3 py-2">
                <span
                    id="question-19"
                    class="flex items-center gap-1"
                    @mouseenter="hoveredQuestion = 19"
                    @mouseleave="hoveredQuestion = null">
                    <input v-model="answers.q19" type="text"
                        class="w-3/5 border border-gray-900 bg-transparent px-1 py-1 text-center focus:border-black focus:outline-none sm:px-2"
                        placeholder="19" />
                    <button
                        @click.stop="emit('toggleFlag', 19)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                        :class="[
                            hoveredQuestion === 19 || isQuestionFlagged(19) ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none',
                            isQuestionFlagged(19)
                                ? 'border-red-300 text-red-500'
                                : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'
                        ]"
                        title="Bookmark">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
                <span data-segment-id="s2_q_31"
                    v-html="getHighlightedSegmentById('s2_q_31')"></span>
            </td>
        </tr>

        <!-- Q20 -->
        <tr>
            <td class="border border-gray-900 px-3 py-2"
                data-segment-id="s2_q_32"
                v-html="getHighlightedSegmentById('s2_q_32')"></td>
            <td class="border border-gray-900 px-3 py-2">
                <span
                    id="question-20"
                    class="flex items-center gap-1"
                    @mouseenter="hoveredQuestion = 20"
                    @mouseleave="hoveredQuestion = null">
                    <input v-model="answers.q20" type="text"
                        class="w-3/5 border border-gray-900 bg-transparent px-1 py-1 text-center focus:border-black focus:outline-none sm:px-2"
                        placeholder="20" />
                    <button
                        @click.stop="emit('toggleFlag', 20)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                        :class="[
                            hoveredQuestion === 20 || isQuestionFlagged(20) ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none',
                            isQuestionFlagged(20)
                                ? 'border-red-300 text-red-500'
                                : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'
                        ]"
                        title="Bookmark">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
                <div class="mt-1 text-xs text-zinc-500"
                    data-segment-id="s2_q_33"
                    v-html="getHighlightedSegmentById('s2_q_33')"></div>
            </td>
        </tr>
    </tbody>
</table>
                                </div>
                            </section>

                            <div class="mt-8 text-center text-sm text-zinc-500">2</div>
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
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
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
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete">
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
