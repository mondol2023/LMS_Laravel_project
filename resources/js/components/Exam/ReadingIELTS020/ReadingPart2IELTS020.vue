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

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

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

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part2-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

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

const answers = ref({
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

// ─── Passage text ────────────────────────────────────────────────────────────
const passageText = `<b>A</b> An ingenious invention is set to bring clean water to the third world, and while the science may be cutting edge, the materials are extremely down to earth. A handful of clay, yesterday's coffee grounds and some cow manure are the ingredients that could bring clean, safe drinking water to much of the third world.<br/><br/><b>B</b> The simple new technology, developed by ANU materials scientist Mr. Tony Flynn, allows water filters to be made from commonly available materials and fired on the ground using cow manure as the source of heat, without the need for a kiln. The filters have been tested and shown to remove common pathogens (disease-producing organisms) including E-coli. Unlike other water filtering devices, the filters are simple and inexpensive to make. "They are very simple to explain and demonstrate and can be made by anyone, anywhere," says Mr. Flynn. "They don't require any western technology. All you need is terracotta clay, a compliant cow and a match."<br/><br/><b>C</b> The production of the filters is extremely simple. Take a handful of dry, crushed clay, mix it with a handful of organic material, such as used tea leaves, coffee grounds or rice hulls, add enough water to make a stiff biscuit-like mixture and form a cylindrical pot that has one end closed, then dry it in the sun. According to Mr. Flynn, used coffee grounds have given the best results to date. Next, surround the pots with straw; put them in a mound of cow manure, light the straw and then top up the burning manure as required. In less than 60 minutes the filters are finished. The walls of the finished pot should be about as thick as an adult's index. The properties of cow manure are vital as the fuel can reach a temperature of 700 degrees in half an hour and will be up to 950 degrees after another 20 to 30 minutes. The manure makes a good fuel because it is very high in organic material that burns readily and quickly; the manure has to be dry and is best used exactly as found in the field, there is no need to break it up or process it any further.<br/><br/><b>D</b> "A potter's kiln is an expensive item and can take up to four or five hours to get up to 800 degrees. It needs expensive or scarce fuel, such as gas or wood to heat it and experience to run it. With no technology, no insulation and nothing other than a pile of cow manure and a match, none of these restrictions apply," Mr. Flynn says.<br/><br/><b>E</b> It is also helpful that, like terracotta clay and organic material, cow dung is freely available across the developing world. "A cow is a natural fuel factory. My understanding is that cow dung as a fuel would be pretty much the same wherever you would find it." Just as using manure as a fuel for domestic uses is not a new idea, the porosity of clay is something that potters have known about for years, and something that as a former ceramics lecturer in the ANU School of Art, Mr. Flynn is well aware of. The difference is that rather than viewing the porous nature of the material as a problem — after all not many people want a pot that won't hold water — his filters capitalize on this property.<br/><br/><b>F</b> Other commercial ceramic filters do exist, but, even if available, with prices starting at US$5 each, they are often outside the budgets of most people in the developing world. The filtration process is simple, but effective. The basic principle is that there are passages through the filter that are wide enough for water droplets to pass through, but too narrow for pathogens. Tests with the deadly E-coli bacterium have seen the filters remove 96.4 to 99.8 per cent of the pathogen — well within safe levels. Using only one filter it takes two hours to filter a litre of water. The use of organic material, which burns away after firing, helps produce the structure in which pathogens will become trapped. It overcomes the potential problems of finer clays that may not let water through and also means that cracks are soon halted. And like clay and cow dung, it is universally available.<br/><br/><b>G</b> The invention was born out of a World Vision project involving the Manatuto community in East Timor. The charity wanted to help set up a small industry manufacturing water filters, but initial research found the local clay to be too fine — a problem solved by the addition of organic material. While the problems of producing a working ceramic filter in East Timor were overcome, the solution was kiln-based and particular to that community's materials and couldn't be applied elsewhere. Manure firing, with no requirement for a kiln, has made this zero-technology approach available anywhere it is needed. With all the components being widely available, Mr. Flynn says there is no reason the technology couldn't be applied throughout the developing world, and with no plans to patent his idea, there will be no legal obstacles to it being adopted in any community that needs it. "Everyone has a right to clean water, these filters have the potential to enable anyone in the world to drink water safely," says Mr. Flynn`;

// ─── Text segments ────────────────────────────────────────────────────────────
// Use negative offsets for headers, passage at 0, questions start at 10000 (well after passage)
const textSegments = ref([
    { id: 'part-header',       text: 'Part 2',                                         offset: -100 },
    { id: 'part-instructions', text: 'Read the text and answer questions 14–26.',       offset: -93  },
    { id: 'header-title',      text: 'Water Filter',                                   offset: -51  },
    { id: 'passage',           text: passageText,                                      offset: 0    },

    // Q14-19 flow-chart (offsets start at 10000 to ensure no overlap with passage)
    { id: 'q14-19-title',      text: 'Questions 14-19',                                offset: 10000 },
    { id: 'q14-19-inst',       text: 'Complete the flow chart, using NO MORE THAN TWO WORDS from the Reading Passage for each answer. Write your answers in boxes 14-19 on your answer sheet.', offset: 10016 },
    { id: 'q14-19-sub',        text: 'Guide to Making Water Filters',                  offset: 10200 },

    // Step 1 texts
    { id: 'step1-label',       text: 'Step one:',                                      offset: 10230 },
    { id: 'q14-text1',         text: 'combination of',                                 offset: 10240 },
    { id: 'q14-text2',         text: 'and organic material, with sufficient',          offset: 10275 },
    { id: 'q15-text',          text: 'to create a thick mixture',                      offset: 10340 },
    { id: 'step1-sun',         text: 'sun dried',                                      offset: 10368 },

    // Step 2 texts
    { id: 'step2-label',       text: 'Step two:',                                      offset: 10380 },
    { id: 'q16-text1',         text: 'pack',                                           offset: 10390 },
    { id: 'q16-text2',         text: 'around the cylinders. Place them in',            offset: 10420 },
    { id: 'q17-text',          text: 'which is used as burning fuel for firing (maximum temperature:',  offset: 10480 },
    { id: 'q18-text',          text: ').',                                              offset: 10560 },
    { id: 'q19-text',          text: 'Filter being baked in under',                    offset: 10570 },

    // Q20-23 T/F/NG
    { id: 'q20-23-title',      text: 'Questions 20-23',                                offset: 10700 },
    { id: 'q20-23-inst',       text: 'Do the following statements agree with the information given in Reading Passage 2? In boxes 20-23 on your answer sheet, write:', offset: 10716 },
    { id: 'q20-23-true',       text: 'TRUE',                                           offset: 10850 },
    { id: 'q20-23-true-desc',  text: 'if the statement is true',                       offset: 10855 },
    { id: 'q20-23-false',      text: 'FALSE',                                          offset: 10882 },
    { id: 'q20-23-false-desc', text: 'if the statement is false',                      offset: 10888 },
    { id: 'q20-23-ng',         text: 'NOT GIVEN',                                      offset: 10916 },
    { id: 'q20-23-ng-desc',    text: 'if the information is not given in the passage', offset: 10927 },

    { id: 'q20-text',          text: '20  It takes half an hour for the manure to reach 950 degrees.',                     offset: 10980 },
    { id: 'q21-text',          text: '21  Clay was initially found to be unsuitable for filter making.',                    offset: 11050 },
    { id: 'q22-text',          text: '22  Coffee grounds are twice as effective as other materials.',                       offset: 11115 },
    { id: 'q23-text',          text: '23  E-coli is the most difficult bacteria to combat.',                                offset: 11178 },

    // Q24-26 MCQ
    { id: 'q24-26-title',      text: 'Questions 24-26',                                offset: 11250 },
    { id: 'q24-26-inst',       text: 'Choose the correct letter, A, B, C or D. Write your answers in boxes 24-26 on your answer sheet.', offset: 11266 },

    { id: 'q24-stem',          text: '24  When making the pot, the thickness of the wall',  offset: 11380 },
    { id: 'q24-a',             text: 'is large enough to let the pathogens to pass.',      offset: 11434 },
    { id: 'q24-b',             text: 'varied according to the temperature of the fuel.',   offset: 11481 },
    { id: 'q24-c',             text: "should be the same as an adult's forefinger.",       offset: 11533 },
    { id: 'q24-d',             text: 'is not mentioned by Mr. Flynn.',                     offset: 11578 },

    { id: 'q25-stem',          text: '25  What is true about the charity, it',               offset: 11615 },
    { id: 'q25-a',             text: 'failed in searching the appropriate materials.',     offset: 11656 },
    { id: 'q25-b',             text: 'thought a kiln is essential.',                       offset: 11703 },
    { id: 'q25-c',             text: 'found that the local clay are good enough.',         offset: 11737 },
    { id: 'q25-d',             text: 'intended to build a filter production factory.',     offset: 11783 },

    { id: 'q26-stem',          text: "26  Mr. Flynn's design is purposed not being patented", offset: 11830 },
    { id: 'q26-a',             text: 'because he hopes it can be freely used around the world', offset: 11878 },
    { id: 'q26-b',             text: "because he doesn't think the technology is perfect enough", offset: 11936 },
    { id: 'q26-c',             text: 'because there are some legal obstacles',             offset: 11993 },
    { id: 'q26-d',             text: 'because the design has already been applied thoroughly', offset: 12030 },
]);

// ─── Highlight helpers ────────────────────────────────────────────────────────
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
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset,
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset,
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset, end: h.end_offset,
            type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start, end: n.end,
            type: 'note' as const, color: 'blue', id: n.id, noteText: n.note,
        })),
    ].sort((a, b) => b.start - a.start);

    let result = segmentText;
    for (const annotation of annotations) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd   = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);
        if (plainStart < plainEnd) {
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd   = plainToHtmlOffset(result, plainEnd);
            const before    = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after     = result.substring(htmlEnd);
            result = annotation.type === 'note'
                ? `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`
                : `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
        }
    }
    return result;
};

// ─── Expose ───────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;

watch(leftPanelWidth, (v) => localStorage.setItem(PANEL_WIDTH_KEY, v.toString()));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ─── Text-select / highlight machinery ───────────────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected  = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect  = range?.getBoundingClientRect();
        if (rect && contentTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 58;
            const vw    = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), vw - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection && range) {
                let targetSpan: Node | null = range.startContainer;
                while (targetSpan && targetSpan.nodeType !== Node.ELEMENT_NODE)
                    targetSpan = targetSpan.parentNode;
                while (
                    targetSpan &&
                    !(targetSpan as Element).classList?.contains('text-gray-700') &&
                    !(targetSpan as Element).classList?.contains('text-gray-800') &&
                    !(targetSpan as Element).classList?.contains('text-gray-900') &&
                    !(targetSpan as Element).classList?.contains('select-text') &&
                    !(targetSpan as Element).classList?.contains('text-lg')
                ) {
                    const parent = targetSpan.parentNode;
                    if (!parent) break;
                    targetSpan = parent;
                }

                if (targetSpan) {
                    const spanText    = (targetSpan as Element).textContent || '';
                    const isPassage   = (targetSpan as Element).classList?.contains('text-lg') || spanText.length > 500;
                    let   segment     = isPassage
                        ? textSegments.value[3]
                        : textSegments.value.find((s) => s.text === spanText.trim()) ??
                          textSegments.value.find((s) => {
                              const ns = spanText.trim().replace(/\s+/g, ' ');
                              const nsg = s.text.trim().replace(/\s+/g, ' ');
                              return ns === nsg || ns.includes(nsg) || nsg.includes(ns);
                          });

                    if (segment) {
                        const pre = document.createRange();
                        pre.selectNodeContents(targetSpan as Element);
                        pre.setEnd(range.startContainer, range.startOffset);
                        const plainOff  = pre.toString().length;
                        const startOff  = segment.offset + plainOff;
                        const endOff    = startOff + selected.trim().length;
                        selectedText.value   = selected.trim();
                        selectionRange.value = { start: startOff, end: endOff };
                    }
                }
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter(
        (n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start),
    );
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    const mw = 320, mh = 180, pad = 10;
    const sel = window.getSelection();
    let x: number, y: number;
    if (sel && sel.rangeCount > 0) {
        const r = sel.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    } else { x = contextMenuPosition.value.x; y = contextMenuPosition.value.y + 70; }
    const vw = window.innerWidth, vh = window.innerHeight;
    const hw = mw / 2;
    if (x - hw < pad) x = hw + pad;
    else if (x + hw > vw - pad) x = vw - hw - pad;
    if (y + mh > vh - pad) {
        if (sel && sel.rangeCount > 0) y = sel.getRangeAt(0).getBoundingClientRect().top - mh - 10;
        if (y < pad) y = pad;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value   = true;
    showContextMenu.value = false;
    setTimeout(() => (document.querySelector<HTMLInputElement>('.note-input-field'))?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: ns, end: ne } = selectionRange.value;
    findOverlappingHighlights(ns, ne).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < ne && n.end > ns));
    notes.value.push({
        id: Date.now(), text: selectedText.value, selectedText: selectedText.value,
        note: noteInputText.value.trim(), start: ns, end: ne,
    });
    noteInputText.value = ''; showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const handleContentClick = (e: MouseEvent) => {
    const mark = (e.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement | null;
    if (mark) {
        const hid = mark.getAttribute('data-highlight-id');
        if (hid) {
            e.stopPropagation();
            const r = mark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: r.left + r.width / 2, y: r.bottom + 8 };
            highlightToDelete.value   = parseInt(hid, 10);
            showDeleteTooltip.value   = true;
            showContextMenu.value     = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value)   showContextMenu.value = false;
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const id = highlightToDelete.value;
        showDeleteTooltip.value = false; highlightToDelete.value = null;
        deleteHighlight(id);
    }
};

const handleNoteMouseEnter = (e: MouseEvent) => {
    const mark = (e.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement | null;
    if (!mark) return;
    const nid  = mark.getAttribute('data-note-id');
    if (!nid)  return;
    const n    = notes.value.find((n) => n.id === parseInt(nid, 10));
    if (!n)    return;
    const r    = mark.getBoundingClientRect();
    const th   = 50;
    let y      = r.top - th - 8;
    if (y < 10) y = r.bottom + 8;
    noteTooltipPosition.value = { x: r.left + r.width / 2, y };
    hoveredNoteId.value  = n.id;
    hoveredNoteText.value = n.note;
    showNoteTooltip.value = true;
};

const handleNoteMouseLeave = (e: MouseEvent) => {
    if ((e.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((e.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
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

// ─── Resize ───────────────────────────────────────────────────────────────────
const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const r = containerRef.value.getBoundingClientRect();
    const w = ((e.clientX - r.left) / r.width) * 100;
    if (w >= 20 && w <= 80) leftPanelWidth.value = w;
};
const stopResize = () => { isResizing.value = false; };

onMounted(() => {
    document.addEventListener('keydown',   handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout',  handleNoteMouseLeave);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup',   stopResize);
});
onBeforeUnmount(() => {
    document.removeEventListener('keydown',   handleKeyDown);
    document.removeEventListener('mouseover', handleNoteMouseEnter);
    document.removeEventListener('mouseout',  handleNoteMouseLeave);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup',   stopResize);
});

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="flex min-h-screen flex-col overflow-y-auto pb-20 sm:pb-24 md:pb-32 lg:h-screen lg:overflow-hidden lg:pb-0">

        <!-- Part 2 Header -->
        <div class="shrink-0 part-header-box mx-5 mt-4 border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegment('Part 2')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegment('Read the text and answer questions 14–26.')"></p>
        </div>

        <div class="mx-auto w-full flex-1 px-3 py-1 sm:px-4 lg:overflow-hidden lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:h-full lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage ── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-32 lg:mb-0 lg:h-full"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                    @mouseup="handleContentTextSelect"
                    @click="handleContentClick">
                    <div class="p-6">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" v-html="getHighlightedSegment('Water Filter')"></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="text-lg text-gray-700 select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[3].text)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Resize Handle ── -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Section ── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                    @mouseup="handleContentTextSelect"
                    @click="handleContentClick">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════════════════════════════════════════
                                 Questions 14-19  — Flow-chart completion
                                 ════════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" v-html="getHighlightedSegment('Questions 14-19')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text"
                                            v-html="getHighlightedSegment('Complete the flow chart, using NO MORE THAN TWO WORDS from the Reading Passage for each answer. Write your answers in boxes 14-19 on your answer sheet.')"></span>
                                    </p>
                                    <p class="mb-4 text-sm font-semibold text-gray-900">
                                        <span class="text-gray-900 select-text" v-html="getHighlightedSegment('Guide to Making Water Filters')"></span>
                                    </p>
                                </div>

                                <!-- Flow-chart box -->
                                <div class=" text-gray-700">

                                    <!-- Step 1 -->
<div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
    <p class="font-bold text-gray-900 text-xs uppercase tracking-wide">
        <span class="select-text" v-html="getHighlightedSegment('Step one:')"></span>
    </p>

    <!-- Q14 row with flag at end -->
    <div id="question-14" class="relative flex flex-wrap items-center gap-1.5 py-1"
        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
        <span class="select-text" v-html="getHighlightedSegment('combination of')"></span>
        <input v-model="answers.q14" type="text" spellcheck="false" autocomplete="off"
            class="question-input border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white"
            style="width:130px" placeholder="14" />
        <span class="select-text" v-html="getHighlightedSegment('and organic material, with sufficient')"></span>
        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
            @click.stop="toggleFlag(14)"
            class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white ml-auto"
            :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q15 row with flag at end -->
    <div id="question-15" class="relative flex flex-wrap items-center gap-1.5 py-1"
        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
        <input v-model="answers.q15" type="text" spellcheck="false" autocomplete="off"
            class="question-input border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white ml-8"
            style="width:110px" placeholder="15" />
        <span class="select-text" v-html="getHighlightedSegment('to create a thick mixture')"></span>
        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
            @click.stop="toggleFlag(15)"
            class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white ml-auto"
            :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <p class="text-xs font-semibold text-gray-500 italic">
        <span class="select-text" v-html="getHighlightedSegment('sun dried')"></span>
    </p>
</div>

<!-- Arrow -->
<div class="flex justify-center text-gray-400 text-xl">↓</div>

<!-- Step 2 -->
<div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
    <p class="font-bold text-gray-900 text-xs uppercase tracking-wide">
        <span class="select-text" v-html="getHighlightedSegment('Step two:')"></span>
    </p>

    <!-- Q16 row with flag at end -->
    <div id="question-16" class="relative flex flex-wrap items-center gap-1.5 py-1"
        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
        <span class="select-text" v-html="getHighlightedSegment('pack')"></span>
        <input v-model="answers.q16" type="text" spellcheck="false" autocomplete="off"
            class="question-input border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white"
            style="width:130px" placeholder="16" />
        <span class="select-text" v-html="getHighlightedSegment('around the cylinders. Place them in')"></span>
        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
            @click.stop="toggleFlag(16)"
            class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white ml-auto"
            :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q17 row with flag at end -->
    <div id="question-17" class="relative flex flex-wrap items-center gap-1.5 py-1"
        @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
        <input v-model="answers.q17" type="text" spellcheck="false" autocomplete="off"
            class="question-input border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white ml-8"
            style="width:130px" placeholder="17" />
        <span class="select-text" v-html="getHighlightedSegment('which is used as burning fuel for firing (maximum temperature:')"></span>
        <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
            @click.stop="toggleFlag(17)"
            class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white ml-auto"
            :class="isQuestionFlagged(17) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q18 row with flag at end -->
    <div id="question-18" class="relative flex flex-wrap items-center gap-1.5 py-1"
        @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
        <input v-model="answers.q18" type="text" spellcheck="false" autocomplete="off"
            class="question-input border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white ml-8"
            style="width:100px" placeholder="18" />
        <span class="select-text" v-html="getHighlightedSegment(').')"></span>
        <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
            @click.stop="toggleFlag(18)"
            class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white ml-auto"
            :class="isQuestionFlagged(18) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q19 row with flag at end -->
    <div id="question-19" class="relative flex flex-wrap items-center gap-1.5 py-1"
        @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
        <span class="select-text" v-html="getHighlightedSegment('Filter being baked in under')"></span>
        <input v-model="answers.q19" type="text" spellcheck="false" autocomplete="off"
            class="question-input border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white"
            style="width:110px" placeholder="19" />
        <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
            @click.stop="toggleFlag(19)"
            class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white ml-auto"
            :class="isQuestionFlagged(19) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>
</div>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════════
                                 Questions 20-23  — TRUE / FALSE / NOT GIVEN
                                 ════════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" v-html="getHighlightedSegment('Questions 20-23')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" v-html="getHighlightedSegment('Do the following statements agree with the information given in Reading Passage 2? In boxes 20-23 on your answer sheet, write:')"></span>
                                        <span class="font-bold mx-0.5 text-gray-900 select-text" v-html="getHighlightedSegment('TRUE')"></span>
                                        <span class="text-gray-700 select-text" v-html="getHighlightedSegment('if the statement is true')"></span>
                                        <span class="font-bold mx-0.5 text-gray-900 select-text" v-html="getHighlightedSegment('FALSE')"></span>
                                        <span class="text-gray-700 select-text" v-html="getHighlightedSegment('if the statement is false')"></span>
                                        <span class="font-bold mx-0.5 text-gray-900 select-text" v-html="getHighlightedSegment('NOT GIVEN')"></span>
                                        <span class="text-gray-700 select-text" v-html="getHighlightedSegment('if the information is not given in the passage')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Q20 -->
                                    <div id="question-20" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('20  It takes half an hour for the manure to reach 950 degrees.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q20" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q20" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q20" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                            @click.stop="toggleFlag(20)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q21 -->
                                    <div id="question-21" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('21  Clay was initially found to be unsuitable for filter making.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q21" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q21" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q21" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                            @click.stop="toggleFlag(21)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q22 -->
                                    <div id="question-22" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('22  Coffee grounds are twice as effective as other materials.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q22" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q22" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q22" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q23 -->
                                    <div id="question-23" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('23  E-coli is the most difficult bacteria to combat.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q23" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q23" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q23" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                            @click.stop="toggleFlag(23)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════════
                                 Questions 24-26  — Multiple Choice A/B/C/D
                                 ════════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" v-html="getHighlightedSegment('Questions 24-26')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text"
                                            v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D. Write your answers in boxes 24-26 on your answer sheet.')"></span>
                                    </p>
                                </div>

                                <div class="space-y-8">
                                    <!-- Q24 -->
                                    <div id="question-24" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-3 text-base font-bold text-gray-900">
                                            <span class="select-text" v-html="getHighlightedSegment('24  When making the pot, the thickness of the wall')"></span>
                                        </p>
                                        <div class="space-y-2 ml-4 select-none">
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q24" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('is large enough to let the pathogens to pass.')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q24" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('varied according to the temperature of the fuel.')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q24" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment(&quot;should be the same as an adult's forefinger.&quot;)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q24" value="D" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('is not mentioned by Mr. Flynn.')"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                            @click.stop="toggleFlag(24)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q25 -->
                                    <div id="question-25" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-3 text-base font-bold text-gray-900">
                                            <span class="select-text" v-html="getHighlightedSegment('25  What is true about the charity, it')"></span>
                                        </p>
                                        <div class="space-y-2 ml-4 select-none">
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q25" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('failed in searching the appropriate materials.')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q25" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('thought a kiln is essential.')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q25" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('found that the local clay are good enough.')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q25" value="D" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('intended to build a filter production factory.')"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                            @click.stop="toggleFlag(25)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q26 -->
                                    <div id="question-26" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-3 text-base font-bold text-gray-900">
                                            <span class="select-text" v-html="getHighlightedSegment(&quot;26  Mr. Flynn's design is purposed not being patented&quot;)"></span>
                                        </p>
                                        <div class="space-y-2 ml-4 select-none">
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q26" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('because he hopes it can be freely used around the world')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q26" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment(&quot;because he doesn't think the technology is perfect enough&quot;)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q26" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('because there are some legal obstacles')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2">
                                                <input type="radio" v-model="answers.q26" value="D" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class=" text-gray-900 select-text" v-html="getHighlightedSegment('because the design has already been applied thoroughly')"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                            @click.stop="toggleFlag(26)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /space-y-8 -->
                    </div>
                </div><!-- /answer-panel -->
            </div>
        </div>

        <!-- ── Teleported overlays ── -->
        <Teleport to="body">
            <!-- Context Menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div class="highlight-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left:`${contextMenuPosition.x}px`, top:`${contextMenuPosition.y}px`, transform:'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"/>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
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
                    :style="{ left:`${deleteTooltipPosition.x}px`, top:`${deleteTooltipPosition.y}px`, transform:'translateX(-50%)' }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                            <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                            <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
                <div class="note-hover-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left:`${noteTooltipPosition.x}px`, top:`${noteTooltipPosition.y}px`, transform:'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
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

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left:`${noteInputPosition.x}px`, top:`${noteInputPosition.y}px`, transform:'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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
mark[data-highlight-id] {
    padding: 2px 0; border-radius: 2px; cursor: pointer; color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}
.bg-clip-text mark[data-highlight-id] {
    -webkit-text-fill-color: initial !important; color: transparent;
    background-clip: padding-box !important;
    -webkit-background-clip: padding-box !important;
}
.highlight-nocolor { background-color: transparent; }
.highlight-yellow  { background-color: rgba(254,240,138,0.5); }
.highlight-green   { background-color: rgba(187,247,208,0.5); }
.highlight-blue    { background-color: rgba(191,219,254,0.5); }
.highlight-pink    { background-color: rgba(251,207,232,0.5); }
.highlight-orange  { background-color: rgba(254,215,170,0.5); }
.highlight-question {
    background-color: rgba(0,0,0,0.1); border-radius: 4px;
    padding: 2px 4px; margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}
@keyframes highlightPulse {
    0%   { background-color:rgba(0,0,0,0.1); transform:scale(1); }
    50%  { background-color:rgba(0,0,0,0.2); transform:scale(1.05); }
    100% { background-color:rgba(0,0,0,0.1); transform:scale(1); }
}
.overflow-y-auto::-webkit-scrollbar       { width:6px; }
.overflow-y-auto::-webkit-scrollbar-track { background:#f1f5f9; border-radius:3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background:#374151; border-radius:3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background:#111827; }
.highlight-tooltip .tooltip-arrow {
    position:absolute; left:50%; bottom:-8px; transform:translateX(-50%);
    width:0; height:0;
    border-left:8px solid transparent; border-right:8px solid transparent;
    border-top:8px solid white;
    filter:drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content:''; position:absolute; left:-9px; bottom:1px;
    width:0; height:0;
    border-left:9px solid transparent; border-right:9px solid transparent;
    border-top:9px solid #d1d5db; z-index:-1;
}
.delete-highlight-tooltip .tooltip-arrow-up {
    position:relative; left:50%; transform:translateX(-50%);
    width:0; height:0;
    border-left:8px solid transparent; border-right:8px solid transparent;
    border-bottom:8px solid white;
    filter:drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content:''; position:absolute; left:-9px; top:1px;
    width:0; height:0;
    border-left:9px solid transparent; border-right:9px solid transparent;
    border-bottom:9px solid #d1d5db; z-index:-1;
}
.note-hover-tooltip .tooltip-arrow-down {
    position:relative; left:50%; transform:translateX(-50%);
    width:0; height:0;
    border-left:8px solid transparent; border-right:8px solid transparent;
    border-top:8px solid white;
    filter:drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content:''; position:absolute; left:-9px; bottom:1px;
    width:0; height:0;
    border-left:9px solid transparent; border-right:9px solid transparent;
    border-top:9px solid #d1d5db; z-index:-1;
}
.note-hover-tooltip .note-tooltip-content { max-width:240px; }
.note-hover-tooltip .note-tooltip-icon    { color:#6b7280; }
.note-hover-tooltip .note-tooltip-text    { line-height:1.4; }
.note-hover-tooltip .note-delete-btn      { color:#9ca3af; }
.note-hover-tooltip .note-delete-btn:hover { color:#ef4444; }
</style>

<style>
.note-highlight {
    background-color: rgba(191,219,254,0.6) !important;
    cursor: pointer; padding: 2px 0; border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147,197,253,0.7) !important; }

/* Prevent passage panel text from being selected during questions highlight */
.passage-panel * {
    -webkit-user-select: none;
    user-select: none;
}
/* Re-enable for explicitly selectable elements in passage panel and their children */
.passage-panel .select-text,
.passage-panel .select-text * {
    -webkit-user-select: text;
    user-select: text;
}

/* Prevent answer panel text from being selected during passage highlight */
.answer-panel * {
    -webkit-user-select: none;
    user-select: none;
}
/* Re-enable for explicitly selectable elements in answer panel and their children */
.answer-panel .select-text,
.answer-panel .select-text *,
.answer-panel input {
    -webkit-user-select: text;
    user-select: text;
}

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>
