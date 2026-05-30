<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const isBookmarkVisible = (questionNumber: number): boolean => {
    return hoveredQuestion.value === questionNumber || isQuestionFlagged(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-section2-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
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

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// ── Answers ──────────────────────────────────────────────────────────────────
const answers = ref({
    // Q15-21: TRUE / FALSE / NOT GIVEN
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
    q21: '',
    // Q22-27: heading match
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
    q27: '',
});

// ── Q22-27: Heading Match drag-and-drop ──────────────────────────────────────
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i',    label: 'Written communication' },
    { value: 'ii',   label: 'Clarity' },
    { value: 'iii',  label: 'Style' },
    { value: 'iv',   label: 'Research' },
    { value: 'v',    label: 'End of message' },
    { value: 'vi',   label: 'One point per email' },
    { value: 'vii',  label: 'Relevance' },
    { value: 'viii', label: 'Specify the response you want' },
    { value: 'ix',   label: 'The subject line' },
    { value: 'x',    label: 'Internal emails' },
];

const availableHeadingOptions = computed(() => {
    const used = [
        answers.value.q22, answers.value.q23, answers.value.q24,
        answers.value.q25, answers.value.q26, answers.value.q27,
    ].filter(Boolean);
    return headingOptions.filter(opt => !used.includes(opt.value));
});

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
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
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

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// ── Passage text ─────────────────────────────────────────────────────────────
const passageText = ref(
`Conditions of employment

Weekly hours of work – 40 hours per week at the ordinary hourly rate of pay for most full-time employees, plus reasonable additional hours (penalty rates¹ apply). Part-time employees work a regular number of hours and days each week, but fewer hours than full-time workers. Casual employees are employed on an hourly or daily basis.
Entitlements (full-time employees):
Parental leave – up to 12 months' unpaid leave for maternity, paternity and adoption related leave.

Sick leave – up to 10 days' paid sick leave per year; more than 4 continuous days requires a medical certificate.

Annual leave – 4 weeks' paid leave per annum, plus an additional week for shift workers.

Public holidays – a paid day off on a public holiday, except where reasonably requested to work. Employees working on public holidays are entitled to 15% above their normal hourly rate.

Notice of termination – 2 weeks' notice of termination (3 weeks if the employee is more than 55 years old and has at least 2 years of continuous service)

Note:
The entitlements you receive will depend on whether you are employed on a full-time, part-time or casual basis.
If you work part-time, you should receive all the entitlements of a full-time employee but on a pro-rata or proportional basis.
If you are a casual worker, you do not have rights to any of the above entitlements nor penalty payments. Casual workers have no guarantee of hours to be worked and they do not have to be given advance notice of termination.

¹ Penalty rate = a higher rate of pay to compensate for working overtime or outside normal hours e.g. night-time or on public holidays.`
);

const passage2Text = ref(
`Writing Effective Emails

Follow these simple rules to make a positive impression and get an appropriate response. The purpose of the message should be outlined in the first paragraph, and the body should contain all of the relevant information.

A  Like a headline in a newspaper: it should grab the recipient's attention and specify what the message is about – use a few well-chosen words. If the email is one of a series e.g. a weekly newsletter, include the date in the subject line. Never leave it blank.

B  If you need to email someone about several different issues, write a separate email for each subject. This allows the recipient to reply to each one individually in a timely manner. For instance, one subject might be dealt with quickly while another could involve some research. If you have several related points, put them all in the same email but present each point in a numbered or bulleted paragraph.

C  Your email should be clear and concise. Sentences should be short and to the point.

D  Be sure to include a 'call to action' – a phone call or a follow-up appointment perhaps. To ensure a prompt reply, incorporate your contact information – name, title, company, phone/fax numbers or extensions, even your business address if necessary. Even internal messages must have contact information.

E  Only use this technique for very short messages or reminders where all the relevant information can fit in the subject line. Write EOM at the end of the line to indicate that the recipient doesn't have to open the email.

F  Emails, even internal ones, should not be too informal – after all, they are written forms of communication. Use your spell check and avoid slang.`
);

// ── Text segments ─────────────────────────────────────────────────────────────
const allSegments = [
    { id: 'part-header',       text: 'SECTION 2' },
    { id: 'part-instructions', text: 'Questions 15–27' },
    { id: 'passage-title',     text: 'Read the text below and answer Questions 15–21' },
    { id: 'passage',           text: passageText.value },
    { id: 'passage2-intro',    text: 'Questions 22–27\nThe text on the next page has six sections, A–F.\nChoose the correct heading for each section, A–F, from the list of headings below.' },
    { id: 'passage2-title',    text: 'Writing Effective Emails' },
    { id: 'passage2',          text: passage2Text.value },

    // Q15-21 section labels
    { id: 'q15-21-title',        text: 'Questions 15–21' },
    { id: 'q15-21-instructions', text: 'Do the following statements agree with the information given in the text?' },
    { id: 'q15-21-write',        text: 'In boxes 15–21 on your answer sheet write:' },
    { id: 'q15-21-true',         text: 'TRUE – if the statement agrees with the information' },
    { id: 'q15-21-false',        text: 'FALSE – if the statement contradicts the information' },
    { id: 'q15-21-ng',           text: 'NOT GIVEN – if there is no information on this' },

    { id: 'q15-text', text: 'Part-time workers are entitled to a higher rate of pay if they work more than their usual number of hours per week.' },
    { id: 'q16-text', text: 'Casual workers may be hired by the hour or by the day.' },
    { id: 'q17-text', text: 'A full-timer who takes a year off to have a baby can return to the same employer.' },
    { id: 'q18-text', text: 'A full-time worker needs a doctor\'s note if he is sick for 4 days in a row.' },
    { id: 'q19-text', text: 'A full-time night-shift worker is entitled to 5 weeks\' paid holiday each year.' },
    { id: 'q20-text', text: 'Any workers over 55 are entitled to 3 weeks\' notice of termination.' },
    { id: 'q21-text', text: 'Casual workers can be dismissed without notice.' },

    // Q22-27 section labels
    { id: 'q22-27-title',        text: 'Questions 22–27' },
    { id: 'q22-27-instructions', text: 'The text above has six sections, A–F.\nChoose the correct heading for each section, A–F, from the list of headings below.\nWrite the correct number, i–x, in boxes 22–27 on your answer sheet.' },
    { id: 'q22-label', text: 'Section A' },
    { id: 'q23-label', text: 'Section B' },
    { id: 'q24-label', text: 'Section C' },
    { id: 'q25-label', text: 'Section D' },
    { id: 'q26-label', text: 'Section E' },
    { id: 'q27-label', text: 'Section F' },
];

let currentOffset = 0;
const textSegments = ref(
    allSegments.map(segment => {
        const s = { id: segment.id, text: segment.text, offset: currentOffset };
        currentOffset += segment.text.length;
        return s;
    })
);

// ── Highlight helpers ────────────────────────────────────────────────────────
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;
    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') htmlIndex++;
            htmlIndex++;
        } else {
            plainIndex++;
            htmlIndex++;
        }
    }
    return htmlIndex;
};

const getPlainTextLength = (htmlText: string): number =>
    htmlText.replace(/<[^>]*>/g, '').length;

const getHighlightedSegment = (segmentId: string): string => {
    const segment = textSegments.value.find(s => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(
        h => h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(
        n => n.start < segmentEnd && n.end > segmentOffset
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ].sort((a, b) => b.start - a.start);

    let result = segmentText;
    for (const annotation of annotations) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);
        if (plainStart < plainEnd) {
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd = plainToHtmlOffset(result, plainEnd);
            const before = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after = result.substring(htmlEnd);
            if (annotation.type === 'note') {
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }
    return result;
};

// ── Expose ───────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;

watch(leftPanelWidth, newWidth => localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString()));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

// ── Text selection & highlight ───────────────────────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) { showContextMenu.value = false; return; }
        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) container = container.parentNode as Node;
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find(s => s.id === segmentIdAttr);
            if (!segment) return null;

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode: Node | null;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) { offsetInSegment += offsetInNode; break; }
                else offsetInSegment += currentNode.textContent?.length || 0;
            }
            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset   = getAbsoluteOffset(range.endContainer,   range.endOffset);
        if (startAbsOffset === null || endAbsOffset === null) { showContextMenu.value = false; window.getSelection()?.removeAllRanges(); return; }
        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 70 - 8;
            const viewportWidth = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), viewportWidth - 80),
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
    notes.value = notes.value.filter(n => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
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
    let x: number, y: number;

    if (selection && selection.rangeCount > 0) {
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }

    const vw = window.innerWidth, vh = window.innerHeight;
    if (x - modalWidth / 2 < padding) x = modalWidth / 2 + padding;
    else if (x + modalWidth / 2 > vw - padding) x = vw - modalWidth / 2 - padding;
    if (y + modalHeight > vh - padding) {
        if (selection && selection.rangeCount > 0) y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
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
    findOverlappingHighlights(newStart, newEnd).forEach(h => deleteHighlight(h.id));
    notes.value = notes.value.filter(n => !(n.start < newEnd && n.end > newStart));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Section 2' });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter(note => note.id !== id); };

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;
    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
        }
    } else {
        closeDeleteTooltip();
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find(n => n.id === noteIdNum);
            if (note) {
                const rect = noteMark.getBoundingClientRect();
                const tooltipHeight = 50;
                let y = rect.top - tooltipHeight - 8;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = note.id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (target.closest('mark[data-note-id]')) {
        if (relatedTarget && relatedTarget.closest('.note-hover-tooltip')) return;
        showNoteTooltip.value = false;
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
    }
};

const handleClickOutside = (event: MouseEvent) => { if (showContextMenu.value) showContextMenu.value = false; };
const handleKeyDown = (event: KeyboardEvent) => { if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); } };

const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const newLeftWidth = ((event.clientX - containerRect.left) / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};
const stopResize = () => { isResizing.value = false; };

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mouseover', handleNoteMouseEnter);
    document.removeEventListener('mouseout', handleNoteMouseLeave);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
});

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <!-- Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header"
                v-html="getHighlightedSegment('part-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions"
                v-html="getHighlightedSegment('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage Panel ── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-base font-semibold text-gray-700 mb-2">
                            <span data-segment-id="passage-title"
                                v-html="getHighlightedSegment('passage-title')"></span>
                        </h2>
                    </div>
                    <div class="space-y-4 px-4" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text sm:text-base">
                            <div class="px-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap">
                                    <span class="text-base text-gray-700 select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment('passage')"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second passage for Q22-27 -->
                    <div class="px-4 mt-8" :style="contentZoom">
                        <div class="px-4">
                            <p class="text-base text-gray-700 whitespace-pre-wrap mb-4 select-text"
                                data-segment-id="passage2-intro"
                                v-html="getHighlightedSegment('passage2-intro')"></p>
                            <h2 class="text-lg text-center font-bold text-gray-900 mb-4">
                                <span data-segment-id="passage2-title"
                                    v-html="getHighlightedSegment('passage2-title')"></span>
                            </h2>
                            <div class="text-justify leading-relaxed whitespace-pre-wrap">
                                <span class="text-base text-gray-700 select-text" data-segment-id="passage2"
                                    v-html="getHighlightedSegment('passage2')"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Panel ── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════════════════════════════════════
                                 Questions 15–21  (TRUE/FALSE/NOT GIVEN)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q15-21-title"
                                                v-html="getHighlightedSegment('q15-21-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q15-21-instructions"
                                            v-html="getHighlightedSegment('q15-21-instructions')"></span>
                                    </p>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q15-21-write"
                                            v-html="getHighlightedSegment('q15-21-write')"></span>
                                    </p>
                                    <div class="ml-4 space-y-0.5 text-base text-gray-700">
                                        <p><span data-segment-id="q15-21-true" v-html="getHighlightedSegment('q15-21-true')"></span></p>
                                        <p><span data-segment-id="q15-21-false" v-html="getHighlightedSegment('q15-21-false')"></span></p>
                                        <p><span data-segment-id="q15-21-ng" v-html="getHighlightedSegment('q15-21-ng')"></span></p>
                                    </div>
                                </div>

                                <div class="space-y-5 leading-relaxed text-gray-800 ">

                                    <!-- Q15 -->
                                    <div id="question-15" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">15.</span>
                                            <span class="select-text flex-1" data-segment-id="q15-text"
                                                v-html="getHighlightedSegment('q15-text')"></span>
                                        </div>
                                        <div class="flex gap-4 ml-11">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q15" value="TRUE" v-model="answers.q15"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q15" value="FALSE" v-model="answers.q15"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q15" value="NOT GIVEN" v-model="answers.q15"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="isBookmarkVisible(15)"
                                            @click.stop="emit('toggleFlag', 15)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    

                                    <!-- Q16 -->
                                    <div id="question-16" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">16.</span>
                                            <span class="select-text flex-1" data-segment-id="q16-text"
                                                v-html="getHighlightedSegment('q16-text')"></span>
                                        </div>
                                        <div class="flex gap-4 ml-11">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q16" value="TRUE" v-model="answers.q16"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q16" value="FALSE" v-model="answers.q16"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q16" value="NOT GIVEN" v-model="answers.q16"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="isBookmarkVisible(16)"
                                            @click.stop="emit('toggleFlag', 16)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    

                                    <!-- Q17 -->
                                    <div id="question-17" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">17.</span>
                                            <span class="select-text flex-1" data-segment-id="q17-text"
                                                v-html="getHighlightedSegment('q17-text')"></span>
                                        </div>
                                        <div class="flex gap-4 ml-11">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q17" value="TRUE" v-model="answers.q17"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q17" value="FALSE" v-model="answers.q17"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q17" value="NOT GIVEN" v-model="answers.q17"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="isBookmarkVisible(17)"
                                            @click.stop="emit('toggleFlag', 17)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    

                                    <!-- Q18 -->
                                    <div id="question-18" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">18.</span>
                                            <span class="select-text flex-1" data-segment-id="q18-text"
                                                v-html="getHighlightedSegment('q18-text')"></span>
                                        </div>
                                        <div class="flex gap-4 ml-11">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q18" value="TRUE" v-model="answers.q18"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q18" value="FALSE" v-model="answers.q18"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q18" value="NOT GIVEN" v-model="answers.q18"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="isBookmarkVisible(18)"
                                            @click.stop="emit('toggleFlag', 18)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    

                                    <!-- Q19 -->
                                    <div id="question-19" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">19.</span>
                                            <span class="select-text flex-1" data-segment-id="q19-text"
                                                v-html="getHighlightedSegment('q19-text')"></span>
                                        </div>
                                        <div class="flex gap-4 ml-11">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q19" value="TRUE" v-model="answers.q19"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q19" value="FALSE" v-model="answers.q19"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q19" value="NOT GIVEN" v-model="answers.q19"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="isBookmarkVisible(19)"
                                            @click.stop="emit('toggleFlag', 19)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    

                                    <!-- Q20 -->
                                    <div id="question-20" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">20.</span>
                                            <span class="select-text flex-1" data-segment-id="q20-text"
                                                v-html="getHighlightedSegment('q20-text')"></span>
                                        </div>
                                        <div class="flex gap-4 ml-11">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q20" value="TRUE" v-model="answers.q20"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q20" value="FALSE" v-model="answers.q20"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q20" value="NOT GIVEN" v-model="answers.q20"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="isBookmarkVisible(20)"
                                            @click.stop="emit('toggleFlag', 20)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    

                                    <!-- Q21 -->
                                    <div id="question-21" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">21.</span>
                                            <span class="select-text flex-1" data-segment-id="q21-text"
                                                v-html="getHighlightedSegment('q21-text')"></span>
                                        </div>
                                        <div class="flex gap-4 ml-11">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q21" value="TRUE" v-model="answers.q21"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q21" value="FALSE" v-model="answers.q21"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="q21" value="NOT GIVEN" v-model="answers.q21"
                                                    class="w-4 h-4 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-800">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="isBookmarkVisible(21)"
                                            @click.stop="emit('toggleFlag', 21)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ════════════════════════════════════
                                 Questions 22–27  (Heading Match)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q22-27-title"
                                                v-html="getHighlightedSegment('q22-27-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-base leading-relaxed font-medium text-gray-800 whitespace-pre-line">
                                        <span data-segment-id="q22-27-instructions"
                                            v-html="getHighlightedSegment('q22-27-instructions')"></span>
                                    </p>
                                </div>

                                <!-- Side by side: drop zones (left) + draggable options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left: section drop zones -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                        <div class="space-y-4 text-sm leading-relaxed text-gray-800">

                                            <!-- Q22 Section A -->
                                            <div id="question-22" class="relative flex items-center gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text w-8 shrink-0">22.</span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q22-label"
                                                        v-html="getHighlightedSegment('q22-label')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 22 ? 'border-blue-500 bg-blue-50' : answers.q22 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 22)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 22)"
                                                    @click="clearAnswer(22)"
                                                    :title="answers.q22 ? 'Click to clear' : 'Drop heading here'">
                                                    {{ answers.q22 || '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(22)"
                                                    @click.stop="emit('toggleFlag', 22)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q23 Section B -->
                                            <div id="question-23" class="relative flex items-center gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text w-8 shrink-0">23.</span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q23-label"
                                                        v-html="getHighlightedSegment('q23-label')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 23 ? 'border-blue-500 bg-blue-50' : answers.q23 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 23)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 23)"
                                                    @click="clearAnswer(23)"
                                                    :title="answers.q23 ? 'Click to clear' : 'Drop heading here'">
                                                    {{ answers.q23 || '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(23)"
                                                    @click.stop="emit('toggleFlag', 23)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q24 Section C -->
                                            <div id="question-24" class="relative flex items-center gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text w-8 shrink-0">24.</span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q24-label"
                                                        v-html="getHighlightedSegment('q24-label')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 24 ? 'border-blue-500 bg-blue-50' : answers.q24 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 24)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 24)"
                                                    @click="clearAnswer(24)"
                                                    :title="answers.q24 ? 'Click to clear' : 'Drop heading here'">
                                                    {{ answers.q24 || '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(24)"
                                                    @click.stop="emit('toggleFlag', 24)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q25 Section D -->
                                            <div id="question-25" class="relative flex items-center gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text w-8 shrink-0">25.</span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q25-label"
                                                        v-html="getHighlightedSegment('q25-label')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 25 ? 'border-blue-500 bg-blue-50' : answers.q25 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 25)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 25)"
                                                    @click="clearAnswer(25)"
                                                    :title="answers.q25 ? 'Click to clear' : 'Drop heading here'">
                                                    {{ answers.q25 || '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(25)"
                                                    @click.stop="emit('toggleFlag', 25)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q26 Section E -->
                                            <div id="question-26" class="relative flex items-center gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text w-8 shrink-0">26.</span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q26-label"
                                                        v-html="getHighlightedSegment('q26-label')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 26 ? 'border-blue-500 bg-blue-50' : answers.q26 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 26)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 26)"
                                                    @click="clearAnswer(26)"
                                                    :title="answers.q26 ? 'Click to clear' : 'Drop heading here'">
                                                    {{ answers.q26 || '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(26)"
                                                    @click.stop="emit('toggleFlag', 26)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q27 Section F -->
                                            <div id="question-27" class="relative flex items-center gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text w-8 shrink-0">27.</span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q27-label"
                                                        v-html="getHighlightedSegment('q27-label')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 27)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 27)"
                                                    @click="clearAnswer(27)"
                                                    :title="answers.q27 ? 'Click to clear' : 'Drop heading here'">
                                                    {{ answers.q27 || '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(27)"
                                                    @click.stop="emit('toggleFlag', 27)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Right: Draggable heading list -->
                                    <div class="w-56 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium">Drag headings to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div v-for="option in availableHeadingOptions" :key="option.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd">
                                                    <span class="font-bold text-gray-900 text-xs shrink-0">{{ option.value }}</span>
                                                    <span class="text-gray-700 text-xs">{{ option.label }}</span>
                                                </div>
                                                <p v-if="availableHeadingOptions.length === 0" class="text-xs text-gray-400 italic px-2 py-1">All headings placed</p>
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

        <!-- ── Context Menu for Highlighting ── -->
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
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
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
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                            title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{
                    left: `${noteInputPosition.x}px`,
                    top: `${noteInputPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
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
    </div>
</template>

<style scoped>
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: col-resize;
}

.passage-panel { width: 100%; }
.answer-panel  { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel  { width: calc(100% - var(--panel-width) - 0.5rem); }
}

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,0.2); transform: scale(1.02); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}

.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green  { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue   { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink   { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

/* Highlight tooltip */
.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}

/* Delete tooltip */
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db; z-index: -1;
}

/* Note tooltip */
.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}
.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-icon    { color: #6b7280; }
.note-hover-tooltip .note-tooltip-text    { line-height: 1.4; }
.note-hover-tooltip .note-delete-btn      { color: #9ca3af; }
.note-hover-tooltip .note-delete-btn:hover{ color: #ef4444; }
</style>

<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
    padding: 2px 0;
    border-radius: 2px;
}
.note-highlight:hover {
    background-color: rgba(147, 197, 253, 0.7) !important;
}
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>