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

const isQuestionFlagged = (questionNumber: number): boolean =>
    props.flaggedQuestions.has(questionNumber);

const toggleFlag = (questionNumber: number) => emit('toggleFlag', questionNumber);

const contentZoom = computed(() => ({ zoom: props.fontSize / 16 }));

// ─── Resize ────────────────────────────────────────────────────────────────
const PANEL_WIDTH_KEY = 'reading-tigers-part2-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// ─── Highlight ─────────────────────────────────────────────────────────────
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } =
    useHighlight();

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
const notes = ref<
    Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>
>([]);

// ─── Answers ───────────────────────────────────────────────────────────────
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

// ─── Passage texts ─────────────────────────────────────────────────────────
const passageA = `As you leave the Bandhavgarh National Park in central India, there is a notice which shows a huge, placid tiger. The notice says, 'You may not have seen me, but I have seen you.' There are more than a billion people In India and Indian tigers probably see humans every single day of their lives. Tigers can and do kill almost everything they meet in the jungle, they will kill even attack elephants and rhino. Surely, then, it is a little strange that attacks o humans are not more frequent.`;
const passageB = `Some people might argue that these attacks were, in fact, common in the past. British writers of adventure stories, such as Jim Corbett, gave the impression that village life in India in the early years of the twentieth century involved a stage of constant siege by man-eating tigers. But they may have overstated the terror spread by tigers. There were also far more tigers around in those days (probably 60.000 in the subcontinent compared to just 3000 today). So in proportion, attacks appear to have been as rare then as they are today.`;
const passageC = `It is widely assumed that the constraint is fear; but what exactly are tigers afraid of? Can they really know that we may be even better armed than they are? Surely not. Have the species programmed the experiences of all tigers with humans its genes to be inherited as instinct? Perhaps. But I think the explanation may be more simple and, in a way, more intriguing.`;
const passageD = `Since the growth of ethology in the 1950s. we have tried to understand animal behaviour from the animal's point of view. Until the first elegant experiments by pioneers in the field such as Konrad Lorenz, naturalists wrote about animals as if they were slightly less intelligent humans. Jim Corbett's breathless accounts of his duels with man-eaters in truth tell us more about Jim Corbett than they do about the animals. The principle of ethology, on the other hand, requires us to attempt to think in the same way as the animal we are studying thinks, and to observe every tiny detail of its behaviour without imposing our own human significances on its actions.`;
const passageE = `I suspect that a tiger's afraid of humans lies not in some pre-programmed ancestral logic but in the way he actually perceives us visually. If you think like a tiger, a human in a car might appear just to be a part of the car, and because tigers don't eat cars the human is safe-unless the car is menacing the tiger or its cubs, in which case a brave or enraged tiger may charge. A human on foot is a different sort of puzzle. Imagine a tiger sees a man who is 1.8m tall. A tigris less than 1m tall but they may be up to 3m long from head to tail. So when a tiger sees the man face on, it might not be unreasonable for him to assume that the man is 6m long. If he meets a deer of this size, he might attack the animal by leaping on its back, but when he looks behind the mind he can't see a back. From the front, the man is huge, but looked at from the side he all but disappears. This must be very disconcerting. A hunter has to be confident that it can tackle its prey, and no one is confident when they are disconcerted. This is especially true of a solitary hunter such as the tiger and may explain why lions-particularly young lionesses who tend to encourage one another to take risks are more dangerous than tigers.`;
const passageF = `If the theory that a tiger is disconcerted to find that a standing human is both very big and yet somehow invisible is correct, the opposite should be true of a squatting human. A squatting human is half the size and presents twice the spread of back, and more closely resembles a medium-sized deer. If tigers were simply frightened of all humans, then a squatting person would be no more attractive as a target than a standing one. This, however, appears not to be the case. Many incidents of attacks on people involving villagers squatting or bending over to cut grass for fodder or building material.`;
const passageG = `The fact that humans stand upright may therefore not just be something that distinguishes them from nearly all other species, but also a factor that helped them to survive in a dangerous and unpredictable environment.`;

// ─── Text segments with corrected offsets ──────────────────────────────────
const textSegments = ref([
    { id: 'part2-title',           text: 'Part 2',                                                                          offset: 0 },
    { id: 'part2-instruction',     text: 'Read the text and answer questions 14-26.',                                      offset: 6 },

    // Passage paragraphs
    { id: 'passage-title',         text: 'Why tiger attacks on humans are rare',                                            offset: 47 },

    { id: 'para-a',                text: passageA,                                                                          offset: 83 },
    { id: 'para-b',                text: passageB,                                                                          offset: 574 },
    { id: 'para-c',                text: passageC,                                                                          offset: 1114 },
    { id: 'para-d',                text: passageD,                                                                          offset: 1480 },
    { id: 'para-e',                text: passageE,                                                                          offset: 2144 },
    { id: 'para-f',                text: passageF,                                                                          offset: 3365 },
    { id: 'para-g',                text: passageG,                                                                          offset: 3968 },

    // Question segments
    { id: 'q14-18-title',          text: 'Questions 14-18',                                                                  offset: 4185 },
    { id: 'q14-18-instruction-1',  text: 'Reading Passage 2 has seven paragraphs labelled A-G.',                             offset: 4200 },
    { id: 'q14-18-instruction-2',  text: 'Which paragraph contains the following information?',                              offset: 4252 },
    { id: 'q14-18-instruction-3',  text: 'Write the correct letter A-G in boxes 14-18 on your answer sheet.',               offset: 4303 },
    { id: 'q14-text',              text: '14. a rejected explanation of why tiger attacks on humans are rare',               offset: 4368 },
    { id: 'q15-text',              text: '15. a reason why tiger attacks on humans might be expected to happen more often than they do', offset: 4434 },
    { id: 'q16-text',              text: '16. examples of situations in which humans are more likely to be attacked by tigers', offset: 4526 },
    { id: 'q17-text',              text: '17. a claim about the relative frequency of tiger attacks on humans',              offset: 4609 },
    { id: 'q18-text',              text: '18. an explanation of tiger behaviour based on the principles of ethology',        offset: 4676 },

    { id: 'q19-23-title',          text: 'Questions 19-23',                                                                  offset: 4749 },
    { id: 'q19-23-instruction-1',  text: 'Do the following statements agree with the information given in Reading Passage 2?', offset: 4764 },
    { id: 'q19-23-instruction-2',  text: 'In boxes 19-23 on your answer sheet write',                                       offset: 4846 },
    { id: 'tfng-true',             text: 'TRUE if the statement agrees with the information',                                offset: 4887 },
    { id: 'tfng-false',            text: 'FALSE if the statement contradicts the information',                               offset: 4936 },
    { id: 'tfng-notgiven',         text: 'NOT GIVEN if there is no information on this',                                    offset: 4986 },

    { id: 'q19-text',              text: 'Tigers in the Bandhavgarh National Park are a protected species.',                 offset: 5030 },
    { id: 'q20-text',              text: 'Some writers of fiction have exaggerated the danger of tigers to man.',            offset: 5094 },
    { id: 'q21-text',              text: "The fear of humans may be passed down in a tiger's genes.",                       offset: 5163 },
    { id: 'q22-text',              text: 'Konrad Lorenz claimed that some animals are more intelligent than humans.',        offset: 5220 },
    { id: 'q23-text',              text: 'Ethology involves applying principles of human behaviour to animals.',             offset: 5293 },

    { id: 'q24-26-title',          text: 'Questions 24-26',                                                                  offset: 5361 },
    { id: 'q24-26-instruction-1',  text: 'Choose the correct answer, A, B, C or D.',                                        offset: 5376 },
    { id: 'q24-26-instruction-2',  text: 'Write your answers in boxes 24-26 on your answer sheet.',                         offset: 5416 },

    { id: 'q24-num',               text: '24.',                                                                              offset: 5471 },
    { id: 'q24-q',                 text: 'Why do tigers rarely attack people in cars?',                                      offset: 5474 },
    { id: 'q24-a',                 text: 'A. They have learned that cars are not dangerous.',                                offset: 5517 },
    { id: 'q24-b',                 text: 'B. They realise that people in cars cannot be harmed.',                            offset: 5566 },
    { id: 'q24-c',                 text: 'C. They do not think people in cars are living creatures.',                        offset: 5619 },
    { id: 'q24-d',                 text: 'D. They do not want to put their cubs at risk.',                                   offset: 5676 },

    { id: 'q25-num',               text: '25.',                                                                              offset: 5722 },
    { id: 'q25-q',                 text: 'The writer says that tigers rarely attack a man who is standing up because',       offset: 5725 },
    { id: 'q25-a',                 text: "A. they are afraid of the man's height.",                                          offset: 5799 },
    { id: 'q25-b',                 text: "B. they are confused by the man's shape.",                                         offset: 5838 },
    { id: 'q25-c',                 text: "C. they are puzzled by the man's lack of movement.",                               offset: 5878 },
    { id: 'q25-d',                 text: "D. they are unable to look at the man directly.",                                  offset: 5928 },

    { id: 'q26-num',               text: '26.',                                                                              offset: 5975 },
    { id: 'q26-q',                 text: 'A human is more vulnerable to tiger attack when squatting because',                offset: 5978 },
    { id: 'q26-a',                 text: "A. he may be unaware of the tiger's approach.",                                    offset: 6043 },
    { id: 'q26-b',                 text: 'B. he cannot easily move his head to see behind him.',                             offset: 6088 },
    { id: 'q26-c',                 text: "C. his head becomes a better target for the tiger.",                               offset: 6140 },
    { id: 'q26-d',                 text: 'D. his back appears longer in relation to his height.',                            offset: 6190 },
]);
// ─── Highlight helpers ─────────────────────────────────────────────────────
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0; let htmlIndex = 0;
    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') htmlIndex++;
            htmlIndex++;
        } else { plainIndex++; htmlIndex++; }
    }
    return htmlIndex;
};

const getPlainTextLength = (htmlText: string) => htmlText.replace(/<[^>]*>/g, '').length;

const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = textSegments.value.find(s => s.id === segmentId);
    if (!segment) return '';
    const { text: segText, offset: segOffset } = segment;
    const segPlainLen = getPlainTextLength(segText);
    const segEnd = segOffset + segPlainLen;

    const hlAnnotations = highlights.value
        .filter(h => h.start_offset < segEnd && h.end_offset > segOffset)
        .map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id }));
    const noteAnnotations = notes.value
        .filter(n => n.start < segEnd && n.end > segOffset)
        .map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note }));

    const annotations = [...hlAnnotations, ...noteAnnotations];
    if (!annotations.length) return segText;

    annotations.sort((a, b) => b.start - a.start);
    let result = segText;
    for (const ann of annotations) {
        const ps = Math.max(0, ann.start - segOffset);
        const pe = Math.min(segPlainLen, ann.end - segOffset);
        if (ps >= pe) continue;
        const hs = plainToHtmlOffset(result, ps);
        const he = plainToHtmlOffset(result, pe);
        const before = result.substring(0, hs);
        const mid    = result.substring(hs, he);
        const after  = result.substring(he);
        if (ann.type === 'note') {
            result = `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${mid}</mark>${after}`;
        } else {
            result = `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${mid}</mark>${after}`;
        }
    }
    return result;
};

// ─── Expose ────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;

watch(leftPanelWidth, v => localStorage.setItem(PANEL_WIDTH_KEY, v.toString()));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ─── Text selection ────────────────────────────────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || !selection.rangeCount) { showContextMenu.value = false; return; }
        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }
        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) container = container.parentNode as Node;
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;
            const segId = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find(s => String(s.id) === segId);
            if (!segment) return null;
            const walker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let off = 0; let cur: Node | null;
            while ((cur = walker.nextNode())) {
                if (cur === node) { off += offsetInNode; break; }
                off += cur.textContent?.length || 0;
            }
            return segment.offset + off;
        };

        let startAbs = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbs   = getAbsoluteOffset(range.endContainer, range.endOffset);
        if (startAbs === null || endAbs === null) { showContextMenu.value = false; return; }
        if (startAbs > endAbs) [startAbs, endAbs] = [endAbs, startAbs];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), window.innerWidth - 80),
                y: Math.max(rect.top - 78, 10),
            };
            showContextMenu.value = true;
            selectedText.value = selection.toString();
            selectionRange.value = { start: startAbs, end: endAbs };
        } else { showContextMenu.value = false; }
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
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x; let y = contextMenuPosition.value.y + 70;
    if (selection && selection.rangeCount > 0) {
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        x = rect.left + rect.width / 2; y = rect.bottom + 10;
    }
    const mw = 320; const mh = 180; const pad = 10;
    const vw = window.innerWidth; const vh = window.innerHeight;
    if (x - mw / 2 < pad) x = mw / 2 + pad;
    else if (x + mw / 2 > vw - pad) x = vw - mw / 2 - pad;
    if (y + mh > vh - pad) {
        if (selection && selection.rangeCount > 0) y = selection.getRangeAt(0).getBoundingClientRect().top - mh - 10;
        if (y < pad) y = pad;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true; showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start, end } = selectionRange.value;
    findOverlappingHighlights(start, end).forEach(h => deleteHighlight(h.id));
    notes.value = notes.value.filter(n => !(n.start < end && n.end > start));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start, end, part: 'Part 2' });
    noteInputText.value = ''; showNoteInput.value = false; window.getSelection()?.removeAllRanges();
};
const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter(n => n.id !== id); };

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const mark = target.closest('mark[data-highlight-id]') as HTMLElement;
    if (mark) {
        const hid = mark.getAttribute('data-highlight-id');
        if (hid) {
            event.stopPropagation();
            const rect = mark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(hid, 10);
            showDeleteTooltip.value = true;
            showContextMenu.value = false;
        }
    } else { closeDeleteTooltip(); if (showContextMenu.value) showContextMenu.value = false; }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const id = highlightToDelete.value;
        showDeleteTooltip.value = false; highlightToDelete.value = null;
        deleteHighlight(id);
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const noteMark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const nid = noteMark.getAttribute('data-note-id');
        if (nid) {
            const note = notes.value.find(n => n.id === parseInt(nid, 10));
            if (note) {
                const rect = noteMark.getBoundingClientRect();
                let y = rect.top - 58; if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = note.id; hoveredNoteText.value = note.note; showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        if (!(event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) {
            showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
        }
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) { deleteNote(hoveredNoteId.value); showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; }
};

const handleClickOutside = () => { if (showContextMenu.value) showContextMenu.value = false; };
const handleKeyDown = (e: KeyboardEvent) => { if (e.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); } };

// ─── Resize ────────────────────────────────────────────────────────────────
const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const pct = ((e.clientX - rect.left) / rect.width) * 100;
    if (pct >= 20 && pct <= 80) leftPanelWidth.value = pct;
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

        <!-- Part Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part2-title"
                v-html="getHighlightedSegmentById('part2-title')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part2-instruction"
                v-html="getHighlightedSegmentById('part2-instruction')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-0.5">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ═══════════════════════════════════
                     LEFT PANEL — Reading Passage
                ═══════════════════════════════════ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">

                    <div class="pt-6 px-4">
                        <h2 class="text-lg text-center font-bold text-gray-900select-text" data-segment-id="passage-title"
                v-html="getHighlightedSegmentById('passage-title')"></h2>
                    </div>

                    <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text">

                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">A. </span>
                                <span class="select-text" data-segment-id="para-a"
                                    v-html="getHighlightedSegmentById('para-a')"></span>
                            </div>

                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">B. </span>
                                <span class="select-text" data-segment-id="para-b"
                                    v-html="getHighlightedSegmentById('para-b')"></span>
                            </div>

                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">C. </span>
                                <span class="select-text" data-segment-id="para-c"
                                    v-html="getHighlightedSegmentById('para-c')"></span>
                            </div>

                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">D. </span>
                                <span class="select-text" data-segment-id="para-d"
                                    v-html="getHighlightedSegmentById('para-d')"></span>
                            </div>

                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">E. </span>
                                <span class="select-text" data-segment-id="para-e"
                                    v-html="getHighlightedSegmentById('para-e')"></span>
                            </div>

                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">F. </span>
                                <span class="select-text" data-segment-id="para-f"
                                    v-html="getHighlightedSegmentById('para-f')"></span>
                            </div>

                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">G. </span>
                                <span class="select-text" data-segment-id="para-g"
                                    v-html="getHighlightedSegmentById('para-g')"></span>
                            </div>

                            <!-- Ethology note -->
                            <p class="text-sm text-gray-500 italic border-t border-gray-200 pt-3">
                                Note: Ethology = the branch of zoology that studies the behaviour of animals in their natural habitats
                            </p>

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

                <!-- ═══════════════════════════════════
                     RIGHT PANEL — Questions
                ═══════════════════════════════════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">

                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ══════════════════════════════
                                 QUESTIONS 14-18 — Dropdown
                            ══════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q14-18-title"
                                            v-html="getHighlightedSegmentById('q14-18-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q14-18-instruction-1"
                                            v-html="getHighlightedSegmentById('q14-18-instruction-1')"></span>
                                    </p>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q14-18-instruction-2"
                                            v-html="getHighlightedSegmentById('q14-18-instruction-2')"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q14-18-instruction-3"
                                            v-html="getHighlightedSegmentById('q14-18-instruction-3')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">

                                    <!-- Q14 -->
                                    <div id="question-14" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span class="select-text" data-segment-id="q14-text"
                                                v-html="getHighlightedSegmentById('q14-text')"></span>
                                        </p>
                                        <select v-model="answers.q14"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q15 -->
                                    <div id="question-15" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span class="select-text" data-segment-id="q15-text"
                                                v-html="getHighlightedSegmentById('q15-text')"></span>
                                        </p>
                                        <select v-model="answers.q15"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q16 -->
                                    <div id="question-16" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span class="select-text" data-segment-id="q16-text"
                                                v-html="getHighlightedSegmentById('q16-text')"></span>
                                        </p>
                                        <select v-model="answers.q16"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q17 -->
                                    <div id="question-17" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span class="select-text" data-segment-id="q17-text"
                                                v-html="getHighlightedSegmentById('q17-text')"></span>
                                        </p>
                                        <select v-model="answers.q17"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q18 -->
                                    <div id="question-18" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span class="select-text" data-segment-id="q18-text"
                                                v-html="getHighlightedSegmentById('q18-text')"></span>
                                        </p>
                                        <select v-model="answers.q18"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="toggleFlag(18)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ══════════════════════════════
                                 QUESTIONS 19-23 — TRUE/FALSE/NOT GIVEN (Radio)
                            ══════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q19-23-title"
                                            v-html="getHighlightedSegmentById('q19-23-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q19-23-instruction-1"
                                            v-html="getHighlightedSegmentById('q19-23-instruction-1')"></span>
                                    </p>
                                    <p class="mb-3 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q19-23-instruction-2"
                                            v-html="getHighlightedSegmentById('q19-23-instruction-2')"></span>
                                    </p>

                                    <!-- T/F/NG key -->
                                    <div class="border border-gray-300 p-4 mb-4">
                                        <div class="grid grid-cols-1 gap-2 text-base">
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900">TRUE</span>
                                                <span class="select-text text-gray-700" data-segment-id="tfng-true"
                                                    v-html="getHighlightedSegmentById('tfng-true')"></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900">FALSE</span>
                                                <span class="select-text text-gray-700" data-segment-id="tfng-false"
                                                    v-html="getHighlightedSegmentById('tfng-false')"></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700 text-sm">NOT GIVEN</span>
                                                <span class="select-text text-gray-700" data-segment-id="tfng-notgiven"
                                                    v-html="getHighlightedSegmentById('tfng-notgiven')"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">

                                    <!-- Q19 -->
                                    <div id="question-19" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 shrink-0">19.</span>
                                            <div class="flex-1">
                                                <p class="mb-2 text-base leading-relaxed text-gray-700">
                                                    <span class="select-text" data-segment-id="q19-text"
                                                        v-html="getHighlightedSegmentById('q19-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q19" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">TRUE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q19" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">FALSE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q19" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                            @click.stop="toggleFlag(19)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q20 -->
                                    <div id="question-20" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 shrink-0">20.</span>
                                            <div class="flex-1">
                                                <p class="mb-2 text-base leading-relaxed text-gray-700">
                                                    <span class="select-text" data-segment-id="q20-text"
                                                        v-html="getHighlightedSegmentById('q20-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q20" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">TRUE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q20" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">FALSE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q20" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                            @click.stop="toggleFlag(20)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q21 -->
                                    <div id="question-21" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 shrink-0">21.</span>
                                            <div class="flex-1">
                                                <p class="mb-2 text-base leading-relaxed text-gray-700">
                                                    <span class="select-text" data-segment-id="q21-text"
                                                        v-html="getHighlightedSegmentById('q21-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q21" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">TRUE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q21" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">FALSE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q21" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                            @click.stop="toggleFlag(21)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q22 -->
                                    <div id="question-22" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 shrink-0">22.</span>
                                            <div class="flex-1">
                                                <p class="mb-2 text-base leading-relaxed text-gray-700">
                                                    <span class="select-text" data-segment-id="q22-text"
                                                        v-html="getHighlightedSegmentById('q22-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q22" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">TRUE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q22" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">FALSE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q22" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q23 -->
                                    <div id="question-23" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 shrink-0">23.</span>
                                            <div class="flex-1">
                                                <p class="mb-2 text-base leading-relaxed text-gray-700">
                                                    <span class="select-text" data-segment-id="q23-text"
                                                        v-html="getHighlightedSegmentById('q23-text')"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q23" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">TRUE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q23" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">FALSE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q23" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="text-base text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                            @click.stop="toggleFlag(23)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ══════════════════════════════
                                 QUESTIONS 24-26 — MCQ (Radio A/B/C/D)
                            ══════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q24-26-title"
                                            v-html="getHighlightedSegmentById('q24-26-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q24-26-instruction-1"
                                            v-html="getHighlightedSegmentById('q24-26-instruction-1')"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q24-26-instruction-2"
                                            v-html="getHighlightedSegmentById('q24-26-instruction-2')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">

                                    <!-- Q24 -->
                                    <div id="question-24" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-3 text-base text-gray-700 flex items-start gap-2">
                                            <span class="font-bold shrink-0" data-segment-id="q24-num"
                                                v-html="getHighlightedSegmentById('q24-num')"></span>
                                            <span class="select-text" data-segment-id="q24-q"
                                                v-html="getHighlightedSegmentById('q24-q')"></span>
                                        </p>
                                        <div class="space-y-2 ml-5">
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q24" value="A" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q24-a"
                                                    v-html="getHighlightedSegmentById('q24-a')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q24" value="B" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q24-b"
                                                    v-html="getHighlightedSegmentById('q24-b')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q24" value="C" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q24-c"
                                                    v-html="getHighlightedSegmentById('q24-c')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q24" value="D" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q24-d"
                                                    v-html="getHighlightedSegmentById('q24-d')"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                            @click.stop="toggleFlag(24)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q25 -->
                                    <div id="question-25" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-3 text-base text-gray-700 flex items-start gap-2">
                                            <span class="font-bold shrink-0" data-segment-id="q25-num"
                                                v-html="getHighlightedSegmentById('q25-num')"></span>
                                            <span class="select-text" data-segment-id="q25-q"
                                                v-html="getHighlightedSegmentById('q25-q')"></span>
                                        </p>
                                        <div class="space-y-2 ml-5">
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q25" value="A" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q25-a"
                                                    v-html="getHighlightedSegmentById('q25-a')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q25" value="B" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q25-b"
                                                    v-html="getHighlightedSegmentById('q25-b')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q25" value="C" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q25-c"
                                                    v-html="getHighlightedSegmentById('q25-c')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q25" value="D" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q25-d"
                                                    v-html="getHighlightedSegmentById('q25-d')"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                            @click.stop="toggleFlag(25)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(25) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q26 -->
                                    <div id="question-26" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-3 text-base text-gray-700 flex items-start gap-2">
                                            <span class="font-bold shrink-0" data-segment-id="q26-num"
                                                v-html="getHighlightedSegmentById('q26-num')"></span>
                                            <span class="select-text" data-segment-id="q26-q"
                                                v-html="getHighlightedSegmentById('q26-q')"></span>
                                        </p>
                                        <div class="space-y-2 ml-5">
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q26" value="A" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q26-a"
                                                    v-html="getHighlightedSegmentById('q26-a')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q26" value="B" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q26-b"
                                                    v-html="getHighlightedSegmentById('q26-b')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q26" value="C" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q26-c"
                                                    v-html="getHighlightedSegmentById('q26-c')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 hover:bg-gray-50 p-1 rounded">
                                                <input type="radio" v-model="answers.q26" value="D" class="h-4 w-4 accent-black" />
                                                <span class="select-text text-base text-gray-700" data-segment-id="q26-d"
                                                    v-html="getHighlightedSegmentById('q26-d')"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                            @click.stop="toggleFlag(26)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(26) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             TELEPORTED OVERLAYS
        ══════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <!-- Context Menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50">
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
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div class="delete-highlight-tooltip fixed z-[9999]"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md" style="max-width:240px;">
                        <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span class="text-sm break-words text-gray-700" style="max-width:180px;">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50" title="Delete note">
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
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                        "{{ selectedText }}"
                    </p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
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

.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

.select-none {
    user-select: none;
    -webkit-user-select: none;
    cursor: col-resize;
}

.passage-panel { width: 100%; }
.answer-panel  { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel  { width: calc(100% - var(--panel-width) - 0.5rem); }
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
}

.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green  { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue   { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink   { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); }
    50%  { background-color: rgba(0,0,0,0.2); }
    100% { background-color: rgba(0,0,0,0.1); }
}

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

/* Tooltip arrows */
.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}
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
</style>

<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer; padding: 2px 0; border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147, 197, 253, 0.7) !important; }
</style>
