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
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q7: '',
    q8: '',
    q9: '',
    q10: '',
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
    { id: 'part1-title', text: 'Part 1', offset: 0 },
    { id: 'part1-desc', text: 'Listen and answer questions 1–10.', offset: 6 },
    // Instructions text segments
    { id: 0, text: 'Questions 1–10', offset: 40 },
    { id: 'instr1', text: 'Complete the form below. Write ', offset: 54 },
    { id: 'instr2', text: 'NO MORE THAN THREE WORDS AND/OR A NUMBER', offset: 85 },
    { id: 'instr3', text: ' for each answer.', offset: 125 },
    // Form title
    { id: 'form-title', text: 'Council Youth Scheme', offset: 142 },
    { id: 'form-subtitle', text: 'Application for funding for group project', offset: 162 },
    // Form fields - labels
    { id: 18, text: 'Name:', offset: 203 },
    { id: 2, text: 'Ralph Pearson', offset: 208 },
    { id: 3, text: 'Contact address:', offset: 221 },
    { id: 4, text: ', Drayton, DR68AB', offset: 237 },
    { id: 19, text: 'Telephone number:', offset: 254 },
    { id: 5, text: '01453 586098', offset: 271 },
    { id: 20, text: 'Name of group:', offset: 283 },
    { id: 6, text: 'Community Youth Theatre Group', offset: 297 },
    { id: 21, text: 'Description of group:', offset: 326 },
    { id: 7, text: 'amateur theatre group (', offset: 347 },
    { id: 8, text: 'members)', offset: 370 },
    { id: 9, text: 'Involved in drama:', offset: 378 },
    { id: 10, text: 'Amount of money requested:', offset: 395 },
    { id: 'pound', text: '£', offset: 421 },
    { id: 22, text: 'Description of project:', offset: 422 },
    { id: 11, text: 'to produce a short', offset: 445 },
    { id: 12, text: 'play for young children', offset: 463 },
    { id: 23, text: 'Money needed for:', offset: 486 },
    { id: 13, text: 'for scenery Costumes Cost of', offset: 503 },
    { id: 14, text: 'Sundries', offset: 531 },
    { id: 24, text: 'How source of funding will be credited:', offset: 539 },
    { id: 16, text: 'Acknowledged in the', offset: 578 },
    { id: 15, text: 'given to audience.', offset: 597 },
    { id: 25, text: 'Other organisations approached for funding:', offset: 615 },
    { id: 17, text: 'National Youth Services – money was', offset: 658 },
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
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3"
                @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part1-title"
                    v-html="getHighlightedSegmentById('part1-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part1-desc"
                    v-html="getHighlightedSegmentById('part1-desc')"></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-3 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-sm text-gray-700 select-text">
                    <span class="font-bold" data-segment-id="instr2"
                        v-html="getHighlightedSegmentById('instr2')"></span>
                    <span data-segment-id="instr3" v-html="getHighlightedSegmentById('instr3')"></span>
                </p>
            </div>

            <!-- Form Content - Minimal Design -->
            <div class="mb-30 ml-4 bg-white" @mouseup="handleContentTextSelect">
                <!-- Form Title -->
                <div class="mb-4 border-b border-gray-300 pb-3">
                    <p class="text-base font-semibold text-gray-900 select-text" data-segment-id="form-title"
                        v-html="getHighlightedSegmentById('form-title')"></p>
                    <p class="text-sm text-gray-700 select-text" data-segment-id="form-subtitle"
                        v-html="getHighlightedSegmentById('form-subtitle')"></p>
                </div>

                <!-- Form Fields - Minimal Layout -->
                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="18"
                            v-html="getHighlightedSegmentById(18)"></span>
                        <span class="ml-2 text-gray-900 select-text" data-segment-id="2"
                            v-html="getHighlightedSegmentById(2)"></span>
                    </div>

                    <!-- Contact Address -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="3"
                            v-html="getHighlightedSegmentById(3)"></span>
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <span id="question-1" class="group relative inline-flex items-center"
                                @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                                <input v-model="answers.q1" type="text" placeholder="1"
                                    class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                    @focus="hoveredQuestion = 1" @blur="hoveredQuestion = null" />
                                <span class="text-gray-900 select-text" data-segment-id="4"
                                v-html="getHighlightedSegmentById(4)"></span>
                                <button v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                    @click.stop="toggleFlag(1)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(1) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </span>

                        </div>
                    </div>

                    <!-- Telephone -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="19"
                            v-html="getHighlightedSegmentById(19)"></span>
                        <span class="ml-2 text-gray-900 select-text" data-segment-id="5"
                            v-html="getHighlightedSegmentById(5)"></span>
                    </div>

                    <!-- Name of Group -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="20"
                            v-html="getHighlightedSegmentById(20)"></span>
                        <span class="ml-2 text-gray-900 select-text" data-segment-id="6"
                            v-html="getHighlightedSegmentById(6)"></span>
                    </div>

                    <!-- Description of Group -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="21"
                            v-html="getHighlightedSegmentById(21)"></span>
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <span class="text-gray-900 select-text" data-segment-id="7"
                                v-html="getHighlightedSegmentById(7)"></span>
                            <span id="question-2" class="group relative inline-flex items-center"
                                @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                                <input v-model="answers.q2" type="text" placeholder="2"
                                    class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                    @focus="hoveredQuestion = 2" @blur="hoveredQuestion = null" />
                                    <span class="text-gray-900 select-text" data-segment-id="8"
                                v-html="getHighlightedSegmentById(8)"></span>
                                <button v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                    @click.stop="toggleFlag(2)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(2) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </span>

                        </div>
                    </div>

                    <!-- Involved in Drama -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="9"
                            v-html="getHighlightedSegmentById(9)"></span>
                        <div class="mt-1">
                            <span id="question-3" class="group relative inline-flex items-center"
                                @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                                <input v-model="answers.q3" type="text" placeholder="3"
                                    class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                    @focus="hoveredQuestion = 3" @blur="hoveredQuestion = null" />
                                <button v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                    @click.stop="toggleFlag(3)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(3) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Amount of Money -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="10"
                            v-html="getHighlightedSegmentById(10)"></span>
                        <div class="mt-1 flex items-center gap-1">
                            <span class="font-medium select-text" data-segment-id="pound"
                                v-html="getHighlightedSegmentById('pound')"></span>
                            <span id="question-4" class="group relative inline-flex items-center"
                                @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                                <input v-model="answers.q4" type="text" placeholder="4"
                                    class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                    @focus="hoveredQuestion = 4" @blur="hoveredQuestion = null" />
                                <button v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                    @click.stop="toggleFlag(4)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(4) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Description of Project -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="22"
                            v-html="getHighlightedSegmentById(22)"></span>
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <span class="text-gray-900 select-text" data-segment-id="11"
                                v-html="getHighlightedSegmentById(11)"></span>
                            <span id="question-5" class="group relative inline-flex items-center"
                                @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                                <input v-model="answers.q5" type="text" placeholder="5"
                                    class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                    @focus="hoveredQuestion = 5" @blur="hoveredQuestion = null" />

                                <span class="text-gray-900 select-text" data-segment-id="12"
                                v-html="getHighlightedSegmentById(12)"></span>
                                <button v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                    @click.stop="toggleFlag(5)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(5) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </span>

                        </div>
                    </div>

                    <!-- Money Needed For -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="23"
                            v-html="getHighlightedSegmentById(23)"></span>
                        <div class="mt-1 space-y-2">
                            <div class="flex flex-wrap items-center gap-1">
                                <span id="question-6" class="group relative inline-flex items-center"
                                    @mouseenter="hoveredQuestion = 6" @mouseleave="hoveredQuestion = null">
                                    <input v-model="answers.q6" type="text" placeholder="6"
                                        class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                        @focus="hoveredQuestion = 6" @blur="hoveredQuestion = null" />
                                    <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                        @click.stop="toggleFlag(6)"
                                        class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(6) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </span>
                                <span class="text-gray-900 select-text" data-segment-id="13"
                                    v-html="getHighlightedSegmentById(13)"></span>
                                <span id="question-7" class="group relative inline-flex items-center"
                                    @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                    <input v-model="answers.q7" type="text" placeholder="7"
                                        class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                        @focus="hoveredQuestion = 7" @blur="hoveredQuestion = null" />
                                    <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                        @click.stop="toggleFlag(7)"
                                        class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(7) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1">
                                <span id="question-8" class="group relative inline-flex items-center"
                                    @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                    <input v-model="answers.q8" type="text" placeholder="8"
                                        class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                        @focus="hoveredQuestion = 8" @blur="hoveredQuestion = null" />
                                    <span class="text-gray-900 select-text" data-segment-id="14"
                                    v-html="getHighlightedSegmentById(14)"></span>
                                    <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                        @click.stop="toggleFlag(8)"
                                        class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(8) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </span>

                            </div>
                        </div>
                    </div>

                    <!-- How Funding Will Be Credited -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="24"
                            v-html="getHighlightedSegmentById(24)"></span>
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <span class="text-gray-900 select-text" data-segment-id="16"
                                v-html="getHighlightedSegmentById(16)"></span>
                            <span id="question-9" class="group relative inline-flex items-center"
                                @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                <input v-model="answers.q9" type="text" placeholder="9"
                                    class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                    @focus="hoveredQuestion = 9" @blur="hoveredQuestion = null" />
                                <span class="text-gray-900 select-text" data-segment-id="15"
                                v-html="getHighlightedSegmentById(15)"></span>
                                <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                    @click.stop="toggleFlag(9)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(9) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </span>

                        </div>
                    </div>

                    <!-- Other Organisations -->
                    <div>
                        <span class="font-bold select-text" data-segment-id="25"
                            v-html="getHighlightedSegmentById(25)"></span>
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <span class="text-gray-900 select-text" data-segment-id="17"
                                v-html="getHighlightedSegmentById(17)"></span>
                            <span id="question-10" class="group relative inline-flex items-center"
                                @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                <input v-model="answers.q10" type="text" placeholder="10"
                                    class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                    @focus="hoveredQuestion = 10" @blur="hoveredQuestion = null" />
                                <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                    @click.stop="toggleFlag(10)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(10) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'">
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
                        <!-- Quote marks icon -->
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
                        <!-- Bold pen/marker icon -->
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

        <!-- Delete Highlight Tooltip - Appears BELOW the highlight -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-9999" :style="{
                left: `${deleteTooltipPosition.x}px`,
                top: `${deleteTooltipPosition.y}px`,
                transform: 'translateX(-50%)',
            }">
                <!-- Arrow pointing UP -->
                <div class="tooltip-arrow-up"></div>
                <!-- Tooltip Content -->
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                    @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <!-- Trash/Bin icon -->
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

        <!-- Note Hover Tooltip - Appears ABOVE noted text -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{
                left: `${noteTooltipPosition.x}px`,
                top: `${noteTooltipPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @mouseleave="handleTooltipMouseLeave" @click.stop>
                <!-- Tooltip Content -->
                <div
                    class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <!-- Note Icon -->
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </span>
                    <!-- Note Text -->
                    <span class="note-tooltip-text max-w-[180px] text-sm wrap-break-word text-gray-700">{{
                        hoveredNoteText }}</span>
                    <!-- Delete Button -->
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
                <!-- Arrow pointing DOWN -->
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
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
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

/* Input focus states */
input:focus {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    input[type="text"] {
        width: 100px;
    }
}
</style>
