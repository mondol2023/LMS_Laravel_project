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
// Multiple choice answers for questions 31-34

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Delete highlight tooltip
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note hover tooltip
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// Text segments with explicit IDs and offsets
const textSegments = ref([
    { id: 'part4-title',               text: 'Part 4',                                                                                    offset: 0 },
    { id: 'part4-desc',                text: 'Listen and answer questions 31–40.',                                                         offset: 6 },
    { id: 'q-header',                  text: 'QUESTIONS 31-40',                                                                            offset: 0 },
    { id: 'q-header-desc1',            text: 'Complete the notes below.',                                                                  offset: 16 },
    { id: 'q-header-desc2',            text: 'Write NO MORE THAN TWO WORDS for each answer.',                                              offset: 43 },
    { id: 'section-title',             text: 'Trying to repeat success',                                                                   offset: 89 },
    { id: 'q31-34-header',             text: 'Questions 31–34',                                                                            offset: 114 },
    { id: 'choose-letter-instr',       text: 'Choose the correct letter,',                                                                 offset: 131 },
    { id: 'abc-options',               text: 'A, B, or C',                                                                                 offset: 159 },
    { id: 'q31-stem',                  text: '31. Compared to introducing new business processes, attempts to copy existing processes are',     offset: 171 },
    { id: 'q31-a',                     text: 'A. more attractive',                                                                         offset: 257 },
    { id: 'q31-b',                     text: 'B. more frequent',                                                                           offset: 276 },
    { id: 'q31-c',                     text: 'C. more straightforward',                                                                    offset: 295 },
    { id: 'q32-stem',                  text: '32. Most research into the repetition of success in business has',                                offset: 319 },
    { id: 'q32-a',                     text: 'A. been done outside the United States',                                                     offset: 381 },
    { id: 'q32-b',                     text: 'B. produced consistent findings',                                                            offset: 420 },
    { id: 'q32-c',                     text: 'C. related to only a few contexts',                                                          offset: 452 },
    { id: 'q33-stem',                  text: '33. What does the speaker say about consulting experts?',                                         offset: 486 },
    { id: 'q33-a',                     text: 'A. Too few managers ever do it',                                                             offset: 538 },
    { id: 'q33-b',                     text: 'B. It can be useful in certain circumstances',                                               offset: 570 },
    { id: 'q33-c',                     text: 'C. Experts are sometimes unwilling to give advice',                                          offset: 616 },
    { id: 'q34-stem',                  text: '34. An expert\u2019s knowledge about a business system may be incomplete because',              offset: 666 },
    { id: 'q34-a',                     text: 'A. some details are difficult for workers to explain',                                       offset: 739 },
    { id: 'q34-b',                     text: 'B. workers choose not to mention certain details',                                           offset: 791 },
    { id: 'q34-c',                     text: 'C. details are sometimes altered by workers',                                                offset: 839 },
    { id: 'q35-40-header',             text: 'Questions 35\u201340',                                                                       offset: 881 },
    { id: 'notes-instr',               text: 'Complete the notes below. Write',                                                            offset: 898 },
    { id: 'one-word-only',             text: 'ONE WORD ONLY',                                                                              offset: 931 },
    { id: 'notes-title',               text: 'Setting up systems based on an existing process',                                            offset: 945 },
    { id: 'two-mistakes-heading',      text: 'Two mistakes',                                                                               offset: 995 },
    { id: 'manager-tries',             text: 'Manager tries to:',                                                                          offset: 1008 },
    { id: 'improve-original',          text: 'Improve on the original process',                                                            offset: 1026 },
    { id: 'create-ideal-prefix',       text: 'Create an ideal',                                                                            offset: 1058 },
    { id: 'from-best-parts',           text: 'from the best parts of several processes',                                                   offset: 1075 },
    { id: 'cause-problems-heading',    text: 'Cause of problems',                                                                          offset: 1116 },
    { id: 'info-inaccurate',           text: 'Information was inaccurate',                                                                 offset: 1134 },
    { id: 'comparison-invalid',        text: 'Comparison between the business settings was invalid',                                       offset: 1160 },
    { id: 'disadvantages-prefix',      text: 'Disadvantages were overlooked e.g. effect of changes on',                                   offset: 1211 },
    { id: 'solution-heading',          text: 'Solution',                                                                                   offset: 1269 },
    { id: 'change-prefix',             text: 'Change',                                                                                     offset: 1278 },
    { id: 'impose-rigorous-prefix',    text: 'Impose rigorous',                                                                            offset: 1285 },
    { id: 'copy-original',             text: 'Copy original very closely:',                                                                offset: 1301 },
    { id: 'physical-features-prefix',  text: 'Physical features of the',                                                                   offset: 1329 },
    { id: 'the-prefix',                text: 'The',                                                                                        offset: 1354 },
    { id: 'of-original-employees',     text: 'of original employees',                                                                     offset: 1358 },
]);

// Helper to apply highlights to text that may contain HTML
const applyHighlightsToHtml = (
    htmlText: string,
    plainText: string,
    highlightsToApply: Array<{ id: number; color: string; start: number; end: number }>,
): string => {
    if (highlightsToApply.length === 0) return htmlText;

    // Build a mapping from plain text index to HTML index
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
    plainToHtml[plainIndex] = htmlText.length;

    // Sort highlights by start position descending
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
            color: 'yellow',
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

// Initialize answers and load drafts
// Expose methods for parent component
const getAnswers = () => {
    const allAnswers = { ...answers.value };

    return allAnswers;
};

// Focus handling for better UX
const focusedInput = ref<string | null>(null);

const handleFocus = (questionKey: string) => {
    focusedInput.value = questionKey;
};

const handleBlur = () => {
    focusedInput.value = null;
};

// Input validation - limit to 2 words
const handleInput = (questionKey: string, event: Event) => {
    const target = event.target as HTMLInputElement;
    const words = target.value.trim().split(/\s+/);

    if (words.length > 2 && target.value.trim() !== '') {
        // Limit to first 2 words
        const limitedValue = words.slice(0, 2).join(' ');
        answers.value[questionKey as keyof typeof answers.value] = limitedValue;
        target.value = limitedValue;
    } else {
        answers.value[questionKey as keyof typeof answers.value] = target.value;
    }
};

// Helper function to calculate text offset within an element
const getTextOffsetInElement = (element: Element, targetNode: Node, targetOffset: number): number => {
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null);
    let offset = 0;
    let node: Node | null;

    while ((node = walker.nextNode())) {
        if (node === targetNode) {
            return offset + targetOffset;
        }
        offset += (node.textContent || '').length;
    }
    return offset;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        const startNode = range?.startContainer as HTMLElement;
        if (startNode?.nodeName === 'INPUT' || startNode?.closest?.('input')) {
            showContextMenu.value = false;
            return;
        }

        if (rect && contentTextRef.value) {
            // Position tooltip ABOVE the selection with arrow pointing down
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

            if (selection && range) {
                // Find which text segment this selection belongs to
                let targetElement: Node | null = range.startContainer;

                // Traverse up to find the element node
                while (targetElement && targetElement.nodeType !== Node.ELEMENT_NODE) {
                    targetElement = targetElement.parentNode;
                }

                // Look for element with data-segment-id attribute
                while (targetElement && !(targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const parent = targetElement.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetElement = parent;
                }

                if (targetElement && (targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const segmentIdAttr = (targetElement as Element).getAttribute('data-segment-id') || '';
                    const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
                    const segment = textSegments.value.find((s) => s.id === segmentId);

                    if (segment) {
                        // Calculate the actual selection start position within the element
                        // by walking through text nodes to find the exact offset
                        const selectionStartInElement = getTextOffsetInElement(targetElement as Element, range.startContainer, range.startOffset);

                        // The selection start in element corresponds directly to position in segment text
                        const startOffset = segment.offset + selectionStartInElement;
                        const endOffset = startOffset + selected.length;

                        selectedText.value = selected;
                        selectionRange.value = { start: startOffset, end: endOffset };
                    }
                }
            }
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

    // Remove overlapping highlights (last action wins - note overwrites highlight)
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    // Remove overlapping notes
    notes.value = notes.value.filter((n) => {
        return !(n.start < newEnd && n.end > newStart);
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

// Delete highlight
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
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

// Handle mouse leave from noted text
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



const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    // Handle grouped questions for 31-32 and 33-34
    let targetId = `question-${questionNumber}`;


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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box -->
            <div class="part-header-box mb-1 rounded border border-gray-200 px-4 py-2" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part4-title"
                    v-html="getHighlightedSegmentById('part4-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part4-desc"
                    v-html="getHighlightedSegmentById('part4-desc')"
                ></p>
            </div>



            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">

                    <!-- Questions 31–34 Block -->
                    <div id="question-31-34" class="mb-6 p-4 sm:p-1 md:p-1">
                        <h1 class=" text-lg font-bold mb-4">
                            <span data-segment-id="section-title" class="select-text" v-html="getHighlightedSegmentById('section-title')"></span>
                        </h1>

                        <div class="space-y-4">
                            <!-- Question 31 -->
                            <div
                                id="question-31"
                                class="flex gap-2 p-3"
                                @mouseenter="hoveredQuestion = 31"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q31-stem" class="select-text" v-html="getHighlightedSegmentById('q31-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q31" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q31-a" class="select-text" v-html="getHighlightedSegmentById('q31-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q31" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q31-b" class="select-text" v-html="getHighlightedSegmentById('q31-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q31" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q31-c" class="select-text" v-html="getHighlightedSegmentById('q31-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                    @click.stop="toggleFlag(31)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(31) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 32 -->
                            <div
                                id="question-32"
                                class="flex gap-2 p-3"
                                @mouseenter="hoveredQuestion = 32"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q32-stem" class="select-text" v-html="getHighlightedSegmentById('q32-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q32" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q32-a" class="select-text" v-html="getHighlightedSegmentById('q32-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q32" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q32-b" class="select-text" v-html="getHighlightedSegmentById('q32-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q32" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q32-c" class="select-text" v-html="getHighlightedSegmentById('q32-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                    @click.stop="toggleFlag(32)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(32) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 33 -->
                            <div
                                id="question-33"
                                class="flex gap-2 p-3"
                                @mouseenter="hoveredQuestion = 33"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q33-stem" class="select-text" v-html="getHighlightedSegmentById('q33-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q33" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q33-a" class="select-text" v-html="getHighlightedSegmentById('q33-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q33" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q33-b" class="select-text" v-html="getHighlightedSegmentById('q33-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q33" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q33-c" class="select-text" v-html="getHighlightedSegmentById('q33-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                    @click.stop="toggleFlag(33)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(33) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 34 -->
                            <div
                                id="question-34"
                                class="flex gap-2 p-3"
                                @mouseenter="hoveredQuestion = 34"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q34-stem" class="select-text" v-html="getHighlightedSegmentById('q34-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q34" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q34-a" class="select-text" v-html="getHighlightedSegmentById('q34-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q34" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q34-b" class="select-text" v-html="getHighlightedSegmentById('q34-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q34" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q34-c" class="select-text" v-html="getHighlightedSegmentById('q34-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                    @click.stop="toggleFlag(34)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(34) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Questions 35–40: Notes completion -->
                    <div id="question-35-40" class="mb-6 p-4 sm:p-6 md:p-8">
                        <div class="mb-4 sm:mb-6">
                            <div class="mb-4 flex  space-x-3 sm:space-x-4">

                                <h3 class="text-base font-bold text-black  ">
                                    <span data-segment-id="q35-40-header" class="select-text" v-html="getHighlightedSegmentById('q35-40-header')"></span>
                                </h3>
                            </div>
                            <div class=" bg-white p-4 sm:p-3 md:p-3">
                                <p class="mb-3 text-sm leading-relaxed font-medium text-gray-800 sm:text-base ">
                                    <span data-segment-id="notes-instr" class="select-text" v-html="getHighlightedSegmentById('notes-instr')"></span>
                                    <strong class="text-black"><span data-segment-id="one-word-only" class="select-text" v-html="getHighlightedSegmentById('one-word-only')"></span></strong>.
                                </p>
                            </div>
                        </div>

                        <!-- Notes Content Box -->
                        <div class="space-y-3  bg-white p-4 sm:p-3">
                            <!-- Title -->
                            <h2 class="mb-4 font-semibold tracking-wide text-zinc-900 uppercase">
                                <span data-segment-id="notes-title" class="select-text" v-html="getHighlightedSegmentById('notes-title')"></span>
                            </h2>

                            <!-- Section: Two mistakes -->
                            <div>
                                <h3 class="mb-2 font-bold text-gray-800">
                                    <span data-segment-id="two-mistakes-heading" class="select-text" v-html="getHighlightedSegmentById('two-mistakes-heading')"></span>
                                </h3>
                                <ul class="list-inside list-disc space-y-2 text-sm text-gray-700 sm:text-base">
                                    <li>
                                        <span data-segment-id="manager-tries" class="select-text" v-html="getHighlightedSegmentById('manager-tries')"></span>
                                    </li>
                                    <ul class="ml-1 list-inside list-disc space-y-2">
                                        <li>
                                            <span data-segment-id="improve-original" class="select-text" v-html="getHighlightedSegmentById('improve-original')"></span>
                                        </li>
                                        <li>
                                            <span data-segment-id="create-ideal-prefix" class="select-text" v-html="getHighlightedSegmentById('create-ideal-prefix')"></span>
                                            <span class="inline-flex items-center gap-2">
                                                <span
                                                    id="question-35"
                                                    class="group relative inline-flex items-center"
                                                    @mouseenter="hoveredQuestion = 35"
                                                    @mouseleave="hoveredQuestion = null"
                                                >
                                                    <input
                                                        v-model="answers.q35"
                                                        type="text"
                                                        class="w-[160px] border border-black bg-transparent px-1 py-0.5 text-center focus:border-black focus:outline-none sm:w-[200px]"
                                                        placeholder="35"
                                                        @focus="hoveredQuestion = 35"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                      <span data-segment-id="from-best-parts" class="select-text" v-html="getHighlightedSegmentById('from-best-parts')"></span>

                                                    <button
                                                        v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                        @click.stop="toggleFlag(35)"
                                                        class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(35) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                        :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </span>
                                          </li>
                                    </ul>
                                </ul>
                            </div>

                            <!-- Section: Cause of problems -->
                            <div>
                                <h3 class="mb-2 font-bold text-gray-800">
                                    <span data-segment-id="cause-problems-heading" class="select-text" v-html="getHighlightedSegmentById('cause-problems-heading')"></span>
                                </h3>
                                <ul class="list-inside list-disc space-y-2 text-sm text-gray-700 sm:text-base">
                                    <li>
                                        <span data-segment-id="info-inaccurate" class="select-text" v-html="getHighlightedSegmentById('info-inaccurate')"></span>
                                    </li>
                                    <li>
                                        <span data-segment-id="comparison-invalid" class="select-text" v-html="getHighlightedSegmentById('comparison-invalid')"></span>
                                    </li>
                                    <li>
                                        <span data-segment-id="disadvantages-prefix" class="select-text" v-html="getHighlightedSegmentById('disadvantages-prefix')"></span>
                                        <span class="inline-flex items-center gap-2">
                                            <span
                                                id="question-36"
                                                class="group relative inline-flex items-center"
                                                @mouseenter="hoveredQuestion = 36"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q36"
                                                    type="text"
                                                    class="w-[160px] border border-black bg-transparent px-1 py-0.5 text-center focus:border-black focus:outline-none sm:w-[200px]"
                                                    placeholder="36"
                                                    @focus="hoveredQuestion = 36"
                                                    @blur="hoveredQuestion = null"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                    @click.stop="toggleFlag(36)"
                                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(36) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Section: Solution -->
                            <div>
                                <h3 class="mb-2 font-bold text-gray-800">
                                    <span data-segment-id="solution-heading" class="select-text" v-html="getHighlightedSegmentById('solution-heading')"></span>
                                </h3>
                                <ul class="list-inside list-disc space-y-2 text-sm text-gray-700 sm:text-base">
                                    <li>
                                        <span data-segment-id="change-prefix" class="select-text" v-html="getHighlightedSegmentById('change-prefix')"></span>
                                        <span class="inline-flex items-center gap-2">
                                            <span
                                                id="question-37"
                                                class="group relative inline-flex items-center"
                                                @mouseenter="hoveredQuestion = 37"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q37"
                                                    type="text"
                                                    class="w-[160px] border border-black bg-transparent px-1 py-0.5 text-center focus:border-black focus:outline-none sm:w-[200px]"
                                                    placeholder="37"
                                                    @focus="hoveredQuestion = 37"
                                                    @blur="hoveredQuestion = null"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                    @click.stop="toggleFlag(37)"
                                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(37) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </span>
                                    </li>
                                    <li>
                                        <span data-segment-id="impose-rigorous-prefix" class="select-text" v-html="getHighlightedSegmentById('impose-rigorous-prefix')"></span>
                                        <span class="inline-flex items-center gap-2">
                                            <span
                                                id="question-38"
                                                class="group relative inline-flex items-center"
                                                @mouseenter="hoveredQuestion = 38"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q38"
                                                    type="text"
                                                    class="w-[160px] border border-black bg-transparent px-1 py-0.5 text-center focus:border-black focus:outline-none sm:w-[200px]"
                                                    placeholder="38"
                                                    @focus="hoveredQuestion = 38"
                                                    @blur="hoveredQuestion = null"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                    @click.stop="toggleFlag(38)"
                                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(38) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </span>
                                    </li>
                                    <li>
                                        <span data-segment-id="copy-original" class="select-text" v-html="getHighlightedSegmentById('copy-original')"></span>
                                    </li>
                                    <ul class="ml-1 list-inside list-disc space-y-2">
                                        <li>
                                            <span data-segment-id="physical-features-prefix" class="select-text" v-html="getHighlightedSegmentById('physical-features-prefix')"></span>
                                            <span class="inline-flex items-center gap-2">
                                                <span
                                                    id="question-39"
                                                    class="group relative inline-flex items-center"
                                                    @mouseenter="hoveredQuestion = 39"
                                                    @mouseleave="hoveredQuestion = null"
                                                >
                                                    <input
                                                        v-model="answers.q39"
                                                        type="text"
                                                        class="w-[160px] border border-black bg-transparent px-1 py-0.5 text-center focus:border-black focus:outline-none sm:w-[200px]"
                                                        placeholder="39"
                                                        @focus="hoveredQuestion = 39"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                    <button
                                                        v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                        @click.stop="toggleFlag(39)"
                                                        class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(39) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                        :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </span>
                                        </li>
                                        <li>
                                            <span data-segment-id="the-prefix" class="select-text" v-html="getHighlightedSegmentById('the-prefix')"></span>
                                            <span class="inline-flex items-center gap-2">
                                                <span
                                                    id="question-40"
                                                    class="group relative inline-flex items-center pr-10"
                                                >
                                                    <input
                                                        v-model="answers.q40"
                                                        type="text"
                                                        class="w-[160px] border border-black bg-transparent px-1 py-0.5 text-center focus:outline-none sm:w-[200px]"
                                                        placeholder="40"
                                                    />
                                                    <button
                                                        @click.stop="toggleFlag(40)"
                                                        class="absolute right-0 top-1/2 -translate-y-1/2
                                                            flex h-7 w-7 items-center justify-center
                                                            rounded border transition-all duration-150
                                                            opacity-0 group-hover:opacity-100 group-focus-within:opacity-100"
                                                        :class="isQuestionFlagged(40)
                                                            ? 'border-red-300 text-red-500 !opacity-100'
                                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                        :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </span>
                                            <span data-segment-id="of-original-employees" class="select-text" v-html="getHighlightedSegmentById('of-original-employees')"></span>
                                        </li>
                                    </ul>
                                </ul>
                            </div>
                        </div>
                        <!-- Page number -->
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
                    <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"/>
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-9999" :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                        <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Hover Tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }" @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm wrap-break-word text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div v-if="showNoteInput" class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }" @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off" placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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
