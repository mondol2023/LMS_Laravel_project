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

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const PANEL_WIDTH_KEY = 'reading-part2-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

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

const answers = ref<Record<string, string>>({
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
    q27: '',
    q28: '',
});

const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i',    label: 'How can reflection problems be avoided?' },
    { value: 'ii',   label: 'How long should I work without a break?' },
    { value: 'iii',  label: 'What if I experience any problems?' },
    { value: 'iv',   label: 'When is the best time to do filing chores?' },
    { value: 'v',    label: 'What makes a good seat?' },
    { value: 'vi',   label: 'What are the common health problems?' },
    { value: 'vii',  label: 'What is the best kind of lighting to have?' },
    { value: 'viii', label: 'What are the roles of management and workers?' },
    { value: 'ix',   label: 'Why does a VDU create eye fatigue?' },
    { value: 'x',    label: 'Where should I place the documents?' },
];

const handleDragStart = (e: DragEvent, option: string) => {
    draggedOption.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

const passageText = `<b>BENEFICIAL WORK PRACTICES FOR THE KEYBOARD OPERATOR</b><br/>

A Sensible work practices are an important factor in the prevention of muscular fatigue, discomfort or pain in the arms, neck, hands or back; or eye strain which can be associated with constant or regular work at a keyboard and visual display unit (VDU).<br/><br/>

B It is vital that the employer pays attention to the physical setting such as workplace design, the office environment, and placement of monitors as well as the organisation of the work and individual work habits. Operators must be able to recognise work-related health problems and be given the opportunity to participate in the management of these. Operators should take note of and follow the preventive measures outlined below.<br/><br/>

C The typist must be comfortably accommodated in a chair that is adjustable for height with a back rest that is also easily adjustable both for angle and height. The back rest and sitting ledge (with a curved edge) should preferably be cloth-covered to avoid excessive perspiration.<br/><br/>

D When the keyboard operator is working from a paper file or manuscript, it should be at the same distance from the eyes as the screen. The most convenient position can be found by using some sort of holder. Individual arrangement will vary according to whether the operator spends more time looking at the VDU or the paper – whichever the eyes are focused on for the majority of time should be put directly in front of the operator.<br/><br/>

E While keying, it is advisable to have frequent but short pauses of around thirty to sixty seconds to proofread. When doing this, relax your hands. After you have been keying for sixty minutes, you should have a ten minute change of activity. During this spell it is important that you do not remain seated but stand up or walk around. This period could be profitably used to do filing or collect and deliver documents.<br/><br/>

F Generally, the best position for a VDU is at right angles to the window. If this is not possible then glare from the window can be controlled by blinds, curtains or movable screens. Keep the face of the VDU vertical to avoid glare from overhead lighting.<br/><br/>

G Unsatisfactory work practices or working conditions may result in aches or pain. Symptoms should be reported to your supervisor early on so that the cause of the trouble can be corrected and the operator should seek medical attention.`;

const passageText2 = `<b>Workplace dismissals</b><br/>

Before the dismissal

If an employer wants to dismiss an employee, there is a process to be followed. Instances of minor misconduct and poor performance must first be addressed through some preliminary steps.<br/><br/>

Firstly, you should be given an improvement note. This will explain the problem, outline any necessary changes and offer some assistance in correcting the situation. Then, if your employer does not think your performance has improved, you may be given a written warning. The last step is called a final written warning which will inform you that you will be dismissed unless there are improvements in performance. If there is no improvement, your employer can begin the dismissal procedure.<br/><br/>

The dismissal procedure begins with a letter from the employer setting out the charges made against the employee. The employee will be invited to a meeting to discuss these accusations. If the employee denies the charges, he is given the opportunity to appear at a formal appeal hearing in front of a different manager. After this, a decision is made as to whether the employee will be let go or not.<br/><br/>

<b>Dismissals</b><br/>
Of the various types of dismissal, a fair dismissal is the best kind if an employer wants an employee out of the workplace. A fair dismissal is legally and contractually strong and it means all the necessary procedures have been correctly followed. In cases where an employee's misconduct has been very serious, however, an employer may not have to follow all of these procedures. If the employer can prove that the employee's behaviour was illegal, dangerous or severely wrong, the employee can be dismissed immediately: a procedure known as summary dismissal.<br/><br/>

Sometimes a dismissal is not considered to have taken place fairly. One of these types is wrongful dismissal and involves a breach of contract by the employer. This could involve dismissing an employee without notice or without following proper disciplinary and dismissal procedures. Another type, unfair dismissal, is when an employee is sacked without good cause.<br/><br/>

There is another kind of dismissal, known as constructive dismissal, which is slightly peculiar because the employee is not actually openly dismissed by the employer. In this case the employee is forced into resigning by an employer who tries to make significant changes to the original contract. This could mean an employee might have to work night shifts after originally signing on for day work, or he could be made to work in dangerous conditions.`;

// ─── BUG FIX 1: Segment map keyed by ID for O(1) lookup ───────────────────
// Previously, getHighlightedSegment() searched by text equality (s.text === segmentText).
// This breaks when: (a) the same text string appears in multiple segments,
// or (b) the segment text passed from the template doesn't exactly match what
// was stored in buildSegments(). Now we key every segment by its unique ID
// and look up by ID in the template — eliminating both failure modes.

interface Segment {
    id: string;
    text: string;
    offset: number;
}

function buildSegments(): Segment[] {
    let offset = 0;
    const segments: Segment[] = [];

    const add = (id: string, text: string) => {
        segments.push({ id, text, offset });
        // Strip HTML tags to count only visible (plain-text) characters
        offset += text.replace(/<[^>]*>/g, '').length;
    };

    add('header-label', 'Part 2');
    add('header-title', 'Read and answer questions 15-28');
    add('passage1', passageText);
    add('passage2', passageText2);
    add('list-headings-title', 'List of Headings');
    add('heading-i',    'How can reflection problems be avoided?');
    add('heading-ii',   'How long should I work without a break?');
    add('heading-iii',  'What if I experience any problems?');
    add('heading-iv',   'When is the best time to do filing chores?');
    add('heading-v',    'What makes a good seat?');
    add('heading-vi',   'What are the common health problems?');
    add('heading-vii',  'What is the best kind of lighting to have?');
    add('heading-viii', 'What are the roles of management and workers?');
    add('heading-ix',   'Why does a VDU create eye fatigue?');
    add('heading-x',    'Where should I place the documents?');
    add('q15-21-title', 'Questions 15–21');
    add('q15-21-instructions', 'The text has seven sections, A–G. Choose the correct heading for each section from the list of headings below. Write the correct number, i–x, in boxes 15–21 on your answer sheet.');
    // BUG FIX 2: Each section label is stored under its own unique ID.
    // Previously 'Section A' … 'Section G' were stored with IDs 'q15'…'q21',
    // but the template's data-segment-id was also 'q15'…'q21' — that part was
    // fine. However the label text 'Section A' is short and generic enough that
    // it could accidentally collide with other segments in a text-equality search.
    // Keeping unique IDs and looking up by ID avoids this entirely.
    add('q15-label', 'Section A');
    add('q16-label', 'Section B');
    add('q17-label', 'Section C');
    add('q18-label', 'Section D');
    add('q19-label', 'Section E');
    add('q20-label', 'Section F');
    add('q21-label', 'Section G');
    add('q22-23-title', 'Questions 22–23');
    add('q22-23-instructions', 'Complete the sentences below. Choose NO MORE THAN THREE WORDS from the text.');
    add('q22', 'If an employee receives a');
    add('q22-end', ', this means he will lose his job if his work does not get better.');
    add('q23', 'If an employee does not accept the reasons for his dismissal, a');
    add('q23-end', 'can be arranged.');
    add('q24-28-title', 'Questions 24–28');
    add('q24-28-instructions', 'Look at the following descriptions (Questions 24–28) and the list of terms in the box below. Match each description with the correct term A–E.');
    // BUG FIX 3: Q24–28 question texts are stored with dedicated segment IDs.
    // In the original code these were added correctly, BUT getHighlightedSegment
    // was called with the raw q.text string and searched by text equality.
    // If those strings ever differed by even one character (whitespace, apostrophe
    // encoding) the lookup silently returned the raw text with no highlighting.
    // Now the template calls getHighlightedSegmentById('q24') etc. — no string
    // matching required.
    add('q24', 'An employee is asked to leave work straight away because he has done something really bad.');
    add('q25', 'An employee is pressured to leave his job unless he accepts conditions that are very different from those agreed to in the beginning.');
    add('q26', 'An employer gets rid of an employee without keeping to conditions in the contract.');
    add('q27', "The reason for an employee's dismissal is not considered good enough.");
    add('q28', "The reasons for an employee's dismissal are acceptable by law and the terms of the employment contract.");
    add('terms-list-title', 'List of terms:');
    add('term-a', 'Fair dismissal');
    add('term-b', 'Summary dismissal');
    add('term-c', 'Unfair dismissal');
    add('term-d', 'Wrongful dismissal');
    add('term-e', 'Constructive dismissal');

    return segments;
}

const textSegments = ref(buildSegments());

// BUG FIX 4: Build a segment map keyed by ID so lookups are O(1) and
// never ambiguous. The old approach (Array.find by text equality) was O(n)
// and silently returned undefined for any near-miss string mismatch.
const segmentMap = computed<Map<string, Segment>>(() => {
    return new Map(textSegments.value.map((s) => [s.id, s]));
});

const termOptions = [
    { value: 'A', label: 'Fair dismissal' },
    { value: 'B', label: 'Summary dismissal' },
    { value: 'C', label: 'Unfair dismissal' },
    { value: 'D', label: 'Wrongful dismissal' },
    { value: 'E', label: 'Constructive dismissal' },
];

const sections1521 = [
    { qNum: 15, label: 'Section A', segId: 'q15-label' },
    { qNum: 16, label: 'Section B', segId: 'q16-label' },
    { qNum: 17, label: 'Section C', segId: 'q17-label' },
    { qNum: 18, label: 'Section D', segId: 'q18-label' },
    { qNum: 19, label: 'Section E', segId: 'q19-label' },
    { qNum: 20, label: 'Section F', segId: 'q20-label' },
    { qNum: 21, label: 'Section G', segId: 'q21-label' },
];

const questions2428 = [
    { qNum: 24, segId: 'q24', text: 'An employee is asked to leave work straight away because he has done something really bad.' },
    { qNum: 25, segId: 'q25', text: 'An employee is pressured to leave his job unless he accepts conditions that are very different from those agreed to in the beginning.' },
    { qNum: 26, segId: 'q26', text: 'An employer gets rid of an employee without keeping to conditions in the contract.' },
    { qNum: 27, segId: 'q27', text: "The reason for an employee's dismissal is not considered good enough." },
    { qNum: 28, segId: 'q28', text: "The reasons for an employee's dismissal are acceptable by law and the terms of the employment contract." },
];

// ─── Plain ↔ HTML offset helpers ──────────────────────────────────────────

const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;
    const len = htmlText.length;

    while (htmlIndex < len && plainIndex < plainOffset) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < len && htmlText[htmlIndex] !== '>') htmlIndex++;
            htmlIndex++;
            // plainIndex intentionally NOT incremented — tags have no plain length
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

// ─── BUG FIX 5: getHighlightedSegmentById — primary lookup by segment ID ──
// This replaces the text-equality search in getHighlightedSegment().
// The template now passes the segment ID (e.g. 'q24') instead of the raw
// text string, so the lookup is always exact and unambiguous.
const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = segmentMap.value.get(segmentId);
    if (!segment) return '';
    return applyAnnotationsToSegment(segment);
};

// ─── BUG FIX 6: Keep getHighlightedSegment for legacy callers (passages) ──
// Passage spans still use the text-equality path because their text strings
// are unique large blocks — no collision risk. For everything else, prefer
// getHighlightedSegmentById.
const getHighlightedSegment = (segmentText: string): string => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;
    return applyAnnotationsToSegment(segment);
};

// ─── BUG FIX 7: Extracted shared annotation logic ─────────────────────────
// Both lookup paths share this function, eliminating duplicated annotation
// code and ensuring consistent behaviour across all segments.
const applyAnnotationsToSegment = (segment: Segment): string => {
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segment.text);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segment.text;
    }

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset,
            end: h.end_offset,
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start,
            end: n.end,
            type: 'note' as const,
            color: 'blue',
            id: n.id,
            noteText: n.note,
        })),
    ];

    // Apply from end to start so earlier offsets remain valid after each splice
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = segment.text;

    for (const annotation of sorted) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);

        if (plainStart >= plainEnd) continue;

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

    return result;
};

const getAnswers = () => answers.value;

watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

// ─── BUG FIX 8: Selection handler now resolves segment by DOM walk ─────────
// Previously the handler walked up to [data-segment-id] and then looked up
// the segment by ID correctly — but in Q24-28, the data-segment-id was set
// to `q${q.qNum}` on the span, which matches the segment IDs in buildSegments().
// No change needed in the selection logic itself; the fix is that
// buildSegments() now stores q24–q28 under IDs 'q24'–'q28' with the correct
// text, so the offset lookup always finds the right segment.
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (!rect || !contentTextRef.value) return;

        // ── Helper: resolve absolute offset for any node+offset ──
        const getAbsOffset = (node: Node, nodeOffset: number): number | null => {
            let el: Node | null = node.nodeType === Node.TEXT_NODE
                ? node.parentNode : node;

            // Walk up to find [data-segment-id]
            while (el && el !== contentTextRef.value) {
                if ((el as Element).getAttribute?.('data-segment-id')) break;
                el = el.parentNode;
            }
            if (!el || el === contentTextRef.value) return null;

            const segId = (el as Element).getAttribute('data-segment-id')!;
            const segment = segmentMap.value.get(segId);
            if (!segment) return null;

            // Count plain-text chars before nodeOffset using TreeWalker
            const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
            let offsetInSegment = 0;
            let cur: Node | null;
            while ((cur = walker.nextNode())) {
                if (cur === node) {
                    offsetInSegment += nodeOffset;
                    break;
                }
                offsetInSegment += cur.textContent?.length || 0;
            }

            const segPlainLen = getPlainTextLength(segment.text);
            return segment.offset + Math.min(offsetInSegment, segPlainLen);
        };

        // ── Resolve START and END independently ──
        let absStart = getAbsOffset(range!.startContainer, range!.startOffset);
        let absEnd   = getAbsOffset(range!.endContainer,   range!.endOffset);

        if (absStart === null || absEnd === null) return;
        if (absStart > absEnd) [absStart, absEnd] = [absEnd, absStart];

        // Position context menu
        const menuX = rect.left + rect.width / 2;
        const menuY = rect.top - 58;
        const vw = window.innerWidth;
        contextMenuPosition.value = {
            x: Math.min(Math.max(menuX, 90), vw - 90),
            y: Math.max(menuY, 10),
        };
        showContextMenu.value = true;

        selectedText.value = selected;
        selectionRange.value = { start: absStart, end: absEnd };
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    notes.value = notes.value.filter(
        (n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start)
    );

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
    let x: number;
    let y: number;

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

    setTimeout(() => {
        document.querySelector<HTMLInputElement>('.note-input-field')?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    findOverlappingHighlights(newStart, newEnd).forEach((h) => deleteHighlight(h.id));

    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));

    notes.value.push({
        id: Date.now(),
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
    });

    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => {
    noteInputText.value = '';
    showNoteInput.value = false;
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
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
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(idToDelete);
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

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const startResize = (event: MouseEvent) => {
    isResizing.value = true;
    event.preventDefault();
};

const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};

const stopResize = () => {
    isResizing.value = false;
};

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Section Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="header-label" v-html="getHighlightedSegmentById('header-label')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="header-title" v-html="getHighlightedSegmentById('header-title')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- Reading Passage Panel -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">

                    </div>

                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <!-- First Passage -->
                            <div class="mb-8">

                                <div class="text-justify leading-relaxed text-gray-700">
                                    <!-- BUG FIX: data-segment-id must match the key in segmentMap -->
                                    <span class="text-gray-700" data-segment-id="passage1" v-html="getHighlightedSegmentById('passage1')"></span>
                                </div>
                            </div>

                            <hr class="my-8 border-t-2 border-gray-300" />

                            <!-- Second Passage -->
                            <div class="mb-8">

                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="text-gray-700" data-segment-id="passage2" v-html="getHighlightedSegmentById('passage2')"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Panel -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ── Questions 15–21: Heading Match ── -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <!-- BUG FIX: Use getHighlightedSegmentById throughout -->
                                        <span data-segment-id="q15-21-title" v-html="getHighlightedSegmentById('q15-21-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q15-21-instructions" v-html="getHighlightedSegmentById('q15-21-instructions')"></span>
                                    </p>
                                </div>

                                <div class="flex gap-6">
                                    <!-- Left: drop zones -->
                                    <div class="flex-1 space-y-3">
                                        <div
                                            v-for="section in sections1521"
                                            :key="section.qNum"
                                            :id="`question-${section.qNum}`"
                                            class="relative flex items-center gap-3 bg-white p-2"
                                        >
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0">{{ section.qNum }}.</span>
                                            <span
                                                class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === section.qNum ? 'border-blue-500 bg-blue-50' : answers[`q${section.qNum}`] ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, section.qNum)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, section.qNum)"
                                                @click="clearAnswer(section.qNum)"
                                                :title="answers[`q${section.qNum}`] ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                {{ answers[`q${section.qNum}`] || '' }}
                                            </span>
                                            <!--
                                                BUG FIX: data-segment-id uses section.segId (e.g. 'q15-label')
                                                and getHighlightedSegmentById uses the same ID.
                                                Old code used data-segment-id="q15" with getHighlightedSegment(section.label)
                                                — the DOM segment ID and the render lookup were inconsistent.
                                            -->
                                            <span
                                                class="text-gray-700"
                                                :data-segment-id="section.segId"
                                                v-html="getHighlightedSegmentById(section.segId)"
                                            ></span>
                                            <button
                                                @click.stop="toggleFlag(section.qNum)"
                                                class="ml-auto flex h-6 w-6 flex-shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(section.qNum) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(section.qNum) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Right: draggable headings -->
                                    <div class="w-52 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-xs font-medium text-gray-600">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 bg-white p-2">
                                            <h4 class="mb-2 text-sm font-bold text-gray-900">
                                                <span data-segment-id="list-headings-title" v-html="getHighlightedSegmentById('list-headings-title')"></span>
                                            </h4>
                                            <div class="space-y-1 text-xs">
                                                <div
                                                    v-for="h in headingOptions"
                                                    :key="h.value"
                                                    class="flex cursor-grab items-start gap-1.5 rounded border border-gray-300 px-2 py-1.5 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-40': draggedOption === h.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, h.value)"
                                                    @dragend="handleDragEnd"
                                                >
                                                    <span class="font-bold text-gray-900 flex-shrink-0">{{ h.value }}</span>
                                                    <span class="text-gray-700">{{ h.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Questions 22–23: Sentence Completion ── -->
                            <div class="border-t-2 border-gray-200 p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <span data-segment-id="q22-23-title" v-html="getHighlightedSegmentById('q22-23-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span data-segment-id="q22-23-instructions" v-html="getHighlightedSegmentById('q22-23-instructions')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4 text-sm leading-relaxed">
                                    <!-- Q22 -->
                                    <div id="question-22" class="relative flex flex-wrap items-center gap-2 p-2" @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">

                                        <span data-segment-id="q22" class="text-gray-700" v-html="getHighlightedSegmentById('q22')"></span>
                                        <input
                                            v-model="answers.q22"
                                            type="text"
                                            class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:outline-none"
                                            style="width: 150px"
                                            placeholder="22"
                                        />
                                        <span data-segment-id="q22-end" class="text-gray-700" v-html="getHighlightedSegmentById('q22-end')"></span>
                                        <button
                                            v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q23 -->
                                    <div id="question-23" class="relative flex flex-wrap items-center gap-2 p-2" @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">

                                        <span data-segment-id="q23" class="text-gray-700" v-html="getHighlightedSegmentById('q23')"></span>
                                        <input
                                            v-model="answers.q23"
                                            type="text"
                                            class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:outline-none"
                                            style="width: 150px"
                                            placeholder="23"
                                        />
                                        <span data-segment-id="q23-end" class="text-gray-700" v-html="getHighlightedSegmentById('q23-end')"></span>
                                        <button
                                            v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                            @click.stop="toggleFlag(23)"
                                            class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Questions 24–28: Matching ── -->
                            <div class="border-t-2 border-gray-200 p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <span data-segment-id="q24-28-title" v-html="getHighlightedSegmentById('q24-28-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span data-segment-id="q24-28-instructions" v-html="getHighlightedSegmentById('q24-28-instructions')"></span>
                                    </p>
                                </div>

                                <div class="flex gap-6">
                                    <!-- Left: drop zones -->
                                    <div class="flex-1 space-y-3 text-sm">
                                        <div
                                            v-for="q in questions2428"
                                            :key="q.qNum"
                                            :id="`question-${q.qNum}`"
                                            class="relative flex items-start gap-3 bg-white p-2"
                                        >
                                            <span class="flex-shrink-0 font-bold text-gray-900 select-text">{{ q.qNum }}.</span>
                                            <span
                                                class="inline-flex min-h-8 min-w-10 flex-shrink-0 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === q.qNum ? 'border-blue-500 bg-blue-50' : answers[`q${q.qNum}`] ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, q.qNum)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, q.qNum)"
                                                @click="clearAnswer(q.qNum)"
                                                :title="answers[`q${q.qNum}`] ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                {{ answers[`q${q.qNum}`] || '' }}
                                            </span>
                                            <!--
                                                BUG FIX (core fix for Q24-28):
                                                1. data-segment-id now uses q.segId (e.g. 'q24') — matches segmentMap key
                                                2. v-html calls getHighlightedSegmentById(q.segId) — ID-based, never fails on
                                                   string mismatch or apostrophe encoding differences (e.g. ' vs ')
                                                OLD (broken): :data-segment-id="`q${q.qNum}`" + getHighlightedSegment(q.text)
                                                The old render call searched by text equality. If q.text used a curly apostrophe
                                                but the segment stored a straight apostrophe (or vice versa), find() returned
                                                undefined and the function fell back to returning the raw text — no highlighting.
                                            -->
                                            <span
                                                class="text-gray-700"
                                                :data-segment-id="q.segId"
                                                v-html="getHighlightedSegmentById(q.segId)"
                                            ></span>
                                            <button
                                                @click.stop="toggleFlag(q.qNum)"
                                                class="ml-auto flex-shrink-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(q.qNum) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(q.qNum) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Right: draggable terms -->
                                    <div class="w-48 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-xs font-medium text-gray-600">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 bg-white p-2">
                                            <h4 class="mb-2 text-sm font-bold text-gray-900">
                                                <span data-segment-id="terms-list-title" v-html="getHighlightedSegmentById('terms-list-title')"></span>
                                            </h4>
                                            <div class="space-y-1 text-xs">
                                                <div
                                                    v-for="term in termOptions"
                                                    :key="term.value"
                                                    class="flex cursor-grab items-center gap-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-40': draggedOption === term.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, term.value)"
                                                    @dragend="handleDragEnd"
                                                >
                                                    <span class="font-bold text-gray-900 flex-shrink-0">{{ term.value }}</span>
                                                    <span class="text-gray-700">{{ term.label }}</span>
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

        <!-- ── Teleported Overlays ── -->
        <Teleport to="body">

            <!-- Context Menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-[9999]"
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
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div
                    class="delete-highlight-tooltip fixed z-[9999]"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                >
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave"
                    @click.stop
                >
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div
                v-if="showNoteInput"
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        spellcheck="false"
                        autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
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

.part-header-box {
    background-color: #F1F2EC;
}
.select-text { user-select: text; -webkit-user-select: text; }
.select-none { user-select: none; -webkit-user-select: none; cursor: col-resize; }

.passage-panel { width: 100%; }
.answer-panel { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel  { width: calc(100% - var(--panel-width) - 0.5rem); }
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}

.highlight-nocolor  { background-color: transparent; }
.highlight-yellow   { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green    { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue     { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink     { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange   { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,.2); transform: scale(1.05); }
    100% { background-color: rgba(0,0,0,.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,.1));
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
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,.1));
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
    filter: drop-shadow(0 1px 1px rgba(0,0,0,.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}

.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
</style>

<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
    padding: 2px 0;
    border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147, 197, 253, 0.7) !important; }
</style>
