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

// Listening Part 3 component

// Single choice answers for questions 21-30
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

// Drag and drop functionality for questions 24-27
const dragOptions = ref(['A. He will do this.', 'B. He might do this.', 'C. He won\'t do this.']);
const dragOverTarget = ref<number | null>(null);
const currentDrag = ref<string | null>(null); // for touch support

type AnswerKey = keyof typeof answers.value;

// Extract just the letter (A, B, or C) from the full option text
function getLetterFromOption(opt: string): string {
    const match = opt.match(/^([A-C])\./);
    return match ? match[1] : opt;
}

// Get full option text from letter
function getFullOptionFromLetter(letter: string): string {
    const option = dragOptions.value.find(opt => opt.startsWith(letter + '.'));
    return option || letter;
}

// Mouse drag handlers
function onDragStart(event: DragEvent, opt: string) {
    event.dataTransfer?.setData('text/plain', opt);
    event.dataTransfer!.effectAllowed = 'copy';
    currentDrag.value = opt;
}

function onDrop(event: DragEvent, questionNum: number) {
    event.preventDefault();

    const opt = event.dataTransfer?.getData('text/plain') || currentDrag.value;
    if (!opt) return;

    const key = `q${questionNum}` as AnswerKey;
    // Store just the letter (A, B, or C)
    answers.value[key] = getLetterFromOption(opt);

    dragOverTarget.value = null;
    currentDrag.value = null;
}

function clearDrop(questionNum: number) {
    const key = `q${questionNum}` as AnswerKey;
    answers.value[key] = '';
}

// Touch handlers (for mobile)
function onTouchStart(_event: TouchEvent, opt: string) {
    currentDrag.value = opt;
}

function onTouchEnd(_event: TouchEvent, questionNum: number) {
    if (!currentDrag.value) return;

    const key = `q${questionNum}` as AnswerKey;
    answers.value[key] = currentDrag.value;

    currentDrag.value = null;
    dragOverTarget.value = null;
}
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

// Text segments with explicit IDs and offsets
const textSegments = ref([
    { id: 'part3-title',          text: 'Part 3',                                                                          offset: 0 },
    { id: 'part3-desc',           text: 'Listen and answer questions 21–30.',                                               offset: 6 },
    { id: 'q-header',             text: 'QUESTIONS 21-30',                                                                  offset: 0 },
    { id: 'q-header-desc',        text: 'Choose the correct answer for each question.',                                     offset: 16 },
    { id: 'section-title',        text: 'Latin American Studies',                                                           offset: 61 },
    { id: 'q21-26-header',        text: 'Questions 21–26',                                                                  offset: 83 },
    { id: 'choose-letter-instr',  text: 'Choose the correct letter,',                                                       offset: 100 },
    { id: 'abc-options',          text: 'A, B, or C',                                                                       offset: 126 },
    { id: 'period',               text: '.',                                                                                offset: 136 },
    { id: 'write-boxes-instr',    text: 'Write your answers in boxes',                                                      offset: 138 },
    { id: 'boxes-range',          text: '21–26',                                                                            offset: 165 },
    { id: 'on-answer-sheet',      text: 'on your answer sheet.',                                                            offset: 171 },
    { id: 'q21-stem',             text: '21. Paul decided to get work experience in South America because he wanted',            offset: 192 },
    { id: 'q21-a',                text: 'A. to teach English there',                                                        offset: 263 },
    { id: 'q21-b',                text: 'B. to improve his Spanish',                                                        offset: 288 },
    { id: 'q21-c',                text: 'C. to learn about Latin American life',                                            offset: 313 },
    { id: 'q22-stem',             text: '22. What project work did Paul originally intend to get involved in?',                 offset: 350 },
    { id: 'q22-a',                text: 'A. construction',                                                                  offset: 415 },
    { id: 'q22-b',                text: 'B. agriculture',                                                                   offset: 430 },
    { id: 'q22-c',                text: 'C. tourism',                                                                       offset: 444 },
    { id: 'q23-stem',             text: '23. Why did Paul change from one project to another?',                                 offset: 454 },
    { id: 'q23-a',                text: 'A. His first job was not well organized',                                          offset: 503 },
    { id: 'q23-b',                text: 'B. He found doing the routine work very boring',                                   offset: 543 },
    { id: 'q23-c',                text: 'C. The work was too physically demanding',                                         offset: 590 },
    { id: 'q24-stem',             text: '24. In the village community, he learnt how important it was to',                     offset: 631 },
    { id: 'q24-a',                text: 'A. respect family life',                                                           offset: 691 },
    { id: 'q24-b',                text: 'B. develop trust',                                                                 offset: 713 },
    { id: 'q24-c',                text: 'C. use money wisely',                                                              offset: 729 },
    { id: 'q25-stem',             text: '25. What does Paul say about his project manager?',                                    offset: 748 },
    { id: 'q25-a',                text: 'A. He let Paul do most of the work',                                               offset: 794 },
    { id: 'q25-b',                text: 'B. His plans were too ambitious',                                                  offset: 829 },
    { id: 'q25-c',                text: 'C. He was very supportive of Paul',                                                offset: 860 },
    { id: 'q26-stem',             text: '26. Paul was surprised to be given',                                                   offset: 893 },
    { id: 'q26-a',                text: 'A. a computer to use',                                                             offset: 924 },
    { id: 'q26-b',                text: 'B. so little money to live on',                                                    offset: 944 },
    { id: 'q26-c',                text: 'C. an extension to his contract',                                                  offset: 973 },
    { id: 'q27-30-header',        text: 'Questions 27-30',                                                                  offset: 1004 },
    { id: 'q27-30-instr',         text: 'What does Paul decide about each of the following modules ?',                      offset: 1020 },
    { id: 'write-correct-letter', text: 'Write the correct letter',                                                         offset: 1080 },
    { id: 'abc-match',            text: 'A, B or C',                                                                        offset: 1104 },
    { id: 'next-to-q',            text: 'next to questions',                                                                offset: 1113 },
    { id: 'q-range',              text: '27–30.',                                                                            offset: 1130 },
    { id: 'legend-a',           text: 'A. He will do this.',   offset: 1136 },
    { id: 'legend-b',           text: 'B. He might do this.',  offset: 1157 },
    { id: 'legend-c',           text: "C. He won't do this.",  offset: 1179 },
    { id: 'q27-label',          text: 'Gender studies in Latin America', offset: 1202 }, // 1179+20+3gap
    { id: 'q28-label',          text: 'Second language acquisition',     offset: 1235 }, // 1202+31+2gap
    { id: 'q29-label',          text: "Indigenous women's lives",        offset: 1264 }, // 1235+27+2gap
    { id: 'q30-label',          text: 'Portuguese language studies',     offset: 1290 }, // 1264+24+2gap
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

// Initialize answers and load drafts
// Expose methods for parent component
const getAnswers = () => {
    const allAnswers = { ...answers.value };

    return allAnswers;
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
    // Handle grouped questions for 28-30
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 28 && questionNumber <= 30) {
        targetId = 'question-28-30';
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
    answers: { ...answers.value },
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
                    data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"
                ></p>
            </div>

            <!-- Questions Header -->


            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-2 lg:p-6">

                    <!-- Questions 21–26 Block -->
                    <div class="border-black bg-white p-3">
                        <h1 class=" text-lg font-bold mb-1">
                            <span data-segment-id="section-title" class="select-text" v-html="getHighlightedSegmentById('section-title')"></span>
                        </h1>

                        <!-- Q21-26 sub-header -->
                        <div class="mb-2">

                        </div>

                        <div class="space-y-4">
                            <!-- Question 21 -->
                            <div
                                id="question-21"
                                class="flex gap-2 border-black bg-white p-3"
                                @mouseenter="hoveredQuestion = 21"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q21-stem" class="select-text" v-html="getHighlightedSegmentById('q21-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q21" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q21-a" class="select-text" v-html="getHighlightedSegmentById('q21-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q21" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q21-b" class="select-text" v-html="getHighlightedSegmentById('q21-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q21" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q21-c" class="select-text" v-html="getHighlightedSegmentById('q21-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                    @click.stop="toggleFlag(21)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(21) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 22 -->
                            <div
                                id="question-22"
                                class="flex gap-2 border-black bg-white p-3"
                                @mouseenter="hoveredQuestion = 22"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q22-stem" class="select-text" v-html="getHighlightedSegmentById('q22-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q22" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q22-a" class="select-text" v-html="getHighlightedSegmentById('q22-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q22" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q22-b" class="select-text" v-html="getHighlightedSegmentById('q22-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q22" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q22-c" class="select-text" v-html="getHighlightedSegmentById('q22-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                    @click.stop="toggleFlag(22)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(22) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 23 -->
                            <div
                                id="question-23"
                                class="flex gap-2 border-black bg-white p-3"
                                @mouseenter="hoveredQuestion = 23"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q23-stem" class="select-text" v-html="getHighlightedSegmentById('q23-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q23" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q23-a" class="select-text" v-html="getHighlightedSegmentById('q23-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q23" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q23-b" class="select-text" v-html="getHighlightedSegmentById('q23-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q23" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q23-c" class="select-text" v-html="getHighlightedSegmentById('q23-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                    @click.stop="toggleFlag(23)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(23) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 24 -->
                            <div
                                id="question-24"
                                class="flex gap-2 border-black bg-white p-3"
                                @mouseenter="hoveredQuestion = 24"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q24-stem" class="select-text" v-html="getHighlightedSegmentById('q24-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q24" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q24-a" class="select-text" v-html="getHighlightedSegmentById('q24-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q24" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q24-b" class="select-text" v-html="getHighlightedSegmentById('q24-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q24" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q24-c" class="select-text" v-html="getHighlightedSegmentById('q24-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                    @click.stop="toggleFlag(24)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(24) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 25 -->
                            <div
                                id="question-25"
                                class="flex gap-2 border-black bg-white p-3"
                                @mouseenter="hoveredQuestion = 25"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q25-stem" class="select-text" v-html="getHighlightedSegmentById('q25-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q25" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q25-a" class="select-text" v-html="getHighlightedSegmentById('q25-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q25" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q25-b" class="select-text" v-html="getHighlightedSegmentById('q25-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q25" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q25-c" class="select-text" v-html="getHighlightedSegmentById('q25-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                    @click.stop="toggleFlag(25)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(25) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 26 -->
                            <div
                                id="question-26"
                                class="flex gap-2 border-black bg-white p-3"
                                @mouseenter="hoveredQuestion = 26"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <div class="flex-1 text-base ">
                                    <p class="mb-3 font-bold text-gray-700">
                                        <span data-segment-id="q26-stem" class="select-text" v-html="getHighlightedSegmentById('q26-stem')"></span>
                                    </p>
                                    <div class="space-y-3">
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q26" type="radio" value="A" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q26-a" class="select-text" v-html="getHighlightedSegmentById('q26-a')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q26" type="radio" value="B" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q26-b" class="select-text" v-html="getHighlightedSegmentById('q26-b')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <input v-model="answers.q26" type="radio" value="C" class="h-4 w-4 border-black text-black accent-black" />
                                            <span data-segment-id="q26-c" class="select-text" v-html="getHighlightedSegmentById('q26-c')"></span>
                                        </label>
                                    </div>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                    @click.stop="toggleFlag(26)"
                                    class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all self-start mt-1"
                                    :class="isQuestionFlagged(26) ? 'border-red-300 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Questions 27–30: Drag & Drop Matching -->
                    <div class="border-black bg-white p-4 mt-4">
                        <h3 class="text-lg font-bold text-black mb-2">
                            <span data-segment-id="q27-30-header" class="select-text" v-html="getHighlightedSegmentById('q27-30-header')"></span>
                        </h3>
                        <p class="mb-2 text-sm leading-relaxed text-gray-700">
                            <span data-segment-id="q27-30-instr" class="select-text" v-html="getHighlightedSegmentById('q27-30-instr')"></span><br />
                            <span data-segment-id="write-correct-letter" class="select-text" v-html="getHighlightedSegmentById('write-correct-letter')"></span>
                            <strong><span data-segment-id="abc-match" class="select-text" v-html="getHighlightedSegmentById('abc-match')"></span></strong>
                            <span data-segment-id="next-to-q" class="select-text" v-html="getHighlightedSegmentById('next-to-q')"></span>
                            <strong><span data-segment-id="q-range" class="select-text" v-html="getHighlightedSegmentById('q-range')"></span></strong>
                        </p>

                        <!-- Main content with questions on left, drag options fixed on right -->
                        <div class="flex gap-6">
                            <!-- Drop targets: Q27–Q30 -->
                            <div class="flex-1 space-y-3">
                                <!-- Q27 -->
                                <div
                                    id="question-27"
                                    class="group/q27 flex items-center gap-3 p-3 rounded border border-gray-200"
                                >
                                    <span class="text-sm font-bold text-gray-600">27</span>
                                    <span data-segment-id="q27-label" class="flex-1 text-base text-gray-800 select-text" v-html="getHighlightedSegmentById('q27-label')"></span>
                                    <!-- Drop zone -->
                                    <div
                                        class="drop-zone flex h-10 w-44 shrink-0 items-center justify-center border"
                                        :class="dragOverTarget === 27 ? 'border-black' : answers.q27 ? 'border-black' : 'border-dashed border-gray-400 bg-gray-50'"
                                        @dragover.prevent="dragOverTarget = 27"
                                        @dragleave="dragOverTarget = null"
                                        @drop.prevent="onDrop($event, 27)"
                                        @touchend.prevent="onTouchEnd($event, 27)"
                                    >
                                        <span
                                            v-if="answers.q27"
                                            class="font-normal text-sm cursor-pointer select-none"
                                            @click.stop="clearDrop(27)"
                                            title="Click to remove"
                                        >{{ getFullOptionFromLetter(answers.q27) }}</span>
                                        <span v-else class="text-gray-400 text-xs">Drop here</span>
                                    </div>
                                    <button
                                        @click.stop="toggleFlag(27)"
                                        class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                            opacity-5 group-hover/q27:opacity-100"
                                        :class="isQuestionFlagged(27)
                                            ? 'border-red-300 text-red-500 opacity-100!'
                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q28 -->
                                <div
                                    id="question-28"
                                    class="group/q28 flex items-center gap-3 p-3 rounded border border-gray-200"
                                >
                                    <span class="text-sm font-bold text-gray-600">28</span>
                                    <span data-segment-id="q28-label" class="flex-1 text-base text-gray-800 select-text" v-html="getHighlightedSegmentById('q28-label')"></span>
                                    <div
                                        class="drop-zone flex h-10 w-44 shrink-0 items-center justify-center border"
                                        :class="dragOverTarget === 28 ? 'border-black' : answers.q28 ? 'border-black' : 'border-dashed border-gray-400 bg-gray-50'"
                                        @dragover.prevent="dragOverTarget = 28"
                                        @dragleave="dragOverTarget = null"
                                        @drop.prevent="onDrop($event, 28)"
                                        @touchend.prevent="onTouchEnd($event, 28)"
                                    >
                                        <span
                                            v-if="answers.q28"
                                            class="font-normal text-sm cursor-pointer select-none"
                                            @click.stop="clearDrop(28)"
                                            title="Click to remove"
                                        >{{ getFullOptionFromLetter(answers.q28) }}</span>
                                        <span v-else class="text-gray-400 text-xs">Drop here</span>
                                    </div>
                                    <button
                                        @click.stop="toggleFlag(28)"
                                        class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                            opacity-5 group-hover/q28:opacity-100"
                                        :class="isQuestionFlagged(28)
                                            ? 'border-red-300 text-red-500 opacity-100!'
                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q29 -->
                                <div
                                    id="question-29"
                                    class="group/q29 flex items-center gap-3 p-3 rounded border border-gray-200"
                                >
                                    <span class="text-sm font-bold text-gray-600">29</span>
                                    <span data-segment-id="q29-label" class="flex-1 text-base text-gray-800 select-text" v-html="getHighlightedSegmentById('q29-label')"></span>
                                    <div
                                        class="drop-zone flex h-10 w-44 shrink-0 items-center justify-center border transition-colors"
                                        :class="dragOverTarget === 29 ? 'border-black' : answers.q29 ? 'border-black' : 'border-dashed border-gray-400 bg-gray-50'"
                                        @dragover.prevent="dragOverTarget = 29"
                                        @dragleave="dragOverTarget = null"
                                        @drop.prevent="onDrop($event, 29)"
                                        @touchend.prevent="onTouchEnd($event, 29)"
                                    >
                                        <span
                                            v-if="answers.q29"
                                            class="font-normal text-sm cursor-pointer select-none"
                                            @click.stop="clearDrop(29)"
                                            title="Click to remove"
                                        >{{ getFullOptionFromLetter(answers.q29) }}</span>
                                        <span v-else class="text-gray-400 text-xs">Drop here</span>
                                    </div>
                                    <button
                                        @click.stop="toggleFlag(29)"
                                        class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                            opacity-5 group-hover/q29:opacity-100"
                                        :class="isQuestionFlagged(29)
                                            ? 'border-red-300 text-red-500 opacity-100!'
                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q30 -->
                                <div
                                    id="question-30"
                                    class="group/q30 flex items-center gap-3 p-3 rounded border border-gray-200"
                                >
                                    <span class="text-sm font-bold text-gray-600">30</span>
                                    <span data-segment-id="q30-label" class="flex-1 text-base text-gray-800 select-text" v-html="getHighlightedSegmentById('q30-label')"></span>
                                    <div
                                        class="drop-zone flex h-10 w-44 shrink-0 items-center justify-center border"
                                        :class="dragOverTarget === 30 ? 'border-black' : answers.q30 ? 'border-black' : 'border-dashed border-gray-400 bg-gray-50'"
                                        @dragover.prevent="dragOverTarget = 30"
                                        @dragleave="dragOverTarget = null"
                                        @drop.prevent="onDrop($event, 30)"
                                        @touchend.prevent="onTouchEnd($event, 30)"
                                    >
                                        <span
                                            v-if="answers.q30"
                                            class="font-normal text-sm cursor-pointer select-none"
                                            @click.stop="clearDrop(30)"
                                            title="Click to remove"
                                        >{{ getFullOptionFromLetter(answers.q30) }}</span>
                                        <span v-else class="text-gray-400 text-xs">Drop here</span>
                                    </div>
                                    <button
                                        @click.stop="toggleFlag(30)"
                                        class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                            opacity-5 group-hover/q30:opacity-100"
                                        :class="isQuestionFlagged(30)
                                            ? 'border-red-300 text-red-500 opacity-100!'
                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Fixed drag source options on the right -->
                            <div class="w-44 flex-shrink-0 sticky top-4 self-start">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Drag options</p>
                                <div class="flex flex-col gap-2" id="drag-source-pool">
                                    <template v-for="opt in dragOptions" :key="opt">
                                        <div
                                            :draggable="true"
                                            @dragstart="onDragStart($event, opt)"
                                            @touchstart.prevent="onTouchStart($event, opt)"
                                            class="inline-flex h-10 w-full cursor-grab items-center justify-center border border-black text-gray-800 font-bold text-sm select-none active:cursor-grabbing hover:bg-gray-50"
                                        >
                                            <span
                                                :data-segment-id="opt === dragOptions[0] ? 'legend-a' : opt === dragOptions[1] ? 'legend-b' : 'legend-c'"
                                                class="select-text text-xs"
                                                v-html="getHighlightedSegmentById(opt === dragOptions[0] ? 'legend-a' : opt === dragOptions[1] ? 'legend-b' : 'legend-c')"
                                            ></span>
                                        </div>
                                    </template>
                                </div>
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
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-black transition-colors hover:bg-gray-800">Save Note</button>
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
