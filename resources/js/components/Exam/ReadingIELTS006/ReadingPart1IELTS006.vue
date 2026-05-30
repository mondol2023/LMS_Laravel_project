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

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part1-panel-width';
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
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q7: '',
    q8: '',
    q9: '',
    q10: '',
    q11: '',
    q12: '',
    q13: '',
});

// ─── PASSAGE ────────────────────────────────────────────────────────────────
const passageText = `<span class=" italic">A beautifully preserved boat, made around 3,000 years ago and discovered by chance in a muddy hole, has had a profound impact on archaeological research.</span>

It was 1992. In England, workmen were building a new road through the heart of Dover, to connect the ancient port and the Channel Tunnel, which, when it opened just two years later, was to be the first land link between Britain and Europe for over 10,000 years. A small team from the Canterbury Archaeological Trust (CAT) worked alongside the workmen, recording new discoveries bought to light by the machines.

At the base of the deep shaft six meters below the modern streets, a wooden structure was revealed. Cleaning away the waterlogged site overlying the timbers, archaeologists realized its true nature. They had found a prehistoric boat, preserved by the type of sediment in which it was buried. It was then named by Dover Bronze- Age Boat.

About nine meters of the boat's length was recovered; one end lay beyond the excavation and had to be left. What survived consisted essentially of four intricately carved oak planks: two on the bottom, joined along a central seam by a complicated system of wedges and stitched to the others. The seams had been made watertight by pads of moss, fixed by wedges and yew stitches.

The timbers that closed the recovered end of the boat had been removed in antiquity when it was abandoned, but much about its original shape could be deduced. There was also evidence for missing upper side planks. The boat was not a wreck, but had been deliberately discarded, dismantled and broken. Perhaps it had been "ritually killed" at the end of its life, like other Bronze-Age objects.

With hindsight, it was significant that the boat was found and studied by mainstream archaeologists who naturally focused on its cultural context. At the time, ancient boats were often considered only from a narrower technological perspective, but news about the Dover boat reached a broad audience. In 2002, on the tenth anniversary of the discovery, the Dover Bronze-Age Boat Trust hosted a conference, where this meeting of different traditions became apparent. Alongside technical papers about the boat, other speakers explored its social and economic contexts, and the religious perceptions of boats in Bronze- Age societies. Many speakers came from overseas, and debate about cultural connections was renewed.

Within seven years of excavation, the Dover boat had been conserved and displayed, but it was apparent that there were issues that could not be resolved simply by studying the old wood. Experimental archaeology seemed to be the solution: a boat reconstruction, half-scale or full-sized, would permit assessment of the different hypotheses regarding its build and the missing end. The possibility of returning to Dover to search for a boat's unexcavated northern end was explored, but practical and financial difficulties were insurmountable- and there was no guarantee that the timbers had survived the previous decade in the changed environment.

Detailed proposals to reconstruct the boat were drawn up in 2004. Archaeological evidence was beginning to suggest a Bronze- Age community straddling the Channel, brought together by the sea, rather than separated by it. In a region today divided by languages and borders, archaeologists had a duty to inform the general public about their common cultural heritage.

The boat project began in England but it was conceived from the start as a European collaboration. Reconstruction was only part of a scheme that would include a major exhibition and an extensive educational and outreach programme. Discussions began early in 2005 with archaeological bodies, universities and heritage organizations either side of the Channel. There was much enthusiasm and support, and an official launch of the project was held at an international seminar in France in 2007. Financial support was confirmed in 2008 and the project then named BOAT 1550BC got under way in June 2011.

A small team began to make the boat at the start of 2012 on the Roman Lawn outside Dover museum. A full- scale reconstruction of a mid-section had been made in 1996, primarily to see how Bronze- Age replica tools performed. In 2012, however, the hull shape was at the centre of the work, so modern power tools were used to carve the oak planks, before turning to prehistoric tools for finishing. It was decided to make the replica haft-scale for reasons of cost and time, any synthetic materials were used for the stitching, owing to doubts about the scaling and tight timetable.

Meanwhile, the exhibition was being prepared ready for opening in July 2012 at the Castle Museum in Boulogne-sur-Mer.

Entitled 'Beyond the Horizon: Societies of the Channel & North Sea 3,500 years ago' it brought together for the first time a remarkable collection of Bronze- Age objects, including many new discoveries for commercial archaeology and some of the great treasure of the past. The reconstructed boat, as a symbol of the maritime connections that bound together the communities either side of the Channel, was the centrepiece.`;

// ─── TEXT SEGMENTS — all offsets are strictly sequential, no overlaps ────────
const textSegments = ref([
    // Header segments (high offsets so they never collide with passage)
    { id: 'part1-title', text: 'Part 1', offset: 9000 },
    { id: 'part1-desc', text: 'Read the text and answer questions 1–13.', offset: 9010 },
    { id: 'part1-passage-title', text: 'The Dover Bronze-Age Boat', offset: 9055 },

    // Main passage — starts at 0
    { id: 'passage', text: passageText, offset: 0 },

    // ── Questions 1-5: Complete the chart ────────────────────────────────────
    { id: 'q1-5-title', text: 'Questions 1-5', offset: 5164 },
    { id: 'q1-5-inst1', text: 'Complete the chart below.', offset: 5187 },
    { id: 'q1-5-inst2', text: 'Choose ONE WORD ONLY from the text for each answer.', offset: 5222 },
    { id: 'q1-5-inst3', text: 'Write your answers in boxes 1-5 on your answer sheet.', offset: 5283 },
    { id: 'chart-header', text: 'Key events', offset: 5346 },
    { id: 'q1-prefix', text: '1992- the boat was discovered during the construction of a', offset: 5366 },
    { id: 'q2-prefix', text: '2002-an international', offset: 5434 },
    { id: 'q2-suffix', text: 'was held to gather information', offset: 5465 },
    { id: 'q3-prefix', text: '2004-', offset: 5505 },
    { id: 'q3-suffix', text: 'for the reconstruction were produced', offset: 5520 },
    { id: 'q4-prefix', text: '2007- the', offset: 5566 },
    { id: 'q4-suffix', text: 'Of BOAT 1550BC took place', offset: 5585 },
    { id: 'q5-prefix', text: '2012- the Bronze-Age', offset: 5620 },
    { id: 'q5-suffix', text: 'featured the boat and other objects', offset: 5650 },

    // ── Questions 6-9: TRUE / FALSE / NOT GIVEN ──────────────────────────────
    { id: 'q6-9-title', text: 'Questions 6-9', offset: 5695 },
    { id: 'q6-9-inst', text: 'Do the following statements agree with the information given in the text?', offset: 5718 },
    { id: 'q6-9-true-label', text: 'TRUE', offset: 5801 },
    { id: 'q6-9-true-desc', text: 'if the statement agrees with the information', offset: 5815 },
    { id: 'q6-9-false-label', text: 'FALSE', offset: 5869 },
    { id: 'q6-9-false-desc', text: 'if the statement contradicts the information', offset: 5884 },
    { id: 'q6-9-ng-label', text: 'NOT GIVEN', offset: 5938 },
    { id: 'q6-9-ng-desc', text: 'if there is no information on this', offset: 5957 },
    { id: 'q6-num', text: '6.', offset: 6001 },
    { id: 'q6-text', text: 'Archaeologists realized that the boat had been damaged on purpose.', offset: 6013 },
    { id: 'q7-num', text: '7.', offset: 6089 },
    { id: 'q7-text', text: 'Initially, only the technological aspects of the boat were examined.', offset: 6101 },
    { id: 'q8-num', text: '8.', offset: 6179 },
    { id: 'q8-text', text: 'Archaeologists went back to the site to try and find the missing northern.', offset: 6191 },
    { id: 'q9-num', text: '9.', offset: 6275 },
    { id: 'q9-text', text: 'Evidence found in 2004 suggested that the Bronze-Age Boat had been used for trade.', offset: 6287 },

    // ── Questions 10-13: Short answer ────────────────────────────────────────
    { id: 'q10-13-title', text: 'Questions 10-13', offset: 6379 },
    { id: 'q10-13-inst1', text: 'Answer the questions below.', offset: 6404 },
    { id: 'q10-13-inst2', text: 'Choose NO MORE THAN THREE WORDS AND/OR A NUMBER from the text for each answer.', offset: 6441 },
    { id: 'q10-13-inst3', text: 'Write your answers in boxes 10-13 on your answer sheet.', offset: 6529 },
    { id: 'q10-num', text: '10.', offset: 6594 },
    { id: 'q10-text', text: 'How far under the ground was the boat found?', offset: 6607 },
    { id: 'q11-num', text: '11.', offset: 6661 },
    { id: 'q11-text', text: 'What natural material had been secured to the boat to prevent water entering?', offset: 6674 },
    { id: 'q12-num', text: '12.', offset: 6761 },
    { id: 'q12-text', text: 'What aspect of the boat was the focus of the 2012 reconstruction?', offset: 6774 },
    { id: 'q13-num', text: '13.', offset: 6849 },
    { id: 'q13-text', text: 'Which two factors influenced the decision not to make a full-scale reconstruction of the boat?', offset: 6862 },
]);

// Convert plain text offset to HTML offset (skips tags)
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

const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(plainText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return plainText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ];

    const sorted = annotations.sort((a, b) => b.start - a.start);
    let result = plainText;

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

const getHighlightedSegment = (segmentText: string): string => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;
    return getHighlightedSegmentById(segment.id as string);
};

// Expose methods for parent component
const getAnswers = () => answers.value;

// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => { localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString()); });

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

// ─── Text selection handler — uses data-segment-id attributes ────────────────
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
                let targetEl: Node | null = range.startContainer;
                while (targetEl && targetEl.nodeType !== Node.ELEMENT_NODE) {
                    targetEl = targetEl.parentNode;
                }
                while (targetEl && !(targetEl as Element).hasAttribute?.('data-segment-id')) {
                    const parent = targetEl.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetEl = parent;
                }

                if (targetEl && (targetEl as Element).hasAttribute?.('data-segment-id')) {
                    const segmentId = (targetEl as Element).getAttribute('data-segment-id') || '';
                    const segment = textSegments.value.find((s) => String(s.id) === segmentId);

                    if (segment) {
                        const walker = document.createTreeWalker(targetEl as Element, NodeFilter.SHOW_TEXT);
                        let offsetInSegment = 0;
                        let node: Node | null;
                        while ((node = walker.nextNode())) {
                            if (node === range.startContainer) {
                                offsetInSegment += range.startOffset;
                                break;
                            }
                            offsetInSegment += (node.textContent || '').length;
                        }

                        const startOffset = segment.offset + offsetInSegment;
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
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
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

    if (x - halfWidth < padding) x = halfWidth + padding;
    else if (x + halfWidth > viewportWidth - padding) x = viewportWidth - halfWidth - padding;

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) y = padding;
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => { document.querySelector<HTMLInputElement>('.note-input-field')?.focus(); }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;
    findOverlappingHighlights(newStart, newEnd).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

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
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const id = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(id);
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
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
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
    if (relatedTarget?.closest('.note-hover-tooltip')) return;
    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; };
const deleteNoteFromTooltip = () => { if (hoveredNoteId.value !== null) { deleteNote(hoveredNoteId.value); showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; } };

// Resize handlers
const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const newLeftWidth = ((event.clientX - containerRect.left) / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};
const stopResize = () => { isResizing.value = false; };

onMounted(async () => {
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
        <!-- Part 1 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400  px-4 py-2" style="background-color: #F1F2EC;">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part1-title"
                v-html="getHighlightedSegmentById('part1-title')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part1-desc"
                v-html="getHighlightedSegmentById('part1-desc')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage ───────────────────────────────────────── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl text-center font-bold">
                            <span class="text-gray-900 select-text" data-segment-id="part1-passage-title"
                                v-html="getHighlightedSegmentById('part1-passage-title')"></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <!-- FIX 1: replaced whitespace-pre-line with passage-text class to prevent multiline highlight bleed -->
                                    <span class="text-lg text-gray-700 passage-text" data-segment-id="passage"
                                        v-html="getHighlightedSegmentById('passage')"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Section ─────────────────────────────────────── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ── Questions 1-5: Complete the chart ──────────── -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q1-5-title"
                                                v-html="getHighlightedSegmentById('q1-5-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q1-5-inst1"
                                            v-html="getHighlightedSegmentById('q1-5-inst1')"></span>
                                    </p>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q1-5-inst2"
                                            v-html="getHighlightedSegmentById('q1-5-inst2')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q1-5-inst3"
                                            v-html="getHighlightedSegmentById('q1-5-inst3')"></span>
                                    </p>
                                </div>

                                <!-- Chart table -->
                                <div class="overflow-x-auto border border-gray-300">
                                    <table class="w-full border-collapse text-sm">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="border border-gray-300 p-3 text-left font-bold text-gray-800"
                                                    colspan="2">
                                                    <span data-segment-id="chart-header"
                                                        v-html="getHighlightedSegmentById('chart-header')"></span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Q1 -->
                                            <tr id="question-1" class="transition-colors hover:bg-gray-50"
                                                @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                                                <td class="border border-gray-300 p-3 text-gray-700 w-full" colspan="2">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span data-segment-id="q1-prefix"
                                                            v-html="getHighlightedSegmentById('q1-prefix')"></span>
                                                        <input v-model="answers.q1" type="text"
                                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                            style="width: 140px" placeholder="1" spellcheck="false"
                                                            autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                        <button
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                            :class="[
                                                                isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                hoveredQuestion === 1 || isQuestionFlagged(1) ? 'opacity-100' : 'opacity-0'
                                                            ]" @click.stop="toggleFlag(1)"
                                                            :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                                            <svg class="h-4 w-4" fill="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path
                                                                    d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- Q2 -->
                                            <tr id="question-2" class="transition-colors hover:bg-gray-50"
                                                @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                                                <td class="border border-gray-300 p-3 text-gray-700 w-full" colspan="2">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span data-segment-id="q2-prefix"
                                                            v-html="getHighlightedSegmentById('q2-prefix')"></span>
                                                        <input v-model="answers.q2" type="text"
                                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                            style="width: 140px" placeholder="2" spellcheck="false"
                                                            autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                        <span data-segment-id="q2-suffix"
                                                            v-html="getHighlightedSegmentById('q2-suffix')"></span>
                                                        <button
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                            :class="[
                                                                isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                hoveredQuestion === 2 || isQuestionFlagged(2) ? 'opacity-100' : 'opacity-0'
                                                            ]" @click.stop="toggleFlag(2)"
                                                            :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                                            <svg class="h-4 w-4" fill="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path
                                                                    d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- Q3 -->
                                            <tr id="question-3" class="transition-colors hover:bg-gray-50"
                                                @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                                                <td class="border border-gray-300 p-3 text-gray-700 w-full" colspan="2">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span data-segment-id="q3-prefix"
                                                            v-html="getHighlightedSegmentById('q3-prefix')"></span>
                                                        <input v-model="answers.q3" type="text"
                                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                            style="width: 140px" placeholder="3" spellcheck="false"
                                                            autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                        <span data-segment-id="q3-suffix"
                                                            v-html="getHighlightedSegmentById('q3-suffix')"></span>
                                                        <button
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                            :class="[
                                                                isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                hoveredQuestion === 3 || isQuestionFlagged(3) ? 'opacity-100' : 'opacity-0'
                                                            ]" @click.stop="toggleFlag(3)"
                                                            :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                                            <svg class="h-4 w-4" fill="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path
                                                                    d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- Q4 -->
                                            <tr id="question-4" class="transition-colors hover:bg-gray-50"
                                                @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                                                <td class="border border-gray-300 p-3 text-gray-700 w-full" colspan="2">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span data-segment-id="q4-prefix"
                                                            v-html="getHighlightedSegmentById('q4-prefix')"></span>
                                                        <input v-model="answers.q4" type="text"
                                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                            style="width: 140px" placeholder="4" spellcheck="false"
                                                            autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                        <span data-segment-id="q4-suffix"
                                                            v-html="getHighlightedSegmentById('q4-suffix')"></span>
                                                        <button
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                            :class="[
                                                                isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                hoveredQuestion === 4 || isQuestionFlagged(4) ? 'opacity-100' : 'opacity-0'
                                                            ]" @click.stop="toggleFlag(4)"
                                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                                            <svg class="h-4 w-4" fill="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path
                                                                    d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- Q5 -->
                                            <tr id="question-5" class="transition-colors hover:bg-gray-50"
                                                @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                                                <td class="border border-gray-300 p-3 text-gray-700 w-full" colspan="2">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span data-segment-id="q5-prefix"
                                                            v-html="getHighlightedSegmentById('q5-prefix')"></span>
                                                        <input v-model="answers.q5" type="text"
                                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                            style="width: 140px" placeholder="5" spellcheck="false"
                                                            autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                        <span data-segment-id="q5-suffix"
                                                            v-html="getHighlightedSegmentById('q5-suffix')"></span>
                                                        <button
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                            :class="[
                                                                isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                hoveredQuestion === 5 || isQuestionFlagged(5) ? 'opacity-100' : 'opacity-0'
                                                            ]" @click.stop="toggleFlag(5)"
                                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                                            <svg class="h-4 w-4" fill="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path
                                                                    d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ── Questions 6-9: TRUE / FALSE / NOT GIVEN ─────────────────────────────── -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q6-9-title"
                                                v-html="getHighlightedSegmentById('q6-9-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q6-9-inst"
                                            v-html="getHighlightedSegmentById('q6-9-inst')"></span>
                                    </p>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        In boxes 6-9 on your answer sheet, write
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="font-bold" data-segment-id="q6-9-true-label"
                                            v-html="getHighlightedSegmentById('q6-9-true-label')"></span>
                                        <span class="mx-1">if the statement agrees with the information</span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="font-bold" data-segment-id="q6-9-false-label"
                                            v-html="getHighlightedSegmentById('q6-9-false-label')"></span>
                                        <span class="mx-1">if the statement contradicts the information</span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span class="font-bold" data-segment-id="q6-9-ng-label"
                                            v-html="getHighlightedSegmentById('q6-9-ng-label')"></span>
                                        <span class="mx-1">if there is no information on this</span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 6 -->
                                    <div id="question-6" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 6"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0"
                                                data-segment-id="q6-num"
                                                v-html="getHighlightedSegmentById('q6-num')"></span>
                                            <span class="text-base font-bold text-gray-900 leading-snug flex-1"
                                                data-segment-id="q6-text"
                                                v-html="getHighlightedSegmentById('q6-text')"></span>
                                            <button
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                :class="[
                                                    isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    hoveredQuestion === 6 || isQuestionFlagged(6) ? 'opacity-100' : 'opacity-0'
                                                ]" @click.stop="toggleFlag(6)"
                                                :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2 pl-5">
                                            <div class="flex gap-4 select-none">
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q6" value="TRUE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q6" value="FALSE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q6" value="NOT GIVEN"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 7 -->
                                    <div id="question-7" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 7"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0"
                                                data-segment-id="q7-num"
                                                v-html="getHighlightedSegmentById('q7-num')"></span>
                                            <span class="text-base font-bold text-gray-900 leading-snug flex-1"
                                                data-segment-id="q7-text"
                                                v-html="getHighlightedSegmentById('q7-text')"></span>
                                            <button
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                :class="[
                                                    isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    hoveredQuestion === 7 || isQuestionFlagged(7) ? 'opacity-100' : 'opacity-0'
                                                ]" @click.stop="toggleFlag(7)"
                                                :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2 pl-5">
                                            <div class="flex gap-4 select-none">
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q7" value="TRUE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q7" value="FALSE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q7" value="NOT GIVEN"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 8 -->
                                    <div id="question-8" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 8"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0"
                                                data-segment-id="q8-num"
                                                v-html="getHighlightedSegmentById('q8-num')"></span>
                                            <span class="text-base font-bold text-gray-900 leading-snug flex-1"
                                                data-segment-id="q8-text"
                                                v-html="getHighlightedSegmentById('q8-text')"></span>
                                            <button
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                :class="[
                                                    isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    hoveredQuestion === 8 || isQuestionFlagged(8) ? 'opacity-100' : 'opacity-0'
                                                ]" @click.stop="toggleFlag(8)"
                                                :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2 pl-5">
                                            <div class="flex gap-4 select-none">
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q8" value="TRUE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q8" value="FALSE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q8" value="NOT GIVEN"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 9 -->
                                    <div id="question-9" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 9"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0"
                                                data-segment-id="q9-num"
                                                v-html="getHighlightedSegmentById('q9-num')"></span>
                                            <span class="text-base font-bold text-gray-900 leading-snug flex-1"
                                                data-segment-id="q9-text"
                                                v-html="getHighlightedSegmentById('q9-text')"></span>
                                            <button
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                :class="[
                                                    isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    hoveredQuestion === 9 || isQuestionFlagged(9) ? 'opacity-100' : 'opacity-0'
                                                ]" @click.stop="toggleFlag(9)"
                                                :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2 pl-5">
                                            <div class="flex gap-4 select-none">
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q9" value="TRUE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q9" value="FALSE"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-1">
                                                    <input type="radio" v-model="answers.q9" value="NOT GIVEN"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                    <span class="text-sm text-gray-900">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Questions 10-13: Short Answer ──────────────────────────────── -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q10-13-title"
                                                v-html="getHighlightedSegmentById('q10-13-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span data-segment-id="q10-13-inst1"
                                            v-html="getHighlightedSegmentById('q10-13-inst1')"></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span data-segment-id="q10-13-inst2"
                                            v-html="getHighlightedSegmentById('q10-13-inst2')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span data-segment-id="q10-13-inst3"
                                            v-html="getHighlightedSegmentById('q10-13-inst3')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 10 -->
                                    <div id="question-10" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span
                                                class="text-base font-bold text-gray-500 shrink-0 select-none">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-base text-gray-900" data-segment-id="q10-text"
                                                        v-html="getHighlightedSegmentById('q10-text')"></span>

                                                </div>
                                                <div class="mt-2 flex gap-2">
                                                    <input v-model="answers.q10" type="text"
                                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 220px" placeholder="10" spellcheck="false"
                                                        autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                    <button
                                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                        :class="[
                                                            isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            hoveredQuestion === 10 || isQuestionFlagged(10) ? 'opacity-100' : 'opacity-0'
                                                        ]" @click.stop="toggleFlag(10)"
                                                        :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 11 -->
                                    <div id="question-11" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span
                                                class="text-base font-bold text-gray-500 shrink-0 select-none">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-base text-gray-900" data-segment-id="q11-text"
                                                        v-html="getHighlightedSegmentById('q11-text')"></span>
                                                </div>
                                                <div class="mt-2 flex gap-2">
                                                    <input v-model="answers.q11" type="text"
                                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 220px" placeholder="11" spellcheck="false"
                                                        autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                    <button
                                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                        :class="[
                                                            isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            hoveredQuestion === 11 || isQuestionFlagged(11) ? 'opacity-100' : 'opacity-0'
                                                        ]" @click.stop="toggleFlag(11)"
                                                        :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 12 -->
                                    <div id="question-12" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span
                                                class="text-base font-bold text-gray-500 shrink-0 select-none">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-base text-gray-900" data-segment-id="q12-text"
                                                        v-html="getHighlightedSegmentById('q12-text')"></span>

                                                </div>
                                                <div class="mt-2 flex gap-2">
                                                    <input v-model="answers.q12" type="text"
                                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 220px" placeholder="12" spellcheck="false"
                                                        autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                    <button
                                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                        :class="[
                                                            isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            hoveredQuestion === 12 || isQuestionFlagged(12) ? 'opacity-100' : 'opacity-0'
                                                        ]" @click.stop="toggleFlag(12)"
                                                        :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 13 -->
                                    <div id="question-13" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span
                                                class="text-base font-bold text-gray-500 shrink-0 select-none">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-base text-gray-900" data-segment-id="q13-text"
                                                        v-html="getHighlightedSegmentById('q13-text')"></span>

                                                </div>
                                                <div class="mt-2 flex gap-2">
                                                    <input v-model="answers.q13" type="text"
                                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 220px" placeholder="13" spellcheck="false"
                                                        autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                    <button
                                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 opacity-0 hover:opacity-100"
                                                        :class="[
                                                            isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            hoveredQuestion === 13 || isQuestionFlagged(13) ? 'opacity-100' : 'opacity-0'
                                                        ]" @click.stop="toggleFlag(13)"
                                                        :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
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
                </div>
            </div>
        </div>
    </div>

    <!-- ── Tooltips (Teleport to body) ─────────────────────────────────────── -->
    <Teleport to="body">
        <!-- Context Menu for Highlighting -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div class="highlight-tooltip pointer-events-auto fixed z-9999"
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

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-9999"
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

        <!-- Note Hover Tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999"
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

        <!-- Note Input Modal -->
        <div v-if="showNoteInput"
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
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
                    class="border-2 border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save
                    Note</button>
            </div>
        </div>
    </Teleport>
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

/* FIX 1: passage-text — renders newlines without whitespace-pre-line which caused
   multiline highlight bleeding. We use inline-block paragraphs via CSS instead. */
.passage-text {
    display: block;
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
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}

.highlight-nocolor {
    background-color: transparent;
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

/* Scrollbar */
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

/* Tooltip arrows */
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

.note-hover-tooltip .note-delete-btn {
    color: #9ca3af;
}

.note-hover-tooltip .note-delete-btn:hover {
    color: #ef4444;
}
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
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

/* FIX 1: passage paragraph breaks via <br> tags in whitespace-normal mode */
.passage-text br {
    display: block;
    content: '';
    margin-top: 1rem;
}
</style>