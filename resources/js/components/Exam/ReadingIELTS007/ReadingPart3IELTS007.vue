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
const emit = defineEmits<{ toggleFlag: [questionNumber: number] }>();

const hoveredQuestion = ref<number | null>(null);
const isQuestionFlagged = (n: number) => props.flaggedQuestions.has(n);
const contentZoom = computed(() => ({ zoom: props.fontSize / 16 }));

// Resize
const PANEL_WIDTH_KEY = 'reading-ielts003-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// ── ANSWERS ──────────────────────────────────────────────
const answers = ref({
    q27: '', q28: '', q29: '', q30: '', q31: '', q32: '',
    q33: '', q34: '', q35: '', q36: '', q37: '',
    q38: '', q39: '', q40: '',
});

// ── DRAG & DROP: Q27-32 (sentence endings A-H) ───────────
const draggedEnding = ref<string | null>(null);
const dragOverQ = ref<number | null>(null);

const endingOptions = [
    { value: 'A', label: 'the discovery of new medical applications.' },
    { value: 'B', label: 'the negative effects of publicity.' },
    { value: 'C', label: 'the large pharmaceutical companies.' },
    { value: 'D', label: 'the industrial revolution.' },
    { value: 'E', label: 'the medical uses of a particular tree.' },
    { value: 'F', label: 'the limited availability of new drugs.' },
    { value: 'G', label: 'the chemical found in the willow tree.' },
    { value: 'H', label: 'commercial advertising campaigns.' },
];

// Q27-32 each ending used once → remove used from pool
const availableEndings = computed(() => {
    const used = [answers.value.q27, answers.value.q28, answers.value.q29,
                  answers.value.q30, answers.value.q31, answers.value.q32].filter(Boolean);
    return endingOptions.filter(o => !used.includes(o.value));
});

const getEndingLabel = (v: string) => endingOptions.find(o => o.value === v)?.label ?? '';

const startEndingDrag = (e: DragEvent, val: string) => {
    draggedEnding.value = val;
    e.dataTransfer && (e.dataTransfer.effectAllowed = 'move') && e.dataTransfer.setData('text/plain', val);
};
const endEndingDrag = () => { draggedEnding.value = null; dragOverQ.value = null; };

const onDragOver = (e: DragEvent, n: number) => {
    e.preventDefault();
    dragOverQ.value = n;
};
const onDragLeave = () => { dragOverQ.value = null; };
const onDrop = (e: DragEvent, n: number) => {
    e.preventDefault();
    const val = e.dataTransfer?.getData('text/plain') || draggedEnding.value;
    if (val) (answers.value as any)[`q${n}`] = val;
    draggedEnding.value = null; dragOverQ.value = null;
};
const clearEnding = (n: number) => { (answers.value as any)[`q${n}`] = ''; };

// ── DRAG & DROP: Q38-40 (summary word bank A-I) ──────────
// These CAN be reused (same option multiple times allowed)
const summaryOptions = [
    { value: 'A', label: 'useful' },
    { value: 'B', label: 'cheap' },
    { value: 'C', label: 'state' },
    { value: 'D', label: 'international' },
    { value: 'E', label: 'major drug companies' },
    { value: 'F', label: 'profitable' },
    { value: 'G', label: 'commercial' },
    { value: 'H', label: 'public sector scientists' },
    { value: 'I', label: 'health officials' },
];
const getSummaryLabel = (v: string) => summaryOptions.find(o => o.value === v)?.label ?? '';

const draggedSummary = ref<string | null>(null);
const dragOverSummaryQ = ref<number | null>(null);

const startSummaryDrag = (e: DragEvent, val: string) => {
    draggedSummary.value = val;
    e.dataTransfer && (e.dataTransfer.effectAllowed = 'move') && e.dataTransfer.setData('text/plain', val);
};
const endSummaryDrag = () => { draggedSummary.value = null; dragOverSummaryQ.value = null; };

const onSummaryDragOver = (e: DragEvent, n: number) => { e.preventDefault(); dragOverSummaryQ.value = n; };
const onSummaryDragLeave = () => { dragOverSummaryQ.value = null; };
const onSummaryDrop = (e: DragEvent, n: number) => {
    e.preventDefault();
    const val = e.dataTransfer?.getData('text/plain') || draggedSummary.value;
    if (val) (answers.value as any)[`q${n}`] = val;
    draggedSummary.value = null; dragOverSummaryQ.value = null;
};
const clearSummary = (n: number) => { (answers.value as any)[`q${n}`] = ''; };

// ── TEXT SEGMENTS (passage + UI strings) ─────────────────
const passageText = `<b>Keep taking the tablets</b>

The history of aspirin is a product of a rollercoaster ride through time, of accidental discoveries, intuitive reasoning and intense corporate rivalry.

In the opening pages of Aspirin: The Remarkable Story of a Wonder Drug, Diarmuid Jeffreys describes this little white pill as 'one of the most amazing creations in medical history, a drug so astonishingly versatile that it can relieve headache, ease your aching limbs, lower your temperature and treat some of the deadliest human diseases'.

Its properties have been known for thousands of years. Ancient Egyptian physicians used extracts from the willow tree as an analgesic, or pain killer. Centuries later the Greek physician Hippocrates recommended the bark of the willow tree as a remedy for the pains of childbirth and as a fever reducer. But it wasn't until the eighteenth and nineteenth centuries that salicylates the chemical found in the willow tree became the subject of serious scientific investigation. The race was on to identify the active ingredient and to replicate it synthetically. At the end of the nineteenth century a German company, Friedrich Bayer & Co. succeeded in creating a relatively safe and very effective chemical compound, acetylsalicylic acid, which was renamed aspirin.

The late nineteenth century was a fertile period for experimentation, partly because of the hunger among scientists to answer some of the great scientific questions, but also because those questions were within their means to answer. One scientist in a laboratory with some chemicals and a test tube could make significant breakthroughs whereas today, in order to map the human genome for instance, one needs 'an army of researchers, a bank of computers and millions and millions of dollars'.

But an understanding of the nature of science and scientific inquiry is not enough on its own to explain how society innovates. In the nineteenth century, scientific advance was closely linked to the industrial revolution. This was a period when people frequently had the means, motive and determination to take an idea and turn it into reality. In the case of aspirin that happened piecemeal - a series of minor, often unrelated advances, fertilised by the century's broader economic, medical and scientific developments, that led to one big final breakthrough.

The link between big money and pharmaceutical innovation is also a significant one. Aspirin's continued shelf life was ensured because, for the first 70 years of its life, huge amounts of money were put into promoting it as an ordinary everyday analgesic. In the 1970s other analgesics, such as ibuprofen and paracetamol, were entering the market, and the pharmaceutical companies then focused on publicising these new drugs. But just at the same time, discoveries were made regarding the beneficial role of aspirin in preventing heart attacks, strokes and other afflictions. Had it not been for these findings, this pharmaceutical marvel may well have disappeared.

So the relationship between big money and drugs is an odd one. Commercial markets are necessary for developing new products and ensuring that they remain around long enough for scientists to carry out research on them. But the commercial markets are just as likely to kill off certain products when something more attractive comes along. In the case of aspirin, a potential 'wonder drug' was around for over 70 years without anybody investigating the way in which it achieved its effects, because they were making more than enough money out of it as it was. If ibuprofen or paracetamol had entered the market just a decade earlier, aspirin might then not be here today. It would be just another forgotten drug that people hadn't bothered to explore.

None of the recent discoveries of aspirin's benefits was made by the big pharmaceutical companies; they were made by scientists working in the public sector. 'The reason for that is very simple and straightforward,' Jeffreys says in his book. 'Drug companies will only pursue research that is going to deliver financial benefits. There's no profit in aspirin anymore. It is incredibly inexpensive with tiny profit margins and it has no patent anymore, so anyone can produce it.' In fact, there's almost a disincentive for drug companies to further boost the drug, he argues, as it could possibly put them out of business by stopping them from selling their more expensive brands.

So what is the solution to a lack of commercial interest in further exploring the therapeutic benefits of aspirin? More public money going into clinical trials, says Jeffreys. 'If I were the Department of Health, I would say "this is a very inexpensive drug. There may be a lot of other things we could do with it." We should put a lot more money into trying to find out.'

Jeffreys' book which not only tells the tale of a 'wonder drug' but also explores the nature of innovation and the role of big business, public money and regulation reminds us why such research is so important.`;

const textSegments = ref([
    { id: 'part3-header', text: 'Part 3', offset: 100 },
    { id: 'part3-instruction', text: 'Read the text and answer questions 27-40.', offset: 116 },
    { id: 'passage', text: passageText, offset: 200 },

    // Q27-32 labels
    { id: 'q27-32-title', text: 'Questions 27-32', offset: 5300 },
{ id: 'q27-32-inst1', text: 'Complete each sentence with the correct ending A-H from the box below.', offset: 5325 },
{ id: 'q27-32-inst2', text: 'Write the correct letter A-H in boxes 27-32 on your answer sheet.', offset: 5400 },
{ id: 'q27-stem', text: 'Ancient Egyptian and Greek doctors were aware of', offset: 5500 },
{ id: 'q28-stem', text: 'Frederick Bayer & Co were able to reproduce', offset: 5560 },
{ id: 'q29-stem', text: 'The development of aspirin was partly due to the effects of', offset: 5620 },
{ id: 'q30-stem', text: 'The creation of a market for aspirin as a painkiller was achieved through', offset: 5690 },
{ id: 'q31-stem', text: 'Aspirin might have become unavailable without', offset: 5770 },
{ id: 'q32-stem', text: 'The way in which aspirin actually worked was not investigated by', offset: 5830 },

// Q33-37 labels
{ id: 'q33-37-title', text: 'Questions 33-37', offset: 5900 },
{ id: 'q33-37-inst1', text: 'Do the following statements agree with the views of the writer in Reading Passage 3?', offset: 5925 },
{ id: 'q33-37-inst2', text: 'In boxes 33-37 on your answer sheet write', offset: 6020 },
{ id: 'ynng-yes', text: 'YES', offset: 6070 },
{ id: 'ynng-yes-desc', text: 'if the statement agrees with the views of the writer', offset: 6080 },
{ id: 'ynng-no', text: 'NO', offset: 6140 },
{ id: 'ynng-no-desc', text: 'if the statement contradicts the views of the writer', offset: 6150 },
{ id: 'ynng-ng', text: 'NOT GIVEN', offset: 6210 },
{ id: 'ynng-ng-desc', text: 'if it is impossible to say what the writer thinks about this', offset: 6225 },
{ id: 'q33-text', text: 'For nineteenth-century scientists, small-scale research was enough to make important discoveries.', offset: 6300 },
{ id: 'q34-text', text: 'The nineteenth-century industrial revolution caused a change in the focus of scientific research.', offset: 6400 },
{ id: 'q35-text', text: 'The development of aspirin in the nineteenth century followed a structured pattern of development.', offset: 6500 },
{ id: 'q36-text', text: 'In the 1970s sales of new analgesic drugs overtook sales of aspirin.', offset: 6600 },
{ id: 'q37-text', text: 'Commercial companies may have both good and bad effects on the availability of pharmaceutical products.', offset: 6680 },

// Q38-40 labels
{ id: 'q38-40-title', text: 'Questions 38-40', offset: 6800 },
{ id: 'q38-40-inst1', text: 'Complete the summary below using the list of words A-I below.', offset: 6825 },
{ id: 'q38-40-inst2', text: 'Write the correct letter A-I in boxes 38-40 on your answer sheet.', offset: 6900 },
{ id: 'summary-title', text: 'Research into aspirin', offset: 6980 },
{ id: 'summary-p1', text: 'Jeffreys argues that the reason why', offset: 7010 },
{ id: 'summary-p2', text: 'did not find out about new uses of aspirin is that aspirin is no longer a', offset: 7060 },
{ id: 'summary-p3', text: 'drug. He, therefore, suggests that there should be', offset: 7145 },
{ id: 'summary-p4', text: 'support for further research into the possible applications of the drug.', offset: 7210 },
]);

// ── HIGHLIGHT HELPERS (same pattern as Part1/2/3) ────────
const plainToHtmlOffset = (html: string, plainOffset: number): number => {
    let pi = 0, hi = 0;
    while (pi < plainOffset && hi < html.length) {
        if (html[hi] === '<') { while (hi < html.length && html[hi] !== '>') hi++; hi++; }
        else { pi++; hi++; }
    }
    return hi;
};
const getPlainTextLength = (html: string) => html.replace(/<[^>]*>/g, '').length;

const getHighlightedSegmentById = (segId: string): string => {
    const seg = textSegments.value.find(s => s.id === segId);
    if (!seg) return '';
    const { text, offset } = seg;
    const plainLen = getPlainTextLength(text);
    const segEnd = offset + plainLen;

    const hl = highlights.value.filter(h => h.start_offset < segEnd && h.end_offset > offset);
    const nl = notes.value.filter(n => n.start < segEnd && n.end > offset);
    if (!hl.length && !nl.length) return text;

    const annotations = [
        ...hl.map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...nl.map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    let result = text;
    for (const ann of annotations) {
        const ps = Math.max(0, ann.start - offset);
        const pe = Math.min(plainLen, ann.end - offset);
        if (ps >= pe) continue;
        const hs = plainToHtmlOffset(result, ps);
        const he = plainToHtmlOffset(result, pe);
        const before = result.slice(0, hs), mid = result.slice(hs, he), after = result.slice(he);
        result = ann.type === 'note'
            ? `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${mid}</mark>${after}`
            : `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${mid}</mark>${after}`;
    }
    return result;
};

// ── EXPOSE ────────────────────────────────────────────────
const getAnswers = () => answers.value;

const scrollToQuestion = async (n: number) => {
    await nextTick();
    const el = document.getElementById(`question-${n}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ── SELECTION / CONTEXT MENU ──────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || !sel.toString().trim()) { showContextMenu.value = false; return; }
        const range = sel.getRangeAt(0);

        const getAbsOffset = (node: Node, off: number): number | null => {
            let container: Node = node.nodeType !== Node.ELEMENT_NODE ? node.parentNode! : node;
            const segEl = (container as Element).closest('[data-segment-id]');
            if (!segEl) return null;
            const seg = textSegments.value.find(s => s.id === segEl.getAttribute('data-segment-id'));
            if (!seg) return null;
            const tw = document.createTreeWalker(segEl, NodeFilter.SHOW_TEXT);
            let acc = 0; let cur: Node | null;
            while ((cur = tw.nextNode())) {
                if (cur === node) { acc += off; break; }
                acc += cur.textContent?.length ?? 0;
            }
            return seg.offset + acc;
        };

        let s = getAbsOffset(range.startContainer, range.startOffset);
        let e = getAbsOffset(range.endContainer, range.endOffset);
        if (s === null || e === null) { showContextMenu.value = false; return; }
        if (s > e) [s, e] = [e, s];

        const rect = range.getBoundingClientRect();
        if (rect.width > 0 || rect.height > 0) {
            const vw = window.innerWidth, mw = 140;
            contextMenuPosition.value = {
                x: Math.min(Math.max(rect.left + rect.width / 2, mw / 2 + 10), vw - mw / 2 - 10),
                y: Math.max(rect.top - 78, 10),
            };
            showContextMenu.value = true;
            selectedText.value = sel.toString();
            selectionRange.value = { start: s, end: e };
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
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start, end } = selectionRange.value;
    findOverlappingHighlights(start, end).forEach(h => deleteHighlight(h.id));
    notes.value = notes.value.filter(n => !(n.start < end && n.end > start));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start, end, part: 'Part 3' });
    noteInputText.value = ''; showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};
const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter(n => n.id !== id); };

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (e: MouseEvent) => {
    const mark = (e.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement;
    if (mark) {
        e.stopPropagation();
        const rect = mark.getBoundingClientRect();
        deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
        highlightToDelete.value = parseInt(mark.getAttribute('data-highlight-id')!);
        showDeleteTooltip.value = true;
    } else { closeDeleteTooltip(); }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const handleNoteMouseEnter = (e: MouseEvent) => {
    const mark = (e.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (mark) {
        const id = parseInt(mark.getAttribute('data-note-id')!);
        const note = notes.value.find(n => n.id === id);
        if (note) {
            const rect = mark.getBoundingClientRect();
            let y = rect.top - 58; if (y < 10) y = rect.bottom + 8;
            noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
            hoveredNoteId.value = id; hoveredNoteText.value = note.note; showNoteTooltip.value = true;
        }
    }
};
const handleNoteMouseLeave = (e: MouseEvent) => {
    if ((e.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) showNoteTooltip.value = false;
};
const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };
const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) { deleteNote(hoveredNoteId.value); showNoteTooltip.value = false; hoveredNoteId.value = null; }
};

// Resize
const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const w = ((e.clientX - rect.left) / rect.width) * 100;
    if (w >= 20 && w <= 80) leftPanelWidth.value = w;
};
const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, v => localStorage.setItem(PANEL_WIDTH_KEY, v.toString()));

onMounted(() => {
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});
onBeforeUnmount(() => {
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
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part3-header"
                v-html="getHighlightedSegmentById('part3-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part3-instruction"
                v-html="getHighlightedSegmentById('part3-instruction')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- Passage Panel -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">

                    <div class="space-y-6 px-4" :style="contentZoom">
                        <div class="px-4">
                            <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                <span class="text-base text-gray-700 select-text" data-segment-id="passage"
                                    v-html="getHighlightedSegmentById('passage')"></span>
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

                <!-- Questions Panel -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ═══ Q27-32: Sentence Endings ═══ -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q27-32-title"
                                            v-html="getHighlightedSegmentById('q27-32-title')"></span>
                                    </h3>
                                    <p class="text-base text-gray-700 mb-1">
                                        <span data-segment-id="q27-32-inst1"
                                            v-html="getHighlightedSegmentById('q27-32-inst1')"></span>
                                    </p>
                                    <p class="text-base text-gray-700">
                                        <span data-segment-id="q27-32-inst2"
                                            v-html="getHighlightedSegmentById('q27-32-inst2')"></span>
                                    </p>
                                </div>

                                <div class="flex gap-6">
                                    <!-- Questions with drop zones -->
                                    <div class="flex-1 border border-gray-900 p-4">
                                        <div class="space-y-4 text-sm text-gray-800">

                                            <!-- Q27 -->
                                            <div id="question-27" class="relative flex items-start gap-2 pr-10"
                                                @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold shrink-0 text-gray-900">27.</span>
                                                <span class="flex-1 select-text" data-segment-id="q27-stem"
                                                    v-html="getHighlightedSegmentById('q27-stem')"></span>
                                                <span class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-sm cursor-pointer shrink-0 transition-colors"
                                                    :class="dragOverQ === 27 ? 'border-gray-700 bg-gray-100' : answers.q27 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400'"
                                                    @dragover="onDragOver($event, 27)" @dragleave="onDragLeave"
                                                    @drop="onDrop($event, 27)" @click="clearEnding(27)"
                                                    :title="answers.q27 ? 'Click to clear' : 'Drop here'">
                                                    {{ answers.q27 || '' }}
                                                </span>
                                                <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                                    @click.stop="emit('toggleFlag', 27)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(27) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q28 -->
                                            <div id="question-28" class="relative flex items-start gap-2 pr-10"
                                                @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold shrink-0 text-gray-900">28.</span>
                                                <span class="flex-1 select-text" data-segment-id="q28-stem"
                                                    v-html="getHighlightedSegmentById('q28-stem')"></span>
                                                <span class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-sm cursor-pointer shrink-0 transition-colors"
                                                    :class="dragOverQ === 28 ? 'border-gray-700 bg-gray-100' : answers.q28 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400'"
                                                    @dragover="onDragOver($event, 28)" @dragleave="onDragLeave"
                                                    @drop="onDrop($event, 28)" @click="clearEnding(28)"
                                                    :title="answers.q28 ? 'Click to clear' : 'Drop here'">
                                                    {{ answers.q28 || '' }}
                                                </span>
                                                <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                                    @click.stop="emit('toggleFlag', 28)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(28) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q29 -->
                                            <div id="question-29" class="relative flex items-start gap-2 pr-10"
                                                @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold shrink-0 text-gray-900">29.</span>
                                                <span class="flex-1 select-text" data-segment-id="q29-stem"
                                                    v-html="getHighlightedSegmentById('q29-stem')"></span>
                                                <span class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-sm cursor-pointer shrink-0 transition-colors"
                                                    :class="dragOverQ === 29 ? 'border-gray-700 bg-gray-100' : answers.q29 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400'"
                                                    @dragover="onDragOver($event, 29)" @dragleave="onDragLeave"
                                                    @drop="onDrop($event, 29)" @click="clearEnding(29)"
                                                    :title="answers.q29 ? 'Click to clear' : 'Drop here'">
                                                    {{ answers.q29 || '' }}
                                                </span>
                                                <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                                    @click.stop="emit('toggleFlag', 29)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(29) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q30 -->
                                            <div id="question-30" class="relative flex items-start gap-2 pr-10"
                                                @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold shrink-0 text-gray-900">30.</span>
                                                <span class="flex-1 select-text" data-segment-id="q30-stem"
                                                    v-html="getHighlightedSegmentById('q30-stem')"></span>
                                                <span class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-sm cursor-pointer shrink-0 transition-colors"
                                                    :class="dragOverQ === 30 ? 'border-gray-700 bg-gray-100' : answers.q30 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400'"
                                                    @dragover="onDragOver($event, 30)" @dragleave="onDragLeave"
                                                    @drop="onDrop($event, 30)" @click="clearEnding(30)"
                                                    :title="answers.q30 ? 'Click to clear' : 'Drop here'">
                                                    {{ answers.q30 || '' }}
                                                </span>
                                                <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                                    @click.stop="emit('toggleFlag', 30)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(30) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q31 -->
                                            <div id="question-31" class="relative flex items-start gap-2 pr-10"
                                                @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold shrink-0 text-gray-900">31.</span>
                                                <span class="flex-1 select-text" data-segment-id="q31-stem"
                                                    v-html="getHighlightedSegmentById('q31-stem')"></span>
                                                <span class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-sm cursor-pointer shrink-0 transition-colors"
                                                    :class="dragOverQ === 31 ? 'border-gray-700 bg-gray-100' : answers.q31 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400'"
                                                    @dragover="onDragOver($event, 31)" @dragleave="onDragLeave"
                                                    @drop="onDrop($event, 31)" @click="clearEnding(31)"
                                                    :title="answers.q31 ? 'Click to clear' : 'Drop here'">
                                                    {{ answers.q31 || '' }}
                                                </span>
                                                <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                                    @click.stop="emit('toggleFlag', 31)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q32 -->
                                            <div id="question-32" class="relative flex items-start gap-2 pr-10"
                                                @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold shrink-0 text-gray-900">32.</span>
                                                <span class="flex-1 select-text" data-segment-id="q32-stem"
                                                    v-html="getHighlightedSegmentById('q32-stem')"></span>
                                                <span class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-sm cursor-pointer shrink-0 transition-colors"
                                                    :class="dragOverQ === 32 ? 'border-gray-700 bg-gray-100' : answers.q32 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400'"
                                                    @dragover="onDragOver($event, 32)" @dragleave="onDragLeave"
                                                    @drop="onDrop($event, 32)" @click="clearEnding(32)"
                                                    :title="answers.q32 ? 'Click to clear' : 'Drop here'">
                                                    {{ answers.q32 || '' }}
                                                </span>
                                                <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                                    @click.stop="emit('toggleFlag', 32)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(32) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Draggable endings (right side) -->
                                    <div class="w-48 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-sm font-medium text-gray-700">Drag endings to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white space-y-1">
                                            <div v-for="opt in availableEndings" :key="opt.value"
                                                class="flex cursor-grab items-start gap-2 border border-gray-300 px-2 py-1.5 text-sm hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                :class="{ 'opacity-50': draggedEnding === opt.value }"
                                                draggable="true"
                                                @dragstart="startEndingDrag($event, opt.value)"
                                                @dragend="endEndingDrag">
                                                <span class="font-bold text-gray-900 shrink-0">{{ opt.value }}</span>
                                                <span class="text-gray-700 text-xs leading-snug">{{ opt.label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ Q33-37: YES/NO/NOT GIVEN ═══ -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q33-37-title"
                                            v-html="getHighlightedSegmentById('q33-37-title')"></span>
                                    </h3>
                                    <div class="border border-gray-300 p-4 mb-4">
                                        <p class="text-base font-medium text-gray-800 mb-3">
                                            <span data-segment-id="q33-37-inst1"
                                                v-html="getHighlightedSegmentById('q33-37-inst1')"></span><br />
                                            <span data-segment-id="q33-37-inst2"
                                                v-html="getHighlightedSegmentById('q33-37-inst2')"></span>
                                        </p>
                                        <div class="space-y-1 text-sm">
                                            <div class="flex gap-3">
                                                <span class="w-20 font-bold bg-gray-100 px-2 py-0.5">YES</span>
                                                <span class="text-gray-700" data-segment-id="ynng-yes-desc"
                                                    v-html="getHighlightedSegmentById('ynng-yes-desc')"></span>
                                            </div>
                                            <div class="flex gap-3">
                                                <span class="w-20 font-bold bg-gray-100 px-2 py-0.5">NO</span>
                                                <span class="text-gray-700" data-segment-id="ynng-no-desc"
                                                    v-html="getHighlightedSegmentById('ynng-no-desc')"></span>
                                            </div>
                                            <div class="flex gap-3">
                                                <span class="w-20 font-bold bg-gray-100 px-2 py-0.5">NOT GIVEN</span>
                                                <span class="text-gray-700" data-segment-id="ynng-ng-desc"
                                                    v-html="getHighlightedSegmentById('ynng-ng-desc')"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-5">
                                    <!-- Q33 -->
                                    <div id="question-33" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold shrink-0 text-gray-900">33.</span>
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-700 mb-2">
                                                    <span class="select-text" data-segment-id="q33-text"
                                                        v-html="getHighlightedSegmentById('q33-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q33" value="YES" class="h-4 w-4 accent-black" />YES
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q33" value="NO" class="h-4 w-4 accent-black" />NO
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q33" value="NOT GIVEN" class="h-4 w-4 accent-black" />NOT GIVEN
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                            @click.stop="emit('toggleFlag', 33)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q34 -->
                                    <div id="question-34" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 34" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold shrink-0 text-gray-900">34.</span>
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-700 mb-2">
                                                    <span class="select-text" data-segment-id="q34-text"
                                                        v-html="getHighlightedSegmentById('q34-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q34" value="YES" class="h-4 w-4 accent-black" />YES
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q34" value="NO" class="h-4 w-4 accent-black" />NO
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q34" value="NOT GIVEN" class="h-4 w-4 accent-black" />NOT GIVEN
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                            @click.stop="emit('toggleFlag', 34)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(34) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q35 -->
                                    <div id="question-35" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold shrink-0 text-gray-900">35.</span>
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-700 mb-2">
                                                    <span class="select-text" data-segment-id="q35-text"
                                                        v-html="getHighlightedSegmentById('q35-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q35" value="YES" class="h-4 w-4 accent-black" />YES
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q35" value="NO" class="h-4 w-4 accent-black" />NO
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q35" value="NOT GIVEN" class="h-4 w-4 accent-black" />NOT GIVEN
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                            @click.stop="emit('toggleFlag', 35)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q36 -->
                                    <div id="question-36" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold shrink-0 text-gray-900">36.</span>
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-700 mb-2">
                                                    <span class="select-text" data-segment-id="q36-text"
                                                        v-html="getHighlightedSegmentById('q36-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q36" value="YES" class="h-4 w-4 accent-black" />YES
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q36" value="NO" class="h-4 w-4 accent-black" />NO
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q36" value="NOT GIVEN" class="h-4 w-4 accent-black" />NOT GIVEN
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                            @click.stop="emit('toggleFlag', 36)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q37 -->
                                    <div id="question-37" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold shrink-0 text-gray-900">37.</span>
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-700 mb-2">
                                                    <span class="select-text" data-segment-id="q37-text"
                                                        v-html="getHighlightedSegmentById('q37-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q37" value="YES" class="h-4 w-4 accent-black" />YES
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q37" value="NO" class="h-4 w-4 accent-black" />NO
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                        <input type="radio" v-model="answers.q37" value="NOT GIVEN" class="h-4 w-4 accent-black" />NOT GIVEN
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                            @click.stop="emit('toggleFlag', 37)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ Q38-40: Summary Gap-Fill Drag ═══ -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q38-40-title"
                                            v-html="getHighlightedSegmentById('q38-40-title')"></span>
                                    </h3>
                                    <p class="text-sm text-gray-700 mb-1">
                                        <span data-segment-id="q38-40-inst1"
                                            v-html="getHighlightedSegmentById('q38-40-inst1')"></span>
                                    </p>
                                    <p class="text-sm text-gray-700">
                                        <span data-segment-id="q38-40-inst2"
                                            v-html="getHighlightedSegmentById('q38-40-inst2')"></span>
                                    </p>
                                </div>

                                <!-- Word bank -->
                                <div class="border border-gray-300 p-3 mb-4">
                                    <p class="text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">Word Bank — drag to fill gaps:</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <div v-for="opt in summaryOptions" :key="opt.value"
                                            class="flex cursor-grab items-center gap-1 border border-gray-300 px-2 py-1 text-sm hover:border-gray-600 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-50': draggedSummary === opt.value }"
                                            draggable="true"
                                            @dragstart="startSummaryDrag($event, opt.value)"
                                            @dragend="endSummaryDrag">
                                            <span class="font-bold text-gray-900">{{ opt.value }}</span>
                                            <span class="text-gray-700 text-xs">{{ opt.label }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary paragraph with inline gaps -->
                                <div class="border border-gray-200 p-4">
                                    <p class="text-base font-bold text-gray-900 mb-3">
                                        <span data-segment-id="summary-title"
                                            v-html="getHighlightedSegmentById('summary-title')"></span>
                                    </p>
                                    <p class="text-sm leading-loose text-gray-700">
                                        <span data-segment-id="summary-p1"
                                            v-html="getHighlightedSegmentById('summary-p1')"></span>

                                        <!-- Q38 gap -->
                                        <span id="question-38"
                                            class="inline-flex min-h-7 min-w-32 mx-1 items-center justify-center border-2 border-dashed px-2 py-0.5 text-sm cursor-pointer align-middle relative transition-colors"
                                            :class="dragOverSummaryQ === 38 ? 'border-gray-700 bg-gray-100' : answers.q38 ? 'border-gray-900 font-medium' : 'border-gray-400'"
                                            @dragover="onSummaryDragOver($event, 38)" @dragleave="onSummaryDragLeave"
                                            @drop="onSummaryDrop($event, 38)" @click="clearSummary(38)"
                                            :title="answers.q38 ? 'Click to clear' : 'Drop here'">
                                            <span v-if="answers.q38">{{ getSummaryLabel(answers.q38) }}</span>
                                            <span v-else class="font-bold text-gray-400">38</span>
                                            <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                @click.stop="emit('toggleFlag', 38)"
                                                class="absolute -top-3.5 -right-3.5 flex h-6 w-6 items-center justify-center rounded border bg-white transition-all"
                                                :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400'"
                                                @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-p2"
                                            v-html="getHighlightedSegmentById('summary-p2')"></span>

                                        <!-- Q39 gap -->
                                        <span id="question-39"
                                            class="inline-flex min-h-7 min-w-24 mx-1 items-center justify-center border-2 border-dashed px-2 py-0.5 text-sm cursor-pointer align-middle relative transition-colors"
                                            :class="dragOverSummaryQ === 39 ? 'border-gray-700 bg-gray-100' : answers.q39 ? 'border-gray-900 font-medium' : 'border-gray-400'"
                                            @dragover="onSummaryDragOver($event, 39)" @dragleave="onSummaryDragLeave"
                                            @drop="onSummaryDrop($event, 39)" @click="clearSummary(39)"
                                            :title="answers.q39 ? 'Click to clear' : 'Drop here'">
                                            <span v-if="answers.q39">{{ getSummaryLabel(answers.q39) }}</span>
                                            <span v-else class="font-bold text-gray-400">39</span>
                                            <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                @click.stop="emit('toggleFlag', 39)"
                                                class="absolute -top-3.5 -right-3.5 flex h-6 w-6 items-center justify-center rounded border bg-white transition-all"
                                                :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400'"
                                                @mouseenter="hoveredQuestion = 39" @mouseleave="hoveredQuestion = null">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-p3"
                                            v-html="getHighlightedSegmentById('summary-p3')"></span>

                                        <!-- Q40 gap -->
                                        <span id="question-40"
                                            class="inline-flex min-h-7 min-w-24 mx-1 items-center justify-center border-2 border-dashed px-2 py-0.5 text-sm cursor-pointer align-middle relative transition-colors"
                                            :class="dragOverSummaryQ === 40 ? 'border-gray-700 bg-gray-100' : answers.q40 ? 'border-gray-900 font-medium' : 'border-gray-400'"
                                            @dragover="onSummaryDragOver($event, 40)" @dragleave="onSummaryDragLeave"
                                            @drop="onSummaryDrop($event, 40)" @click="clearSummary(40)"
                                            :title="answers.q40 ? 'Click to clear' : 'Drop here'">
                                            <span v-if="answers.q40">{{ getSummaryLabel(answers.q40) }}</span>
                                            <span v-else class="font-bold text-gray-400">40</span>
                                            <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                @click.stop="emit('toggleFlag', 40)"
                                                class="absolute -top-3.5 -right-3.5 flex h-6 w-6 items-center justify-center rounded border bg-white transition-all"
                                                :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400'"
                                                @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-p4"
                                            v-html="getHighlightedSegmentById('summary-p4')"></span>
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
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 hover:bg-gray-50"
                            title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 hover:bg-gray-50"
                            title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                        </button>
                    </div>
                    <div class="tooltip-arrow"></div>
                </div>
            </div>

            <!-- Delete Highlight Tooltip -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div class="delete-highlight-tooltip fixed z-[9999]"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 hover:bg-gray-50">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                            <span class="mt-1.5 text-xs font-medium text-gray-600">Delete</span>
                            <span class="text-xs font-medium text-gray-600">Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md max-w-[240px]">
                        <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50" title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                        "{{ selectedText }}"
                    </p>
                    <input v-model="noteInputText" type="text" placeholder="Write your note here..."
                        class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                        class="border border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-1 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.part-header-box { background-color: #F1F2EC; }
.select-text { user-select: text; -webkit-user-select: text; }
.select-none { user-select: none; cursor: col-resize; }

.passage-panel { width: 100%; }
.answer-panel  { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel  { width: calc(100% - var(--panel-width) - 0.5rem); }
}

mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; cursor: pointer; }
.highlight-yellow  { background-color: rgba(254,240,138,.5); }
.highlight-green   { background-color: rgba(187,247,208,.5); }
.highlight-blue    { background-color: rgba(191,219,254,.5); }
.highlight-pink    { background-color: rgba(251,207,232,.5); }
.highlight-orange  { background-color: rgba(254,215,170,.5); }

.highlight-question { animation: hPulse 2s ease-in-out; }
@keyframes hPulse {
    0%,100% { background-color: rgba(0,0,0,.1); }
    50%      { background-color: rgba(0,0,0,.2); }
}

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

/* Context menu arrow */
.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,.1));
}
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-bottom: 8px solid white; filter: drop-shadow(0 -1px 1px rgba(0,0,0,.1));
}
.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,.1));
}
</style>

<style>
.note-highlight {
    background-color: rgba(191,219,254,.6) !important;
    cursor: pointer; padding: 2px 0; border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147,197,253,.7) !important; }
</style>
