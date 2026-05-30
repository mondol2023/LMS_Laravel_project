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

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part3-panel-width';
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

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// ── Answers ──────────────────────────────────────────────────────────────────
// Q27-32: TRUE / FALSE / NOT GIVEN
// Q33-40: short text answers (no more than two words)
const answers = ref({
    q27: '', q28: '', q29: '', q30: '', q31: '', q32: '',
    q33: '', q34: '', q35: '', q36: '', q37: '', q38: '', q39: '', q40: '',
});

const tfngOptions = ['TRUE', 'FALSE', 'NOT GIVEN'];

// ── Passage paragraphs ────────────────────────────────────────────────────────
const passagePara1 = `Jean-Antoine Nollet was a French clergyman and physicist. In 1746 he gathered about two hundred monks into a circle about a mile (1.6 km) in circumference, with pieces iron wire connecting them. He then discharged a battery of Leyden jars through the human chain and observed that each man reacted at substantially the same time to the electric shock, showing that the speed of electricity's propagation was very high. Given a more humane detection system, this could be a way of signaling over long distances. In 1748, Nollet invented one of the first electrometers, the electroscope, which detected the presence of an electric charge by using electrostatic attraction and repulsion.`;

const passagePara2 = `After the introduction of the European semaphore lines in 1792, the world's desire to further its ability to communicate from a distance only grew. People wanted a way to send and receive news from remote locations so that they could better understand what was happening in the world around them—not just what was going on in their immediate town or city. This type of communication not only appealed to the media industry, but also to private individuals and companies who wished to stay in touch with contacts. In 1840 Charles Wheatstone from Britain, with William Cooke, obtained a new patent for a telegraphic arrangement. The new apparatus required only a single pair of wires, but the telegraph was still too costly for general purposes. In 1845, however, Cooke and Wheatstone succeeded in producing the single needle apparatus, which they patented, and from that time the electric telegraph became a practical instrument, soon adopted on all the railway lines of the country.`;

const passagePara3 = `It was the European optical telegraph, or semaphore, that was the predecessor of the electrical recording telegraph that changed the history of communication forever. Building on the success of the optical telegraph, Samuel F. B. Morse completed a working version of the electrical recording telegraph, which only required a single wire to send code of dots and dashes. At first, it was imagined that only a few highly skilled encoders would be able to use it but it soon became clear that many people could become proficient in Morse code. A system of lines strung on telegraph poles began to spread in Europe and America.`;

const passagePara4 = `In the 1840s and 1850s several individuals proposed or advocated construction of a telegraph cable across the Atlantic Ocean, including Edward Thornton and Alonzo Jackman. At that time there was no material available for cable insulation and the first breakthrough came with the discovery of a rubber-like latex called gutta-percha. Introduced to Britain in 1843, gutta-percha is the gum of a tree native to the Malay Peninsula and Malaysia. After the failure of their first cable in 1850, the British brothers John and Jacob Brett laid a successful submarine cable from Dover to Calais in 1851. This used two layers of gutta-percha insulation and an armoured outer layer. With thin wire and thick insulation, it floated and had to be weighed down with lead pipe.`;

const passagePara5 = `In the case of first submarine-cable telegraphy, there was the limitation of knowledge of how its electrical properties were affected by water. The voltage which may be impressed on the cable was limited to a definite value. Moreover, for certain reasons, the cable had an impedance associated with it at the sending end which could make the voltage on the cable differ from the voltage applied to the sending-end apparatus. In fact, the cable was too big for a single boat, so two had to start in the middle of the Atlantic, join their cables and sail in opposite directions. Amazingly, the first official telegram to pass between two continents was a letter of congratulation from Queen Victoria of the United Kingdom to the President of the United States, James Buchanan, on August 16, 1858. However, signal quality declined rapidly, slowing transmission to an almost unusable speed and the cable was destroyed the following month.`;

const passagePara6 = `To complete the link between England and Australia, John Pender formed the British-Australian Telegraph Company. The first stage was to lay a 557nm cable from Singapore to Batavia on the island of Java in 1870. It seemed likely that it would come ashore at the northern port of Darwin from where it might connect around the coast to Queensland and New South Wales. It was an undertaking more ambitious than spanning ocean. Flocks of sheep had to be driven with the 400 workers to provide food. They needed horses and bullock carts and, for the parched interior, camels. In the north, tropical rains left the teams flooded. In the centre, it seemed that they would die of thirst. One critical section in the red heart of Australia involved finding a route through the McDonnell mountain range and then finding water on the other side. The water was not only essential for the construction teams. There had to be telegraph repeater stations every few hundred miles to boost the signal and the staff obviously had to have a supply of water.`;

const passagePara7 = `On August 22, 1872, the Northern and Southern sections of the Overland Telegraph Line were connected, uniting the Australian continent and within a few months, Australia was at last in direct contact with England via the submarine cable, too. This allowed the Australian Government to receive news from around the world almost instantaneously for the first time. It could cost several pounds to send a message and it might take several hours for it to reach its destination on the other side of the globe, but the world would never be the same again. The telegraph was the first form of communication over a great distance and was a landmark in human history.`;

// ── Text segments ─────────────────────────────────────────────────────────────
const textSegments = ref([
    // Header
    { id: 'part3-title', text: 'READING PASSAGE 3', offset: 10000 },
    { id: 'part3-passage-title', text: 'History of telegraph in communication', offset: 10050 },
    // Passage paragraphs
    { id: 'passage-p1', text: passagePara1, offset: 0 },
    { id: 'passage-p2', text: passagePara2, offset: 900 },
    { id: 'passage-p3', text: passagePara3, offset: 1900 },
    { id: 'passage-p4', text: passagePara4, offset: 2700 },
    { id: 'passage-p5', text: passagePara5, offset: 3700 },
    { id: 'passage-p6', text: passagePara6, offset: 4800 },
    { id: 'passage-p7', text: passagePara7, offset: 6000 },

    // Q27-32 header
    { id: 'q27-32-title', text: 'Questions 27-32', offset: 20000 },
    { id: 'q27-32-inst1', text: 'Do the following statements agree with the information given in Reading Passage 3.', offset: 20030 },
    { id: 'q27-32-inst2', text: 'In boxes 27-32 on your answer sheet, write', offset: 20120 },
    { id: 'q27-32-inst3', text: 'TRUE if the statement agrees with the information', offset: 20165 },
    { id: 'q27-32-inst4', text: 'FALSE if the statement contradicts the information', offset: 20215 },
    { id: 'q27-32-inst5', text: 'NOT GIVEN if there is no information on this', offset: 20270 },

    // Q27-32 stems
    { id: 'q27-stem', text: 'In the research of the French scientist, metal lines were used to send messages.', offset: 20330 },
    { id: 'q28-stem', text: 'People increasingly hoped to explore ways of long-distance communication in the late eighteenth century.', offset: 20430 },
    { id: 'q29-stem', text: 'Using Morse Code to send message needed special personnel to first simplify the message.', offset: 20560 },
    { id: 'q30-stem', text: 'Morse was a famous inventor before he invented the code.', offset: 20660 },
    { id: 'q31-stem', text: 'Water was significant to early telegraph repeater stations on the continent.', offset: 20720 },
    { id: 'q32-stem', text: 'The Australian Government offered funds for the first overland line across the continent.', offset: 20810 },

    // Q33-40 header
    { id: 'q33-40-title', text: 'Questions 33 – 40', offset: 21000 },
    { id: 'q33-40-inst1', text: 'Answer the questions below.', offset: 21020 },
    { id: 'q33-40-inst2', text: 'Choose NO MORE THAN TWO WORDS from the passage for each answer.', offset: 21055 },

    // Q33-40 stems
    { id: 'q33-stem', text: 'Why did Charles Wheatstone\'s telegraph system fail to come into common use in the beginning?', offset: 21120 },
    { id: 'q34-stem', text: 'What material was used for insulating cable across the sea?', offset: 21230 },
    { id: 'q35-stem', text: 'What was used by British pioneers to increase the weight of the cable in the sea?', offset: 21300 },
    { id: 'q36-stem', text: 'What would occur in the submarine cable when the voltage was applied?', offset: 21400 },
    { id: 'q37-stem', text: 'Who was a message first sent to across the Atlantic by the Queen?', offset: 21490 },
    { id: 'q38-stem', text: 'What animals were used to carry the cable through desert?', offset: 21570 },
    { id: 'q39-stem', text: 'What weather condition delayed construction in north Australia?', offset: 21640 },
    { id: 'q40-stem', text: 'How long did it take to send a telegraph message from Australia to England in 1872?', offset: 21720 },
]);

// ── Highlight helpers ─────────────────────────────────────────────────────────
const getPlainTextLength = (html: string) => html.replace(/<[^>]*>/g, '').length;

const plainToHtmlOffset = (html: string, plainOffset: number): number => {
    let plain = 0, htmlIdx = 0;
    while (plain < plainOffset && htmlIdx < html.length) {
        if (html[htmlIdx] === '<') { while (htmlIdx < html.length && html[htmlIdx] !== '>') htmlIdx++; htmlIdx++; }
        else { plain++; htmlIdx++; }
    }
    return htmlIdx;
};

const applyAnnotations = (text: string, segOffset: number, isHtml = false): string => {
    const plainLen = isHtml ? getPlainTextLength(text) : text.length;
    const segEnd = segOffset + plainLen;

    const anns = [
        ...highlights.value
            .filter(h => h.start_offset < segEnd && h.end_offset > segOffset)
            .map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...notes.value
            .filter(n => n.start < segEnd && n.end > segOffset)
            .map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    if (anns.length === 0) return text;

    let result = text;
    for (const ann of anns) {
        const pStart = Math.max(0, ann.start - segOffset);
        const pEnd = Math.min(plainLen, ann.end - segOffset);
        if (pStart >= pEnd) continue;
        const hStart = isHtml ? plainToHtmlOffset(result, pStart) : pStart;
        const hEnd = isHtml ? plainToHtmlOffset(result, pEnd) : pEnd;
        const before = result.substring(0, hStart);
        const annotated = result.substring(hStart, hEnd);
        const after = result.substring(hEnd);
        result = ann.type === 'note'
            ? `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${annotated}</mark>${after}`
            : `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${annotated}</mark>${after}`;
    }
    return result;
};

const getHighlightedSegmentById = (id: string) => {
    const seg = textSegments.value.find(s => s.id === id);
    if (!seg) return '';
    return applyAnnotations(seg.text, seg.offset, false);
};

// ── Expose ────────────────────────────────────────────────────────────────────
const getAnswers = () => ({ ...answers.value });

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

// ── Selection handling ────────────────────────────────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container: Node | null = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode;
            }
            const segmentEl = (container as Element)?.closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segId = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find(s => s.id === segId);
            if (!segment) return null;

            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let offsetInSegment = 0;
            let currentNode: Node | null;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) {
                    offsetInSegment += offsetInNode;
                    break;
                } else {
                    offsetInSegment += currentNode.textContent?.length || 0;
                }
            }
            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);

        if (startAbsOffset === null || endAbsOffset === null) {
            showContextMenu.value = false;
            return;
        }

        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70;
            const menuY = rect.top - menuHeight - 8;
            const vw = window.innerWidth;
            const menuWidth = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), vw - menuWidth / 2 - 10),
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
    const modalWidth = 320; const modalHeight = 180; const padding = 10;
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x, y = contextMenuPosition.value.y + 70;
    if (selection && selection.rangeCount > 0) {
        const r = selection.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    }
    const vw = window.innerWidth; const vh = window.innerHeight;
    const hw = modalWidth / 2;
    if (x - hw < padding) x = hw + padding;
    else if (x + hw > vw - padding) x = vw - hw - padding;
    if (y + modalHeight > vh - padding) {
        if (selection?.rangeCount) y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
        if (y < padding) y = padding;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: ns, end: ne } = selectionRange.value;
    findOverlappingHighlights(ns, ne).forEach(h => deleteHighlight(h.id));
    notes.value = notes.value.filter(n => !(n.start < ne && n.end > ns));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: ns, end: ne });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter(n => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement;
    if (mark) {
        const id = mark.getAttribute('data-highlight-id');
        if (id) {
            event.stopPropagation();
            const rect = mark.getBoundingClientRect();
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
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (mark) {
        const noteId = mark.getAttribute('data-note-id');
        if (noteId) {
            const id = parseInt(noteId, 10);
            const note = notes.value.find(n => n.id === id);
            if (note) {
                const rect = mark.getBoundingClientRect();
                let y = rect.top - 58;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
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

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; };

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

// Resize
const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const pct = ((e.clientX - rect.left) / rect.width) * 100;
    if (pct >= 20 && pct <= 80) leftPanelWidth.value = pct;
};
const stopResize = () => { isResizing.value = false; };

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

        <!-- Part 3 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part3-title"
                v-html="getHighlightedSegmentById('part3-title')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part3-passage-title"
                v-html="getHighlightedSegmentById('part3-passage-title')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage ──────────────────────────────────── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" data-segment-id="part3-passage-title"
                                v-html="getHighlightedSegmentById('part3-passage-title')"></span>
                        </h2>
                    </div>
                    <div class="space-y-4 p-6" :style="contentZoom">
                        <p class="text-justify leading-relaxed text-gray-700 select-text">
                            <span data-segment-id="passage-p1" v-html="getHighlightedSegmentById('passage-p1')"></span>
                        </p>
                        <p class="text-justify leading-relaxed text-gray-700 select-text">
                            <span data-segment-id="passage-p2" v-html="getHighlightedSegmentById('passage-p2')"></span>
                        </p>
                        <p class="text-justify leading-relaxed text-gray-700 select-text">
                            <span data-segment-id="passage-p3" v-html="getHighlightedSegmentById('passage-p3')"></span>
                        </p>
                        <p class="text-justify leading-relaxed text-gray-700 select-text">
                            <span data-segment-id="passage-p4" v-html="getHighlightedSegmentById('passage-p4')"></span>
                        </p>
                        <p class="text-justify leading-relaxed text-gray-700 select-text">
                            <span data-segment-id="passage-p5" v-html="getHighlightedSegmentById('passage-p5')"></span>
                        </p>
                        <p class="text-justify leading-relaxed text-gray-700 select-text">
                            <span data-segment-id="passage-p6" v-html="getHighlightedSegmentById('passage-p6')"></span>
                        </p>
                        <p class="text-justify leading-relaxed text-gray-700 select-text">
                            <span data-segment-id="passage-p7" v-html="getHighlightedSegmentById('passage-p7')"></span>
                        </p>
                    </div>
                </div>

                <!-- ── Resize Handle ────────────────────────────────────── -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Section ────────────────────────────────── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ══ Questions 27-32 (TRUE / FALSE / NOT GIVEN) ══ -->
                            <div class="p-4">
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 select-text"
                                        data-segment-id="q27-32-title"
                                        v-html="getHighlightedSegmentById('q27-32-title')"></h3>
                                    <p class="mt-1 text-sm text-gray-700 select-text" data-segment-id="q27-32-inst1"
                                        v-html="getHighlightedSegmentById('q27-32-inst1')"></p>
                                    <p class="text-sm text-gray-700 select-text" data-segment-id="q27-32-inst2"
                                        v-html="getHighlightedSegmentById('q27-32-inst2')"></p>
                                    <p class="text-sm text-gray-700 ml-4 select-text" data-segment-id="q27-32-inst3"
                                        v-html="getHighlightedSegmentById('q27-32-inst3')"></p>
                                    <p class="text-sm text-gray-700 ml-4 select-text" data-segment-id="q27-32-inst4"
                                        v-html="getHighlightedSegmentById('q27-32-inst4')"></p>
                                    <p class="text-sm text-gray-700 ml-4 select-text" data-segment-id="q27-32-inst5"
                                        v-html="getHighlightedSegmentById('q27-32-inst5')"></p>
                                </div>

                                <div class="space-y-6">

                                    <!-- ── Q27 ── -->
                                    <div id="question-27" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 27"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                                @click.stop="toggleFlag(27)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">27.</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q27-stem"
                                                v-html="getHighlightedSegmentById('q27-stem')"></span>
                                        </div>
                                        <div class="flex gap-3 ml-4 flex-wrap">
                                            <label v-for="opt in tfngOptions" :key="opt"
                                                class="flex cursor-pointer items-center gap-2 px-2 py-1 hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q27" :value="opt"
                                                    class="h-4 w-4 flex-shrink-0 accent-black" />
                                                <span class="text-sm font-medium text-gray-800">{{ opt }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ── Q28 ── -->
                                    <div id="question-28" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 28"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                                @click.stop="toggleFlag(28)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">28.</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q28-stem"
                                                v-html="getHighlightedSegmentById('q28-stem')"></span>
                                        </div>
                                        <div class="flex gap-3 ml-4 flex-wrap">
                                            <label v-for="opt in tfngOptions" :key="opt"
                                                class="flex cursor-pointer items-center gap-2 px-2 py-1 hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q28" :value="opt"
                                                    class="h-4 w-4 flex-shrink-0 accent-black" />
                                                <span class="text-sm font-medium text-gray-800">{{ opt }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ── Q29 ── -->
                                    <div id="question-29" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 29"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                                @click.stop="toggleFlag(29)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">29.</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q29-stem"
                                                v-html="getHighlightedSegmentById('q29-stem')"></span>
                                        </div>
                                        <div class="flex gap-3 ml-4 flex-wrap">
                                            <label v-for="opt in tfngOptions" :key="opt"
                                                class="flex cursor-pointer items-center gap-2 px-2 py-1 hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q29" :value="opt"
                                                    class="h-4 w-4 flex-shrink-0 accent-black" />
                                                <span class="text-sm font-medium text-gray-800">{{ opt }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ── Q30 ── -->
                                    <div id="question-30" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 30"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                                @click.stop="toggleFlag(30)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">30.</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q30-stem"
                                                v-html="getHighlightedSegmentById('q30-stem')"></span>
                                        </div>
                                        <div class="flex gap-3 ml-4 flex-wrap">
                                            <label v-for="opt in tfngOptions" :key="opt"
                                                class="flex cursor-pointer items-center gap-2 px-2 py-1 hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q30" :value="opt"
                                                    class="h-4 w-4 flex-shrink-0 accent-black" />
                                                <span class="text-sm font-medium text-gray-800">{{ opt }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ── Q31 ── -->
                                    <div id="question-31" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 31"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                                @click.stop="toggleFlag(31)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">31.</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q31-stem"
                                                v-html="getHighlightedSegmentById('q31-stem')"></span>
                                        </div>
                                        <div class="flex gap-3 ml-4 flex-wrap">
                                            <label v-for="opt in tfngOptions" :key="opt"
                                                class="flex cursor-pointer items-center gap-2 px-2 py-1 hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q31" :value="opt"
                                                    class="h-4 w-4 flex-shrink-0 accent-black" />
                                                <span class="text-sm font-medium text-gray-800">{{ opt }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ── Q32 ── -->
                                    <div id="question-32" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                                @click.stop="toggleFlag(32)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">32.</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q32-stem"
                                                v-html="getHighlightedSegmentById('q32-stem')"></span>
                                        </div>
                                        <div class="flex gap-3 ml-4 flex-wrap">
                                            <label v-for="opt in tfngOptions" :key="opt"
                                                class="flex cursor-pointer items-center gap-2 px-2 py-1 hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q32" :value="opt"
                                                    class="h-4 w-4 flex-shrink-0 accent-black" />
                                                <span class="text-sm font-medium text-gray-800">{{ opt }}</span>
                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ══ Questions 33-40 (Short Answer) ══ -->
                            <div class="p-4 border-t">
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 select-text"
                                        data-segment-id="q33-40-title"
                                        v-html="getHighlightedSegmentById('q33-40-title')"></h3>
                                    <p class="mt-1 text-sm text-gray-700 select-text" data-segment-id="q33-40-inst1"
                                        v-html="getHighlightedSegmentById('q33-40-inst1')"></p>
                                    <p class="text-sm text-gray-700 select-text" data-segment-id="q33-40-inst2"
                                        v-html="getHighlightedSegmentById('q33-40-inst2')"></p>
                                </div>

                                <div class="space-y-6">

                                    <!-- ── Q33 ── -->
                                    <div id="question-33" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 33"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                                @click.stop="toggleFlag(33)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q33-stem"
                                                v-html="getHighlightedSegmentById('q33-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q33" spellcheck="false"
                                                autocomplete="off" placeholder="33"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
                                        </div>
                                    </div>

                                    <!-- ── Q34 ── -->
                                    <div id="question-34" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 34"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                                @click.stop="toggleFlag(34)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q34-stem"
                                                v-html="getHighlightedSegmentById('q34-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q34" spellcheck="false"
                                                autocomplete="off" placeholder="34"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
                                        </div>
                                    </div>

                                    <!-- ── Q35 ── -->
                                    <div id="question-35" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 35"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                @click.stop="toggleFlag(35)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q35-stem"
                                                v-html="getHighlightedSegmentById('q35-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q35" spellcheck="false"
                                                autocomplete="off" placeholder="35"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
                                        </div>
                                    </div>

                                    <!-- ── Q36 ── -->
                                    <div id="question-36" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 36"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                @click.stop="toggleFlag(36)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q36-stem"
                                                v-html="getHighlightedSegmentById('q36-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q36" spellcheck="false"
                                                autocomplete="off" placeholder="36"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
                                        </div>
                                    </div>

                                    <!-- ── Q37 ── -->
                                    <div id="question-37" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 37"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                @click.stop="toggleFlag(37)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q37-stem"
                                                v-html="getHighlightedSegmentById('q37-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q37" spellcheck="false"
                                                autocomplete="off" placeholder="37"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
                                        </div>
                                    </div>

                                    <!-- ── Q38 ── -->
                                    <div id="question-38" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                @click.stop="toggleFlag(38)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q38-stem"
                                                v-html="getHighlightedSegmentById('q38-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q38" spellcheck="false"
                                                autocomplete="off" placeholder="38"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
                                        </div>
                                    </div>

                                    <!-- ── Q39 ── -->
                                    <div id="question-39" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                @click.stop="toggleFlag(39)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q39-stem"
                                                v-html="getHighlightedSegmentById('q39-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q39" spellcheck="false"
                                                autocomplete="off" placeholder="No more than two words"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
                                        </div>
                                    </div>

                                    <!-- ── Q40 ── -->
                                    <div id="question-40" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                @click.stop="toggleFlag(40)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-start gap-1 mb-2 pr-8">
                                            <span class="font-bold text-gray-900 select-text shrink-0">•</span>
                                            <span class="text-sm text-gray-800 select-text" data-segment-id="q40-stem"
                                                v-html="getHighlightedSegmentById('q40-stem')"></span>
                                        </div>
                                        <div class="ml-4">
                                            <input type="text" v-model="answers.q40" spellcheck="false"
                                                autocomplete="off" placeholder="40"
                                                class="w-full text-center max-w-xs border border-gray-900 bg-transparent px-1 py-1   focus:border-gray-900 focus:outline-none" />
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
                <div
                    class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText
                        }}</span>
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
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save
                    Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.select-text {
    user-select: text;
    -webkit-user-select: text;
}

.select-none {
    user-select: none;
    -webkit-user-select: none;
    cursor: col-resize;
}

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
    cursor: pointer;
    color: inherit;
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
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1);
    }

    50% {
        background-color: rgba(0, 0, 0, 0.2);
        transform: scale(1.05);
    }

    100% {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1);
    }
}

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

.note-hover-tooltip .note-tooltip-text {
    line-height: 1.4;
}
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
</style>