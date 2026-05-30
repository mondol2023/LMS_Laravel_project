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

const emit = defineEmits<{ toggleFlag: [questionNumber: number] }>();

const hoveredQuestion = ref<number | null>(null);
const isQuestionFlagged = (n: number) => props.flaggedQuestions.has(n);
const toggleFlag = (n: number) => emit('toggleFlag', n);

const contentZoom = computed(() => ({ zoom: props.fontSize / 16 }));

const answers = ref({
    q31: '', q32: '', q33: '', q34: '',
    q35: '', q36: '', q37: '', q38: '', q39: '', q40: '',
});

const multipleAnswers = ref({
    q31_32: [] as string[],
    q33_34: [] as string[],
});

const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// All offsets are sequential and contiguous for proper multiline highlighting
// This allows selecting text across multiple segments to work correctly
const textSegments = ref([
    // Part header (outside contentTextRef — not interactive for highlight)
    { id: 'part4-title', text: 'Part 4', offset: 0 },                              // 0-6
    { id: 'part4-desc', text: 'Listen and answer questions 31–40.', offset: 7 },   // 7-42
    { id: 'instr-heading', text: 'Questions 31–40', offset: 43 },                  // 43-58
    { id: 'instr1', text: 'Complete the notes below. Write ', offset: 59 },        // 59-91
    { id: 'instr2', text: 'NO MORE THAN TWO WORDS AND/OR A NUMBER', offset: 92 },  // 92-131

    // ── Q31-32 section ──
    { id: 'q3132-heading', text: 'Questions 31 and 32', offset: 132 },             // 132-151
    { id: 'q3132-choose', text: 'Choose <strong>TWO</strong> letters <strong>A–F</strong>.', offset: 152 }, // plain: 22 chars → 152-174
    { id: 'q3132-question', text: 'Which TWO of the following problems are causing concern to educational authorities in the USA?', offset: 175 }, // 95 chars → 175-270
    // Options — letter prefix is static in template, only option text here
    { id: 'q3132-A', text: 'differences between rich and poor students', offset: 271 },   // 42 chars → 271-313
    { id: 'q3132-B', text: 'high numbers dropping out of education', offset: 314 },       // 38 chars → 314-352
    { id: 'q3132-C', text: 'falling standards of students', offset: 353 },                // 29 chars → 353-382
    { id: 'q3132-D', text: 'poor results compared with other nationalities', offset: 383 }, // 46 chars → 383-429
    { id: 'q3132-E', text: 'low scores of overseas students', offset: 430 },              // 31 chars → 430-461
    { id: 'q3132-F', text: 'differences between rural and urban students', offset: 462 }, // 44 chars → 462-506

    // ── Q33-34 section ──
    { id: 'q3334-heading', text: 'Questions 33 and 34', offset: 507 },             // 507-526
    { id: 'q3334-choose', text: 'Choose <strong>TWO</strong> letters <strong>A–F</strong>.', offset: 527 }, // plain: 22 chars → 527-549
    { id: 'q3334-question', text: 'According to the speaker, what are two advantages of reducing class sizes?', offset: 550 }, // 74 chars → 550-624
    { id: 'q3334-A', text: 'more employment for teachers', offset: 625 },          // 28 chars → 625-653
    { id: 'q3334-B', text: 'improvement in general health of the population', offset: 654 }, // 47 chars → 654-701
    { id: 'q3334-C', text: 'reduction in number of days taken off sick by teachers', offset: 702 }, // 54 chars → 702-756
    { id: 'q3334-D', text: 'better use of existing buildings and resources', offset: 757 }, // 46 chars → 757-803
    { id: 'q3334-E', text: 'better level of education of workforce', offset: 804 }, // 38 chars → 804-842
    { id: 'q3334-F', text: 'availability of better qualified teachers', offset: 843 }, // 41 chars → 843-884

    // ── Q35-40 table section ──
    { id: 'q3540-heading', text: 'Questions 35-40', offset: 885 },                 // 885-900
    { id: 'q3540-instr', text: 'Complete the table below. Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.', offset: 901 }, // 90 chars → 901-991
    { id: 'q3540-title', text: 'USA Research Projects into Class Size', offset: 992 }, // 37 chars → 992-1029
    // Table headers
    { id: 'th-state', text: 'State', offset: 1030 },                               // 1030-1035
    { id: 'th-schools', text: 'Schools involved', offset: 1036 },                  // 1036-1052
    { id: 'th-students', text: 'Number of students participating', offset: 1053 }, // 1053-1085
    { id: 'th-findings', text: 'Key findings', offset: 1086 },                     // 1086-1098
    { id: 'th-problems', text: 'Problems', offset: 1099 },                         // 1099-1107
    // Tennessee row
    { id: 'tn-state', text: 'Tennessee', offset: 1108 },                           // 1108-1117
    { id: 'tn-schools', text: 'about 70 schools', offset: 1118 },                  // 1118-1134
    { id: 'tn-q35-pre', text: 'in total', offset: 1135 },                          // 1135-1143
    { id: 'tn-q36-pre', text: 'significant benefit especially for', offset: 1144 }, // 1144-1179
    { id: 'tn-q36-post', text: 'pupils', offset: 1180 },                           // 1180-1186
    { id: 'tn-problems', text: 'lack of agreement on implications of data', offset: 1187 }, // 1187-1228
    // California row
    { id: 'ca-state', text: 'California', offset: 1229 },                          // 1229-1239
    { id: 'ca-q37-post', text: 'schools', offset: 1240 },                          // 1240-1247
    { id: 'ca-students', text: '1.8 million', offset: 1248 },                      // 1248-1259
    { id: 'ca-findings', text: 'very little benefit', offset: 1260 },              // 1260-1279
    { id: 'ca-q38-pre', text: '– shortage of', offset: 1280 },                     // 1280-1293
    { id: 'ca-q38-post', text: 'especially in poorer areas – no proper method for', offset: 1294 }, // 1294-1343
    { id: 'ca-q39-post', text: 'of project', offset: 1344 },                       // 1344-1354
    // Wisconsin row
    { id: 'wi-state', text: 'Wisconsin', offset: 1355 },                           // 1355-1364
    { id: 'wi-q40-pre', text: '14 schools with pupils from', offset: 1365 },       // 1365-1392
    { id: 'wi-q40-post', text: 'families', offset: 1393 },                         // 1393-1401
    { id: 'wi-findings', text: 'similar results to Tennessee project', offset: 1402 }, // 1402-1438
]);

// Helper: strip HTML tags to get plain text
const getPlainTextFromHtml = (html: string): string => {
    return html.replace(/<[^>]*>/g, '');
};

// Helper: apply highlights to HTML content, splitting at tag boundaries
const applyHighlightsToHtml = (
    htmlText: string,
    plainText: string,
    highlightsToApply: Array<{ id: number; color: string; start: number; end: number; type: 'highlight' | 'note' }>,
): string => {
    if (highlightsToApply.length === 0) return htmlText;

    // Build mapping: for each plain text character, store its position in the HTML string
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

    // For each highlight, split into segments at tag boundaries
    const segments: Array<{ htmlStart: number; htmlEnd: number; color: string; id: number; type: 'highlight' | 'note' }> = [];

    for (const highlight of highlightsToApply) {
        let segStart = highlight.start;

        for (let i = highlight.start; i < highlight.end && i < charInfo.length; i++) {
            const currentChar = charInfo[i];
            const nextChar = i + 1 < highlight.end && i + 1 < charInfo.length ? charInfo[i + 1] : null;

            // Check if there's an HTML tag between current and next character
            const hasTagBetween = nextChar && currentChar.htmlEnd !== nextChar.htmlStart;

            if (hasTagBetween || i === highlight.end - 1 || i === charInfo.length - 1) {
                // End of a segment - save it
                if (charInfo[segStart] && currentChar) {
                    segments.push({
                        htmlStart: charInfo[segStart].htmlStart,
                        htmlEnd: currentChar.htmlEnd,
                        color: highlight.color,
                        id: highlight.id,
                        type: highlight.type,
                    });
                }
                segStart = i + 1;
            }
        }
    }

    // Apply segments in reverse order (to preserve positions)
    segments.sort((a, b) => b.htmlStart - a.htmlStart);

    let result = htmlText;
    for (const seg of segments) {
        const markStart = seg.type === 'note'
            ? `<mark class="highlight-${seg.color} note-highlight" data-note-id="${seg.id}">`
            : `<mark class="highlight-${seg.color}" data-highlight-id="${seg.id}">`;
        const markEnd = '</mark>';
        result = result.substring(0, seg.htmlStart) + markStart + result.substring(seg.htmlStart, seg.htmlEnd) + markEnd + result.substring(seg.htmlEnd);
    }

    return result;
};

const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;
    const hasHtml = /<[^>]+>/.test(segmentText);
    const plainText = hasHtml ? getPlainTextFromHtml(segmentText) : segmentText;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset,
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset,
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    // Convert global offsets to local (relative to this segment's plain text)
    const localHighlights = [
        ...overlappingHighlights.map((h) => ({
            id: h.id,
            color: h.color,
            start: Math.max(0, h.start_offset - segmentOffset),
            end: Math.min(plainText.length, h.end_offset - segmentOffset),
            type: 'highlight' as const,
        })),
        ...overlappingNotes.map((n) => ({
            id: n.id,
            color: 'yellow',
            start: Math.max(0, n.start - segmentOffset),
            end: Math.min(plainText.length, n.end - segmentOffset),
            type: 'note' as const,
        })),
    ].filter((h) => h.start < h.end);

    // Use HTML-aware highlighting if segment contains HTML
    if (hasHtml) {
        return applyHighlightsToHtml(segmentText, plainText, localHighlights);
    }

    // Plain text highlighting (original logic)
    const annotations = localHighlights.sort((a, b) => b.start - a.start);

    let result = plainText;
    for (const annotation of annotations) {
        const before = result.substring(0, annotation.start);
        const annotated = result.substring(annotation.start, annotation.end);
        const after = result.substring(annotation.end);
        result = annotation.type === 'note'
            ? `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`
            : `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
    }
    return result;
};

const getAnswers = () => {
    const allAnswers = { ...answers.value };
    if (multipleAnswers.value.q31_32.length > 0) {
        allAnswers.q31 = multipleAnswers.value.q31_32[0] || '';
        allAnswers.q32 = multipleAnswers.value.q31_32[1] || '';
    }
    if (multipleAnswers.value.q33_34.length > 0) {
        allAnswers.q33 = multipleAnswers.value.q33_34[0] || '';
        allAnswers.q34 = multipleAnswers.value.q33_34[1] || '';
    }
    return allAnswers;
};

const getTextOffsetInElement = (element: Element, targetNode: Node, targetOffset: number): number => {
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null);
    let offset = 0;
    let node: Node | null;
    while ((node = walker.nextNode())) {
        if (node === targetNode) return offset + targetOffset;
        offset += (node.textContent || '').length;
    }
    return offset;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && contentTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 58;
            const vw = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), vw - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection && range) {
                let targetElement: Node | null = range.startContainer;
                while (targetElement && targetElement.nodeType !== Node.ELEMENT_NODE)
                    targetElement = targetElement.parentNode;
                while (targetElement && !(targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const parent = targetElement.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetElement = parent;
                }
                if (targetElement && (targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const attr = (targetElement as Element).getAttribute('data-segment-id') || '';
                    const segId = /^\d+$/.test(attr) ? parseInt(attr, 10) : attr;
                    const segment = textSegments.value.find((s) => s.id === segId);
                    if (segment) {
                        const start = segment.offset + getTextOffsetInElement(targetElement as Element, range.startContainer, range.startOffset);
                        selectedText.value = selected;
                        selectionRange.value = { start, end: start + selected.length };
                    }
                }
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    const modalWidth = 320; const modalHeight = 180; const padding = 10;
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x;
    let y = contextMenuPosition.value.y + 70;
    if (selection && selection.rangeCount > 0) {
        const r = selection.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    }
    const vw = window.innerWidth; const vh = window.innerHeight;
    const hw = modalWidth / 2;
    if (x - hw < padding) x = hw + padding;
    else if (x + hw > vw - padding) x = vw - hw - padding;
    if (y + modalHeight > vh - padding) {
        if (selection && selection.rangeCount > 0)
            y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
        if (y < padding) y = padding;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: newStart, end: newEnd } = selectionRange.value;
    findOverlappingHighlights(newStart, newEnd).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });
    noteInputText.value = ''; showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const highlightMark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement;
    if (highlightMark) {
        const id = highlightMark.getAttribute('data-highlight-id');
        if (id) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(id, 10);
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
        deleteHighlight(highlightToDelete.value);
        showDeleteTooltip.value = false; highlightToDelete.value = null;
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const noteMark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find((n) => n.id === noteIdNum);
            if (note) {
                const rect = noteMark.getBoundingClientRect();
                let y = rect.top - 58;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = noteIdNum; hoveredNoteText.value = note.note; showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);
    if (index > -1) currentAnswers.splice(index, 1);
    else if (currentAnswers.length < 2) currentAnswers.push(option);
};

const isSelected = (questionGroup: string, option: string) =>
    multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);

const isDisabled = (questionGroup: string, option: string) => {
    const cur = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    return cur.length >= 2 && !cur.includes(option);
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 31 && questionNumber <= 32) targetId = 'question-31-32';
    else if (questionNumber >= 33 && questionNumber <= 34) targetId = 'question-33-34';
    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
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

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box (outside contentTextRef — no highlight interaction) -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part4-title"
                    v-html="getHighlightedSegmentById('part4-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part4-desc"
                    v-html="getHighlightedSegmentById('part4-desc')"></p>
            </div>

            <!-- Instructions Section (outside contentTextRef — no highlight interaction) -->
            <div class="shrink-0 px-2 pb-3 sm:px-3">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="instr-heading"
                    v-html="getHighlightedSegmentById('instr-heading')"></p>
                <p class="text-sm text-gray-700 select-text">
                    <span data-segment-id="instr1" v-html="getHighlightedSegmentById('instr1')"></span>
                    <span class="font-bold" data-segment-id="instr2"
                        v-html="getHighlightedSegmentById('instr2')"></span>
                    <span> for each answer.</span>
                </p>
            </div>

            <!-- Scrollable Questions Content — contentTextRef wraps ONLY highlightable Q content -->
            <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
                class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">

                    <!-- ───── Questions 31-32 ───── -->
                    <div id="question-31-32" class="relative mb-4 p-2" @mouseenter="hoveredQuestion = 31"
                        @mouseleave="hoveredQuestion = null">
                        <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)" @click.stop="toggleFlag(31)"
                            class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                            </svg>
                        </button>

                        <h3 class="mb-2 text-base font-bold text-black select-text" data-segment-id="q3132-heading"
                            v-html="getHighlightedSegmentById('q3132-heading')"></h3>

                        <div class="mb-2 px-1">
                            <p class="mb-1 text-sm font-medium text-gray-800 select-text" data-segment-id="q3132-choose"
                                v-html="getHighlightedSegmentById('q3132-choose')"></p>
                            <p class="text-sm font-semibold text-gray-800 select-text" data-segment-id="q3132-question"
                                v-html="getHighlightedSegmentById('q3132-question')"></p>
                        </div>

                        <!-- FIX: compact space-y-0.5, text-sm, bold letter labels outside segment -->
                        <div class="space-y-0.5">
                            <label
                                v-for="(opt, letter) in { A: 'q3132-A', B: 'q3132-B', C: 'q3132-C', D: 'q3132-D', E: 'q3132-E', F: 'q3132-F' }"
                                :key="letter" class="flex items-center gap-2 px-1 py-1 transition-colors"
                                :class="isDisabled('q31_32', letter) ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                <input type="checkbox" :checked="isSelected('q31_32', letter)"
                                    :disabled="isDisabled('q31_32', letter)"
                                    @change="handleMultipleChoice('q31_32', letter)"
                                    class="h-4 w-4 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed" />
                                <span class="text-sm font-bold text-gray-800 select-none">{{ letter }}</span>
                                <span class="text-sm font-medium text-gray-800 select-text" :data-segment-id="opt"
                                    v-html="getHighlightedSegmentById(opt)"></span>
                            </label>
                        </div>

                        <p class="mt-2 flex items-center gap-1.5 px-1 text-xs font-semibold text-gray-600">

                            Selected: {{ multipleAnswers.q31_32.length }}/2 answers
                        </p>
                    </div>

                    <!-- ───── Questions 33-34 ───── -->
                    <div id="question-33-34" class="relative mb-4 p-2" @mouseenter="hoveredQuestion = 33"
                        @mouseleave="hoveredQuestion = null">
                        <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)" @click.stop="toggleFlag(33)"
                            class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                            </svg>
                        </button>

                        <h3 class="mb-2 text-base font-bold text-black select-text" data-segment-id="q3334-heading"
                            v-html="getHighlightedSegmentById('q3334-heading')"></h3>

                        <div class="mb-2 px-1">
                            <p class="mb-1 text-sm font-medium text-gray-800 select-text" data-segment-id="q3334-choose"
                                v-html="getHighlightedSegmentById('q3334-choose')"></p>
                            <p class="text-sm font-semibold text-gray-800 select-text" data-segment-id="q3334-question"
                                v-html="getHighlightedSegmentById('q3334-question')"></p>
                        </div>

                        <div class="space-y-0.5">
                            <label
                                v-for="(opt, letter) in { A: 'q3334-A', B: 'q3334-B', C: 'q3334-C', D: 'q3334-D', E: 'q3334-E', F: 'q3334-F' }"
                                :key="letter" class="flex items-center gap-2 px-1 py-1 transition-colors"
                                :class="isDisabled('q33_34', letter) ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-gray-50'">
                                <input type="checkbox" :checked="isSelected('q33_34', letter)"
                                    :disabled="isDisabled('q33_34', letter)"
                                    @change="handleMultipleChoice('q33_34', letter)"
                                    class="h-4 w-4 flex-shrink-0 border-gray-300 accent-black disabled:cursor-not-allowed" />
                                <span class="text-sm font-bold text-gray-800 select-none">{{ letter }}</span>
                                <span class="text-sm font-medium text-gray-800 select-text" :data-segment-id="opt"
                                    v-html="getHighlightedSegmentById(opt)"></span>
                            </label>
                        </div>

                        <p class="mt-2 flex items-center gap-1.5 px-1 text-xs font-semibold text-gray-600">

                            Selected: {{ multipleAnswers.q33_34.length }}/2 answers
                        </p>
                    </div>

                    <!-- ───── Questions 35-40 ───── -->
                    <div class="p-2">
                        <div class="mb-4">
                            <h3 class="mb-1 text-base font-bold text-black select-text" data-segment-id="q3540-heading"
                                v-html="getHighlightedSegmentById('q3540-heading')"></h3>
                            <p class="mb-1 text-sm font-medium text-gray-800 select-text" data-segment-id="q3540-instr"
                                v-html="getHighlightedSegmentById('q3540-instr')"></p>
                            <p class="text-sm font-semibold text-gray-800 select-text" data-segment-id="q3540-title"
                                v-html="getHighlightedSegmentById('q3540-title')"></p>
                        </div>

                        <!-- Mobile Card Layout -->
                        <div class="block space-y-4 lg:hidden">
                            <!-- Tennessee Card -->
                            <div class="border border-gray-300 p-3">
                                <div class="mb-3 border-b border-gray-200 pb-2">
                                    <span class="text-base font-bold text-black select-text" data-segment-id="tn-state"
                                        v-html="getHighlightedSegmentById('tn-state')"></span>
                                </div>
                                <div class="space-y-3 text-sm">
                                    <div>
                                        <span class="font-semibold text-gray-600">Schools:</span>
                                        <span class="ml-1 select-text" data-segment-id="tn-schools"
                                            v-html="getHighlightedSegmentById('tn-schools')"></span>
                                    </div>
                                    <div id="question-35-mobile" class="relative" @mouseenter="hoveredQuestion = 35"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                            @click.stop="toggleFlag(35)"
                                            class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="font-semibold text-gray-600">Students:</span>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="select-text" data-segment-id="tn-q35-pre"
                                                v-html="getHighlightedSegmentById('tn-q35-pre')"></span>
                                            <input v-model="answers.q35" type="text"
                                                class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                spellcheck="false" autocomplete="off" placeholder="35" />
                                        </div>
                                    </div>
                                    <div id="question-36-mobile" class="relative" @mouseenter="hoveredQuestion = 36"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                            @click.stop="toggleFlag(36)"
                                            class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="font-semibold text-gray-600">Key findings:</span>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="select-text" data-segment-id="tn-q36-pre"
                                                v-html="getHighlightedSegmentById('tn-q36-pre')"></span>
                                            <input v-model="answers.q36" type="text"
                                                class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                spellcheck="false" autocomplete="off" placeholder="36" />
                                            <span class="select-text" data-segment-id="tn-q36-post"
                                                v-html="getHighlightedSegmentById('tn-q36-post')"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-600">Problems:</span>
                                        <span class="ml-1 select-text" data-segment-id="tn-problems"
                                            v-html="getHighlightedSegmentById('tn-problems')"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- California Card -->
                            <div class="border border-gray-300 p-3">
                                <div class="mb-3 border-b border-gray-200 pb-2">
                                    <span class="text-base font-bold text-black select-text" data-segment-id="ca-state"
                                        v-html="getHighlightedSegmentById('ca-state')"></span>
                                </div>
                                <div class="space-y-3 text-sm">
                                    <div id="question-37-mobile" class="relative" @mouseenter="hoveredQuestion = 37"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                            @click.stop="toggleFlag(37)"
                                            class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="font-semibold text-gray-600">Schools:</span>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <input v-model="answers.q37" type="text"
                                                class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                spellcheck="false" autocomplete="off" placeholder="37" />
                                            <span class="select-text" data-segment-id="ca-q37-post"
                                                v-html="getHighlightedSegmentById('ca-q37-post')"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-600">Students:</span>
                                        <span class="ml-1 select-text" data-segment-id="ca-students"
                                            v-html="getHighlightedSegmentById('ca-students')"></span>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-600">Key findings:</span>
                                        <span class="ml-1 select-text" data-segment-id="ca-findings"
                                            v-html="getHighlightedSegmentById('ca-findings')"></span>
                                    </div>
                                    <div id="question-38-mobile" class="relative" @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                            @click.stop="toggleFlag(38)"
                                            class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="font-semibold text-gray-600">Problems:</span>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="select-text" data-segment-id="ca-q38-pre"
                                                v-html="getHighlightedSegmentById('ca-q38-pre')"></span>
                                            <input v-model="answers.q38" type="text"
                                                class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                spellcheck="false" autocomplete="off" placeholder="38" />
                                            <span class="select-text" data-segment-id="ca-q38-post"
                                                v-html="getHighlightedSegmentById('ca-q38-post')"></span>
                                        </div>
                                    </div>
                                    <div id="question-39-mobile" class="relative" @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                            @click.stop="toggleFlag(39)"
                                            class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <input v-model="answers.q39" type="text"
                                                class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                spellcheck="false" autocomplete="off" placeholder="39" />
                                            <span class="select-text" data-segment-id="ca-q39-post"
                                                v-html="getHighlightedSegmentById('ca-q39-post')"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Wisconsin Card -->
                            <div class="border border-gray-300 p-3">
                                <div class="mb-3 border-b border-gray-200 pb-2">
                                    <span class="text-base font-bold text-black select-text" data-segment-id="wi-state"
                                        v-html="getHighlightedSegmentById('wi-state')"></span>
                                </div>
                                <div class="space-y-3 text-sm">
                                    <div id="question-40-mobile" class="relative" @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                            @click.stop="toggleFlag(40)"
                                            class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="font-semibold text-gray-600">Schools:</span>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="select-text" data-segment-id="wi-q40-pre"
                                                v-html="getHighlightedSegmentById('wi-q40-pre')"></span>
                                            <input v-model="answers.q40" type="text"
                                                class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                spellcheck="false" autocomplete="off" placeholder="40" />
                                            <span class="select-text" data-segment-id="wi-q40-post"
                                                v-html="getHighlightedSegmentById('wi-q40-post')"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-600">Key findings:</span>
                                        <span class="ml-1 select-text" data-segment-id="wi-findings"
                                            v-html="getHighlightedSegmentById('wi-findings')"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table Layout -->
                        <div class="hidden lg:block">
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr>
                                        <th class="border border-gray-300 bg-gray-50 p-2 text-left font-bold text-gray-800 select-text"
                                            data-segment-id="th-state" v-html="getHighlightedSegmentById('th-state')">
                                        </th>
                                        <th class="border border-gray-300 bg-gray-50 p-2 text-left font-bold text-gray-800 select-text"
                                            data-segment-id="th-schools"
                                            v-html="getHighlightedSegmentById('th-schools')"></th>
                                        <th class="border border-gray-300 bg-gray-50 p-2 text-left font-bold text-gray-800 select-text"
                                            data-segment-id="th-students"
                                            v-html="getHighlightedSegmentById('th-students')"></th>
                                        <th class="border border-gray-300 bg-gray-50 p-2 text-left font-bold text-gray-800 select-text"
                                            data-segment-id="th-findings"
                                            v-html="getHighlightedSegmentById('th-findings')"></th>
                                        <th class="border border-gray-300 bg-gray-50 p-2 text-left font-bold text-gray-800 select-text"
                                            data-segment-id="th-problems"
                                            v-html="getHighlightedSegmentById('th-problems')"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Tennessee Row -->
                                    <tr>
                                        <td class="border border-gray-300 p-2 align-top font-semibold text-gray-800">
                                            <span class="select-text" data-segment-id="tn-state"
                                                v-html="getHighlightedSegmentById('tn-state')"></span>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top text-gray-800">
                                            <span class="select-text" data-segment-id="tn-schools"
                                                v-html="getHighlightedSegmentById('tn-schools')"></span>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top">
                                            <div id="question-35" class="relative flex flex-wrap items-center gap-1"
                                                @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null">
                                                <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                    @click.stop="toggleFlag(35)"
                                                    class="absolute -top-1 -right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(35) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                                <span class="select-text" data-segment-id="tn-q35-pre"
                                                    v-html="getHighlightedSegmentById('tn-q35-pre')"></span>
                                                <input v-model="answers.q35" type="text"
                                                    class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                    spellcheck="false" autocomplete="off" placeholder="35" />
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top">
                                            <div id="question-36" class="relative flex flex-wrap items-center gap-1"
                                                @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null">
                                                <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                    @click.stop="toggleFlag(36)"
                                                    class="absolute -top-1 -right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(36) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                                <span class="select-text" data-segment-id="tn-q36-pre"
                                                    v-html="getHighlightedSegmentById('tn-q36-pre')"></span>
                                                <input v-model="answers.q36" type="text"
                                                    class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                    spellcheck="false" autocomplete="off" placeholder="36" />
                                                <span class="select-text" data-segment-id="tn-q36-post"
                                                    v-html="getHighlightedSegmentById('tn-q36-post')"></span>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top text-gray-700">
                                            <span class="select-text" data-segment-id="tn-problems"
                                                v-html="getHighlightedSegmentById('tn-problems')"></span>
                                        </td>
                                    </tr>
                                    <!-- California Row -->
                                    <tr>
                                        <td class="border border-gray-300 p-2 align-top font-semibold text-gray-800">
                                            <span class="select-text" data-segment-id="ca-state"
                                                v-html="getHighlightedSegmentById('ca-state')"></span>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top">
                                            <div id="question-37" class="relative flex flex-wrap items-center gap-1"
                                                @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                                <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                    @click.stop="toggleFlag(37)"
                                                    class="absolute -top-1 -right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(37) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                                <input v-model="answers.q37" type="text"
                                                    class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                    spellcheck="false" autocomplete="off" placeholder="37" />
                                                <span class="select-text" data-segment-id="ca-q37-post"
                                                    v-html="getHighlightedSegmentById('ca-q37-post')"></span>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top text-gray-800">
                                            <span class="select-text" data-segment-id="ca-students"
                                                v-html="getHighlightedSegmentById('ca-students')"></span>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top text-gray-600">
                                            <span class="select-text" data-segment-id="ca-findings"
                                                v-html="getHighlightedSegmentById('ca-findings')"></span>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top">
                                            <div class="space-y-2">
                                                <div id="question-38" class="relative flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 38"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                        @click.stop="toggleFlag(38)"
                                                        class="absolute -top-1 -right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(38) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                        <svg class="h-3.5 w-3.5" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path
                                                                d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                        </svg>
                                                    </button>
                                                    <span class="select-text" data-segment-id="ca-q38-pre"
                                                        v-html="getHighlightedSegmentById('ca-q38-pre')"></span>
                                                    <input v-model="answers.q38" type="text"
                                                        class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                        spellcheck="false" autocomplete="off" placeholder="38" />
                                                    <span class="select-text" data-segment-id="ca-q38-post"
                                                        v-html="getHighlightedSegmentById('ca-q38-post')"></span>
                                                </div>
                                                <div id="question-39" class="relative flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 39"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                        @click.stop="toggleFlag(39)"
                                                        class="absolute -top-1 -right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(39) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                        <svg class="h-3.5 w-3.5" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path
                                                                d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                        </svg>
                                                    </button>
                                                    <input v-model="answers.q39" type="text"
                                                        class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                        spellcheck="false" autocomplete="off" placeholder="39" />
                                                    <span class="select-text" data-segment-id="ca-q39-post"
                                                        v-html="getHighlightedSegmentById('ca-q39-post')"></span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Wisconsin Row -->
                                    <tr>
                                        <td class="border border-gray-300 p-2 align-top font-semibold text-gray-800">
                                            <span class="select-text" data-segment-id="wi-state"
                                                v-html="getHighlightedSegmentById('wi-state')"></span>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top">
                                            <div id="question-40" class="relative flex flex-wrap items-center gap-1"
                                                @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null">
                                                <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                    @click.stop="toggleFlag(40)"
                                                    class="absolute -top-1 -right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(40) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                                <span class="select-text" data-segment-id="wi-q40-pre"
                                                    v-html="getHighlightedSegmentById('wi-q40-pre')"></span>
                                                <input v-model="answers.q40" type="text"
                                                    class="w-32 border border-gray-900 px-3 py-1 text-center placeholder:font-bold placeholder:text-gray-900"
                                                    spellcheck="false" autocomplete="off" placeholder="40" />
                                                <span class="select-text" data-segment-id="wi-q40-post"
                                                    v-html="getHighlightedSegmentById('wi-q40-post')"></span>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top text-gray-800"></td>
                                        <td class="border border-gray-300 p-2 align-top text-gray-700">
                                            <span class="select-text" data-segment-id="wi-findings"
                                                v-html="getHighlightedSegmentById('wi-findings')"></span>
                                        </td>
                                        <td class="border border-gray-300 p-2 align-top text-gray-800"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <!-- Highlight context menu -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
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

        <!-- Delete highlight tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
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

        <!-- Note hover tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50"
                        title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note input modal -->
        <div v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
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
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100">Cancel</button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
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

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #000;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #374151;
}

.select-text {
    user-select: text;
    -webkit-user-select: text;
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