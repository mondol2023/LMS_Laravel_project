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

// ─── Resize ────────────────────────────────────────────────────────────────
const PANEL_WIDTH_KEY = 'reading-ielts008-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// ─── Refs ──────────────────────────────────────────────────────────────────
const contentTextRef = ref<HTMLDivElement | null>(null);

/**
 * BUG 2 FIX — passage panel ref.
 * The @mouseup handler sits on the root wrapper (both panels). Without this
 * ref there was no way to know whether the selection was in the passage or the
 * questions panel. We now bail out early inside handleContentTextSelect when
 * either endpoint of the selection falls outside the passage panel.
 */
const passagePanelRef = ref<HTMLDivElement | null>(null);

const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// ─── Delete-highlight tooltip ──────────────────────────────────────────────
/**
 * BUG 1 FIX — the delete tooltip UI was missing from the Teleport block.
 * confirmDeleteHighlight() existed but was never wired to any button.
 * Added the tooltip <div> in the Teleport section below.
 */
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// ─── Note tooltip (hover) ──────────────────────────────────────────────────
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// ─── Note input modal ──────────────────────────────────────────────────────
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{
    id: number; text: string; selectedText: string;
    note: string; start: number; end: number; part: string;
}>>([]);

// ─── Answers ───────────────────────────────────────────────────────────────
const answers = ref({
    q1: '', q2: '', q3: '', q4: '', q5: '',
    q6: '', q7: '', q8: '', q9: '',
    q10: '', q11: '', q12: '', q13: '',
});

// ─── Passage text ──────────────────────────────────────────────────────────
const passageText = `It was 1992. In England, workmen were building a new road through the heart of Dover, to connect the ancient port and the Channel Tunnel, which, when it opened just two years later, was to be the first land link between Britain and Europe for over 10,000 years. A small team from the Canterbury Archaeological Trust (CAT) worked alongside the workmen, recording new discoveries bought to light by the machines.

At the base of the deep shaft six meters below the modern streets, a wooden structure was revealed. Cleaning away the waterlogged site overlying the timbers, archaeologists realized its true nature. They had found a prehistoric boat, preserved by the type of sediment in which it was buried. It was then named by Dover Bronze- Age Boat.

About nine meters of the boat's length was recovered; one end lay beyond the excavation and had to be left. What survived consisted essentially of four intricately carved oak planks: two on the bottom, joined along a central seam by a complicated system of wedges and stitched to the others. The seams had been made watertight by pads of moss, fixed by wedges and yew stitches.

The timbers that closed the recovered end of the boat had been removed in antiquity when it was abandoned, but much about its original shape could be deduced. There was also evidence for missing upper side planks. The boat was not a wreck, but had been deliberately discarded, dismantled and broken. Perhaps it had been "ritually killed" at the end of its life, like other Bronze-Age objects.

With hindsight, it was significant that the boat was found and studied by mainstream archaeologists who naturally focused on its cultural context. At the time, ancient boats were often considered only from a narrower technological perspective, but news about the Dover boat reached a broad audience. In 2002, on the tenth anniversary of the discovery, the Dover Bronze-Age Boat Trust hosted a conference, where this meeting of different traditions became apparent. Alongside technical papers about the boat, other speakers explored its social and economic contexts, and the religious perceptions of boats in Bronze- Age societies. Many speakers came from overseas, and debate about cultural connections was renewed.

Within seven years of excavation, the Dover boat had been conserved and displayed, but it was apparent that there were issues that could not be resolved simply by studying the old wood. Experimental archaeology seemed to be the solution: a boat reconstruction, half-scale or full-sized, would permit assessment of the different hypotheses regarding its build and the missing end. The possibility of returning to Dover to search for a boat's unexcavated northern end was explored, but practical and financial difficulties were insurmountable- and there was no guarantee that the timbers had survived the previous decade in the changed environment.

Detailed proposals to reconstruct the boat were drawn up in 2004. Archaeological evidence was beginning to suggest a Bronze- Age community straddling the Channel, brought together by the sea, rather than separated by it. In a region today divided by languages and borders, archaeologists had a duty to inform the general public about their common cultural heritage.

The boat project began in England but it was conceived from the start as a European collaboration. Reconstruction was only part of a scheme that would include a major exhibition and an extensive educational and outreach programme. Discussions began early in 2005 with archaeological bodies, universities and heritage organizations either side of the Channel. There was much enthusiasm and support,and an official launch of the project was held at an international seminar in France in 2007. Financial support was confirmed in 2008 and the project then named BOAT 1550BC got under way in June 2011.

A small team began to make the boat at the start of 2012 on the Roman Lawn outside Dover museum. A full- scale reconstruction of a mid-section had been made in 1996, primarily to see how Bronze- Age replica tools performed. In 2012, however, the hull shape was at the centre of the work, so modern power tools were used to carve the oak planks, before turning to prehistoric tools for finishing. It was decided to make the replica haft-scale for reasons of cost and time, any synthetic materials were used for the stitching, owing to doubts about the scaling and tight timetable.

Meanwhile, the exhibition was being prepared ready for opening in July 2012 at the Castle Museum in Boulogne-sur-Mer.

Entitled 'Beyond the Horizon: Societies of the Channel & North Sea 3,500 years ago' it brought together for the first time a remarkable collection of Bronze- Age objects, including many new discoveries for commercial archaeology and some of the great treasure of the past. The reconstructed boat, as a symbol of the maritime connections that bound together the communities either side of the Channel, was the centrepiece.`;

const trueFalseQuestions = [
    'Archaeologists realized that the boat had been damaged on purpose.',
    'Initially, only the technological aspects of the boat were examined.',
    'Archaeologists went back to the site to try and find the missing northern end.',
    'Evidence found in 2004 suggested that the Bronze-Age Boat had been used for trade.',
];

// ─── Segments (flat — no index gaps) ──────────────────────────────────────
const textSegments = ref([
    { id: 'segment_0',  text: passageText,                                                                                            offset: 0 },
    { id: 'segment_1',  text: 'READING PASSAGE 1',                                                                                    offset: 3500 },
    { id: 'segment_2',  text: 'The Dover Bronze-Age Boat',                                                                            offset: 3519 },
    { id: 'segment_3',  text: 'A beautifully preserved boat, made around 3,000 years ago and discovered by chance in a muddy hole, has had a profound impact on archaeological research.', offset: 3545 },
    { id: 'segment_4',  text: 'QUESTIONS',                                                                                            offset: 3703 },
    { id: 'segment_5',  text: 'Answer all questions based on Reading Passage 1',                                                      offset: 3712 },
    { id: 'segment_6',  text: 'Questions 1\u20135',                                                                                   offset: 3759 },
    { id: 'segment_7',  text: 'Complete the chart below.',                                                                            offset: 3773 },
    { id: 'segment_8',  text: 'Choose ONE WORD ONLY from the text for each answer.',                                                  offset: 3798 },
    { id: 'segment_9',  text: 'Write your answers in boxes 1\u20135 on your answer sheet.',                                           offset: 3850 },
    { id: 'segment_10', text: 'Key events',                                                                                           offset: 3903 },
    { id: 'segment_11', text: '1992 \u2013 the boat was discovered during the construction of a',                                     offset: 3913 },
    { id: 'segment_13', text: '2002 \u2013 an international',                                                                         offset: 3974 },
    { id: 'segment_15', text: 'was held to gather information',                                                                       offset: 3998 },
    { id: 'segment_16', text: '2004 \u2013',                                                                                          offset: 4027 },
    { id: 'segment_18', text: 'for the reconstruction were produced',                                                                 offset: 4034 },
    { id: 'segment_19', text: '2007 \u2013 the',                                                                                      offset: 4069 },
    { id: 'segment_21', text: 'of BOAT 1550BC took place',                                                                            offset: 4080 },
    { id: 'segment_22', text: '2012 \u2013 the Bronze-Age',                                                                           offset: 4106 },
    { id: 'segment_24', text: 'featured the boat and other objects',                                                                  offset: 4128 },
    { id: 'segment_25', text: 'Questions 6\u20139',                                                                                   offset: 4162 },
    { id: 'segment_26', text: 'Do the following statements agree with the information given in the text?',                            offset: 4176 },
    { id: 'segment_27', text: 'In boxes 6\u20139 on your answer sheet, write',                                                        offset: 4246 },
    { id: 'segment_28', text: 'TRUE',                                                                                                 offset: 4286 },
    { id: 'segment_29', text: 'if the statement agrees with the information',                                                         offset: 4290 },
    { id: 'segment_30', text: 'FALSE',                                                                                                offset: 4332 },
    { id: 'segment_31', text: 'if the statement contradicts the information',                                                         offset: 4337 },
    { id: 'segment_32', text: 'NOT GIVEN',                                                                                            offset: 4378 },
    { id: 'segment_33', text: 'if there is no information on this',                                                                   offset: 4387 },
    { id: 'segment_34', text: '6',                                                                                                    offset: 4420 },
    { id: 'segment_35', text: trueFalseQuestions[0],                                                                                  offset: 4421 },
    { id: 'segment_36', text: trueFalseQuestions[1],                                                                                  offset: 4489 },
    { id: 'segment_37', text: trueFalseQuestions[2],                                                                                  offset: 4558 },
    { id: 'segment_38', text: trueFalseQuestions[3],                                                                                  offset: 4631 },
    { id: 'segment_39', text: 'Questions 10\u201313',                                                                                 offset: 4710 },
    { id: 'segment_40', text: 'Answer the questions below.',                                                                          offset: 4726 },
    { id: 'segment_41', text: 'Choose NO MORE THAN THREE WORDS AND/OR A NUMBER from the text for each answer.',                      offset: 4752 },
    { id: 'segment_42', text: 'Write your answers in boxes 10\u201313 on your answer sheet.',                                         offset: 4833 },
    { id: 'segment_43', text: 'How far under the ground was the boat found?',                                                     offset: 4888 },
    { id: 'segment_45', text: 'What natural material had been secured to the boat to prevent water entering?',                    offset: 4938 },
    { id: 'segment_47', text: 'What aspect of the boat was the focus of the 2012 reconstruction?',                               offset: 5024 },
    { id: 'segment_49', text: 'Which two factors influenced the decision not to make a full-scale reconstruction of the boat?',   offset: 5096 },
]);

const getSegmentById = (id: string) => textSegments.value.find((s) => s.id === id) ?? null;

// ─── Highlight rendering helpers ───────────────────────────────────────────
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let pi = 0, hi = 0;
    while (pi < plainOffset && hi < htmlText.length) {
        if (htmlText[hi] === '<') {
            while (hi < htmlText.length && htmlText[hi] !== '>') hi++;
            hi++;
        } else { pi++; hi++; }
    }
    return hi;
};

const getPlainTextLength = (html: string) => html.replace(/<[^>]*>/g, '').length;

const getHighlightedSegmentById = (segmentId: string): string => {
    const seg = getSegmentById(segmentId);
    if (!seg) return '';

    const segEnd = seg.offset + getPlainTextLength(seg.text);

    const hl = highlights.value.filter((h) => h.start_offset < segEnd && h.end_offset > seg.offset);
    const nt = notes.value.filter((n) => n.start < segEnd && n.end > seg.offset);
    if (!hl.length && !nt.length) return seg.text;

    const anns = [
        ...hl.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...nt.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    const segPlainLen = getPlainTextLength(seg.text);
    let result = seg.text;

    for (const ann of anns) {
        const ps = Math.max(0, ann.start - seg.offset);
        const pe = Math.min(segPlainLen, ann.end - seg.offset);
        if (ps >= pe) continue;
        const hs = plainToHtmlOffset(result, ps);
        const he = plainToHtmlOffset(result, pe);
        const b = result.substring(0, hs);
        const m = result.substring(hs, he);
        const a = result.substring(he);
        result = ann.type === 'note'
            ? `${b}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${m}</mark>${a}`
            : `${b}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${m}</mark>${a}`;
    }
    return result;
};

// Text-based lookup used by trueFalseQuestions v-for loop
const getHighlightedSegment = (text: string): string => {
    const seg = textSegments.value.find((s) => s.text === text);
    return seg ? getHighlightedSegmentById(seg.id) : text;
};

// ─── Misc ──────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;
watch(leftPanelWidth, (w) => localStorage.setItem(PANEL_WIDTH_KEY, w.toString()));

const scrollToQuestion = async (n: number) => {
    await nextTick();
    const el = document.getElementById(`question-${n}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ─── Selection → context menu ──────────────────────────────────────────────
// ─── Selection → context menu ──────────────────────────────────────────────
// ─── Selection → context menu ──────────────────────────────────────────────
const handleContentTextSelect = (event: MouseEvent) => {
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

        const anchorNode = selection.anchorNode;
        const focusNode = selection.focusNode;

        if (!anchorNode || !focusNode) {
            showContextMenu.value = false;
            return;
        }

        // ✅ NEW: Allow selection from ANY panel that has data-segment-id elements
        // Instead of restricting to passagePanelRef, check if nodes have segment data
        const hasSegmentData = (node: Node | null): boolean => {
            if (!node) return false;
            let el: Element | null = node.nodeType === Node.ELEMENT_NODE
                ? node as Element
                : (node.parentNode as Element);
            while (el && !el.hasAttribute('data-segment-id')) {
                el = el.parentElement;
            }
            return el !== null;
        };

        if (!hasSegmentData(anchorNode) || !hasSegmentData(focusNode)) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }

        const range = selection.getRangeAt(0);

        // ... [keep your existing getAbsoluteOffset function] ...
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {

            let element: Element | null = node.nodeType === Node.ELEMENT_NODE
            ? node as Element
            : (node.parentNode as Element);

            // Walk up to find element with data-segment-id
            let depth = 0;
            while (element && !element.hasAttribute('data-segment-id')) {
            element = element.parentElement;
            depth++;
            if (depth > 10) {
            return null;
            }
            }

            if (!element) {
            return null;
            }

            const segmentIdAttr = element.getAttribute('data-segment-id') || '';
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;

            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) {
            return null;
            }

            // Count text offset using TreeWalker
            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
            let currentNode: Node | null;

            while ((currentNode = treeWalker.nextNode())) {
            if (currentNode === node) {
            offsetInSegment += offsetInNode;
            break;
            } else {
            const textLen = currentNode.textContent?.length || 0;
            offsetInSegment += textLen;
            }
            }

            const finalOffset = segment.offset + offsetInSegment;
            return finalOffset;
            };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);

        if (startAbsOffset === null || endAbsOffset === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }

        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        const rect = range.getBoundingClientRect();
        if (!rect || (rect.width === 0 && rect.height === 0)) {
            showContextMenu.value = false;
            return;
        }

        // Calculate position
        const menuX = rect.left + rect.width / 2;
        const menuHeight = 70;
        const menuY = rect.top - menuHeight - 8;
        const viewportWidth = window.innerWidth;
        const menuWidth = 140;

        contextMenuPosition.value = {
            x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
            y: Math.max(menuY, 10),
        };

        // ✅ Show the menu!
        showContextMenu.value = true;
        selectedText.value = selection.toString();
        selectionRange.value = { start: startAbsOffset, end: endAbsOffset };

    }, 10);
};

// ─── Highlight creation ────────────────────────────────────────────────────
const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    console.log('🎨 Highlighting:', selectedText.value, 'with', color);
    notes.value = notes.value.filter(
        (n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start),
    );
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

// ─── Note modal ────────────────────────────────────────────────────────────
const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    const mw = 320, mh = 180, pad = 10;
    const s = window.getSelection();
    let x: number, y: number;
    if (s && s.rangeCount > 0) {
        const r = s.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2;
        y = r.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }
    const vw = window.innerWidth, vh = window.innerHeight, half = mw / 2;
    if (x - half < pad) x = half + pad;
    else if (x + half > vw - pad) x = vw - half - pad;
    if (y + mh > vh - pad) {
        if (s && s.rangeCount > 0) y = s.getRangeAt(0).getBoundingClientRect().top - mh - 10;
        if (y < pad) y = pad;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

// Add this function in your <script setup>
const handleQuestionPanelSelectStart = (event: Event) => {
    const target = event.target as HTMLElement;
    // Allow selection only on interactive elements
    if (!target.closest('input, select, textarea, [contenteditable]')) {
        event.preventDefault();
    }
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start, end } = selectionRange.value;
    findOverlappingHighlights(start, end).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < end && n.end > start));
    notes.value.push({
        id: Date.now(), text: selectedText.value, selectedText: selectedText.value,
        note: noteInputText.value.trim(), start, end, part: 'Part 1',
    });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };

// ─── Highlight click / delete ──────────────────────────────────────────────
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement | null;
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
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

// ── BUG 1 FIX ────────────────────────────────────────────────────────────
// confirmDeleteHighlight was already correct — the problem was that no button
// ever called it. The delete tooltip in the Teleport section below is new.
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value === null) return;
    const id = highlightToDelete.value;
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
    deleteHighlight(id);
};

// ─── Note hover tooltip ────────────────────────────────────────────────────
const handleNoteMouseEnter = (event: MouseEvent) => {

    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const mark = target.closest('mark.note-highlight') as HTMLElement | null;
    if (!mark) return;
    const nid = mark.getAttribute('data-note-id');
    if (!nid) return;
    const note = notes.value.find((n) => n.id === parseInt(nid, 10));
    if (!note) return;
    const rect = mark.getBoundingClientRect();
    let y = rect.top - 58;
    if (y < 10) y = rect.bottom + 8;
    noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
    hoveredNoteId.value = note.id;
    hoveredNoteText.value = note.note;
    showNoteTooltip.value = true;
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((event.target as HTMLElement).closest('mark.note-highlight')) {
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
    if (hoveredNoteId.value === null) return;
    deleteNote(hoveredNoteId.value);
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

// ─── Global guards ─────────────────────────────────────────────────────────
const handleClickOutside = () => { if (showContextMenu.value) showContextMenu.value = false; };
const handleKeyDown = (e: KeyboardEvent) => { if (e.key === 'Escape') showContextMenu.value = false; };

// ─── Resize ────────────────────────────────────────────────────────────────
const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const pct = ((e.clientX - containerRef.value.getBoundingClientRect().left) / containerRef.value.offsetWidth) * 100;
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
    <!--
        @mouseup fires for both panels.
        Bug 2 is handled inside handleContentTextSelect via passagePanelRef.
    -->
    <div ref="contentTextRef"  @click="handleContentClick"
         class="flex flex-col h-screen overflow-hidden">

        <!-- Top Header Bar -->
        <div class="flex border-b border-gray-200 bg-white shrink-0">
            <div class="flex items-center gap-2 px-4 py-2 w-1/2 border-r border-gray-200">
                <div class="flex h-8 w-8 items-center justify-center rounded ">
                    <svg class="h-4 w-4 text-grey" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332-.477-4.5-1.253" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800"
                       data-segment-id="segment_1"
                       v-html="getHighlightedSegmentById('segment_1')"></p>
                </div>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 w-1/2">
                <div class="flex h-8 w-8 items-center justify-center rounded">
                    <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800"
                       data-segment-id="segment_4"
                       v-html="getHighlightedSegmentById('segment_4')"></p>
                    <p class="text-xs text-gray-500"
                       data-segment-id="segment_5"
                       v-html="getHighlightedSegmentById('segment_5')"></p>
                </div>
            </div>
        </div>

        <!-- Main Split Panel -->
        <div ref="containerRef" class="flex flex-1 overflow-hidden"
             :class="{ 'select-none': isResizing }">

            <!--
                LEFT: Reading Passage
                ref="passagePanelRef" — Bug 2 anchor. Only selections fully
                inside this div trigger the highlight/note context menu.
            -->
            <div ref="passagePanelRef"
                 class="overflow-y-auto bg-white px-6 py-4 shrink-0" @mouseup="handleContentTextSelect($event)"
                 :style="{ width: `${leftPanelWidth}%` }">
                <h2 class="mb-1 text-sm font-bold text-gray-900">
                    <span data-segment-id="segment_2"
                          v-html="getHighlightedSegmentById('segment_2')"></span>
                </h2>
                <p class="mb-4 text-xs italic text-gray-500">
                    <span data-segment-id="segment_3"
                          v-html="getHighlightedSegmentById('segment_3')"></span>
                </p>
                <div class="text-justify text-sm leading-relaxed text-gray-700 select-text whitespace-pre-wrap"
                     :style="contentZoom">
                    <span data-segment-id="segment_0"
                          v-html="getHighlightedSegmentById('segment_0')"></span>
                </div>
            </div>

            <!-- Resize Handle -->
            <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center
                        border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                @mousedown="startResize" title="Drag to resize panels">
                <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                    <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                    </svg>
                </div>
            </div>

            <!-- RIGHT: Questions -->
            <div class="flex flex-1 flex-col overflow-y-auto bg-gray-50 pb-32 " @mouseup="handleContentTextSelect($event)">
                <div class="space-y-4 p-4" >

                    <!-- ===== QUESTIONS 1–5 ===== -->
                    <div class="rounded-xl  bg-white p-5">
                        <div class="mb-4 rounded-lg  p-4">
                            <h3 class="mb-1 text-sm font-bold ">
                                <span data-segment-id="segment_6"
                                      v-html="getHighlightedSegmentById('segment_6')"></span>
                            </h3>
                            <p class="text-xs text-gray-600">
                                <span data-segment-id="segment_7"  v-html="getHighlightedSegmentById('segment_7')"></span><br />
                                <span data-segment-id="segment_8"  v-html="getHighlightedSegmentById('segment_8')"></span><br />
                                <span data-segment-id="segment_9"  v-html="getHighlightedSegmentById('segment_9')"></span>
                            </p>
                        </div>
                        <p class="mb-3 text-center text-sm font-semibold text-gray-800">
                            <span data-segment-id="segment_10"
                                  v-html="getHighlightedSegmentById('segment_10')"></span>
                        </p>

                        <!-- Q1 -->
                        <div id="question-1" class="relative mb-3 flex flex-wrap items-center gap-1 pr-8 text-sm text-gray-800"
                             @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                            <span data-segment-id="segment_11" v-html="getHighlightedSegmentById('segment_11')"></span>
                            <span class="inline-flex items-center gap-1">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold text-grey"></span>
                                <input v-model="answers.q1" type="text"
                                       class="w-36 border border-gray-400 bg-transparent px-1 py-0.5 text-center text-sm  focus:outline-none"
                                       placeholder="1" />
                            </span>
                            <button v-show="hoveredQuestion === 1 || isQuestionFlagged(1)" @click.stop="toggleFlag(1)"
                                    class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(1) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>

                        <!-- Q2 -->
                        <div id="question-2" class="relative mb-3 flex flex-wrap items-center gap-1 pr-8 text-sm text-gray-800"
                             @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                            <span data-segment-id="segment_13" v-html="getHighlightedSegmentById('segment_13')"></span>
                            <span class="inline-flex items-center gap-1">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold text-grey"></span>
                                <input v-model="answers.q2" type="text"
                                       class="w-36 border border-gray-400  px-1 py-0.5 text-center text-sm  focus:outline-none"
                                       placeholder="2" />
                            </span>
                            <span data-segment-id="segment_15" v-html="getHighlightedSegmentById('segment_15')"></span>
                            <button v-show="hoveredQuestion === 2 || isQuestionFlagged(2)" @click.stop="toggleFlag(2)"
                                    class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(2) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>

                        <!-- Q3 -->
                        <div id="question-3" class="relative mb-3 flex flex-wrap items-center gap-1 pr-8 text-sm text-gray-800"
                             @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                            <span data-segment-id="segment_16" v-html="getHighlightedSegmentById('segment_16')"></span>
                            <span class="inline-flex items-center gap-1">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold text-grey"></span>
                                <input v-model="answers.q3" type="text"
                                       class="w-36  border-gray-400 border px-1 py-0.5 text-center text-sm  focus:outline-none"
                                       placeholder="3" />
                            </span>
                            <span data-segment-id="segment_18" v-html="getHighlightedSegmentById('segment_18')"></span>
                            <button v-show="hoveredQuestion === 3 || isQuestionFlagged(3)" @click.stop="toggleFlag(3)"
                                    class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(3) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>

                        <!-- Q4 -->
                        <div id="question-4" class="relative mb-3 flex flex-wrap items-center gap-1 pr-8 text-sm text-gray-800"
                             @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                            <span data-segment-id="segment_19" v-html="getHighlightedSegmentById('segment_19')"></span>
                            <span class="inline-flex items-center gap-1">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold text-grey"></span>
                                <input v-model="answers.q4" type="text"
                                       class="w-36  border-gray-400 border px-1 py-0.5 text-center text-sm  focus:outline-none"
                                       placeholder="4" />
                            </span>
                            <span data-segment-id="segment_21" v-html="getHighlightedSegmentById('segment_21')"></span>
                            <button v-show="hoveredQuestion === 4 || isQuestionFlagged(4)" @click.stop="toggleFlag(4)"
                                    class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(4) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>

                        <!-- Q5 -->
                        <div id="question-5" class="relative flex flex-wrap items-center gap-1 pr-8 text-sm text-gray-800"
                             @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                            <span data-segment-id="segment_22" v-html="getHighlightedSegmentById('segment_22')"></span>
                            <span class="inline-flex items-center gap-1">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold text-grey"></span>
                                <input v-model="answers.q5" type="text"
                                       class="w-36  border-gray-400 border px-1 py-0.5 text-center text-sm  focus:outline-none"
                                       placeholder="5" />
                            </span>
                            <span data-segment-id="segment_24" v-html="getHighlightedSegmentById('segment_24')"></span>
                            <button v-show="hoveredQuestion === 5 || isQuestionFlagged(5)" @click.stop="toggleFlag(5)"
                                    class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(5) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- ===== QUESTIONS 6–9: True/False/Not Given ===== -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <div class="mb-4 rounded-lg border p-4">
                            <h3 class="mb-1 text-sm font-bold text-grey-700">
                                <span data-segment-id="segment_25" v-html="getHighlightedSegmentById('segment_25')"></span>
                            </h3>
                            <p class="text-xs text-gray-600">
                                <span data-segment-id="segment_26" v-html="getHighlightedSegmentById('segment_26')"></span><br />
                                <span data-segment-id="segment_27" v-html="getHighlightedSegmentById('segment_27')"></span>
                            </p>
                            <p class="mt-2 text-xs text-gray-600">
                                <strong data-segment-id="segment_28" v-html="getHighlightedSegmentById('segment_28')"></strong>
                                &nbsp;<span data-segment-id="segment_29" v-html="getHighlightedSegmentById('segment_29')"></span><br />
                                <strong data-segment-id="segment_30" v-html="getHighlightedSegmentById('segment_30')"></strong>
                                &nbsp;<span data-segment-id="segment_31" v-html="getHighlightedSegmentById('segment_31')"></span><br />
                                <strong data-segment-id="segment_32" v-html="getHighlightedSegmentById('segment_32')"></strong>
                                &nbsp;<span data-segment-id="segment_33" v-html="getHighlightedSegmentById('segment_33')"></span>
                            </p>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(q, i) in trueFalseQuestions" :key="i"
                                 :id="`question-${i + 6}`"
                                 class="relative flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 pr-10"
                                 @mouseenter="hoveredQuestion = i + 6" @mouseleave="hoveredQuestion = null">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full  text-xs font-bold text-grey">{{ i + 6 }}</span>
                                <select v-model="answers[`q${i + 6}`]"
                                        class="w-32 shrink-0 rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-700 0 focus:outline-none">
                                    <option value="">-- Select --</option>
                                    <option value="TRUE">TRUE</option>
                                    <option value="FALSE">FALSE</option>
                                    <option value="NOT GIVEN">NOT GIVEN</option>
                                </select>
                                <p class="text-sm text-gray-700"
                                   :data-segment-id="`segment_${35 + i}`"
                                   v-html="getHighlightedSegment(q)"></p>
                                <button v-show="hoveredQuestion === i + 6 || isQuestionFlagged(i + 6)"
                                        @click.stop="toggleFlag(i + 6)"
                                        class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(i + 6) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== QUESTIONS 10–13: Short Answer ===== -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <div class="mb-4 rounded-lg border  p-4">
                            <h3 class="mb-1 text-sm font-bold ">
                                <span data-segment-id="segment_39" v-html="getHighlightedSegmentById('segment_39')"></span>
                            </h3>
                            <p class="text-xs text-gray-600">
                                <span data-segment-id="segment_40" v-html="getHighlightedSegmentById('segment_40')"></span><br />
                                <span data-segment-id="segment_41" v-html="getHighlightedSegmentById('segment_41')"></span><br />
                                <span data-segment-id="segment_42" v-html="getHighlightedSegmentById('segment_42')"></span>
                            </p>
                        </div>
                        <div class="space-y-4">

                            <!-- Q10 -->
                            <div id="question-10" class="relative pr-8"
                                 @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                <p class="mb-1 text-sm text-gray-800">
                                    <span data-segment-id="segment_43" v-html="getHighlightedSegmentById('segment_43')"></span>
                                </p>
                                <span class="inline-flex items-center gap-1">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold "></span>
                                    <input v-model="answers.q10" type="text"
                                           class="w-40  border-gray-400 border px-1 py-0.5 text-center text-sm  focus:outline-none"
                                           placeholder="10" />
                                </span>
                                <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)" @click.stop="toggleFlag(10)"
                                        class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(10) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>
                            </div>

                            <!-- Q11 -->
                            <div id="question-11" class="relative pr-8"
                                 @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                <p class="mb-1 text-sm text-gray-800">
                                    <span data-segment-id="segment_45" v-html="getHighlightedSegmentById('segment_45')"></span>
                                </p>
                                <span class="inline-flex items-center gap-1">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold "></span>
                                    <input v-model="answers.q11" type="text"
                                           class="w-40  border-gray-400 border px-1 py-0.5 text-center text-sm  focus:outline-none"
                                           placeholder="11" />
                                </span>
                                <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)" @click.stop="toggleFlag(11)"
                                        class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(11) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>
                            </div>

                            <!-- Q12 -->
                            <div id="question-12" class="relative pr-8"
                                 @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                <p class="mb-1 text-sm text-gray-800">
                                    <span data-segment-id="segment_47" v-html="getHighlightedSegmentById('segment_47')"></span>
                                </p>
                                <span class="inline-flex items-center gap-1">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold "></span>
                                    <input v-model="answers.q12" type="text"
                                           class="w-40  border-gray-400 border px-1 py-0.5 text-center text-sm  focus:outline-none"
                                           placeholder="12" />
                                </span>
                                <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)" @click.stop="toggleFlag(12)"
                                        class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(12) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>
                            </div>

                            <!-- Q13 -->
                            <div id="question-13" class="relative pr-8"
                                 @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                <p class="mb-1 text-sm text-gray-800">
                                    <span data-segment-id="segment_49" v-html="getHighlightedSegmentById('segment_49')"></span>
                                </p>
                                <span class="inline-flex items-center gap-1">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full  text-xs font-bold "></span>
                                    <input v-model="answers.q13" type="text"
                                           class="w-56 border border-gray-400  px-1 py-0.5 text-center text-sm  focus:outline-none"
                                           placeholder="13" />
                                </span>
                                <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)" @click.stop="toggleFlag(13)"
                                        class="absolute top-0.5 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(13) ? 'border-gray-300 text-red-500' : 'border-gray-200 text-gray-300 hover:text-gray-500'">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ===== Teleport: all floating UI ===== -->
        <Teleport to="body">
        <!-- Context menu (highlight / note) -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                            title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"/>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                            title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
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

            <!-- Note hover tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note input modal -->
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
                        class="border border-gray-900 bg-white px-3 py-0.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                        Cancel
                    </button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-0.5 text-xs font-medium text-grey transition-colors hover:bg-gray-800">
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

/* Ensure marks are visible inside gradient text */
.bg-clip-text mark[data-highlight-id] {
    -webkit-text-fill-color: initial !important;
    color: transparent;
    background-clip: padding-box !important;
    -webkit-background-clip: padding-box !important;
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

/* Bold placeholder styling for question inputs */
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
