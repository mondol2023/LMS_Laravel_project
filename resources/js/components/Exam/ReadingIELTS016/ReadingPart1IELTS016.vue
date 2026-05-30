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

// Hover state for showing bookmark buttons
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-tea-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Delete highlight tooltip state
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note hover tooltip state
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// Q1-7: heading matching answers
// Q8-13: TRUE/FALSE/NOT GIVEN answers
const answers = ref({
    q1: '', q2: '', q3: '', q4: '', q5: '', q6: '', q7: '',
    q8: '', q9: '', q10: '', q11: '', q12: '', q13: '',
});

// Drag and drop functionality for questions 1-7
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i',    label: 'Cases of Japan, Holland and France' },
    { value: 'ii',   label: 'Tea drinking in Japan and Britain' },
    { value: 'iii',  label: 'Failed to find a plausible cause for mystery about lower mortality rate' },
    { value: 'iv',   label: 'Preconditions necessary for industrial revolution' },
    { value: 'v',    label: 'Time and place of industrialization' },
    { value: 'vi',   label: 'Conclusion drawn from the comparison with Japan' },
    { value: 'vii',  label: 'Japan conscious rejection of labour-saving devices' },
    { value: 'viii', label: 'Relation between population and changes of drink in Britain' },
    { value: 'ix',   label: 'Two possible solutions to the puzzle' },
];

// Filter out used heading options
const availableHeadingOptions = computed(() => {
    const usedOptions = [
        answers.value.q1, answers.value.q2, answers.value.q3, answers.value.q4,
        answers.value.q5, answers.value.q6, answers.value.q7,
    ].filter(Boolean);
    return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
});

const handleHeadingDragStart = (e: DragEvent, option: string) => {
    draggedHeading.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleHeadingDragEnd = () => {
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};

const handleHeadingDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    dragOverQuestion.value = questionNum;
};

const handleHeadingDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleHeadingDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedHeading.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};

const clearHeadingAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

const getHeadingLabel = (value: string): string => {
    const option = headingOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// ===== PASSAGE PARAGRAPHS =====
const paragraphA = `Alan Macfarlane thinks he could rewrite history. The professor of anthropological science at King's College, Cambridge has, like other historians, spent decades trying to understand the enigma of the Industrial Revolution. Why did this particular important event — the world-changing birth of industry — happen in Britain? And why did it happen at the end of the 18th century?`;

const paragraphB = `Macfarlane compares the question to a puzzle. He claims that there were about 20 different factors and all of them needed to be present before the revolution could happen. The chief conditions are to be found in history textbooks. For industry to 'take off', there needed to be the technology and power to drive factories, large urban populations to provide cheap labour, easy transport to move goods around, an affluent middle-class willing to buy mass-produced objects, a market-driven economy, and a political system that allowed this to happen. While this was the case for England, other nations, such as Japan, Holland and France also met some of these criteria. All these factors must have been necessary but not sufficient to cause the revolution. Holland had everything except coal, while China also had many of these factors.`;

const paragraphC = `Most historians, however, are convinced that one or two missing factors are needed to solve the puzzle. The missing factors, he proposes, are to be found in every kitchen cupboard. Tea and beer, two of the nation's favourite drinks, drove the revolution. Tannin, the active ingredient in tea, and hops, used in making beer, both contain antiseptic properties. This — plus the fact that both are made with boiled water — helped prevent epidemics of waterborne diseases, such as dysentery, in densely populated urban areas. The theory initially sounds eccentric but his explanation of the detective work that went into his deduction and the fact his case has been strengthened by a favourable appraisal of his research by Roy Porter (distinguished medical historian), the scepticism gives way to wary admiration.`;

const paragraphD = `Historians had noticed one interesting factor around the mid-18th century that required explanation. Between about 1650 and 1740, the population was static. But then there was a burst in population. The infant mortality rate halved in the space of 20 years, and this happened in both rural areas and cities, and across all classes. Four possible causes have been suggested. There could have been a sudden change in the viruses and bacteria present at that time, but this is unlikely. Was there a revolution in medical science? But this was a century before Lister introduced antiseptic surgery. Was there a change in environmental conditions? There were improvements in agriculture that wiped out malaria, but these were small gains. Sanitation did not become widespread until the 19th century. The only option left was food. But the height and weight statistics show a decline. So the food got worse. Efforts to explain this sudden reduction in child deaths appeared to draw a blank.`;

const paragraphE = `This population burst seemed to happen at just the right time to provide labour for the Industrial Revolution. But why? When the Industrial Revolution started, it was economically efficient to have people crowded together forming towns and cities. But with crowded living conditions comes disease, particularly from human waste. Some research in the historical records revealed that there was a change in the incidence of waterborne disease at that time. The English were protected by the strong antibacterial agent in hops, which were added to make beer last. But in the late 17th century a tax was introduced on malt. The poor turned to water and gin, and in the 1720s the mortality rate began to rise again.`;

const paragraphF = `Macfarlane looked to Japan, which was also developing large cities about the same time, and also had no sanitation. Waterborne diseases in the Japanese population were far fewer than those in Britain. Could it be the prevalence of tea in their culture? That was when Macfarlane thought about the role of tea in Britain. The history of tea in Britain provided an extraordinary coincidence of dates. Tea was relatively expensive until Britain started direct trade with China in the early 18th century. By the 1740s, about the time that infant mortality was falling, the drink was common. Macfarlane guesses that the fact that water had to be boiled, together with the stomach-purifying properties of tea so eloquently described in Buddhist texts, meant that the breast milk provided by mothers was healthier than it had ever been. No other European nation drank tea so often as the British, which, by Macfarlane's logic, pushed the other nations out of the race for the Industrial Revolution.`;

const paragraphG = `But, if tea is a factor in the puzzle, why didn't this cause an industrial revolution in Japan? Macfarlane notes that in the 17th century, Japan had large cities, high literacy rates and even a futures market. However, Japan decided against a work-based revolution, by giving up labour-saving devices, even animals, to avoid putting people out of work. Astonishingly, the nation that we now think of as one of the most technologically advanced entered the 19th century having almost abandoned the wheel. While Britain was undergoing the Industrial Revolution, Macfarlane notes wryly, Japan was undergoing an industrious one.`;

// ===== FIX: Compute offsets programmatically so they never drift out of sync =====
const rawSegments = [
    { id: 'part1-header',      text: 'Reading Passage 1' },
    { id: 'part1-instruction', text: 'You should spend about 20 minutes on Questions 1–13, which are based on Reading Passage 1 below.' },
    { id: 'part1-title',       text: 'Tea and the Industrial Revolution' },
    { id: 'para-a',            text: paragraphA },
    { id: 'para-b',            text: paragraphB },
    { id: 'para-c',            text: paragraphC },
    { id: 'para-d',            text: paragraphD },
    { id: 'para-e',            text: paragraphE },
    { id: 'para-f',            text: paragraphF },
    { id: 'para-g',            text: paragraphG },
    // Q1-7
    { id: 'q1-7-title',        text: 'Questions 1–7' },
    { id: 'q1-7-inst1',        text: 'Reading Passage 1 has seven paragraphs, A–G.' },
    { id: 'q1-7-inst2',        text: 'Choose the correct heading for paragraphs A–G from the list of headings below.' },
    { id: 'q1-7-inst3',        text: 'Write the correct number, i–x, in boxes 1–7 on your answer sheet.' },
    { id: 'headings-title',    text: 'List of Headings' },
    { id: 'para-A-label',      text: 'Paragraph A' },
    { id: 'para-B-label',      text: 'Paragraph B' },
    { id: 'para-C-label',      text: 'Paragraph C' },
    { id: 'para-D-label',      text: 'Paragraph D' },
    { id: 'para-E-label',      text: 'Paragraph E' },
    { id: 'para-F-label',      text: 'Paragraph F' },
    { id: 'para-G-label',      text: 'Paragraph G' },
    // Q8-13
    { id: 'q8-13-title',       text: 'Questions 8–13' },
    { id: 'q8-13-inst1',       text: 'Do the following statements agree with the information given in Reading Passage 1?' },
    { id: 'q8-13-inst2',       text: 'In boxes 8–13 on your answer sheet, write' },
    { id: 'q8-13-true',        text: 'TRUE' },
    { id: 'q8-13-true-desc',   text: 'if the statement agrees with the information' },
    { id: 'q8-13-false',       text: 'FALSE' },
    { id: 'q8-13-false-desc',  text: 'if the statement contradicts the information' },
    { id: 'q8-13-ng',          text: 'NOT GIVEN' },
    { id: 'q8-13-ng-desc',     text: 'if there is no information on this' },
    { id: 'q8-num',            text: '8' },
    { id: 'q8-text',           text: 'The industrialization did not happen in China because of its inefficient railway transportation.' },
    { id: 'q9-num',            text: '9' },
    { id: 'q9-text',           text: 'Tea and beer contributed to protect people from waterborne disease.' },
    { id: 'q10-num',           text: '10' },
    { id: 'q10-text',          text: 'Roy Porter disagreed with the proposed theory about the missing factors.' },
    { id: 'q11-num',           text: '11' },
    { id: 'q11-text',          text: 'The reason of lower child deaths is fully explained by food.' },
    { id: 'q12-num',           text: '12' },
    { id: 'q12-text',          text: 'The British made beer by themselves.' },
    { id: 'q13-num',           text: '13' },
    { id: 'q13-text',          text: 'Tax on malt indirectly affected the increase of population in late 17th century.' },
];

// Auto-compute cumulative offsets — no more manual offset maintenance
const textSegments = computed(() => {
    let offset = 0;
    return rawSegments.map((seg) => {
        const result = { ...seg, offset };
        offset += seg.text.length;
        return result;
    });
});

// Convert plain text offset to HTML offset
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

const getPlainTextLength = (htmlText: string): number => {
    return htmlText.replace(/<[^>]*>/g, '').length;
};

const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ];

    const sorted = annotations.sort((a, b) => b.start - a.start);
    let result = segmentText;

    for (const annotation of sorted) {
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

const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';
    return getHighlightedSegment(segment.text);
};

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
            const segment = textSegments.value.find((s) => s.id === segmentIdAttr);
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

        if (startAbsOffset === null || endAbsOffset === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }

        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70;
            const menuY = rect.top - menuHeight - 8;
            const viewportWidth = window.innerWidth;
            const menuWidth = 140;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
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
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => { document.querySelector<HTMLInputElement>('.note-input-field')?.focus(); }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    const noteId = Date.now();
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Part 1' });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((note) => note.id !== id); };

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
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

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

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) showContextMenu.value = false;
};

const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};
const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, (newWidth) => { localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString()); });

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
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32"
    >
        <!-- Part 1 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 part-header-box  px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part1-header" v-html="getHighlightedSegmentById('part1-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part1-instruction" v-html="getHighlightedSegmentById('part1-instruction')"></p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ===== READING PASSAGE ===== -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-shrink-0 border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 select-text" data-segment-id="part1-title" v-html="getHighlightedSegmentById('part1-title')"></h2>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text sm:text-base">

                            <!-- Paragraph A with drop zone above -->
                            <div class="px-4">
                                <div
                                    id="question-1"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 1"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 1 ? 'border-blue-500 bg-blue-50' : answers.q1 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 1)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 1)"
                                        @click="clearHeadingAnswer(1)"
                                        :title="answers.q1 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q1" class="font-medium">{{ getHeadingLabel(answers.q1) }}</span>
                                        <span v-else class="font-bold text-gray-500">1</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 1)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(1) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 1 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">A. </span><span class="select-text" data-segment-id="para-a" v-html="getHighlightedSegment(paragraphA)"></span>
                                </div>
                            </div>

                            <!-- Paragraph B with drop zone above -->
                            <div class="px-4">
                                <div
                                    id="question-2"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 2"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 2 ? 'border-blue-500 bg-blue-50' : answers.q2 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 2)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 2)"
                                        @click="clearHeadingAnswer(2)"
                                        :title="answers.q2 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q2" class="font-medium">{{ getHeadingLabel(answers.q2) }}</span>
                                        <span v-else class="font-bold text-gray-500">2</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 2)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(2) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 2 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">B. </span><span class="select-text" data-segment-id="para-b" v-html="getHighlightedSegment(paragraphB)"></span>
                                </div>
                            </div>

                            <!-- Paragraph C with drop zone above -->
                            <div class="px-4">
                                <div
                                    id="question-3"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 3"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 3 ? 'border-blue-500 bg-blue-50' : answers.q3 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 3)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 3)"
                                        @click="clearHeadingAnswer(3)"
                                        :title="answers.q3 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q3" class="font-medium">{{ getHeadingLabel(answers.q3) }}</span>
                                        <span v-else class="font-bold text-gray-500">3</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 3)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(3) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 3 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">C. </span><span class="select-text" data-segment-id="para-c" v-html="getHighlightedSegment(paragraphC)"></span>
                                </div>
                            </div>

                            <!-- Paragraph D with drop zone above -->
                            <div class="px-4">
                                <div
                                    id="question-4"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 4"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 4 ? 'border-blue-500 bg-blue-50' : answers.q4 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 4)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 4)"
                                        @click="clearHeadingAnswer(4)"
                                        :title="answers.q4 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q4" class="font-medium">{{ getHeadingLabel(answers.q4) }}</span>
                                        <span v-else class="font-bold text-gray-500">4</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 4)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(4) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 4 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">D. </span><span class="select-text" data-segment-id="para-d" v-html="getHighlightedSegment(paragraphD)"></span>
                                </div>
                            </div>

                            <!-- Paragraph E with drop zone above -->
                            <div class="px-4">
                                <div
                                    id="question-5"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 5"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 5 ? 'border-blue-500 bg-blue-50' : answers.q5 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 5)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 5)"
                                        @click="clearHeadingAnswer(5)"
                                        :title="answers.q5 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q5" class="font-medium">{{ getHeadingLabel(answers.q5) }}</span>
                                        <span v-else class="font-bold text-gray-500">5</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 5)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(5) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 5 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">E. </span><span class="select-text" data-segment-id="para-e" v-html="getHighlightedSegment(paragraphE)"></span>
                                </div>
                            </div>

                            <!-- Paragraph F with drop zone above -->
                            <div class="px-4">
                                <div
                                    id="question-6"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 6"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 6 ? 'border-blue-500 bg-blue-50' : answers.q6 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 6)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 6)"
                                        @click="clearHeadingAnswer(6)"
                                        :title="answers.q6 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q6" class="font-medium">{{ getHeadingLabel(answers.q6) }}</span>
                                        <span v-else class="font-bold text-gray-500">6</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 6)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(6) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 6 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">F. </span><span class="select-text" data-segment-id="para-f" v-html="getHighlightedSegment(paragraphF)"></span>
                                </div>
                            </div>

                            <!-- Paragraph G with drop zone above -->
                            <div class="px-4">
                                <div
                                    id="question-7"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 7"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 7 ? 'border-blue-500 bg-blue-50' : answers.q7 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 7)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 7)"
                                        @click="clearHeadingAnswer(7)"
                                        :title="answers.q7 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q7" class="font-medium">{{ getHeadingLabel(answers.q7) }}</span>
                                        <span v-else class="font-bold text-gray-500">7</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 7)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(7) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 7 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">G. </span><span class="select-text" data-segment-id="para-g" v-html="getHighlightedSegment(paragraphG)"></span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ===== QUESTIONS PANEL ===== -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- ===== Q1–7: Heading List (drag from here) ===== -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q1-7-title" v-html="getHighlightedSegmentById('q1-7-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-0.5">
                                        <span data-segment-id="q1-7-inst1" v-html="getHighlightedSegmentById('q1-7-inst1')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-0.5">
                                        <span data-segment-id="q1-7-inst2" v-html="getHighlightedSegmentById('q1-7-inst2')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-4">
                                        <span data-segment-id="q1-7-inst3" v-html="getHighlightedSegmentById('q1-7-inst3')"></span>
                                    </p>
                                </div>

                                <!-- Heading cards -->
                                <div class="mb-2">
                                    <h4 class="text-sm font-bold text-gray-900 mb-3">
                                        <span data-segment-id="headings-title" v-html="getHighlightedSegmentById('headings-title')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <div
                                            v-for="option in availableHeadingOptions"
                                            :key="option.value"
                                            class="flex items-center gap-3 border border-gray-300 bg-white px-3 py-2.5 cursor-grab hover:bg-gray-50 transition-colors active:cursor-grabbing"
                                            :class="{ 'opacity-40': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd"
                                        >
                                            <span class="text-gray-400 text-base leading-none shrink-0 font-bold tracking-tighter">=</span>
                                            <span class="font-bold text-gray-900 text-sm shrink-0 w-8">{{ option.value }}</span>
                                            <span class="text-sm text-gray-700">{{ option.label }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== Q8–13: TRUE/FALSE/NOT GIVEN ===== -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="q8-13-title" v-html="getHighlightedSegmentById('q8-13-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q8-13-inst1" v-html="getHighlightedSegmentById('q8-13-inst1')"></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700 italic">
                                        <span data-segment-id="q8-13-inst2" v-html="getHighlightedSegmentById('q8-13-inst2')"></span>
                                    </p>
                                    <div class="mt-2 space-y-0.5 text-sm text-gray-700">
                                        <p><span class="font-bold text-gray-900 w-24 inline-block" data-segment-id="q8-13-true" v-html="getHighlightedSegmentById('q8-13-true')"></span>&nbsp;&nbsp;<span data-segment-id="q8-13-true-desc" v-html="getHighlightedSegmentById('q8-13-true-desc')"></span></p>
                                        <p><span class="font-bold text-gray-900 w-24 inline-block" data-segment-id="q8-13-false" v-html="getHighlightedSegmentById('q8-13-false')"></span>&nbsp;&nbsp;<span data-segment-id="q8-13-false-desc" v-html="getHighlightedSegmentById('q8-13-false-desc')"></span></p>
                                        <p><span class="font-bold text-gray-900 w-24 inline-block" data-segment-id="q8-13-ng" v-html="getHighlightedSegmentById('q8-13-ng')"></span>&nbsp;&nbsp;<span data-segment-id="q8-13-ng-desc" v-html="getHighlightedSegmentById('q8-13-ng-desc')"></span></p>
                                    </div>
                                </div>

                                <div class="space-y-4">

                                    <!-- Q8 -->
                                    <div
                                        id="question-8"
                                        class="relative p-3"
                                        @mouseenter="hoveredQuestion = 8"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                            @click.stop="emit('toggleFlag', 8)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(8) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q8-num" v-html="getHighlightedSegmentById('q8-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q8-text" v-html="getHighlightedSegmentById('q8-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q8" type="radio" value="TRUE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q8" type="radio" value="FALSE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q8" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q9 -->
                                    <div
                                        id="question-9"
                                        class="relative p-3"
                                        @mouseenter="hoveredQuestion = 9"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="emit('toggleFlag', 9)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q9-num" v-html="getHighlightedSegmentById('q9-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q9-text" v-html="getHighlightedSegmentById('q9-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q9" type="radio" value="TRUE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q9" type="radio" value="FALSE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q9" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q10 -->
                                    <div
                                        id="question-10"
                                        class="relative p-3"
                                        @mouseenter="hoveredQuestion = 10"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="emit('toggleFlag', 10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q10-num" v-html="getHighlightedSegmentById('q10-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q10-text" v-html="getHighlightedSegmentById('q10-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q10" type="radio" value="TRUE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q10" type="radio" value="FALSE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q10" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q11 -->
                                    <div
                                        id="question-11"
                                        class="relative p-3"
                                        @mouseenter="hoveredQuestion = 11"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="emit('toggleFlag', 11)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q11-num" v-html="getHighlightedSegmentById('q11-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q11-text" v-html="getHighlightedSegmentById('q11-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q11" type="radio" value="TRUE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q11" type="radio" value="FALSE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q11" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q12 -->
                                    <div
                                        id="question-12"
                                        class="relative p-3"
                                        @mouseenter="hoveredQuestion = 12"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="emit('toggleFlag', 12)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q12-num" v-html="getHighlightedSegmentById('q12-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q12-text" v-html="getHighlightedSegmentById('q12-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q12" type="radio" value="TRUE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q12" type="radio" value="FALSE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q12" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q13 -->
                                    <div
                                        id="question-13"
                                        class="relative p-3"
                                        @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="emit('toggleFlag', 13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q13-num" v-html="getHighlightedSegmentById('q13-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q13-text" v-html="getHighlightedSegmentById('q13-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q13" type="radio" value="TRUE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q13" type="radio" value="FALSE" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q13" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

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
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
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
        </Teleport>

        <!-- Delete Highlight Tooltip -->
        <Teleport to="body">
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
                <div
                    class="delete-highlight-tooltip fixed z-9999"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                >
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Note Hover Tooltip -->
        <Teleport to="body">
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave"
                    @click.stop
                >
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>
        </Teleport>

        <!-- Note Input Modal -->
        <Teleport to="body">
            <div
                v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="rounded border border-gray-300 bg-white px-3 py-0.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</button>
                    <button @click="saveNote" class="rounded bg-gray-900 px-3 py-0.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>

.part-header-box {
    background-color: #F1F2EC;
}
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
.answer-panel { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel { width: calc(100% - var(--panel-width) - 0.5rem); }
}

mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; }

.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question { animation: highlightPulse 2s ease-in-out; }

@keyframes highlightPulse {
    0% { background-color: rgba(0, 0, 0, 0.1); transform: scale(1); }
    50% { background-color: rgba(0, 0, 0, 0.2); transform: scale(1.02); }
    100% { background-color: rgba(0, 0, 0, 0.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}

.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-bottom: 8px solid white; filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db; z-index: -1;
}

.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}

.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-icon { color: #6b7280; }
.note-hover-tooltip .note-tooltip-text { line-height: 1.4; }
.note-hover-tooltip .note-delete-btn { color: #9ca3af; }
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
.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>
