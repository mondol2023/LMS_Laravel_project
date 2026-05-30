<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Drag and drop functionality for questions 24-27
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const writerOptions = [
    { value: 'A', label: 'the benefits of moving to a warmer environment' },
    { value: 'B', label: 'the type of weather with the worst effect on mood' },
    { value: 'C', label: 'how past events affect attitudes to weather' },
    { value: 'D', label: 'the important effect of stress on mood' },
    { value: 'E', label: 'the important effect of hours of sunshine on mood' },
    { value: 'F', label: 'psychological problems due to having to cope with bad weather' },
];

const handleDragStart = (e: DragEvent, option: string) => {
    draggedOption.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer24_27 = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

const getWriterOptionLabel = (value: string): string => {
    const option = writerOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

const availableWriterOptions = computed(() => {
    const usedOptions = [
        answers.value.q24,
        answers.value.q25,
        answers.value.q26,
        answers.value.q27,
    ].filter(Boolean);
    return writerOptions.filter((opt) => !usedOptions.includes(opt.value));
});

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

// Multiple choice answers for questions 25-30
const multipleAnswers = ref({
    q28_30: [],
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

// Text segments with explicit IDs and sequential non-overlapping offsets
const textSegments = ref([
    // Part header box text segments
    { id: 'part3-title', text: 'Part 3', offset: 0 },                    // 0-6
    { id: 'part3-desc', text: 'Listen and answer questions 21–30.', offset: 7 }, // 7-42
    // Instructions text segments
    { id: 0, text: 'Questions 21–30', offset: 43 },                      // 43-58
    { id: 'instr1', text: 'Choose the correct letter, ', offset: 59 },   // 59-86
    { id: 'instr2', text: 'A, B or C', offset: 87 },                     // 87-96
    // Questions 21-23 section
    { id: 2, text: 'Questions 21-23', offset: 97 },                      // 97-112
    { id: 3, text: 'Complete the sentences below.', offset: 113 },       // 113-142
    { id: 4, text: 'Write NO MORE THAN THREE WORDS for each answer.', offset: 143 }, // 143-190
    { id: 5, text: 'Effects on weather on mood', offset: 191 },          // 191-217
    // Question 21
    { id: 6, text: "Phil and Stella's goal is to", offset: 218 },        // 218-246
    { id: 7, text: "the hypothesis that weather has an effect on a person's mood.", offset: 247 }, // 247-308
    // Question 22
    { id: 8, text: "They expect to find that 'good' weather (weather which is", offset: 309 }, // 309-366
    { id: 9, text: ") has a positive effect on a person's mood.", offset: 367 }, // 367-410
    // Question 23
    { id: 10, text: 'Stella defines "effect on mood" as a', offset: 411 }, // 411-448
    { id: 11, text: 'in the way a person feels.', offset: 449 },         // 449-475
    // Questions 24-27 section
    { id: 12, text: 'Questions 24-27', offset: 476 },                    // 476-491
    { id: 13, text: 'What information was given by each writer?', offset: 492 }, // 492-534
    { id: 14, text: 'Choose your answers from the list below and write correct letters A-F next to questions 24-27.', offset: 535 }, // 535-630

    // Writer names (Questions 24-27)
    { id: 22, text: 'Vickers', offset: 631 },                            // 631-638
    { id: 23, text: 'Whitebourne', offset: 639 },                        // 639-650
    { id: 24, text: 'Haverton', offset: 651 },                           // 651-659
    { id: 25, text: 'Stanfield', offset: 660 },                          // 660-669
    // Questions 28-30 section
    { id: 26, text: 'Questions 28 and 30', offset: 670 },                // 670-689
    { id: 'q28-30-choose', text: 'Choose <strong>THREE</strong> letters <strong>A-H</strong>.', offset: 690 }, // 690-715 (plain: 25 chars)
    { id: 27, text: 'Which THREE things do Phil and Stella still have to decide on?', offset: 716 }, // 716-779
    // Options A-H for 28-30 (plain text lengths)
    { id: 28, text: '<strong>A</strong> how to analyse their results', offset: 780 },       // 780-810 (30 chars plain)
    { id: 29, text: '<strong>B</strong> their methods of presentation', offset: 811 },      // 811-842 (31 chars plain)
    { id: 30, text: '<strong>C</strong> the design of their questionnaire', offset: 843 },  // 843-878 (35 chars plain)
    { id: 31, text: '<strong>D</strong> the location of their survey', offset: 879 },       // 879-909 (30 chars plain)
    { id: 32, text: '<strong>E</strong> weather variables to be measured', offset: 910 },   // 910-944 (34 chars plain)
    { id: 33, text: '<strong>F</strong> weather conditions to be studied', offset: 945 },   // 945-979 (34 chars plain)
    { id: 34, text: '<strong>G</strong> the size of their survey', offset: 980 },           // 980-1006 (26 chars plain)
    { id: 35, text: '<strong>H</strong> the source of data on weather variables', offset: 1007 }, // 1007-1048 (41 chars plain)
]);

// Helper to apply highlights to text that may contain HTML
const applyHighlightsToHtml = (
    htmlText: string,
    plainText: string,
    highlightsToApply: Array<{ id: number; color: string; start: number; end: number }>,
): string => {
    if (highlightsToApply.length === 0) return htmlText;

    // Build character-level mapping: for each plain text char, store its HTML position
    const charInfo: Array<{ htmlStart: number; htmlEnd: number }> = [];
    let plainIndex = 0;
    let inTag = false;

    for (let htmlIndex = 0; htmlIndex < htmlText.length; htmlIndex++) {
        const char = htmlText[htmlIndex];
        if (char === '<') {
            inTag = true;
        } else if (char === '>') {
            inTag = false;
        } else if (!inTag) {
            charInfo[plainIndex] = { htmlStart: htmlIndex, htmlEnd: htmlIndex + 1 };
            plainIndex++;
        }
    }

    // For each highlight, find continuous segments (not crossing tags)
    const segments: Array<{ htmlStart: number; htmlEnd: number; color: string; id: number }> = [];

    for (const highlight of highlightsToApply) {
        let segStart = highlight.start;

        for (let i = highlight.start; i < highlight.end && i < charInfo.length; i++) {
            const currentChar = charInfo[i];
            const nextChar = i + 1 < highlight.end && i + 1 < charInfo.length ? charInfo[i + 1] : null;

            // Check if there's a tag between current and next character
            const hasTagBetween = nextChar && currentChar.htmlEnd !== nextChar.htmlStart;

            if (hasTagBetween || i === highlight.end - 1 || i === charInfo.length - 1) {
                // End of a continuous segment
                if (charInfo[segStart] && currentChar) {
                    segments.push({
                        htmlStart: charInfo[segStart].htmlStart,
                        htmlEnd: currentChar.htmlEnd,
                        color: highlight.color,
                        id: highlight.id,
                    });
                }
                segStart = i + 1;
            }
        }
    }

    // Sort segments by htmlStart descending (apply from end to start)
    segments.sort((a, b) => b.htmlStart - a.htmlStart);

    let result = htmlText;
    for (const seg of segments) {
        const markStart = `<mark class="highlight-${seg.color}" data-highlight-id="${seg.id}">`;
        const markEnd = '</mark>';
        result = result.substring(0, seg.htmlStart) + markStart + result.substring(seg.htmlStart, seg.htmlEnd) + markEnd + result.substring(seg.htmlEnd);
    }

    return result;
};

// Helper to get plain text from HTML
const getPlainTextFromHtml = (html: string): string => {
    return html.replace(/<[^>]*>/g, '');
};

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;
    const containsHtml = /<[^>]+>/.test(segmentText);
    const plainText = containsHtml ? getPlainTextFromHtml(segmentText) : segmentText;
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
        return segmentText;
    }

    // Combine highlights and notes into a single list of annotations
    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: Math.max(0, h.start_offset - segmentOffset),
            end: Math.min(plainText.length, h.end_offset - segmentOffset),
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: Math.max(0, n.start - segmentOffset),
            end: Math.min(plainText.length, n.end - segmentOffset),
            type: 'note' as const,
            color: 'blue',
            id: n.id,
            noteText: n.note,
        })),
    ];

    // If segment contains HTML, use HTML-aware highlighting
    if (containsHtml) {
        return applyHighlightsToHtml(segmentText, plainText, annotations.map(a => ({
            id: a.id,
            color: a.color,
            start: a.start,
            end: a.end,
        })));
    }

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = segmentText;
    for (const annotation of sorted) {
        if (annotation.start < annotation.end) {
            const before = result.substring(0, annotation.start);
            const annotated = result.substring(annotation.start, annotation.end);
            const after = result.substring(annotation.end);

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
    const allAnswers = { ...answers.value };

    // Handle multiple choice questions 28-30
    if (multipleAnswers.value.q28_30?.length > 0) {
        const selectedOptions = multipleAnswers.value.q28_30;
        allAnswers.q28 = selectedOptions[0] || '';
        allAnswers.q29 = selectedOptions[1] || '';
        allAnswers.q30 = selectedOptions[2] || '';
    }

    return allAnswers;
};

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 3) {
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    return currentAnswers.length >= 3 && !currentAnswers.includes(option);
};

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
                let targetElement: Node | null = range.startContainer;

                while (targetElement && targetElement.nodeType !== Node.ELEMENT_NODE) {
                    targetElement = targetElement.parentNode;
                }

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
                        const selectionStartInElement = getTextOffsetInElement(targetElement as Element, range.startContainer, range.startOffset);
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

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
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

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
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
    answers: { ...answers.value, ...multipleAnswers.value },
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3"
                @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-3 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"></p>
                <p class="text-sm text-gray-700 select-text">
                    <span data-segment-id="instr1" v-html="getHighlightedSegmentById('instr1')"></span>
                    <span class="font-bold" data-segment-id="instr2"
                        v-html="getHighlightedSegmentById('instr2')"></span>
                    <span>.</span>
                </p>
            </div>

            <!-- Scrollable Questions Content -->
            <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
                class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">

                        <!-- Questions 21-23 -->
                        <div class="p-2">
                            <div>
                                <div class="mb-4 flex items-center space-x-2">
                                    <h3 class="text-lg font-bold text-black select-text" data-segment-id="2"
                                        v-html="getHighlightedSegmentById(2)"></h3>
                                </div>
                                <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-lg">
                                    <span class="select-text" data-segment-id="3"
                                        v-html="getHighlightedSegmentById(3)"></span><br />
                                    <!-- FIX 2: font-bold makes the instruction line bold -->
                                    <span class="select-text font-bold" data-segment-id="4"
                                        v-html="getHighlightedSegmentById(4)"></span>
                                </p>
                            </div>
                            <div class="space-y-4">
                                <!-- FIX 3: text-left (was text-center) -->
                                <p class="text-left font-bold select-text" data-segment-id="5"
                                    v-html="getHighlightedSegmentById(5)"></p>
                                <div class="space-y-4 text-base leading-relaxed text-gray-800 sm:text-lg">

                                    <!-- Question 21 -->
                                    <div id="question-21" class="group relative select-text">
                                        <!-- FIX 1: opacity-0 default, revealed on group-hover -->
                                        <button @click.stop="toggleFlag(21)"
                                            class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(21)
                                                    ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                    : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'
                                                "
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <p>
                                            <span class="select-text" data-segment-id="6"
                                                v-html="getHighlightedSegmentById(6)"></span>
                                            <input v-model="answers.q21" type="text" placeholder="21"
                                                class="mx-2 w-36 border border-gray-900 bg-white px-3 py-1 text-center text-sm font-medium transition-colors placeholder:font-bold placeholder:text-gray-900 focus:border-black focus:bg-white focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                            <span class="select-text" data-segment-id="7"
                                                v-html="getHighlightedSegmentById(7)"></span>
                                        </p>
                                    </div>

                                    <!-- Question 22 -->
                                    <div id="question-22" class="group relative select-text">
                                        <!-- FIX 1: opacity-0 default, revealed on group-hover -->
                                        <button @click.stop="toggleFlag(22)"
                                            class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(22)
                                                    ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                    : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'
                                                "
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <p>
                                            <span class="select-text" data-segment-id="8"
                                                v-html="getHighlightedSegmentById(8)"></span>
                                            <input v-model="answers.q22" type="text" placeholder="22"
                                                class="mx-2 w-36 border border-gray-900 bg-white px-3 py-1 text-center text-sm font-medium transition-colors placeholder:font-bold placeholder:text-gray-900 focus:border-black focus:bg-white focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                            <span class="select-text" data-segment-id="9"
                                                v-html="getHighlightedSegmentById(9)"></span>
                                        </p>
                                    </div>

                                    <!-- Question 23 -->
                                    <div id="question-23" class="group relative select-text">
                                        <!-- FIX 1: opacity-0 default, revealed on group-hover -->
                                        <button @click.stop="toggleFlag(23)"
                                            class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(23)
                                                    ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                    : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'
                                                "
                                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <p>
                                            <span class="select-text" data-segment-id="10"
                                                v-html="getHighlightedSegmentById(10)"></span>
                                            <input v-model="answers.q23" type="text" placeholder="23"
                                                class="mx-2 w-36 border border-gray-900 bg-white px-3 py-1 text-center text-sm font-medium transition-colors placeholder:font-bold placeholder:text-gray-900 focus:border-black focus:bg-white focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                            <span class="select-text" data-segment-id="11"
                                                v-html="getHighlightedSegmentById(11)"></span>
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Questions 24-27 -->
                        <div class="p-2">
                            <div class="mb-8">
                                <div class="mb-6 flex items-center space-x-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-black select-text" data-segment-id="12"
                                            v-html="getHighlightedSegmentById(12)"></h3>
                                    </div>
                                </div>
                                <div class="p-2">
                                    <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                        <span class="select-text" data-segment-id="13"
                                            v-html="getHighlightedSegmentById(13)"></span><br />
                                        <span class="select-text" data-segment-id="14"
                                            v-html="getHighlightedSegmentById(14)"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- QUESTIONS 24–27 Drag and Drop Layout -->
                            <div class="flex gap-16">
                                <!-- Left side: Questions with drop zones -->
                                <div class="space-y-5">

                                    <!-- Question 24 -->
                                    <div id="question-24" class="group relative flex items-center gap-3">
                                        <span class="text-base text-gray-700 select-text sm:text-lg"
                                            data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 24 ? 'border-blue-500 bg-blue-50' : answers.q24 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 24)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 24)" @click="clearAnswer24_27(24)"
                                            :title="answers.q24 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q24">{{ getWriterOptionLabel(answers.q24) }}</span>
                                            <span v-else class="font-bold text-gray-900">24</span>
                                        </span>
                                        <!-- FIX 1: opacity-0 default, revealed on group-hover -->
                                        <button @click.stop="toggleFlag(24)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'"
                                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 25 -->
                                    <div id="question-25" class="group relative flex items-center gap-3">
                                        <span class="text-base text-gray-700 select-text sm:text-lg"
                                            data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 25 ? 'border-blue-500 bg-blue-50' : answers.q25 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 25)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 25)" @click="clearAnswer24_27(25)"
                                            :title="answers.q25 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q25">{{ getWriterOptionLabel(answers.q25) }}</span>
                                            <span v-else class="font-bold text-gray-900">25</span>
                                        </span>
                                        <!-- FIX 1: opacity-0 default, revealed on group-hover -->
                                        <button @click.stop="toggleFlag(25)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'"
                                            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 26 -->
                                    <div id="question-26" class="group relative flex items-center gap-3">
                                        <span class="text-base text-gray-700 select-text sm:text-lg"
                                            data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 26 ? 'border-blue-500 bg-blue-50' : answers.q26 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 26)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 26)" @click="clearAnswer24_27(26)"
                                            :title="answers.q26 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q26">{{ getWriterOptionLabel(answers.q26) }}</span>
                                            <span v-else class="font-bold text-gray-900">26</span>
                                        </span>
                                        <!-- FIX 1: opacity-0 default, revealed on group-hover -->
                                        <button @click.stop="toggleFlag(26)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'"
                                            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 27 -->
                                    <div id="question-27" class="group relative flex items-center gap-3">
                                        <span class="text-base text-gray-700 select-text sm:text-lg"
                                            data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 27)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 27)" @click="clearAnswer24_27(27)"
                                            :title="answers.q27 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q27">{{ getWriterOptionLabel(answers.q27) }}</span>
                                            <span v-else class="font-bold text-gray-900">27</span>
                                        </span>
                                        <!-- FIX 1: opacity-0 default, revealed on group-hover -->
                                        <button @click.stop="toggleFlag(27)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'"
                                            :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>

                                <!-- Right side: Draggable options -->
                                <div class="w-72 shrink-0 self-start sticky top-12">
                                    <p class="mb-3 text-sm font-semibold text-gray-700">Drag options to fill blanks:</p>
                                    <div class="border border-gray-900 p-3 bg-white">
                                        <div class="space-y-2">
                                            <div v-for="option in availableWriterOptions" :key="option.value"
                                                class="flex cursor-grab items-center gap-2 rounded border border-gray-300 px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                :class="{ 'opacity-50': draggedOption === option.value }"
                                                draggable="true" @dragstart="handleDragStart($event, option.value)"
                                                @dragend="handleDragEnd">
                                                <span class="font-bold text-gray-900 text-sm">{{ option.value }}</span>
                                                <span class="text-gray-700 text-sm">{{ option.label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 28-30: Multiple Choice -->
                        <div id="question-28-30" class="group relative p-2">
                            <!-- Bookmark Button -->
                            <button @click.stop="toggleFlag(28)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all duration-200"
                                :class="isQuestionFlagged(28)
                                        ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                        : 'border-gray-300 bg-transparent text-gray-400 opacity-0 hover:border-gray-400 hover:text-gray-600 group-hover:opacity-100'
                                    " :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark these questions'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>

                            <div class="mb-2 sm:mb-3 md:mb-4">
                                <div class="mb-1 flex items-center space-x-1.5 sm:space-x-2">
                                    <h3 class="text-sm font-bold text-black select-text sm:text-base"
                                        data-segment-id="26" v-html="getHighlightedSegmentById(26)"></h3>
                                </div>
                                <div class="p-1">
                                    <p class="mb-1 text-sm leading-relaxed font-medium text-gray-800 select-text sm:text-base"
                                        data-segment-id="q28-30-choose" v-html="getHighlightedSegmentById('q28-30-choose')"></p>
                                    <p class="text-sm leading-tight font-semibold text-gray-800 select-text sm:text-base"
                                        data-segment-id="27" v-html="getHighlightedSegmentById(27)"></p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'A') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'A')"
                                        :disabled="isDisabled('q28_30', 'A')"
                                        @change="handleMultipleChoice('q28_30', 'A')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="28"
                                            v-html="getHighlightedSegmentById(28)"></span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'B') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'B')"
                                        :disabled="isDisabled('q28_30', 'B')"
                                        @change="handleMultipleChoice('q28_30', 'B')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="29"
                                            v-html="getHighlightedSegmentById(29)"></span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'C') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'C')"
                                        :disabled="isDisabled('q28_30', 'C')"
                                        @change="handleMultipleChoice('q28_30', 'C')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="30"
                                            v-html="getHighlightedSegmentById(30)"></span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'D') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'D')"
                                        :disabled="isDisabled('q28_30', 'D')"
                                        @change="handleMultipleChoice('q28_30', 'D')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="31"
                                            v-html="getHighlightedSegmentById(31)"></span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'E') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'E')"
                                        :disabled="isDisabled('q28_30', 'E')"
                                        @change="handleMultipleChoice('q28_30', 'E')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="32"
                                            v-html="getHighlightedSegmentById(32)"></span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'F') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'F')"
                                        :disabled="isDisabled('q28_30', 'F')"
                                        @change="handleMultipleChoice('q28_30', 'F')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="33"
                                            v-html="getHighlightedSegmentById(33)"></span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'G') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'G')"
                                        :disabled="isDisabled('q28_30', 'G')"
                                        @change="handleMultipleChoice('q28_30', 'G')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="34"
                                            v-html="getHighlightedSegmentById(34)"></span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 p-0.5 transition-colors"
                                    :class="isDisabled('q28_30', 'H') ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                    <input type="checkbox" :checked="isSelected('q28_30', 'H')"
                                        :disabled="isDisabled('q28_30', 'H')"
                                        @change="handleMultipleChoice('q28_30', 'H')"
                                        class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed sm:h-4 sm:w-4" />
                                    <span class="text-sm leading-relaxed font-medium text-gray-800 sm:text-base">
                                        <span class="select-text" data-segment-id="35"
                                            v-html="getHighlightedSegmentById(35)"></span>
                                    </span>
                                </label>
                            </div>

                            <div class="mt-1 p-1">
                                <p class="text-xs font-medium text-gray-700">
                                    Selected: {{ multipleAnswers.q28_30.length }}/3 answers
                                </p>
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
            <div class="highlight-tooltip pointer-events-auto fixed z-9999" :style="{
                left: `${contextMenuPosition.x}px`,
                top: `${contextMenuPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @click.stop>
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
                    "{{ selectedText }}"</p>
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