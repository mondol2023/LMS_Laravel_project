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

// Resize
const PANEL_WIDTH_KEY = 'reading-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight
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
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// ── Answers ──────────────────────────────────────────────────────────────────
const answers = ref({
    q14: '' as string,
    q15: '' as string,
    q16: '' as string,
    q17: '' as string,
    q18: '' as string,
    q19: '',
    q20: '',
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

// ── Drag & Drop for Q14-18 ────────────────────────────────────────────────────
const draggedLetter = ref<string | null>(null);
const dragSource = ref<'bank' | number | null>(null);

const paragraphLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

const usedLetters = computed(() =>
    [answers.value.q14, answers.value.q15, answers.value.q16, answers.value.q17, answers.value.q18].filter(Boolean)
);

const bankLetters = computed(() =>
    paragraphLetters.filter((l) => !usedLetters.value.includes(l))
);

const getAnswerForQ = (qNum: number): string =>
    (answers.value as any)[`q${qNum}`] as string;

const setAnswerForQ = (qNum: number, val: string) => {
    (answers.value as any)[`q${qNum}`] = val;
};

const onDragStartFromBank = (event: DragEvent, letter: string) => {
    draggedLetter.value = letter;
    dragSource.value = 'bank';
    event.dataTransfer!.effectAllowed = 'move';
    event.dataTransfer!.setData('text/plain', letter);
};

const onDragStartFromSlot = (event: DragEvent, letter: string, qNum: number) => {
    draggedLetter.value = letter;
    dragSource.value = qNum;
    event.dataTransfer!.effectAllowed = 'move';
    event.dataTransfer!.setData('text/plain', letter);
};

const onDropToSlot = (event: DragEvent, targetQ: number) => {
    event.preventDefault();
    if (!draggedLetter.value) return;
    const incoming = draggedLetter.value;
    const currentInTarget = getAnswerForQ(targetQ);

    if (dragSource.value === 'bank') {
        setAnswerForQ(targetQ, incoming);
    } else if (typeof dragSource.value === 'number') {
        const sourceQ = dragSource.value as number;
        if (sourceQ !== targetQ) {
            setAnswerForQ(sourceQ, currentInTarget);
            setAnswerForQ(targetQ, incoming);
        }
    }
    draggedLetter.value = null;
    dragSource.value = null;
};

const onDropToBank = (event: DragEvent) => {
    event.preventDefault();
    if (draggedLetter.value && typeof dragSource.value === 'number') {
        setAnswerForQ(dragSource.value as number, '');
    }
    draggedLetter.value = null;
    dragSource.value = null;
};

const onDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.dataTransfer!.dropEffect = 'move';
};

// ── Passage ───────────────────────────────────────────────────────────────────
// Plain-text version used for offset calculations.
const passageText = `Airports continue to diversify their role in an effort to generate income. Are business meeting facilities the next step? Nigel Halpern, Anne Graham and Rob Davidson investigate.

A  In recent times developing commercial revenues has become more challenging for airports due to a combination of factors, such as increased competition from Internet shopping, restrictions on certain sales, such as tobacco, and new security procedures that have had an impact on the dwell time of passengers. Moreover, the global economic downturn has caused a reduction in passenger numbers while those that are travelling generally have less money to spend. This has meant that the share subsequently declined slightly. Meanwhile, the pressures to control the level of aeronautical revenues are as strong as ever due to the poor financial health of many airlines and the rapid rise of the low-cost carrier sector.

B  Some of the more obvious solutions to growing commercial revenues, such as extending the merchandising space or expanding the variety of shopping opportunities, have already been tried to their limit at many airports. A more radical solution is to find new sources of commercial revenue within the terminal, and this has been explored by many airports over the last decade or so. As a result, many terminals are now much more than just shopping malls and offer an array of entertainment, leisure, and beauty and wellness facilities. At this stage of facilities provision, the airport also has the possibility of taking on the role of the final destination rather than merely a facilitator of access.

C  At the same time, airports have been developing and expanding the range of services that they provide specifically for the business traveller in the terminal. This includes offering business centres that supply support services, meeting or conference rooms and other space for special events. Within this context, Jarach (2001) discusses how dedicated meetings facilities located within the terminal and managed directly by the airport operator may be regarded as an expansion of the concept of airline lounges or as a way to reconvert abandoned or underused areas of terminal and managed directly by the airport hotels and other facilities offered in the surrounding area of the airport that had the potential to take on this role and become active as a business space (McNeill, 2009).

D  When an airport location can be promoted as a business venue, this may increase the overall appeal of the airport and help it become more competitive in both attracting and retaining airlines and their passengers. In particular, the presence of meeting facilities could become one of the determining factors taken into consideration when business people are choosing airlines and where they change their planes. This enhanced attractiveness itself may help to improve the airport operator's financial position and future prospects, but clearly, this will be dependent on the competitive advantage that the airport is able to achieve in comparison with other venues.

E  In 2011, an online airport survey was conducted and some of the areas investigated included the provision and use of meeting facilities at airports and the perceived role and importance of these facilities in generating income and raising passenger numbers. In total, there were responses from staff at 154 airports and 68% of these answered "yes" to the question: Does your airport own and have meetings facilities available for hire? The existence of meeting facilities, therefore, seems high at airports. In addition, 28% of respondents that did not have meeting facilities stated that they were likely to invest in them during the next five years. The survey also asked to what extent respondents agreed or disagreed with a number of statements about the meeting facilities at their airport. 49% of respondents agreed that they would invest more in the immediate future. These are fairly high proportions considering the recent economic climate.

F  The survey also asked airports with meeting facilities to estimate what proportion of users are from the local area, i.e. within a 90-minute drive from the airport, or from abroad. Their findings show that meeting facilities provided by the majority of respondents tend to serve local versus non-local or foreign needs. 63% of respondents estimated that over 60% of users are from the local area. Only 3% estimated that over 80% of users are from abroad. It is therefore not surprising that the facilities are of limited importance when it comes to increasing use of flights at the airports: 16% of respondents estimated that none of the users of their meeting facilities uses flights when travelling to or from them, while 56% estimated that 20% or fewer of the users of their facilities use flights.

G  The survey asked respondents with meeting facilities to estimate how much revenue their airport earned from its meeting facilities during the last financial year. Average revenue per airport was just $12,959. Meeting facilities are effectively a non-aeronautical source of airport revenue. Only 1% of respondents generated more than 20% non-aeronautical revenue from their meetings facilities; none generated more than 40%. Given the focus on local demand, it is not surprising that less than a third of respondents agreed that their meeting facilities support business and tourism development in their home region or country.

H  The findings of this study suggest that few airports provide meetings facilities as a serious commercial venture. It may be that, as owners of large property, space is available for meeting facilities at airports and could play an important role in serving the needs of the airport, its partners, and stakeholders such as government and the local community. Thus, while the local orientation means that competition with other airports is likely to be minimal, competition with local providers of meetings facilities is likely to be much greater.`;

// HTML version for display only.
const passageHtml = `<i>Airports continue to diversify their role in an effort to generate income. Are business meeting facilities the next step? Nigel Halpern, Anne Graham and Rob Davidson investigate.</i><br/><br/>
<b>A</b>&nbsp;&nbsp;In recent times developing commercial revenues has become more challenging for airports due to a combination of factors, such as increased competition from Internet shopping, restrictions on certain sales, such as tobacco, and new security procedures that have had an impact on the dwell time of passengers. Moreover, the global economic downturn has caused a reduction in passenger numbers while those that are travelling generally have less money to spend. This has meant that the share subsequently declined slightly. Meanwhile, the pressures to control the level of aeronautical revenues are as strong as ever due to the poor financial health of many airlines and the rapid rise of the low-cost carrier sector.<br/><br/>
<b>B</b>&nbsp;&nbsp;Some of the more obvious solutions to growing commercial revenues, such as extending the merchandising space or expanding the variety of shopping opportunities, have already been tried to their limit at many airports. A more radical solution is to find new sources of commercial revenue within the terminal, and this has been explored by many airports over the last decade or so. As a result, many terminals are now much more than just shopping malls and offer an array of entertainment, leisure, and beauty and wellness facilities. At this stage of facilities provision, the airport also has the possibility of taking on the role of the final destination rather than merely a facilitator of access.<br/><br/>
<b>C</b>&nbsp;&nbsp;At the same time, airports have been developing and expanding the range of services that they provide specifically for the business traveller in the terminal. This includes offering business centres that supply support services, meeting or conference rooms and other space for special events. Within this context, Jarach (2001) discusses how dedicated meetings facilities located within the terminal and managed directly by the airport operator may be regarded as an expansion of the concept of airline lounges or as a way to reconvert abandoned or underused areas of terminal and managed directly by the airport hotels and other facilities offered in the surrounding area of the airport that had the potential to take on this role and become active as a business space (McNeill, 2009).<br/><br/>
<b>D</b>&nbsp;&nbsp;When an airport location can be promoted as a business venue, this may increase the overall appeal of the airport and help it become more competitive in both attracting and retaining airlines and their passengers. In particular, the presence of meeting facilities could become one of the determining factors taken into consideration when business people are choosing airlines and where they change their planes. This enhanced attractiveness itself may help to improve the airport operator's financial position and future prospects, but clearly, this will be dependent on the competitive advantage that the airport is able to achieve in comparison with other venues.<br/><br/>
<b>E</b>&nbsp;&nbsp;In 2011, an online airport survey was conducted and some of the areas investigated included the provision and use of meeting facilities at airports and the perceived role and importance of these facilities in generating income and raising passenger numbers. In total, there were responses from staff at 154 airports and 68% of these answered &ldquo;yes&rdquo; to the question: Does your airport own and have meetings facilities available for hire? The existence of meeting facilities, therefore, seems high at airports. In addition, 28% of respondents that did not have meeting facilities stated that they were likely to invest in them during the next five years. The survey also asked to what extent respondents agreed or disagreed with a number of statements about the meeting facilities at their airport. 49% of respondents agreed that they would invest more in the immediate future. These are fairly high proportions considering the recent economic climate.<br/><br/>
<b>F</b>&nbsp;&nbsp;The survey also asked airports with meeting facilities to estimate what proportion of users are from the local area, i.e. within a 90-minute drive from the airport, or from abroad. Their findings show that meeting facilities provided by the majority of respondents tend to serve local versus non-local or foreign needs. 63% of respondents estimated that over 60% of users are from the local area. Only 3% estimated that over 80% of users are from abroad. It is therefore not surprising that the facilities are of limited importance when it comes to increasing use of flights at the airports: 16% of respondents estimated that none of the users of their meeting facilities uses flights when travelling to or from them, while 56% estimated that 20% or fewer of the users of their facilities use flights.<br/><br/>
<b>G</b>&nbsp;&nbsp;The survey asked respondents with meeting facilities to estimate how much revenue their airport earned from its meeting facilities during the last financial year. Average revenue per airport was just $12,959. Meeting facilities are effectively a non-aeronautical source of airport revenue. Only 1% of respondents generated more than 20% non-aeronautical revenue from their meetings facilities; none generated more than 40%. Given the focus on local demand, it is not surprising that less than a third of respondents agreed that their meeting facilities support business and tourism development in their home region or country.<br/><br/>
<b>H</b>&nbsp;&nbsp;The findings of this study suggest that few airports provide meetings facilities as a serious commercial venture. It may be that, as owners of large property, space is available for meeting facilities at airports and could play an important role in serving the needs of the airport, its partners, and stakeholders such as government and the local community. Thus, while the local orientation means that competition with other airports is likely to be minimal, competition with local providers of meetings facilities is likely to be much greater.`;

// ── Text segments ─────────────────────────────────────────────────────────────
// KEY RULE: every segment offset must be well beyond passage plain-text length
// passageText.length ~ 3700 chars, so we start question segments at 10000.
// Each segment is spaced 500 apart to guarantee zero overlap regardless of text length.
const textSegments = ref([
    { id: 'passage',        text: passageText, offset: 0 },
{id:'psg-title', text: 'The changing role of airports', offset: 100 }, 
    // Q14-18 block
    { id: 'q14-18-title',  text: 'Questions 14-18',                                                                                     offset: 10000 },
    { id: 'q14-18-inst1',  text: 'Which paragraph contains the following information?',                                                  offset: 10100 },
    { id: 'q14-18-inst2',  text: 'Write the correct letter, A-H, in the boxes below.',                                                  offset: 10200 },
    { id: 'q14-text',      text: 'evidence that a significant number of airports provide meeting facilities.',                            offset: 10300 },
    { id: 'q15-text',      text: 'a statement regarding the fact that no further developments are possible in some areas of airport trade.', offset: 10500 },
    { id: 'q16-text',      text: 'reference to the low level of income that meeting facilities produce for airports.',                   offset: 10700 },
    { id: 'q17-text',      text: 'mention of the impact of budget airlines on airport income.',                                          offset: 10900 },
    { id: 'q18-text',      text: 'examples of airport premises that might be used for business purposes.',                               offset: 11100 },

    // Q19-22 block
    { id: 'q19-22-title',  text: 'Questions 19-22',                                                                                     offset: 11400 },
    { id: 'q19-22-inst1',  text: 'Complete the sentences below.',                                                                        offset: 11500 },
    { id: 'q19-22-inst2',  text: 'Choose NO MORE THAN TWO WORDS from the text for each answer.',                                         offset: 11600 },
    { id: 'q19-pre',       text: 'The length of time passengers spend shopping at airports has been affected by updated',                 offset: 11700 },
    { id: 'q20-pre',       text: 'Airports with a wide range of recreational facilities can become a',                                   offset: 11900 },
    { id: 'q20-post',      text: 'for people rather than a means to travel.',                                                            offset: 12050 },
    { id: 'q21-pre',       text: 'Both passengers and',                                                                                 offset: 12200 },
    { id: 'q21-post',      text: 'may feel encouraged to use and develop a sense of loyalty towards airports that market their business services.', offset: 12300 },
    { id: 'q22-pre',       text: 'Airports that supply meeting facilities may need to develop a',                                        offset: 12600 },
    { id: 'q22-post',      text: 'over other venues.',                                                                                  offset: 12750 },

    // Q23-26 block
    { id: 'q23-26-title',   text: 'Questions 23-26',                                                                                    offset: 13100 },
    { id: 'q23-26-inst1',   text: 'Complete the summary below.',                                                                         offset: 13200 },
    { id: 'q23-26-inst2',   text: 'Choose NO MORE THAN TWO WORDS from the text for each answer.',                                        offset: 13300 },
    { id: 'q23-26-heading', text: 'Survey Findings',                                                                                    offset: 13400 },
    { id: 'q23-pre',        text: 'Despite financial constraints due to the',                                                            offset: 13500 },
    { id: 'q23-post',       text: ', a significant percentage of airports provide and wish to further support business meeting facilities. Also, just under 30% of the airports surveyed plan to provide these facilities within', offset: 13650 },
    { id: 'q24-post',       text: '; however, the main users of the facilities are',                                                    offset: 13950 },
    { id: 'q25-post',       text: 'and as many as 16% of respondents to the survey stated that their users did not take any',            offset: 14150 },
    { id: 'q26-post',       text: 'at the airport.',                                                                                    offset: 14450 },
]);

// ── Highlight helpers ─────────────────────────────────────────────────────────
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

const getPlainTextLength = (html: string): number =>
    html.replace(/<[^>]*>/g, '').length;

const applyAnnotations = (segText: string, segOffset: number): string => {
    const segLen = getPlainTextLength(segText);
    const segEnd = segOffset + segLen;

    const hl = highlights.value.filter((h) => h.start_offset < segEnd && h.end_offset > segOffset);
    const nt = notes.value.filter((n) => n.start < segEnd && n.end > segOffset);
    if (!hl.length && !nt.length) return segText;

    const anns = [
        ...hl.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...nt.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    let result = segText;
    for (const a of anns) {
        const ps = Math.max(0, a.start - segOffset);
        const pe = Math.min(segLen, a.end - segOffset);
        if (ps >= pe) continue;
        const hs = plainToHtmlOffset(result, ps);
        const he = plainToHtmlOffset(result, pe);
        const mid = result.substring(hs, he);
        result =
            result.substring(0, hs) +
            (a.type === 'note'
                ? `<mark class="highlight-${a.color} note-highlight" data-note-id="${a.id}">${mid}</mark>`
                : `<mark class="highlight-${a.color}" data-highlight-id="${a.id}">${mid}</mark>`) +
            result.substring(he);
    }
    return result;
};

const getHighlightedSegmentById = (id: string): string => {
    const seg = textSegments.value.find((s) => s.id === id);
    if (!seg) return '';
    return applyAnnotations(seg.text, seg.offset);
};

// Passage uses passageHtml for display but passageText plain length for offsets.
const getHighlightedPassage = (): string => {
    const seg = textSegments.value.find((s) => s.id === 'passage')!;
    return applyAnnotations(passageHtml, seg.offset);
};

// ── Expose ────────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;

watch(leftPanelWidth, (v) => localStorage.setItem(PANEL_WIDTH_KEY, String(v)));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ── Selection / highlight interaction ────────────────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected  = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect  = range?.getBoundingClientRect();
        if (!rect || !contentTextRef.value) return;

        const vw = window.innerWidth;
        contextMenuPosition.value = {
            x: Math.min(Math.max(rect.left + rect.width / 2, 80), vw - 80),
            y: Math.max(rect.top - 58, 10),
        };
        showContextMenu.value = true;

        if (!selection || !range) return;

        // Walk up to find the span that carries the segment data
        let node: Node | null = range.startContainer;
        while (node && node.nodeType !== Node.ELEMENT_NODE) node = node.parentNode;
        while (
            node &&
            !(node as Element).classList?.contains('text-gray-700') &&
            !(node as Element).classList?.contains('text-gray-800') &&
            !(node as Element).classList?.contains('text-gray-900') &&
            !(node as Element).classList?.contains('select-text') &&
            !(node as Element).classList?.contains('passage-text')
        ) {
            node = node.parentNode;
        }
        if (!node) return;

        const el = node as Element;
        const spanPlain = el.textContent?.trim().replace(/\s+/g, ' ') ?? '';
        const isPassage = el.classList.contains('passage-text') || spanPlain.length > 500;

        let seg: { id: string; text: string; offset: number } | undefined;
        if (isPassage) {
            seg = textSegments.value.find((s) => s.id === 'passage');
        } else {
            seg = textSegments.value.find((s) => {
                const sp = s.text.replace(/<[^>]*>/g, '').trim().replace(/\s+/g, ' ');
                return sp === spanPlain || sp.includes(spanPlain) || spanPlain.includes(sp);
            });
        }
        if (!seg) return;

        const preRange = document.createRange();
        preRange.selectNodeContents(el);
        preRange.setEnd(range.startContainer, range.startOffset);
        const plainOffset = preRange.toString().length;

        selectedText.value   = selected;
        selectionRange.value = { start: seg.offset + plainOffset, end: seg.offset + plainOffset + selected.length };
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
    const mw = 320, mh = 180, pad = 10;
    const sel = window.getSelection();
    let x: number, y: number;
    if (sel && sel.rangeCount > 0) {
        const r = sel.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    } else {
        x = contextMenuPosition.value.x; y = contextMenuPosition.value.y + 70;
    }
    const vw = window.innerWidth, vh = window.innerHeight, hw = mw / 2;
    if (x - hw < pad) x = hw + pad;
    else if (x + hw > vw - pad) x = vw - hw - pad;
    if (y + mh > vh - pad) {
        if (sel && sel.rangeCount > 0) y = sel.getRangeAt(0).getBoundingClientRect().top - mh - 10;
        if (y < pad) y = pad;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true; showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start, end } = selectionRange.value;
    findOverlappingHighlights(start, end).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < end && n.end > start));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start, end });
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
    const hm = (e.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement | null;
    if (hm) {
        const hid = hm.getAttribute('data-highlight-id');
        if (hid) {
            e.stopPropagation();
            const r = hm.getBoundingClientRect();
            deleteTooltipPosition.value = { x: r.left + r.width / 2, y: r.bottom + 8 };
            highlightToDelete.value = parseInt(hid, 10);
            showDeleteTooltip.value = true; showContextMenu.value = false;
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
    const nm = (e.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement | null;
    if (nm) {
        const nid = nm.getAttribute('data-note-id');
        if (nid) {
            const id = parseInt(nid, 10);
            const note = notes.value.find((n) => n.id === id);
            if (note) {
                const r = nm.getBoundingClientRect();
                let y = r.top - 58; if (y < 10) y = r.bottom + 8;
                noteTooltipPosition.value = { x: r.left + r.width / 2, y };
                hoveredNoteId.value = id; hoveredNoteText.value = note.note; showNoteTooltip.value = true;
            }
        }
    }
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

// Resize
const startResize  = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const cr = containerRef.value.getBoundingClientRect();
    const pct = ((e.clientX - cr.left) / cr.width) * 100;
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

        <!-- Part Header -->
        <div class="  mx-5 mt-4 border-gray-400  px-4 py-2" style="background-color: #F1F2EC;">
            <p class="text-sm font-bold text-gray-900 select-text">Part 1</p>
            <p class="text-sm text-gray-700 select-text">Read the text and answer questions 14–26.</p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                 :class="{ 'select-none': isResizing }">

                <!-- ═══════ LEFT – Passage ═══════ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                     :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl text-center font-bold">
                            <span v-html="getHighlightedSegmentById('psg-title')" ></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="p-4">
                            <div class="text-justify leading-relaxed text-gray-700">
                                <span class="passage-text text-base text-gray-700 select-text"
                                      v-html="getHighlightedPassage()"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center
                            border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                     @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ═══════ RIGHT – Questions ═══════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                     :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ══════════════════════════════
     Q14-18  SELECT DROPDOWNS (replacing drag & drop)
══════════════════════════════ -->
<div class="p-6">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900 mb-2">
            <span class="text-gray-700 select-text"
                  v-html="getHighlightedSegmentById('q14-18-title')"></span>
        </h3>
        <p class="mb-1 text-sm leading-relaxed text-gray-700 select-text"
           v-html="getHighlightedSegmentById('q14-18-inst1')"></p>
        <p class="mb-4 text-sm leading-relaxed text-gray-700 select-text"
           v-html="getHighlightedSegmentById('q14-18-inst2')"></p>
    </div>

    <!-- Q14 -->
    <div id="question-14" class="relative bg-white p-2 mb-3"
         @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
        <div class="flex items-start gap-2">
            <span class="shrink-0 text-base font-bold text-gray-900">14.</span>
            <span class="text-base text-gray-900 select-text flex-1"
                  v-html="getHighlightedSegmentById('q14-text')"></span>
            <select v-model="answers.q14"
                    class="shrink-0 ml-2 border border-gray-900 px-3 py-1 text-sm bg-white focus:border-black focus:outline-none cursor-pointer">
                <option value="">select</option>
                <option v-for="letter in paragraphLetters" :key="letter" :value="letter">
                    {{ letter }}
                </option>
            </select>
        </div>
        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)" @click.stop="toggleFlag(14)"
                class="absolute top-2 -right-8 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
        </button>
    </div>

    <!-- Q15 -->
    <div id="question-15" class="relative bg-white p-2 mb-3"
         @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
        <div class="flex items-start gap-2">
            <span class="shrink-0 text-base font-bold text-gray-900">15.</span>
            <span class="text-base text-gray-900 select-text flex-1"
                  v-html="getHighlightedSegmentById('q15-text')"></span>
            <select v-model="answers.q15"
                    class="shrink-0 ml-2 border border-gray-900 px-3 py-1 text-sm bg-white focus:border-black focus:outline-none cursor-pointer">
                <option value="">select</option>
                <option v-for="letter in paragraphLetters" :key="letter" :value="letter">
                    {{ letter }}
                </option>
            </select>
        </div>
        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)" @click.stop="toggleFlag(15)"
                class="absolute top-2 -right-8 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
        </button>
    </div>

    <!-- Q16 -->
    <div id="question-16" class="relative bg-white p-2 mb-3"
         @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
        <div class="flex items-start gap-2">
            <span class="shrink-0 text-base font-bold text-gray-900">16.</span>
            <span class="text-base text-gray-900 select-text flex-1"
                  v-html="getHighlightedSegmentById('q16-text')"></span>
            <select v-model="answers.q16"
                    class="shrink-0 ml-2 border border-gray-900 px-3 py-1 text-sm bg-white focus:border-black focus:outline-none cursor-pointer">
                <option value="">select</option>
                <option v-for="letter in paragraphLetters" :key="letter" :value="letter">
                    {{ letter }}
                </option>
            </select>
        </div>
        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)" @click.stop="toggleFlag(16)"
                class="absolute top-2 -right-8 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
        </button>
    </div>

    <!-- Q17 -->
    <div id="question-17" class="relative bg-white p-2 mb-3"
         @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
        <div class="flex items-start gap-2">
            <span class="shrink-0 text-base font-bold text-gray-900">17.</span>
            <span class="text-base text-gray-900 select-text flex-1"
                  v-html="getHighlightedSegmentById('q17-text')"></span>
            <select v-model="answers.q17"
                    class="shrink-0 ml-2 border border-gray-900 px-3 py-1 text-sm bg-white focus:border-black focus:outline-none cursor-pointer">
                <option value="">select</option>
                <option v-for="letter in paragraphLetters" :key="letter" :value="letter">
                    {{ letter }}
                </option>
            </select>
        </div>
        <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)" @click.stop="toggleFlag(17)"
                class="absolute top-2 -right-8 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
        </button>
    </div>

    <!-- Q18 -->
    <div id="question-18" class="relative bg-white p-2 mb-3"
         @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
        <div class="flex items-start gap-2">
            <span class="shrink-0 text-base font-bold text-gray-900">18.</span>
            <span class="text-base text-gray-900 select-text flex-1"
                  v-html="getHighlightedSegmentById('q18-text')"></span>
            <select v-model="answers.q18"
                    class="shrink-0 ml-2 border border-gray-900 px-3 py-1 text-sm bg-white focus:border-black focus:outline-none cursor-pointer">
                <option value="">select</option>
                <option v-for="letter in paragraphLetters" :key="letter" :value="letter">
                    {{ letter }}
                </option>
            </select>
        </div>
        <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)" @click.stop="toggleFlag(18)"
                class="absolute top-2 -right-8 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
        </button>
    </div>
</div>

                            <!-- ══════════════════════════════
                                 Q19-22  Sentence completion
                            ══════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <span class="text-gray-700 select-text"
                                              v-html="getHighlightedSegmentById('q19-22-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700 select-text"
                                       v-html="getHighlightedSegmentById('q19-22-inst1')"></p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700 select-text"
                                       v-html="getHighlightedSegmentById('q19-22-inst2')"></p>
                                </div>

                                <div class="space-y-5">
                                    <!-- Q19 -->
                                    <div id="question-19" class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                         @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
                                        <span class="shrink-0 text-base font-bold text-gray-900">•</span>
                                        <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q19-pre')"></span>
                                        <input v-model="answers.q19" type="text"
                                               class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                               style="width:160px" placeholder="19"
                                               spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
                                        <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)" @click.stop="toggleFlag(19)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>

                                    <!-- Q20 -->
                                    <div id="question-20" class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                         @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                        <span class="shrink-0 text-base font-bold text-gray-900">•</span>
                                        <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q20-pre')"></span>
                                        <input v-model="answers.q20" type="text"
                                               class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                               style="width:160px" placeholder="20"
                                               spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
                                        <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q20-post')"></span>
                                        <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)" @click.stop="toggleFlag(20)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>

                                    <!-- Q21 -->
                                    <div id="question-21" class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                         @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                        <span class="shrink-0 text-base font-bold text-gray-900">•</span>
                                        <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q21-pre')"></span>
                                        <input v-model="answers.q21" type="text"
                                               class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                               style="width:160px" placeholder="21"
                                               spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
                                        <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q21-post')"></span>
                                        <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)" @click.stop="toggleFlag(21)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>

                                    <!-- Q22 -->
                                    <div id="question-22" class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                         @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <span class="shrink-0 text-base font-bold text-gray-900">•</span>
                                        <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q22-pre')"></span>
                                        <input v-model="answers.q22" type="text"
                                               class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                               style="width:160px" placeholder="22"
                                               spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
                                        <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q22-post')"></span>
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)" @click.stop="toggleFlag(22)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Questions 23-26: Summary completion ──────────────────────────────── -->
<div class="p-6">
    <div class="mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">
            <span class="text-gray-700 select-text"
                  v-html="getHighlightedSegmentById('q23-26-title')"></span>
        </h3>
        <p class="mb-1 text-sm text-gray-700 select-text"
           v-html="getHighlightedSegmentById('q23-26-inst1')"></p>
        <p class="mb-4 text-sm text-gray-700 select-text"
           v-html="getHighlightedSegmentById('q23-26-inst2')"></p>
        <p class="mb-3 text-sm font-bold text-gray-900 select-text"
           v-html="getHighlightedSegmentById('q23-26-heading')"></p>
    </div>

    <!-- Summary paragraph with inline input fields -->
    <div class="border border-gray-300 rounded p-4 bg-gray-50 text-base text-gray-900 leading-loose">
        <span class="select-text" v-html="getHighlightedSegmentById('q23-pre')"></span>

        <!-- Q23 with inline bookmark button -->
        <span id="question-23" 
              class="inline-flex items-center align-middle relative mx-1"
              @mouseenter="hoveredQuestion = 23" 
              @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q23" type="text"
                   class="question-input inline-block border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                   style="width: 160px" placeholder="23"
                   spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 23 || isQuestionFlagged(23) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(23)"
                :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>

        <span class="select-text" v-html="getHighlightedSegmentById('q23-post')"></span>

        <!-- Q24 with inline bookmark button -->
        <span id="question-24" 
              class="inline-flex items-center align-middle relative mx-1"
              @mouseenter="hoveredQuestion = 24" 
              @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q24" type="text"
                   class="question-input inline-block border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                   style="width: 160px" placeholder="24"
                   spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 24 || isQuestionFlagged(24) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(24)"
                :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>

        <span class="select-text" v-html="getHighlightedSegmentById('q24-post')"></span>

        <!-- Q25 with inline bookmark button -->
        <span id="question-25" 
              class="inline-flex items-center align-middle relative mx-1"
              @mouseenter="hoveredQuestion = 25" 
              @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q25" type="text"
                   class="question-input inline-block border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                   style="width: 160px" placeholder="25"
                   spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 25 || isQuestionFlagged(25) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(25)"
                :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>

        <span class="select-text" v-html="getHighlightedSegmentById('q25-post')"></span>

        <!-- Q26 with inline bookmark button -->
        <span id="question-26" 
              class="inline-flex items-center align-middle relative mx-1"
              @mouseenter="hoveredQuestion = 26" 
              @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q26" type="text"
                   class="question-input inline-block border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                   style="width: 160px" placeholder="26"
                   spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"/>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 26 || isQuestionFlagged(26) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(26)"
                :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>

        <span class="select-text" v-html="getHighlightedSegmentById('q26-post')"></span>
    </div>
</div>

                        </div>
                    </div>
                </div><!-- /answer-panel -->
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         Teleported overlays — identical to original
    ══════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div class="highlight-tooltip pointer-events-auto fixed z-9999"
                 :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
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
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-9999"
                 :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999"
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

        <div v-if="showNoteInput"
             class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
             :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
             @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                       placeholder="Write your note here..."
                       class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                       @keyup.enter="saveNote" @keyup.escape="cancelNote"/>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                        class="border-2 border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                        class="bg-black px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.select-text {
    user-select: text; -webkit-user-select: text; -moz-user-select: text; -ms-user-select: text;
}
.select-none {
    user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;
    cursor: col-resize;
}

.passage-panel { width: 100%; }
.answer-panel  { width: 100%; }
@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel  { width: calc(100% - var(--panel-width) - 0.5rem); }
}

/* ── Drag & drop ─────────────────────────────────────────────────────────── */
.letter-chip {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem;
    border: 2px solid #374151; border-radius: 4px;
    background: #fff; font-weight: 700; font-size: 0.875rem; color: #111827;
    cursor: grab; user-select: none;
    transition: background 0.15s, box-shadow 0.15s;
}
.letter-chip:active  { cursor: grabbing; }
.letter-chip:hover   { background: #f3f4f6; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }

.letter-chip-placeholder {
    display: inline-flex; width: 2rem; height: 2rem;
    border: 2px dashed #d1d5db; border-radius: 4px; background: transparent;
}

.drop-zone {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2.5rem; height: 2rem;
    border: 2px dashed #9ca3af; border-radius: 4px; background: #f9fafb;
    transition: border-color 0.15s, background 0.15s;
}
.drop-zone:hover { border-color: #374151; background: #f3f4f6; }
.drop-zone-filled { border-style: solid; border-color: #374151; background: #fff; }

.drop-placeholder {
    font-size: 0.65rem; color: #9ca3af;
    letter-spacing: 0.02em; pointer-events: none;
}

.letter-chip-placed {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem;
    font-weight: 700; font-size: 0.875rem; color: #111827;
    cursor: grab; user-select: none;
}
.letter-chip-placed:active { cursor: grabbing; }

/* ── Highlight marks ────────────────────────────────────────────────────── */
mark[data-highlight-id] {
    padding: 2px 0; border-radius: 2px; cursor: pointer; color: inherit;
    -webkit-background-clip: padding-box !important; background-clip: padding-box !important;
}
.highlight-nocolor { background-color: transparent; }
.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question {
    background-color: rgba(0,0,0,0.1); border-radius: 4px;
    padding: 2px 4px; margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}
@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,0.2); transform: scale(1.05); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}

/* Scrollbar */
.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

/* Tooltips */
.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent;
    border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px; width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent;
    border-right: 8px solid transparent; border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px; width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db; z-index: -1;
}
.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent;
    border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px; width: 0; height: 0;
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
    cursor: pointer; padding: 2px 0; border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147, 197, 253, 0.7) !important; }
.question-input::placeholder { font-weight: 700; color: #374151; }
</style>