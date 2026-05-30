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

// Listening Section 2 component - Outdoor Activities
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
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Text segments with their cumulative offsets in the full text
// Each offset = previous offset + previous text length + small gap (5-10)
const textSegments = ref([
    // Part header box text segments
    { id: 'part2-title', text: 'SECTION 2', offset: 0 },                          // len=9, next=15
    { id: 'part2-desc', text: 'Listen and answer questions 11–20.', offset: 15 }, // len=35, next=55

    // Instruction section text segments
    { id: 0, text: 'Questions 11–20', offset: 55 },                               // len=15, next=75
    { id: 1, text: 'Choose the correct letter, A, B or C.', offset: 75 },         // len=37, next=120

    // Question 11
    { id: 2, text: 'The guided bushwalk is suitable for', offset: 120 },          // len=35, next=160
    { id: 3, text: 'adults only', offset: 160 },                                  // len=11, next=180
    { id: 4, text: 'children over 12 and adults', offset: 180 },                  // len=27, next=215
    { id: 5, text: 'children over 8 accompanied by a parent', offset: 215 },      // len=39, next=260

    // Question 12
    { id: 6, text: 'On the bird observation outing, it is recommended that you have', offset: 260 }, // len=63, next=330
    { id: 7, text: 'waterproof footwear', offset: 330 },                          // len=19, next=355
    { id: 8, text: 'a bird identification book', offset: 355 },                   // len=26, next=390
    { id: 9, text: 'binoculars', offset: 390 },                                   // len=10, next=410

    // Question 13
    { id: 10, text: 'For the trip to the sand dunes, a company will donate', offset: 410 }, // len=53, next=470
    { id: 11, text: 'water', offset: 470 },                                       // len=5, next=480
    { id: 12, text: 'tools', offset: 480 },                                       // len=5, next=490
    { id: 13, text: 'gloves', offset: 490 },                                      // len=6, next=505

    // Question 14
    { id: 14, text: 'The bush tucker excursion will cost (per person)', offset: 505 }, // len=48, next=560
    { id: 15, text: '$15', offset: 560 },                                         // len=3, next=570
    { id: 16, text: '$12', offset: 570 },                                         // len=3, next=580
    { id: 17, text: '$7', offset: 580 },                                          // len=2, next=590

    // Question 15
    { id: 18, text: 'The deadline to register for the bush tucker outing is', offset: 590 }, // len=54, next=650
    { id: 19, text: '25 November', offset: 650 },                                 // len=11, next=670
    { id: 20, text: '15 November', offset: 670 },                                 // len=11, next=690
    { id: 21, text: '10 November', offset: 690 },                                 // len=11, next=710

    // Questions 16-20 section header
    { id: 22, text: 'Questions 16–20', offset: 710 },                             // len=15, next=730
    { id: 23, text: 'Complete the table below.', offset: 730 },                   // len=25, next=760
    { id: 24, text: 'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.', offset: 760 }, // len=62, next=830

    // Table headers
    { id: 25, text: 'Activity', offset: 830 },                                    // len=8, next=845
    { id: 26, text: 'Leader', offset: 845 },                                      // len=6, next=855
    { id: 27, text: 'Date', offset: 855 },                                        // len=4, next=865
    { id: 28, text: 'Venue', offset: 865 },                                       // len=5, next=875
    { id: 29, text: 'Time', offset: 875 },                                        // len=4, next=885

    // Row 1
    { id: 30, text: 'Bush walk', offset: 885 },                                   // len=9, next=900
    { id: 31, text: 'Glenn Ford', offset: 900 },                                  // len=10, next=915
    { id: 32, text: 'Springvale', offset: 915 },                                  // len=10, next=930

    // Row 2
    { id: 33, text: 'Bird watching', offset: 930 },                               // len=13, next=950
    { id: 34, text: 'Joy Black, club', offset: 950 },                             // len=15, next=970
    { id: 35, text: 'Camford', offset: 970 },                                     // len=7, next=985
    { id: 36, text: '4.30–6.30 pm', offset: 985 },                                // len=12, next=1005

    // Row 3
    { id: 37, text: 'Sand dunes', offset: 1005 },                                 // len=10, next=1020
    { id: 38, text: 'Rex Rose', offset: 1020 },                                   // len=8, next=1035
    { id: 39, text: '26 November', offset: 1035 },                                // len=11, next=1055
    { id: 40, text: '8.30–10.30 am', offset: 1055 },                              // len=13, next=1075

    // Row 4
    { id: 41, text: 'Bush tucker', offset: 1075 },                                // len=11, next=1095
    { id: 42, text: 'Jim Kerr, ranger', offset: 1095 },                           // len=16, next=1120
    { id: 43, text: '3 December', offset: 1120 },                                 // len=10, next=1140
    { id: 44, text: 'Carson Hills', offset: 1140 },                               // len=12, next=1160
    { id: 45, text: '10 am –', offset: 1160 },                                    // len=7, end=1167
]);
// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
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

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
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

// Handle keyboard events
const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
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

// Load saved answers when component mounts
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
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 rounded border border-gray-200 bg-[#F1F2EC] px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"
                ></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto pb-24">
                <div class="p-2 sm:p-3">
                    <!-- Questions 11-15: Multiple Choice (without A, B, C labels) -->
                    <div class="space-y-6">
                        <!-- Question 11 -->
                        <div
                            id="question-11"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 11"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text">11.</span>
                                <div class="min-w-0 flex-1">
                                    <!-- Bookmark Button -->
                                    <button
                                        v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                        @click.stop="toggleFlag(11)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(11)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="3"
                                                v-html="getHighlightedSegmentById(3)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="4"
                                                v-html="getHighlightedSegmentById(4)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="5"
                                                v-html="getHighlightedSegmentById(5)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 12 -->
                        <div
                            id="question-12"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 12"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text">12.</span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                        @click.stop="toggleFlag(12)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(12)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="7"
                                                v-html="getHighlightedSegmentById(7)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="8"
                                                v-html="getHighlightedSegmentById(8)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="9"
                                                v-html="getHighlightedSegmentById(9)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 13 -->
                        <div
                            id="question-13"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 13"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text">13.</span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                        @click.stop="toggleFlag(13)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(13)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="11"
                                                v-html="getHighlightedSegmentById(11)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="12"
                                                v-html="getHighlightedSegmentById(12)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="13"
                                                v-html="getHighlightedSegmentById(13)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 14 -->
                        <div
                            id="question-14"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 14"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text">14.</span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                        @click.stop="toggleFlag(14)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(14)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q14"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="15"
                                                v-html="getHighlightedSegmentById(15)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q14"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="16"
                                                v-html="getHighlightedSegmentById(16)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q14"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="17"
                                                v-html="getHighlightedSegmentById(17)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 15 -->
                        <div
                            id="question-15"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 15"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text">15.</span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                        @click.stop="toggleFlag(15)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(15)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q15"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="19"
                                                v-html="getHighlightedSegmentById(19)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q15"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="20"
                                                v-html="getHighlightedSegmentById(20)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q15"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="21"
                                                v-html="getHighlightedSegmentById(21)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 16-20: Table Completion -->
<div class="border-t pt-4">
    <div class="px-2">
        <p class="text-base font-bold text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></p>
        <p class="text-sm text-gray-700 select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></p>
        <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></p>
    </div>

    <div class="mt-2 overflow-x-auto border border-gray-300">
        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-50">
                    <th class="border-r px-4 py-2 text-sm font-bold" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></th>
                    <th class="border-r px-4 py-2 text-sm font-bold" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></th>
                    <th class="border-r px-4 py-2 text-sm font-bold" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></th>
                    <th class="border-r px-4 py-2 text-sm font-bold" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></th>
                    <th class="px-4 py-2 text-sm font-bold" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></th>
                </tr>
            </thead>

            <tbody>
                <!-- Row 1 -->
                <tr class="border-b">
                    <td class="border-r px-4 py-2" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></td>
                    <td class="border-r px-4 py-2" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></td>

                    <!-- Q16 -->
                    <td class="border-r px-4 py-2">
                        <div class="flex items-center gap-1"
                             @mouseenter="hoveredQuestion = 16"
                             @mouseleave="hoveredQuestion = null">
                            <input v-model="answers.q16" class="question-input min-w-[80px] border px-2 py-1 text-center" placeholder="16"/>
                            <div class="relative w-7 h-7">
                                <button
                                    v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                    @click.stop="toggleFlag(16)"
                                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </td>

                    <td class="border-r px-4 py-2" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></td>

                    <!-- Q17 -->
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-1"
                             @mouseenter="hoveredQuestion = 17"
                             @mouseleave="hoveredQuestion = null">
                            <input v-model="answers.q17" class="question-input min-w-[80px] border px-2 py-1 text-center" placeholder="17"/>
                            <div class="relative w-7 h-7">
                                <button
                                    v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                    @click.stop="toggleFlag(17)"
                                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(17) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>

                <!-- Row 2 -->
                <tr class="border-b">
                    <td class="border-r px-4 py-2" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></td>
                    <td class="border-r px-4 py-2" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></td>

                    <!-- Q18 -->
                    <td class="border-r px-4 py-2">
                        <div class="flex items-center gap-1"
                             @mouseenter="hoveredQuestion = 18"
                             @mouseleave="hoveredQuestion = null">
                            <input v-model="answers.q18" class="question-input min-w-[80px] border px-2 py-1 text-center" placeholder="18"/>
                            <div class="relative w-7 h-7">
                                <button
                                    v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                    @click.stop="toggleFlag(18)"
                                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(18) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </td>

                    <td class="border-r px-4 py-2" data-segment-id="35" v-html="getHighlightedSegmentById(35)"></td>
                    <td class="px-4 py-2" data-segment-id="36" v-html="getHighlightedSegmentById(36)"></td>
                </tr>

                <!-- Row 3 -->
                <tr class="border-b">
                    <td class="border-r px-4 py-2" data-segment-id="37" v-html="getHighlightedSegmentById(37)"></td>
                    <td class="border-r px-4 py-2" data-segment-id="38" v-html="getHighlightedSegmentById(38)"></td>
                    <td class="border-r px-4 py-2" data-segment-id="39" v-html="getHighlightedSegmentById(39)"></td>

                    <!-- Q19 -->
                    <td class="border-r px-4 py-2">
                        <div class="flex items-center gap-1"
                             @mouseenter="hoveredQuestion = 19"
                             @mouseleave="hoveredQuestion = null">
                            <input v-model="answers.q19" class="question-input min-w-[80px] border px-2 py-1 text-center" placeholder="19"/>
                            <div class="relative w-7 h-7">
                                <button
                                    v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                    @click.stop="toggleFlag(19)"
                                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(19) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-2" data-segment-id="40" v-html="getHighlightedSegmentById(40)"></td>
                </tr>

                <!-- Row 4 -->
                <tr>
                    <td class="border-r px-4 py-2" data-segment-id="41" v-html="getHighlightedSegmentById(41)"></td>
                    <td class="border-r px-4 py-2" data-segment-id="42" v-html="getHighlightedSegmentById(42)"></td>
                    <td class="border-r px-4 py-2" data-segment-id="43" v-html="getHighlightedSegmentById(43)"></td>
                    <td class="border-r px-4 py-2" data-segment-id="44" v-html="getHighlightedSegmentById(44)"></td>

                    <!-- Q20 -->
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-1"
                             @mouseenter="hoveredQuestion = 20"
                             @mouseleave="hoveredQuestion = null">
                            <span data-segment-id="45" v-html="getHighlightedSegmentById(45)"></span>
                            <input v-model="answers.q20" class="question-input min-w-[60px] border px-2 py-1 text-center" placeholder="20"/>
                            <div class="relative w-7 h-7">
                                <button
                                    v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                    @click.stop="toggleFlag(20)"
                                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(20) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
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
                        <!-- Quote marks icon like in image -->
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
                        <!-- Bold pen/marker icon -->
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

        <!-- Delete Highlight Tooltip - Simple approach with backdrop -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <!-- Tooltip positioned above the backdrop click area -->
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
            >
                <!-- Arrow pointing UP -->
                <div class="tooltip-arrow-up"></div>
                <!-- Tooltip Content -->
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button
                        @click="confirmDeleteHighlight"
                        type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                    >
                        <!-- Trash/Bin icon -->
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

        <!-- Note Hover Tooltip - Appears ABOVE noted text -->
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
                <!-- Tooltip Content -->
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <!-- Note Icon -->
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
                    <!-- Note Text -->
                    <span class="note-tooltip-text max-w-[180px] text-sm wrap-break-word text-gray-700">{{ hoveredNoteText }}</span>
                    <!-- Delete Button -->
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
                <!-- Arrow pointing DOWN -->
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

       <!-- Note Input Modal -->
<div
    v-if="showNoteInput"
    class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-5 shadow-2xl"
    :style="{
        left: `${noteInputPosition.x}px`,
        top: `${noteInputPosition.y}px`,
        transform: 'translateX(-50%)',
    }"
    @click.stop
>
    <div class="mb-4">
        <p class="mb-4 max-h-20 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 italic">
            "{{ selectedText }}"
        </p>
        <input
            v-model="noteInputText"
            type="text"
            spellcheck="false"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            placeholder="Write your note here..."
            class="note-input-field w-full border-2 border-gray-900 px-4 py-3 text-base focus:border-black focus:outline-none"
            @keyup.enter="saveNote"
            @keyup.escape="cancelNote"
        />
    </div>
    <div class="flex justify-end gap-4">
        <button
            @click="cancelNote"
            class="border-2 border-gray-900 bg-white px-4 py-2 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
        >
            Cancel
        </button>
        <button 
            @click="saveNote" 
            class="bg-black px-4 py-2 text-xs font-medium text-white transition-colors hover:bg-gray-800"
        >
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

<!-- Non-scoped styles for v-html generated note indicators -->
<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
}
</style>