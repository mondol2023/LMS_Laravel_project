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
const PANEL_WIDTH_KEY = 'reading-snowmakers-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// ─── Highlight ─────────────────────────────────────────────────────────────
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

// ─── Answers ───────────────────────────────────────────────────────────────
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

// ─── Drag & Drop for Q1-5 headings ─────────────────────────────────────────
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i',    label: 'Considering ecological costs' },
    { value: 'ii',   label: 'Modifications to the design of the snow gun' },
    { value: 'iii',  label: 'The need for different varieties of snow' },
    { value: 'iv',   label: 'Local concern over environmental issues' },
    { value: 'v',    label: 'A problem and a solution' },
    { value: 'vi',   label: 'Applications beyond the ski slopes' },
    { value: 'vii',  label: 'Converting wet snow to dry snow' },
    { value: 'viii', label: 'New method for calculating modifications' },
    { value: 'ix',   label: 'Artificial process, natural product' },
    { value: 'x',    label: 'Snow formation in nature' },
];

const availableHeadingOptions = computed(() => {
    const used = [answers.value.q1, answers.value.q2, answers.value.q3, answers.value.q4, answers.value.q5].filter(Boolean);
    return headingOptions.filter(opt => !used.includes(opt.value));
});

const getHeadingLabel = (value: string) => headingOptions.find(o => o.value === value)?.label ?? '';

const handleHeadingDragStart = (e: DragEvent, option: string) => {
    draggedHeading.value = option;
    if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', option); }
};
const handleHeadingDragEnd = () => { draggedHeading.value = null; dragOverQuestion.value = null; };
const handleHeadingDragOver = (e: DragEvent, qNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    dragOverQuestion.value = qNum;
};
const handleHeadingDragLeave = () => { dragOverQuestion.value = null; };
const handleHeadingDrop = (e: DragEvent, qNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedHeading.value;
    if (option) {
        const key = `q${qNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};
const clearHeadingAnswer = (qNum: number) => {
    const key = `q${qNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// ─── Passage texts ─────────────────────────────────────────────────────────
const passageA = `In the early to mid twentieth century, with the growing popularity of skiing, ski slopes became extremely profitable businesses. But ski resort owners were completely dependent on the weather: if it didn't snow, or didn't snow enough, they had to close everything down. Fortunately, a device called the snow gun can now provide snow whenever it is needed. These days such machines are standard equipment in the vast majority of ski resorts around the world, making it possible for many resorts to stay open for months or more a year.`;
const passageB = `Snow formed by natural weather systems comes from water vapour in the atmosphere. The water vapour condenses into droplets, forming clouds. If the temperature is sufficiently low, the water droplets freeze into tiny ice crystals. More water particles then condense onto the crystal and join with it to form a snowflake. As the snow flake grows heavier, it falls towards the Earth.`;
const passageC = `The snow gun works very differently from a natural weather system, but it accomplishes exactly the same thing. The device basically works by combining water and air. Two different hoses are attached to the gun. one leading from a water pumping station which pumps water up from a lake or reservoir, and the other leading from an air compressor. When the compressed air passes through the hose into the gun. it atomises the water - that is, it disrupts the stream so that the water splits up into tiny droplets. The droplets are then blown out of the gun and if the outside temperature is below 0°C, ice crystals will form, and will then make snowflakes in the same way as natural snow.`;
const passageD = `Snow-makers often talk about dry snow and wet snow. Dry snow has a relatively low amount of water, so it is very light and powdery. This type of snow is excellent for skiing because skis glide over it easily without getting stuck in wet slush. One of the advantages of using a snow-maker is that this powdery snow can be produced to give the ski slopes a level surface. However, on slopes which receive heavy use, resort owners also use denser, wet snow underneath the dry snow. Many resorts build up the snow depth this way once or twice a year, and then regularly coat the trails with a layer of dry snow throughout the winter.`;
const passageE = `The wetness of snow is dependent on the temperature and humidity outside, as well as the size of the water droplets launched by the gun. Snow-makers have to adjust the proportions of water and air in their snow guns to get the perfect snow consistency for the outdoor weather conditions. Many ski slopes now do this with a central computer system that is connected to weather-reading stations all over the slope.`;
const passageF = `But man-made snow makes heavy demands on the environment. It takes about 275,000 litres of water to create a blanket of snow covering a 60x60 metre area. Most resorts pump water from one or more reservoirs located in low-lying areas. The run-off water from the slopes feeds back into these reservoirs, so the resort can actually use the same water over and over again. However, considerable amounts of energy are needed to run the large air-compressing pumps, and the diesel engines which run them also cause air pollution.`;
const passageG = `Because of the expense of making snow, ski resorts have to balance the cost of running the machines with the benefits of extending the ski season, making sure they only make snow when it is really needed and when it will bring the maximum amount of profit in return for the investment. But man-made snow has a number of other uses as well. A layer of snow keeps a lot of the Earth's heat from escaping into the atmosphere, so farmers often use man-made snow to provide insulation for winter crops. Snow-making machines have played a big part in many movie productions. Movie producers often take several months to shoot scenes that cover just a few days. If the movie takes place in a snowy setting, the set decorators have to get the right amount of snow for each day of shooting either by adding man-made snow or melting natural snow. And another important application of man-made snow is its use in the tests that aircraft must undergo in order to ensure that they can function safely in extreme conditions.`;

// ─── Text segments ─────────────────────────────────────────────────────────
// IMPORTANT: passage segments use sequential offsets starting at 0.
// Non-passage/UI segments use negative offsets so they never interfere.
const textSegments = ref([
    // UI segments — negative offsets keep them isolated
    { id: 'part1',              text: 'Part 1',                                             offset: -200 },
    { id: 'part1-desc',         text: 'Read the text and answer questions 1–13.',           offset: -190 },
    { id: 'passage-title',      text: 'Snow – makers',                                      offset: -160 },
    { id: 'passage-intro',      text: 'Skiing is big business nowadays. But what can ski resort owners do if the snow doesn\'t come?', offset: -150 },

    // Passage segments — strictly sequential, zero-based
    { id: 'passage-a', text: passageA, offset: 0 },
    { id: 'passage-b', text: passageB, offset: passageA.length },
    { id: 'passage-c', text: passageC, offset: passageA.length + passageB.length },
    { id: 'passage-d', text: passageD, offset: passageA.length + passageB.length + passageC.length },
    { id: 'passage-e', text: passageE, offset: passageA.length + passageB.length + passageC.length + passageD.length },
    { id: 'passage-f', text: passageF, offset: passageA.length + passageB.length + passageC.length + passageD.length + passageE.length },
    { id: 'passage-g', text: passageG, offset: passageA.length + passageB.length + passageC.length + passageD.length + passageE.length + passageF.length },

    // Question segments — start well after all passages
    { id: 'q1-5-title',          text: 'Questions 1-5',                                                                              offset: 5000 },
    { id: 'q1-5-instruction-1',  text: 'Reading Passage 1 has seven paragraphs A-G.',                                               offset: 5015 },
    { id: 'q1-5-instruction-2',  text: 'Choose the correct heading for each paragraph from the list of headings below.',            offset: 5058 },
    { id: 'q1-5-instruction-3',  text: 'Write the correct number (i-x) in boxes 1-5 on your answer sheet.',                        offset: 5136 },
    { id: 'headings-title',       text: 'List of headings',                                                                         offset: 5200 },
    { id: 'heading-i',            text: 'Considering ecological costs',                                                              offset: 5216 },
    { id: 'heading-ii',           text: 'Modifications to the design of the snow gun',                                              offset: 5244 },
    { id: 'heading-iii',          text: 'The need for different varieties of snow',                                                  offset: 5287 },
    { id: 'heading-iv',           text: 'Local concern over environmental issues',                                                   offset: 5327 },
    { id: 'heading-v',            text: 'A problem and a solution',                                                                 offset: 5366 },
    { id: 'heading-vi',           text: 'Applications beyond the ski slopes',                                                       offset: 5390 },
    { id: 'heading-vii',          text: 'Converting wet snow to dry snow',                                                          offset: 5424 },
    { id: 'heading-viii',         text: 'New method for calculating modifications',                                                  offset: 5455 },
    { id: 'heading-ix',           text: 'Artificial process, natural product',                                                      offset: 5495 },
    { id: 'heading-x',            text: 'Snow formation in nature',                                                                 offset: 5530 },
    { id: 'q6-8-title',           text: 'Questions 6-8',                                                                            offset: 5600 },
    { id: 'q6-8-instruction-1',   text: 'Label the diagram below.',                                                                 offset: 5613 },
    { id: 'q6-8-instruction-2',   text: 'Choose NO MORE THAN TWO WORDS from the passage for each answer.',                         offset: 5637 },
    { id: 'q6-8-instruction-3',   text: 'Write your answers in boxes 6-8 on your answer sheet.',                                   offset: 5700 },
    { id: 'diagram-label-6',      text: '6.',                                                                                       offset: 5760 },
    { id: 'diagram-label-7',      text: '7.',                                                                                       offset: 5763 },
    { id: 'diagram-label-8',      text: '8.',                                                                                       offset: 5766 },
    { id: 'q9-13-title',          text: 'Questions 9-13',                                                                           offset: 5800 },
    { id: 'q9-13-instruction',    text: 'Complete the sentences below. Choose NO MORE THAN THREE WORDS from the passage for each answer.', offset: 5814 },
    { id: 'q9-text',              text: '9. Dry snow is used to give slopes a level surface, while wet snow is used to increase the',     offset: 5910 },
    { id: 'q10-text',             text: '10. To calculate the required snow consistency, the',                                           offset: 6000 },
    { id: 'q11-text',             text: '11. The machinery used in the process of making the snow consumes a lot of',                    offset: 6054 },
    { id: 'q12-text',             text: '12. Artificial snow is used in agriculture as a type of',                                      offset: 6130 },
    { id: 'q13-text',             text: '13. Artificial snow may also be used in carrying out safety checks on',                         offset: 6185 },
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
    const segmentText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainLength;

    const overlappingHighlights = highlights.value.filter(h => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter(n => n.start < segmentEnd && n.end > segmentOffset);

    if (!overlappingHighlights.length && !overlappingNotes.length) return segmentText;

    const annotations = [
        ...overlappingHighlights.map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ].sort((a, b) => b.start - a.start);

    let result = segmentText;
    for (const ann of annotations) {
        const plainStart = Math.max(0, ann.start - segmentOffset);
        const plainEnd   = Math.min(segmentPlainLength, ann.end - segmentOffset);
        if (plainStart >= plainEnd) continue;
        const htmlStart = plainToHtmlOffset(result, plainStart);
        const htmlEnd   = plainToHtmlOffset(result, plainEnd);
        const before = result.substring(0, htmlStart);
        const annotated = result.substring(htmlStart, htmlEnd);
        const after = result.substring(htmlEnd);
        if (ann.type === 'note') {
            result = `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${annotated}</mark>${after}`;
        } else {
            result = `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${annotated}</mark>${after}`;
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

// ─── Text selection / highlight ────────────────────────────────────────────
/**
 * FIX: The core bug was that when a selection starts or ends inside a drop-zone
 * <span> (which has no [data-segment-id] ancestor), getAbsoluteOffset() returned
 * null, and the fallback logic couldn't recover properly — causing highlights to
 * be truncated or skipped entirely for paragraphs D-G.
 *
 * The fix uses two strategies:
 * 1. getAbsoluteOffset — walks up to [data-segment-id] and measures char offset.
 * 2. getOffsetByDOMPosition — when (1) fails, finds the segment whose DOM element
 *    is CLOSEST (in document order) to the target node, then clamps to that
 *    segment's start or end depending on whether we need a start or end offset.
 */
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || !selection.rangeCount) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        // ── Helper: resolve offset inside a [data-segment-id] element ──────
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container: Node = node.nodeType !== Node.ELEMENT_NODE
                ? node.parentNode as Node
                : node;
            if (!container) return null;

            const segmentEl = (container as Element).closest?.('[data-segment-id]');
            if (!segmentEl) return null;

            const segId = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find(s => String(s.id) === segId);
            if (!segment) return null;

            const walker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let offsetInSeg = 0;
            let cur: Node | null;
            while ((cur = walker.nextNode())) {
                if (cur === node) { offsetInSeg += offsetInNode; break; }
                offsetInSeg += cur.textContent?.length || 0;
            }
            return segment.offset + offsetInSeg;
        };

        // ── Helper: fallback — find the nearest segment by DOM position ────
        // compareEnd=true  → return the END of the nearest preceding segment
        //                     (best guess for selection-end inside a drop zone)
        // compareEnd=false → return the START of the nearest following segment
        //                     (best guess for selection-start inside a drop zone)
        const getOffsetByDOMPosition = (node: Node, compareEnd: boolean): number | null => {
            const allSegmentEls = Array.from(
                document.querySelectorAll<HTMLElement>('[data-segment-id]')
            );

            // Sort by DOM position
            allSegmentEls.sort((a, b) => {
                const pos = a.compareDocumentPosition(b);
                return pos & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
            });

            // Find which segments come BEFORE and AFTER the target node
            let lastBefore: typeof textSegments.value[0] | null = null;
            let firstAfter: typeof textSegments.value[0] | null = null;

            for (const el of allSegmentEls) {
                const sid = el.getAttribute('data-segment-id') || '';
                const seg = textSegments.value.find(s => String(s.id) === sid);
                if (!seg) continue;

                const pos = el.compareDocumentPosition(node);
                // DOCUMENT_POSITION_FOLLOWING means node comes AFTER el
                if (pos & Node.DOCUMENT_POSITION_FOLLOWING) {
                    lastBefore = seg;
                } else if (!firstAfter && (pos & Node.DOCUMENT_POSITION_CONTAINED_BY || pos & Node.DOCUMENT_POSITION_PRECEDING)) {
                    // el comes after node (node precedes el), or el contains node
                    if (pos & Node.DOCUMENT_POSITION_CONTAINED_BY) {
                        // node is inside el — this really shouldn't happen since
                        // getAbsoluteOffset already handles this, but just in case:
                        lastBefore = seg;
                    } else {
                        firstAfter = seg;
                    }
                }
            }

            if (compareEnd && lastBefore) {
                // Selection ends inside a drop zone → use end of the last text segment before it
                return lastBefore.offset + getPlainTextLength(lastBefore.text);
            }
            if (!compareEnd && firstAfter) {
                // Selection starts inside a drop zone → use start of the next text segment
                return firstAfter.offset;
            }
            // Last resort fallbacks
            if (compareEnd && firstAfter) return firstAfter.offset;
            if (!compareEnd && lastBefore) return lastBefore.offset + getPlainTextLength(lastBefore.text);
            return null;
        };

        // ── Resolve start offset ─────────────────────────────────────────
        let startAbs = getAbsoluteOffset(range.startContainer, range.startOffset);
        if (startAbs === null) {
            startAbs = getOffsetByDOMPosition(range.startContainer, false);
        }

        // ── Resolve end offset ───────────────────────────────────────────
        let endAbs = getAbsoluteOffset(range.endContainer, range.endOffset);
        if (endAbs === null) {
            endAbs = getOffsetByDOMPosition(range.endContainer, true);
        }

        if (startAbs === null || endAbs === null) {
            showContextMenu.value = false;
            return;
        }

        // Normalise reversed selections
        if (startAbs > endAbs) [startAbs, endAbs] = [endAbs, startAbs];

        if (startAbs === endAbs) {
            showContextMenu.value = false;
            return;
        }

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX      = rect.left + rect.width / 2;
            const menuHeight = 70;
            const menuWidth  = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), window.innerWidth - menuWidth / 2 - 10),
                y: Math.max(rect.top - menuHeight - 8, 10),
            };

            showContextMenu.value    = true;
            selectedText.value       = selection.toString();
            selectionRange.value     = { start: startAbs, end: endAbs };
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
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x; let y = contextMenuPosition.value.y + 70;
    if (selection && selection.rangeCount > 0) {
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        x = rect.left + rect.width / 2; y = rect.bottom + 10;
    }
    const modalW = 320; const modalH = 180; const pad = 10;
    const vw = window.innerWidth; const vh = window.innerHeight;
    if (x - modalW / 2 < pad) x = modalW / 2 + pad;
    else if (x + modalW / 2 > vw - pad) x = vw - modalW / 2 - pad;
    if (y + modalH > vh - pad) {
        if (selection && selection.rangeCount > 0) y = selection.getRangeAt(0).getBoundingClientRect().top - modalH - 10;
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
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start, end, part: 'Part 1' });
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

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; };

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
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part1"
                v-html="getHighlightedSegmentById('part1')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part1-desc"
                v-html="getHighlightedSegmentById('part1-desc')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-0.5">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ═══ LEFT PANEL — Reading Passage ═══ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">

                    <div class="pt-6 px-4">
                        <h2 class="text-lg text-center font-bold text-gray-900"
                            data-segment-id="passage-title"
                            v-html="getHighlightedSegmentById('passage-title')"></h2>
                        <p class="text-center text-sm text-gray-600 mt-1 italic select-text"
                            data-segment-id="passage-intro"
                            v-html="getHighlightedSegmentById('passage-intro')"></p>
                    </div>

                    <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text">

                            <!-- Paragraph A — Example (no drop zone) -->
                            <div class="text-justify leading-relaxed text-gray-700">
                                <div class="mb-1 text-xs text-gray-500 italic">Example: Paragraph A → v (A problem and a solution)</div>
                                <span class="font-bold">A. </span>
                                <span class="select-text" data-segment-id="passage-a"
                                    v-html="getHighlightedSegmentById('passage-a')"></span>
                            </div>

                            <!-- Paragraph B — Example (no drop zone) -->
                            <div class="text-justify leading-relaxed text-gray-700">
                                <div class="mb-1 text-xs text-gray-500 italic">Example: Paragraph B → x (Snow formation in nature)</div>
                                <span class="font-bold">B. </span>
                                <span class="select-text" data-segment-id="passage-b"
                                    v-html="getHighlightedSegmentById('passage-b')"></span>
                            </div>

                            <!-- Paragraph C — Q1 drop zone
                                 FIX: The drop zone <span> is placed OUTSIDE the data-segment-id span.
                                 The passage text itself keeps its own data-segment-id so offset
                                 resolution always finds a valid ancestor. -->
                            <div class="text-justify leading-relaxed text-gray-700">
                                <div id="question-1" class="group mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                                    <span class="inline-flex min-h-8 flex-1 items-center justify-center select-none rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 1 ? 'border-blue-500 bg-blue-50' : answers.q1 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 1)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 1)"
                                        @click.stop="clearHeadingAnswer(1)"
                                        :title="answers.q1 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q1">{{ getHeadingLabel(answers.q1) }}</span>
                                        <span v-else class="font-bold text-gray-400">1</span>
                                    </span>
                                    <button @click.stop="toggleFlag(1)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                        :class="isQuestionFlagged(1) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100'"
                                        :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="font-bold">C. </span>
                                <!-- passage-c keeps its own segment wrapper so offset resolution works -->
                                <span class="select-text" data-segment-id="passage-c"
                                    v-html="getHighlightedSegmentById('passage-c')"></span>
                            </div>

                            <!-- Paragraph D — Q2 drop zone -->
                            <div class="text-justify leading-relaxed text-gray-700">
                                <div id="question-2" class="group mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                                    <span class="inline-flex min-h-8 flex-1 items-center justify-center select-none rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 2 ? 'border-blue-500 bg-blue-50' : answers.q2 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 2)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 2)"
                                        @click.stop="clearHeadingAnswer(2)"
                                        :title="answers.q2 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q2">{{ getHeadingLabel(answers.q2) }}</span>
                                        <span v-else class="font-bold text-gray-400">2</span>
                                    </span>
                                    <button @click.stop="toggleFlag(2)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                        :class="isQuestionFlagged(2) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100'"
                                        :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="font-bold">D. </span>
                                <span class="select-text" data-segment-id="passage-d"
                                    v-html="getHighlightedSegmentById('passage-d')"></span>
                            </div>

                            <!-- Paragraph E — Q3 drop zone -->
                            <div class="text-justify leading-relaxed text-gray-700">
                                <div id="question-3" class="group mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                                    <span class="inline-flex min-h-8 flex-1 items-center justify-center select-none rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 3 ? 'border-blue-500 bg-blue-50' : answers.q3 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 3)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 3)"
                                        @click.stop="clearHeadingAnswer(3)"
                                        :title="answers.q3 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q3">{{ getHeadingLabel(answers.q3) }}</span>
                                        <span v-else class="font-bold text-gray-400">3</span>
                                    </span>
                                    <button @click.stop="toggleFlag(3)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                        :class="isQuestionFlagged(3) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100'"
                                        :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="font-bold">E. </span>
                                <span class="select-text" data-segment-id="passage-e"
                                    v-html="getHighlightedSegmentById('passage-e')"></span>
                            </div>

                            <!-- Paragraph F — Q4 drop zone -->
                            <div class="text-justify leading-relaxed text-gray-700">
                                <div id="question-4" class="group mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                                    <span class="inline-flex min-h-8 flex-1 items-center justify-center select-none rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 4 ? 'border-blue-500 bg-blue-50' : answers.q4 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 4)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 4)"
                                        @click.stop="clearHeadingAnswer(4)"
                                        :title="answers.q4 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q4">{{ getHeadingLabel(answers.q4) }}</span>
                                        <span v-else class="font-bold text-gray-400">4</span>
                                    </span>
                                    <button @click.stop="toggleFlag(4)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                        :class="isQuestionFlagged(4) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100'"
                                        :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="font-bold">F. </span>
                                <span class="select-text" data-segment-id="passage-f"
                                    v-html="getHighlightedSegmentById('passage-f')"></span>
                            </div>

                            <!-- Paragraph G — Q5 drop zone
                                 FIX: was checking `answers.q1` instead of `answers.q5` -->
                            <div class="text-justify leading-relaxed text-gray-700">
                                <div id="question-5" class="group mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                                    <span class="inline-flex min-h-8 flex-1 items-center justify-center select-none rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 5 ? 'border-blue-500 bg-blue-50' : answers.q5 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 5)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 5)"
                                        @click.stop="clearHeadingAnswer(5)"
                                        :title="answers.q5 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q5">{{ getHeadingLabel(answers.q5) }}</span>
                                        <span v-else class="font-bold text-gray-400">5</span>
                                    </span>
                                    <button @click.stop="toggleFlag(5)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                        :class="isQuestionFlagged(5) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100'"
                                        :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="font-bold">G. </span>
                                <span class="select-text" data-segment-id="passage-g"
                                    v-html="getHighlightedSegmentById('passage-g')"></span>
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

                <!-- ═══ RIGHT PANEL — Questions ═══ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">

                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 1-5: List of Headings -->
                            <div class="p-6">
                                <h3 class="text-base font-bold text-gray-900 mb-2">
                                    <span data-segment-id="q1-5-title" v-html="getHighlightedSegmentById('q1-5-title')"></span>
                                </h3>
                                <p class="mb-1 text-base leading-relaxed text-gray-700">
                                    <span data-segment-id="q1-5-instruction-1" v-html="getHighlightedSegmentById('q1-5-instruction-1')"></span>
                                </p>
                                <p class="mb-1 text-base leading-relaxed text-gray-700">
                                    <span data-segment-id="q1-5-instruction-2" v-html="getHighlightedSegmentById('q1-5-instruction-2')"></span>
                                </p>
                                <p class="mb-4 text-base leading-relaxed text-gray-700">
                                    <span data-segment-id="q1-5-instruction-3" v-html="getHighlightedSegmentById('q1-5-instruction-3')"></span>
                                </p>

                                <!-- Draggable heading options -->
                                <div class="border border-gray-300 p-4">
                                    <h4 class="mb-3 text-base font-bold text-gray-900">
                                        <span data-segment-id="headings-title" v-html="getHighlightedSegmentById('headings-title')"></span>
                                    </h4>
                                    <div class="space-y-1.5 text-sm">
                                        <div v-for="option in availableHeadingOptions" :key="option.value"
                                            class="flex cursor-grab items-center gap-2 rounded border border-gray-300 bg-white px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-50': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd">
                                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                            </svg>
                                            <span class="font-bold text-gray-900">{{ option.value }}</span>
                                            <span class="text-gray-700">{{ option.label }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Placed answers summary -->
                                <div v-if="[answers.q1,answers.q2,answers.q3,answers.q4,answers.q5].some(Boolean)"
                                    class="mt-4 border border-gray-200 p-3 bg-gray-50">
                                    <p class="text-xs font-bold text-gray-600 mb-2">Placed answers:</p>
                                    <div v-for="(qKey, idx) in (['q1','q2','q3','q4','q5'] as const)" :key="qKey">
                                        <div v-if="answers[qKey]" class="flex items-center gap-2 text-xs text-gray-700 mb-1">
                                            <span class="font-semibold">{{ idx+1 }}. Para {{ ['C','D','E','F','G'][idx] }}:</span>
                                            <span>{{ getHeadingLabel(answers[qKey]) }}</span>
                                            <button @click="clearHeadingAnswer(idx+1)" class="text-red-400 hover:text-red-600 ml-auto">✕</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 6-8: Diagram Labelling -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q6-8-title" v-html="getHighlightedSegmentById('q6-8-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q6-8-instruction-1" v-html="getHighlightedSegmentById('q6-8-instruction-1')"></span>
                                    </p>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <strong><span data-segment-id="q6-8-instruction-2" v-html="getHighlightedSegmentById('q6-8-instruction-2')"></span></strong>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q6-8-instruction-3" v-html="getHighlightedSegmentById('q6-8-instruction-3')"></span>
                                    </p>
                                </div>

                                <div class="border border-gray-300 p-4">
                                    <div class="relative w-full">
                                        <img
                                            src="/images/reading/IELTS007.png"
                                            alt="Snow gun diagram"
                                            class="w-full h-auto block"
                                            draggable="false"
                                        />
                                        <div class="mt-2 flex items-center gap-1">
                                            <span class="text-sm font-bold text-gray-900 select-text" data-segment-id="diagram-label-6"
                                                v-html="getHighlightedSegmentById('diagram-label-6')"></span>
                                            <div id="question-6" class="relative"
                                                @mouseenter="hoveredQuestion = 6" @mouseleave="hoveredQuestion = null">
                                                <input v-model="answers.q6" type="text" placeholder="6"
                                                    class="w-32 border border-gray-900 px-2 py-0.5 text-center text-sm font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                    spellcheck="false" autocomplete="off" />
                                                <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                                    @click.stop="toggleFlag(6)"
                                                    class="absolute -top-1 -right-8 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(6) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-2 mb-2 flex items-center gap-1">
                                            <span class="text-sm font-bold text-gray-900 select-text" data-segment-id="diagram-label-7"
                                                v-html="getHighlightedSegmentById('diagram-label-7')"></span>
                                            <div id="question-7" class="relative"
                                                @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                                <input v-model="answers.q7" type="text" placeholder="7"
                                                    class="w-32 border border-gray-900 px-2 py-0.5 text-center text-sm font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                    spellcheck="false" autocomplete="off" />
                                                <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                                    @click.stop="toggleFlag(7)"
                                                    class="absolute -top-1 -right-8 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(7) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex items-center gap-1">
                                            <span class="text-sm font-bold text-gray-900 select-text" data-segment-id="diagram-label-8"
                                                v-html="getHighlightedSegmentById('diagram-label-8')"></span>
                                            <div id="question-8" class="relative"
                                                @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                                <input v-model="answers.q8" type="text" placeholder="8"
                                                    class="w-32 border border-gray-900 px-2 py-0.5 text-center text-sm font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                    spellcheck="false" autocomplete="off" />
                                                <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                                    @click.stop="toggleFlag(8)"
                                                    class="absolute -top-1 -right-8 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(8) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 9-13: Sentence Completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q9-13-title" v-html="getHighlightedSegmentById('q9-13-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q9-13-instruction" v-html="getHighlightedSegmentById('q9-13-instruction')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4 text-base leading-relaxed text-gray-800">
                                    <!-- Q9 -->
                                    <p id="question-9" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                        <span class="select-text" data-segment-id="q9-text" v-html="getHighlightedSegmentById('q9-text')"></span>
                                        <span class="mx-2 inline-flex items-center">
                                            <input v-model="answers.q9" type="text" placeholder="9"
                                                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                        </span>
                                        <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="toggleFlag(9)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>

                                    <!-- Q10 -->
                                    <p id="question-10" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <span class="select-text" data-segment-id="q10-text" v-html="getHighlightedSegmentById('q10-text')"></span>
                                        <span class="mx-2 inline-flex items-center">
                                            <input v-model="answers.q10" type="text" placeholder="10"
                                                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                        </span>
                                        <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>

                                    <!-- Q11 -->
                                    <p id="question-11" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <span class="select-text" data-segment-id="q11-text" v-html="getHighlightedSegmentById('q11-text')"></span>
                                        <span class="mx-2 inline-flex items-center">
                                            <input v-model="answers.q11" type="text" placeholder="11"
                                                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                        </span>
                                        <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>

                                    <!-- Q12 -->
                                    <p id="question-12" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <span class="select-text" data-segment-id="q12-text" v-html="getHighlightedSegmentById('q12-text')"></span>
                                        <span class="mx-2 inline-flex items-center">
                                            <input v-model="answers.q12" type="text" placeholder="12"
                                                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                        </span>
                                        <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>

                                    <!-- Q13 -->
                                    <p id="question-13" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <span class="select-text" data-segment-id="q13-text" v-html="getHighlightedSegmentById('q13-text')"></span>
                                        <span class="mx-2 inline-flex items-center">
                                            <input v-model="answers.q13" type="text" placeholder="13"
                                                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                        </span>
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TELEPORTED OVERLAYS ═══ -->
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
                            <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                            <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md" style="max-width: 240px;">
                        <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span class="text-sm break-words text-gray-700" style="max-width:180px;">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50" title="Delete note">
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
                        class="border border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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

.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

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
    cursor: pointer;
    padding: 2px 0;
    border-radius: 2px;
}
.note-highlight:hover {
    background-color: rgba(147, 197, 253, 0.7) !important;
}
</style>
