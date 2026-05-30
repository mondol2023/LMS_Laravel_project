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

// ── Resize ────────────────────────────────────────────────────────────────────
const PANEL_WIDTH_KEY = 'reading-ielts-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);
const contentTextRef = ref<HTMLDivElement | null>(null);
const passageTextRef = ref<HTMLDivElement | null>(null);

// ── Highlight / Note ──────────────────────────────────────────────────────────
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

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

// ── Answers ───────────────────────────────────────────────────────────────────
const answers = ref({
    q27: '', q28: '', q29: '', q30: '', q31: '', q32: '',
    q33: '', q34: '', q35: '',
    q36: '', q37: '', q38: '', q39: '', q40: '',
});

// ── Q36-40 Drag & Drop ────────────────────────────────────────────────────────
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// All 11 letter options — always visible, can be reused across questions
const letterOptions = [
    { value: 'A', label: 'frequencies' },
    { value: 'B', label: 'the engine' },
    { value: 'C', label: 'rubbish' },
    { value: 'D', label: 'resonators' },
    { value: 'E', label: 'air flow' },
    { value: 'F', label: 'dissipation' },
    { value: 'G', label: 'sound energy' },
    { value: 'H', label: 'pores' },
    { value: 'I', label: 'lanes' },
    { value: 'J', label: 'drainage' },
    { value: 'K', label: 'sources' },
];

function getLetterLabel(value: string): string {
    const found = letterOptions.find((o) => o.value === value);
    return found ? `${found.value}. ${found.label}` : value;
}

const handleDragStart = (e: DragEvent, value: string) => {
    draggedOption.value = value;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', value);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const value = e.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (value) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = value;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// ── Passage + textSegments ────────────────────────────────────────────────────
const passageText = ref(`A.
The noise produced by busy roads is a growing problem. While vehicle designers have worked hard to quieten engines, they have been less successful elsewhere. The sound created by the tyres on the surface of the road now accounts for more than half the noise that vehicles create, and as road building and car sales continue to boom - particularly in Asia and the US - this is turning into a global issue.

B.
According to the World Health Organization, exposure to noise from road traffic over long periods can lead to stress-related health problems. And where traffic noise exceeds a certain threshold, road builders have to spend money erecting sound barriers and installing double glazing in blighted homes. Houses become harder to sell where environmental noise is high, and people are not as efficient or productive at work.

C.
Already, researchers in the Netherlands - one of the most densely populated countries in the world - are working to develop techniques for silencing the roads. In the next five years, the Dutch government aims to have reduced noise levels from the country's road surfaces by six decibels overall. Dutch mechanical engineer Ard Kuijpers has come up with one of the most promising, and radical, ideas. He set out to tackle the three most important factors: surface texture, hardness and ability to absorb sound.

D.
The rougher the surface, the more likely it is that a tyre will vibrate and create noise. Road builders usually eliminate bumps on freshly laid asphalt with heavy rollers, but Kuijpers has developed a method of road building that he thinks can create the ultimate quiet road. His secret is a special mould 3 metres wide and 50 metres long. Hot asphalt, mixed with small stones, is spread into the mould by a rail-mounted machine which flattens the asphalt mix with a roller. When it sets, the 10-millimetre- thick sheet has a surface smoother than anything that can be achieved by conventional methods.

E.
To optimise the performance of his road surface - to make it hard wearing yet soft enough to snuff out vibrations - he then adds another layer below the asphalt. This consists of a 30-millimetre-thick layer of rubber, mixed with stones which are larger than those in the layer above. 'It's like a giant mouse mat, making the road softer,' says Kuijpers.

F.
The size of the stones used in the two layers is important, since they create pores of a specific size in the road surface. Those used in the top layer are just 4 or 5 millimetres across, while the ones below are approximately twice that size - about 9 millimetres. Kuijpers says the surface can absorb any air that is passing through a tyre's tread (the indentations or ridges on the surface of a tyre), damping oscillations that would otherwise create noise. And in addition, they make it easier for the water to drain away, which can make the road safer in wet weather.

G.
Compared with the complex manufacturing process, laying the surface is quite simple. It emerges from the factory rolled, like a carpet, onto a drum 1.5 metres in diameter. On site, it is unrolled and stuck onto its foundation with bitumen. Even the white lines are applied in the factory.

H.
The foundation itself uses an even more sophisticated technique to reduce noise further. It consists of a sound-absorbing concrete base containing flask-shaped slots up to 10 millimetres wide and 30 millimetres deep that are open at the top and sealed at the lower end. These cavities act like Helmholtz resonators - when sound waves of specific frequencies enter the top of a flask, they set up resonances inside and the energy of the sound dissipates into the concrete as heat. The cavities play another important role: they help to drain water that seeps through from the upper surface. This flow will help flush out waste material and keep the pores in the outer layers clear.

I.
Kuijpers can even control the sounds that his resonators absorb, simply by altering their dimensions. This could prove especially useful since different vehicles produce noise at different frequencies. Car tyres peak at around 1000 hertz, for example, but trucks generate lower-frequency noise at around 600 hertz. By varying the size of the Kuijpers resonators, it is possible to control which frequencies the concrete absorbs. On large highways, trucks tend to use the inside lane, so resonators here could be tuned to absorb sounds at around 600 hertz while those in other lanes could deal with higher frequency noise from cars.

J.
Kuijpers believes he can cut noise by five decibels compared to the quietest of today's roads. He has already tested a 100- metre-long section of his road on a motorway near Apeldoorn, and Dutch construction company Heijmans is discussing the location of the next roll-out road with the country's government. The success of Kuijpers' design will depend on how much it eventually costs. But for those affected by traffic noise, there is hope of quieter times ahead.`);

const textSegments = ref([
    { id: 'part3-header',      text: 'Part 3',                                    offset: 0 },
    { id: 'part3-instruction', text: 'Read the text and answer questions 28-40.', offset: 7 },
    { text: 'The silent road',                                                      offset: 47 + 19 },
    { text: passageText.value, offset: 47+19 + 17 },
    { text: 'READING PASSAGE 3',                                                   offset: passageText.value.length+47+19+47 },

    { text: 'Questions 27\u201332',                                                 offset: passageText.value.length+47+19+47 + 35 },
    { text: 'Reading Passage 3 has ten paragraphs labelled A\u2013J.',              offset: passageText.value.length+47+19+47 + 51 },
    { text: 'Which paragraph contains the following information?',                  offset: passageText.value.length+47+19+47 + 106 },
    { text: 'Write the correct letter A\u2013J in boxes 27\u201332 on your answer sheet.', offset: passageText.value.length+47+19+47 + 158 },
    { text: "27. a description of the form in which Kuijpers\u2019 road surface is taken to its destination", offset: passageText.value.length+47+19+47 + 233 },
    { text: '28. an explanation of how Kuijpers makes a smooth road surface',           offset: passageText.value.length+47+19+47 + 316 },
    { text: "29. something that has to be considered when evaluating Kuijpers\u2019 proposal", offset: passageText.value.length+47+19+47 + 377 },
    { text: '30. various economic reasons for reducing road noise',                     offset: passageText.value.length+47+19+47 + 448 },
    { text: '31. a generalisation about the patterns of use of vehicles on major roads', offset: passageText.value.length+47+19+47 + 497 },
    { text: '32. a summary of the different things affecting levels of noise on roads', offset: passageText.value.length+47+19+47 + 572 },
    { text: 'Questions 33-35',                                                      offset: passageText.value.length+47+19+47 + 648 },
    { text: 'Label the diagram below.',                                             offset: passageText.value.length+47+19+47 + 665 },
    { text: 'Choose NO MORE THAN ONE WORD AND/OR A NUMBER from the passage for each answer.', offset: passageText.value.length+47+19+47 + 689 },
    { text: 'Write your answers in boxes 33-35 on your answer sheet.',             offset: passageText.value.length+47+19+47 + 771 },
    { text: 'Cross section of Kuijpers\u2019 proposed noise-reducing road',        offset: passageText.value.length+47+19+47 + 828 },
    { text: 'Questions 36\u201340',                                                 offset: passageText.value.length+47+19+47 + 884 },
    { text: 'Complete the table below using the list of letters A\u2013K. Write the correct letters in boxes 36\u201340 on your answer sheet.', offset: passageText.value.length+47+19+47 + 900 },
    { text: 'Letter list (A\u2013K):',                                             offset: passageText.value.length+47+19+47 + 1014 },
    { text: 'A. frequencies',                                                      offset: passageText.value.length+47+19+47 + 1034 },
    { text: 'B. the engine',                                                       offset: passageText.value.length+47+19+47 + 1050 },
    { text: 'C. rubbish',                                                          offset: passageText.value.length+47+19+47 + 1064 },
    { text: 'D. resonators',                                                       offset: passageText.value.length+47+19+47 + 1075 },
    { text: 'E. air flow',                                                         offset: passageText.value.length+47+19+47 + 1089 },
    { text: 'F. dissipation',                                                      offset: passageText.value.length+47+19+47 + 1101 },
    { text: 'G. sound energy',                                                     offset: passageText.value.length+47+19+47 + 1116 },
    { text: 'H. pores',                                                            offset: passageText.value.length+47+19+47 + 1132 },
    { text: 'I. lanes',                                                            offset: passageText.value.length+47+19+47 + 1141 },
    { text: 'J. drainage',                                                         offset: passageText.value.length+47+19+47 + 1150 },
    { text: 'K. sources',                                                          offset: passageText.value.length+47+19+47 + 1163 },
    { text: 'Layer',                                                               offset: passageText.value.length+47+19+47 + 1172 },
    { text: 'Component',                                                           offset: passageText.value.length+47+19+47 + 1177 },
    { text: 'Function',                                                            offset: passageText.value.length+47+19+47 + 1186 },
    { text: 'upper and lower',                                                     offset: passageText.value.length+47+19+47 + 1194 },
    { text: 'stones',                                                              offset: passageText.value.length+47+19+47 + 1209 },
    { text: '\u2022 reduce oscillations caused by ',                               offset: passageText.value.length+47+19+47 + 1215 },
    { text: '\u2022 create pores which help ',                                     offset: passageText.value.length+47+19+47 + 1246 },
    { text: 'foundation',                                                          offset: passageText.value.length+47+19+47 + 1272 },
    { text: 'slots',                                                               offset: passageText.value.length+47+19+47 + 1282 },
    { text: '\u2022 convert ',                                                     offset: passageText.value.length+47+19+47 + 1287 },
    { text: ' to heat.',                                                           offset: passageText.value.length+47+19+47 + 1297 },
    { text: '\u2022 help to remove ',                                              offset: passageText.value.length+47+19+47 + 1306 },
    { text: '\u2022 can be adapted to absorb different ',                          offset: passageText.value.length+47+19+47 + 1323 },
]);

// ── Highlighting helpers ──────────────────────────────────────────────────────
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

const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset
    );

    if (!overlappingHighlights.length && !overlappingNotes.length) return segmentText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
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
            result = annotation.type === 'note'
                ? `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`
                : `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
        }
    }
    return result;
};

// ── Exposed API ───────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

// ── Selection / highlight handlers ───────────────────────────────────────────
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
            const segmentEl = (container as Element).closest('[data-segment-text]');
            if (!segmentEl) return null;
            const segmentText = segmentEl.getAttribute('data-segment-text') || '';
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) return null;
            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) { offsetInSegment += offsetInNode; break; }
                else offsetInSegment += currentNode.textContent?.length || 0;
            }
            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);
        if (startAbsOffset === null || endAbsOffset === null) { showContextMenu.value = false; window.getSelection()?.removeAllRanges(); return; }
        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 78;
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
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x;
    let y = contextMenuPosition.value.y + 70;
    if (selection && selection.rangeCount > 0) {
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    }
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    if (x - 160 < 10) x = 170;
    else if (x + 160 > vw - 10) x = vw - 170;
    if (y + 180 > vh - 10) y = Math.max(10, y - 200);
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
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Part 3' });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const id = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(id);
    }
};

const handleContentClick = (event: MouseEvent) => {
    const selection = window.getSelection();
    if (selection && selection.toString().trim() && !selection.isCollapsed) {
        return; // Let selection finish
    }
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
            showContextMenu.value = false;
        }
    } else {
        closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const noteMark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteIdNum = parseInt(noteMark.getAttribute('data-note-id') || '0', 10);
        const note = notes.value.find((n) => n.id === noteIdNum);
        if (note) {
            const rect = noteMark.getBoundingClientRect();
            const y = rect.top - 58 < 10 ? rect.bottom + 8 : rect.top - 58;
            noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
            hoveredNoteId.value = noteIdNum;
            hoveredNoteText.value = note.note;
            showNoteTooltip.value = true;
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') &&
        !(event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; };

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const closeContextMenu = () => { showContextMenu.value = false; };

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; }
};

// ── Resize ────────────────────────────────────────────────────────────────────
const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const pct = ((event.clientX - rect.left) / rect.width) * 100;
    if (pct >= 20 && pct <= 80) leftPanelWidth.value = pct;
};
const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, (w) => localStorage.setItem(PANEL_WIDTH_KEY, w.toString()));

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});

onBeforeUnmount(() => {
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

        <!-- Part Header -->
        <div class="mx-5 mt-4  border-gray-300 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text"
                data-segment-id="part3-header"
                v-html="getHighlightedSegment(textSegments[0].text)"></p>
            <p class="text-sm text-gray-700 select-text"
                data-segment-id="part3-instruction"
                v-html="getHighlightedSegment(textSegments[1].text)"></p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ══ PASSAGE PANEL ══ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">

                    <!-- Passage Header -->
                    <div class="flex-shrink-0  border-gray-200 p-6">

                        <h2 class="mt-1 text-xl font-bold text-gray-900"
                            :data-segment-text="'The silent road'"
                            v-html="getHighlightedSegment('The silent road')"></h2>
                    </div>

                    <!-- Passage Body -->
                    <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                        <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text">
                            <div class=" p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700"
                                    :data-segment-text="passageText.valueOf()"
                                    v-html="getHighlightedSegment(passageText.valueOf())"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ RESIZE HANDLE ══ -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ══ QUESTIONS PANEL ══ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- ─── Q27-32: Paragraph matching ─── -->
                            <div class="border border-gray-200 p-6">
                                <h3 class="mb-4 text-base font-bold text-gray-900">
                                    <span :data-segment-text="'Questions 27\u201332'"
                                        v-html="getHighlightedSegment('Questions 27\u201332')"></span>
                                </h3>
                                <div class="mb-4 border border-gray-200 p-4">
                                    <p class="text-base font-medium text-gray-800"
                                        :data-segment-text="'Reading Passage 3 has ten paragraphs labelled A\u2013J.'"
                                        v-html="getHighlightedSegment('Reading Passage 3 has ten paragraphs labelled A\u2013J.')"></p>
                                    <p class="pt-2 text-base text-gray-700"
                                        :data-segment-text="'Which paragraph contains the following information?'"
                                        v-html="getHighlightedSegment('Which paragraph contains the following information?')"></p>
                                    <p class="pt-2 text-base text-gray-700"
                                        :data-segment-text="'Write the correct letter A\u2013J in boxes 27\u201332 on your answer sheet.'"
                                        v-html="getHighlightedSegment('Write the correct letter A\u2013J in boxes 27\u201332 on your answer sheet.')"></p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Q27 -->
                                    <div id="question-27" class="group/q27 border border-gray-200 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="grid flex-1 grid-cols-12 gap-3">
                                                <div class="col-span-4">
                                                    <select v-model="answers.q27"
                                                        class="w-full border-2 border-gray-900 bg-white px-3 py-1 text-base font-medium focus:outline-none">
                                                        <option value="">Select...</option>
                                                        <option v-for="l in ['A','B','C','D','E','F','G','H','I','J']" :key="l" :value="l">{{ l }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-8">
                                                    <p class="text-base leading-relaxed font-medium text-gray-800"
                                                        :data-segment-text="'27. a description of the form in which Kuijpers\u2019 road surface is taken to its destination'"
                                                        v-html="getHighlightedSegment('27. a description of the form in which Kuijpers\u2019 road surface is taken to its destination')"></p>
                                                </div>
                                            </div>
                                            <button @click.stop="emit('toggleFlag', 27)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q27:opacity-100"
                                                :class="isQuestionFlagged(27) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                title="Bookmark">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q28 -->
                                    <div id="question-28" class="group/q28 border border-gray-200 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="grid flex-1 grid-cols-12 gap-3">
                                                <div class="col-span-4">
                                                    <select v-model="answers.q28"
                                                        class="w-full border-2 border-gray-900 bg-white px-3 py-1 text-base font-medium focus:outline-none">
                                                        <option value="">Select...</option>
                                                        <option v-for="l in ['A','B','C','D','E','F','G','H','I','J']" :key="l" :value="l">{{ l }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-8">
                                                    <p class="text-base leading-relaxed font-medium text-gray-800"
                                                        :data-segment-text="'28. an explanation of how Kuijpers makes a smooth road surface'"
                                                        v-html="getHighlightedSegment('28. an explanation of how Kuijpers makes a smooth road surface')"></p>
                                                </div>
                                            </div>
                                            <button @click.stop="emit('toggleFlag', 28)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q28:opacity-100"
                                                :class="isQuestionFlagged(28) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                title="Bookmark">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q29 -->
                                    <div id="question-29" class="group/q29 border border-gray-200 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="grid flex-1 grid-cols-12 gap-3">
                                                <div class="col-span-4">
                                                    <select v-model="answers.q29"
                                                        class="w-full border-2 border-gray-900 bg-white px-3 py-1 text-base font-medium focus:outline-none">
                                                        <option value="">Select...</option>
                                                        <option v-for="l in ['A','B','C','D','E','F','G','H','I','J']" :key="l" :value="l">{{ l }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-8">
                                                    <p class="text-base leading-relaxed font-medium text-gray-800"
                                                        :data-segment-text="'29. something that has to be considered when evaluating Kuijpers\u2019 proposal'"
                                                        v-html="getHighlightedSegment('29. something that has to be considered when evaluating Kuijpers\u2019 proposal')"></p>
                                                </div>
                                            </div>
                                            <button @click.stop="emit('toggleFlag', 29)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q29:opacity-100"
                                                :class="isQuestionFlagged(29) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                title="Bookmark">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q30 -->
                                    <div id="question-30" class="group/q30 border border-gray-200 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="grid flex-1 grid-cols-12 gap-3">
                                                <div class="col-span-4">
                                                    <select v-model="answers.q30"
                                                        class="w-full border-2 border-gray-900 bg-white px-3 py-1 text-base font-medium focus:outline-none">
                                                        <option value="">Select...</option>
                                                        <option v-for="l in ['A','B','C','D','E','F','G','H','I','J']" :key="l" :value="l">{{ l }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-8">
                                                    <p class="text-base leading-relaxed font-medium text-gray-800"
                                                        :data-segment-text="'30. various economic reasons for reducing road noise'"
                                                        v-html="getHighlightedSegment('30. various economic reasons for reducing road noise')"></p>
                                                </div>
                                            </div>
                                            <button @click.stop="emit('toggleFlag', 30)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q30:opacity-100"
                                                :class="isQuestionFlagged(30) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                title="Bookmark">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q31 -->
                                    <div id="question-31" class="group/q31 border border-gray-200 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="grid flex-1 grid-cols-12 gap-3">
                                                <div class="col-span-4">
                                                    <select v-model="answers.q31"
                                                        class="w-full border-2 border-gray-900 bg-white px-3 py-1 text-base font-medium focus:outline-none">
                                                        <option value="">Select...</option>
                                                        <option v-for="l in ['A','B','C','D','E','F','G','H','I','J']" :key="l" :value="l">{{ l }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-8">
                                                    <p class="text-base leading-relaxed font-medium text-gray-800"
                                                        :data-segment-text="'31. a generalisation about the patterns of use of vehicles on major roads'"
                                                        v-html="getHighlightedSegment('31. a generalisation about the patterns of use of vehicles on major roads')"></p>
                                                </div>
                                            </div>
                                            <button @click.stop="emit('toggleFlag', 31)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q31:opacity-100"
                                                :class="isQuestionFlagged(31) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                title="Bookmark">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q32 -->
                                    <div id="question-32" class="group/q32 border border-gray-200 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="grid flex-1 grid-cols-12 gap-3">
                                                <div class="col-span-4">
                                                    <select v-model="answers.q32"
                                                        class="w-full border-2 border-gray-900 bg-white px-3 py-1 text-base font-medium focus:outline-none">
                                                        <option value="">Select...</option>
                                                        <option v-for="l in ['A','B','C','D','E','F','G','H','I','J']" :key="l" :value="l">{{ l }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-8">
                                                    <p class="text-base leading-relaxed font-medium text-gray-800"
                                                        :data-segment-text="'32. a summary of the different things affecting levels of noise on roads'"
                                                        v-html="getHighlightedSegment('32. a summary of the different things affecting levels of noise on roads')"></p>
                                                </div>
                                            </div>
                                            <button @click.stop="emit('toggleFlag', 32)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q32:opacity-100"
                                                :class="isQuestionFlagged(32) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                title="Bookmark">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ─── Q33-35: Diagram labelling ─── -->
                            <div id="question-33-35" class="border border-gray-200 p-6">
                                <h3 class="mb-3 text-base font-bold text-gray-900">
                                    <span :data-segment-text="'Questions 33-35'"
                                        v-html="getHighlightedSegment('Questions 33-35')"></span>
                                </h3>
                                <p class="mb-2 text-base text-gray-700"
                                    :data-segment-text="'Label the diagram below.'"
                                    v-html="getHighlightedSegment('Label the diagram below.')"></p>
                                <p class="mb-2 text-base text-gray-700"
                                    :data-segment-text="'Choose NO MORE THAN ONE WORD AND/OR A NUMBER from the passage for each answer.'"
                                    v-html="getHighlightedSegment('Choose NO MORE THAN ONE WORD AND/OR A NUMBER from the passage for each answer.')"></p>
                                <p class="mb-4 text-base text-gray-700"
                                    :data-segment-text="'Write your answers in boxes 33-35 on your answer sheet.'"
                                    v-html="getHighlightedSegment('Write your answers in boxes 33-35 on your answer sheet.')"></p>

                                <div class="border border-gray-200 p-4">
                                    <h4 class="mb-3 text-base font-bold text-gray-900"
                                        :data-segment-text="'Cross section of Kuijpers\u2019 proposed noise-reducing road'"
                                        v-html="getHighlightedSegment('Cross section of Kuijpers\u2019 proposed noise-reducing road')"></h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <img src="/images/reading/IELTS005.PNG"
                                                alt="Cross section of Kuijpers' road" class="mx-auto" />
                                        </div>
                                        <div class="flex flex-col justify-center space-y-6 text-base text-gray-800">
                                            <!-- Q33 -->
                                            <div id="question-33" class="group/q33 flex items-center gap-2">
                                                <span class="font-bold"></span>
                                                <input v-model="answers.q33" type="text"
                                                    class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-center text-sm font-medium focus:outline-none"
                                                    placeholder="33" />
                                                <button @click.stop="emit('toggleFlag', 33)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                        opacity-5 group-hover/q33:opacity-100"
                                                    :class="isQuestionFlagged(33) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    title="Bookmark">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <!-- Q34 -->
                                            <div id="question-34" class="group/q34 flex flex-wrap items-center gap-2">
                                                <span class="font-base">stoned (approx. mm diameter)</span>
                                                <input v-model="answers.q34" type="text"
                                                    class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-center text-sm font-medium focus:outline-none"
                                                    placeholder="34" />
                                                <button @click.stop="emit('toggleFlag', 34)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                        opacity-5 group-hover/q34:opacity-100"
                                                    :class="isQuestionFlagged(34) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    title="Bookmark">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <!-- Q35 -->
                                            <div id="question-35" class="group/q35 flex flex-wrap items-center gap-2">
                                                <span class="font-bold"></span>
                                                <input v-model="answers.q35" type="text"
                                                    class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-center text-sm font-medium focus:outline-none"
                                                    placeholder="35" />
                                                <span class="text-sm text-gray-600">flask-shaped slots</span>
                                                <button @click.stop="emit('toggleFlag', 35)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                        opacity-5 group-hover/q35:opacity-100"
                                                    :class="isQuestionFlagged(35) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    title="Bookmark">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ─── Q36-40: Drag-and-drop table ─── -->
                            <div class="border border-gray-200 p-6">
                                <h3 class="mb-3 text-base font-bold text-gray-900">
                                    <span :data-segment-text="'Questions 36\u201340'"
                                        v-html="getHighlightedSegment('Questions 36\u201340')"></span>
                                </h3>
                                <p class="mb-4 text-base text-gray-700"
                                    :data-segment-text="'Complete the table below using the list of letters A\u2013K. Write the correct letters in boxes 36\u201340 on your answer sheet.'"
                                    v-html="getHighlightedSegment('Complete the table below using the list of letters A\u2013K. Write the correct letters in boxes 36\u201340 on your answer sheet.')"></p>

                                <!-- Letter bank + table side by side -->
                                <div class="flex gap-4">

                                    <!-- Letter bank (draggable — always visible, reusable) -->
                                    <div class="w-40 shrink-0 self-start border border-gray-900 p-3">
                                        <p class="mb-2 text-xs font-bold text-gray-900 uppercase tracking-wide"
                                            :data-segment-text="'Letter list (A\u2013K):'"
                                            v-html="getHighlightedSegment('Letter list (A\u2013K):')"></p>
                                        <div class="space-y-1">
                                            <div v-for="opt in letterOptions" :key="opt.value"
                                                class="flex cursor-grab items-center gap-1.5 border border-gray-300 bg-white px-2 py-1 text-xs transition-all hover:border-gray-900 hover:bg-gray-50 active:cursor-grabbing"
                                                :class="{ 'opacity-40': draggedOption === opt.value }"
                                                draggable="true"
                                                @dragstart="handleDragStart($event, opt.value)"
                                                @dragend="handleDragEnd">
                                                <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                </svg>
                                                <span class="font-bold text-gray-900">{{ opt.value }}.</span>
                                                <span class="text-gray-700">{{ opt.label }}</span>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-400 italic">Drag to table →<br>Click drop zone to clear</p>
                                    </div>

                                    <!-- Table -->
                                    <div class="flex-1 overflow-x-auto">
                                        <table class="w-full border border-gray-900 text-left text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="border border-gray-900 px-3 py-2 font-semibold text-gray-900"
                                                        :data-segment-text="'Layer'"
                                                        v-html="getHighlightedSegment('Layer')"></th>
                                                    <th class="border border-gray-900 px-3 py-2 font-semibold text-gray-900"
                                                        :data-segment-text="'Component'"
                                                        v-html="getHighlightedSegment('Component')"></th>
                                                    <th class="border border-gray-900 px-3 py-2 font-semibold text-gray-900"
                                                        :data-segment-text="'Function'"
                                                        v-html="getHighlightedSegment('Function')"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white">
                                                <!-- Row 1: upper/lower + stones → Q36, Q37 -->
                                                <tr>
                                                    <td class="border border-gray-900 px-3 py-3 align-top"
                                                        :data-segment-text="'upper and lower'"
                                                        v-html="getHighlightedSegment('upper and lower')"></td>
                                                    <td class="border border-gray-900 px-3 py-3 align-top"
                                                        :data-segment-text="'stones'"
                                                        v-html="getHighlightedSegment('stones')"></td>
                                                    <td class="border border-gray-900 px-3 py-3">
                                                        <!-- Q36 -->
                                                        <div class="group/q36 mb-3 flex flex-wrap items-center gap-1">
                                                            <span :data-segment-text="'\u2022 reduce oscillations caused by '"
                                                                v-html="getHighlightedSegment('\u2022 reduce oscillations caused by ')"></span>
                                                            <span
                                                                id="question-36"
                                                                class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-xs transition-all cursor-pointer"
                                                                :class="dragOverQuestion === 36 ? 'border-gray-900 bg-gray-100' : answers.q36 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                                @dragover="handleDragOver($event, 36)"
                                                                @dragleave="handleDragLeave"
                                                                @drop="handleDrop($event, 36)"
                                                                @click="clearAnswer(36)"
                                                                :title="answers.q36 ? 'Click to clear' : 'Drop here'">
                                                                <span v-if="answers.q36">{{ getLetterLabel(answers.q36) }}</span>
                                                                <span v-else class="font-bold text-gray-400">36</span>
                                                            </span>
                                                            <button @click.stop="emit('toggleFlag', 36)"
                                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all duration-150
                                                                    opacity-5 group-hover/q36:opacity-100"
                                                                :class="isQuestionFlagged(36) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                title="Bookmark">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <!-- Q37 -->
                                                        <div class="group/q37 flex flex-wrap items-center gap-1">
                                                            <span :data-segment-text="'\u2022 create pores which help '"
                                                                v-html="getHighlightedSegment('\u2022 create pores which help ')"></span>
                                                            <span
                                                                id="question-37"
                                                                class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-xs transition-all cursor-pointer"
                                                                :class="dragOverQuestion === 37 ? 'border-gray-900 bg-gray-100' : answers.q37 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                                @dragover="handleDragOver($event, 37)"
                                                                @dragleave="handleDragLeave"
                                                                @drop="handleDrop($event, 37)"
                                                                @click="clearAnswer(37)"
                                                                :title="answers.q37 ? 'Click to clear' : 'Drop here'">
                                                                <span v-if="answers.q37">{{ getLetterLabel(answers.q37) }}</span>
                                                                <span v-else class="font-bold text-gray-400">37</span>
                                                            </span>
                                                            <button @click.stop="emit('toggleFlag', 37)"
                                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all duration-150
                                                                    opacity-5 group-hover/q37:opacity-100"
                                                                :class="isQuestionFlagged(37) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                title="Bookmark">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- Row 2: foundation + slots → Q38, Q39, Q40 -->
                                                <tr>
                                                    <td class="border border-gray-900 px-3 py-3 align-top"
                                                        :data-segment-text="'foundation'"
                                                        v-html="getHighlightedSegment('foundation')"></td>
                                                    <td class="border border-gray-900 px-3 py-3 align-top"
                                                        :data-segment-text="'slots'"
                                                        v-html="getHighlightedSegment('slots')"></td>
                                                    <td class="border border-gray-900 px-3 py-3">
                                                        <!-- Q38 -->
                                                        <div class="group/q38 mb-3 flex flex-wrap items-center gap-1">
                                                            <span :data-segment-text="'\u2022 convert '"
                                                                v-html="getHighlightedSegment('\u2022 convert ')"></span>
                                                            <span
                                                                id="question-38"
                                                                class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-xs transition-all cursor-pointer"
                                                                :class="dragOverQuestion === 38 ? 'border-gray-900 bg-gray-100' : answers.q38 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                                @dragover="handleDragOver($event, 38)"
                                                                @dragleave="handleDragLeave"
                                                                @drop="handleDrop($event, 38)"
                                                                @click="clearAnswer(38)"
                                                                :title="answers.q38 ? 'Click to clear' : 'Drop here'">
                                                                <span v-if="answers.q38">{{ getLetterLabel(answers.q38) }}</span>
                                                                <span v-else class="font-bold text-gray-400">38</span>
                                                            </span>
                                                            <span :data-segment-text="' to heat.'"
                                                                v-html="getHighlightedSegment(' to heat.')"></span>
                                                            <button @click.stop="emit('toggleFlag', 38)"
                                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all duration-150
                                                                    opacity-5 group-hover/q38:opacity-100"
                                                                :class="isQuestionFlagged(38) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                title="Bookmark">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <!-- Q39 -->
                                                        <div class="group/q39 mb-3 flex flex-wrap items-center gap-1">
                                                            <span :data-segment-text="'\u2022 help to remove '"
                                                                v-html="getHighlightedSegment('\u2022 help to remove ')"></span>
                                                            <span
                                                                id="question-39"
                                                                class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-xs transition-all cursor-pointer"
                                                                :class="dragOverQuestion === 39 ? 'border-gray-900 bg-gray-100' : answers.q39 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                                @dragover="handleDragOver($event, 39)"
                                                                @dragleave="handleDragLeave"
                                                                @drop="handleDrop($event, 39)"
                                                                @click="clearAnswer(39)"
                                                                :title="answers.q39 ? 'Click to clear' : 'Drop here'">
                                                                <span v-if="answers.q39">{{ getLetterLabel(answers.q39) }}</span>
                                                                <span v-else class="font-bold text-gray-400">39</span>
                                                            </span>
                                                            <button @click.stop="emit('toggleFlag', 39)"
                                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all duration-150
                                                                    opacity-5 group-hover/q39:opacity-100"
                                                                :class="isQuestionFlagged(39) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                title="Bookmark">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <!-- Q40 -->
                                                        <div class="group/q40 flex flex-wrap items-center gap-1">
                                                            <span :data-segment-text="'\u2022 can be adapted to absorb different '"
                                                                v-html="getHighlightedSegment('\u2022 can be adapted to absorb different ')"></span>
                                                            <span
                                                                id="question-40"
                                                                class="inline-flex min-h-8 min-w-24 items-center justify-center border-2 border-dashed px-2 py-1 text-center text-xs transition-all cursor-pointer"
                                                                :class="dragOverQuestion === 40 ? 'border-gray-900 bg-gray-100' : answers.q40 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                                @dragover="handleDragOver($event, 40)"
                                                                @dragleave="handleDragLeave"
                                                                @drop="handleDrop($event, 40)"
                                                                @click="clearAnswer(40)"
                                                                :title="answers.q40 ? 'Click to clear' : 'Drop here'">
                                                                <span v-if="answers.q40">{{ getLetterLabel(answers.q40) }}</span>
                                                                <span v-else class="font-bold text-gray-400">40</span>
                                                            </span>
                                                            <button @click.stop="emit('toggleFlag', 40)"
                                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all duration-150
                                                                    opacity-5 group-hover/q40:opacity-100"
                                                                :class="isQuestionFlagged(40) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                title="Bookmark">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div><!-- end space-y-8 -->
                    </div>
                </div><!-- end answer panel -->

            </div><!-- end flex container -->
        </div>

        <!-- ══ Teleport: Context Menu ══ -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div class="highlight-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden border border-gray-300 bg-white shadow-md">
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
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
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
                    <div class="flex flex-col items-center overflow-hidden border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2"></path>
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
                    <div class="note-tooltip-content flex items-center gap-2.5 border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 p-1 transition-colors hover:bg-red-50" title="Delete note">
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
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
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
    </div>
</template>

<style scoped>
.part-header-box { background-color: #F1F2EC; }

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

/* Responsive panel widths */
.passage-panel {
    width: 100%;
}

.answer-panel {
    width: 100%;
}

@media (min-width: 1024px) {
    .passage-panel {
        width: calc(var(--panel-width) - 0.5rem);
    }

    .answer-panel {
        width: calc(100% - var(--panel-width) - 0.5rem);
    }
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
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

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(0, 0, 0, 0.2);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1);
    }
}

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #111827;
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
