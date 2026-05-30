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

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

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

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part2-panel-width';
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

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

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
    q27: '',
});

const headingOptions1 = [
    { value: 'i',    label: 'Some success has resulted from observing how the brain functions.' },
    { value: 'ii',   label: 'Are we expecting too much from one robot?' },
    { value: 'iii',  label: 'Scientists are examining the humanistic possibilities.' },
    { value: 'iv',   label: 'There are judgements that robots cannot make.' },
    { value: 'v',    label: 'Has the power of robots become too great?' },
    { value: 'vi',   label: 'Human skills have been heightened with the help of robotics.' },
    { value: 'vii',  label: 'There are some things we prefer the brain to control.' },
    { value: 'viii', label: 'Robots have quietly infiltrated our lives.' },
    { value: 'ix',   label: 'Original predictions have been revised.' },
    { value: 'x',    label: 'Another approach meets the same result.' },
];

const availableHeadingOptions1 = computed(() => {
    const used = [
        answers.value.q14, answers.value.q15, answers.value.q16,
        answers.value.q17, answers.value.q18, answers.value.q19,
    ].filter(Boolean);
    return headingOptions1.filter(opt => !used.includes(opt.value));
});

const paragraphA = `A . The modern world is increasingly populated by quasi-intelligent gizmos whose presence we barely notice but whose creeping ubiquity has removed much human drudgery. Our factories hum to the rhythm of robot assembly arms. Our banking is done at automated teller terminals that thank us with rote politeness for the transaction. Our subway trains are controlled by tireless robo-drivers. Our mine shafts are dug by automated moles, and our nuclear accidents - such as those at Three Mile Island and Chernobyl - are cleaned up by robotic muckers fit to withstand radiation. Such is the scope of uses envisioned by Karel Capek, the Czech playwright who coined the term 'robot' in 1920 (the word 'robota' means 'forced labor' in Czech). As progress accelerates, the experimental becomes the exploitable at record pace.`;

const paragraphB = `B . Other innovations promise to extend the abilities of human operators. Thanks to the incessant miniaturisation of electronics and micromechanics, there are already robot systems that can perform some kinds of brain and bone surgery with submillimeter accuracy - far greater precision than highly skilled physicians can achieve with their hands alone. At the same time, techniques of long-distance control will keep people even farther from hazard. In 1994 a ten- foot-tall NASA robotic explorer called Dante, with video-camera eyes and with spider-like legs, scrambled over the menacing rim of an Alaskan volcano while technicians 2,000 miles away in California watched the scene by satellite and controlled Dante's descent.`;

const paragraphC = `C . But if robots are to reach the next stage of labour-saving utility, they will have to operate with less human supervision and be able to make at least a few decisions for themselves - goals that pose a formidable challenge. 'While we know how to tell a robot to handle a specific error,' says one expert, 'we can't yet give a robot enough common sense to reliably interact with a dynamic world.' Indeed the quest for true artificial intelligence (Al) has produced very mixed results. Despite a spasm of initial optimism in the 1960s and 1970s, when it appeared that transistor circuits and microprocessors might be able to perform in the same way as the human brain by the 21st century, researchers lately have extended their forecasts by decades if not centuries.`;

const paragraphD = `D . What they found, in attempting to model thought, is that the human brain's roughly one hundred billion neurons are much more talented - and human perception far more complicated - than previously imagined. They have built robots that can recognise the misalignment of a machine panel by a fraction of a millimeter in a controlled factory environment. But the human mind can glimpse a rapidly changing scene and immediately disregard the 98 per cent that is irrelevant, instantaneously focusing on the woodchuck at the side of a winding forest road or the single suspicious face in a tumultuous crowd. The most advanced computer systems on Earth can't approach that kind of ability, and neuroscientists still don't know quite how we do it.`;

const paragraphE = `E . Nonetheless, as information theorists, neuroscientists, and computer experts pool their talents, they are finding ways to get some life like intelligence from robots. One method renounces the linear, logical structure of conventional electronic circuits in favour of the messy, ad hoc arrangement of a real brain's neurons. These 'neural networks' do not have to be programmed. They can 'teach' themselves by a system of feedback signals that reinforce electrical pathways that produced correct responses and, conversely, wipe out connections that produced errors. Eventually, the net wires itself into a system that can pronounce certain words or distinguish certain shapes.`;

const paragraphF = `F . In other areas researchers are struggling to fashion a more natural relationship between people and robots in the expectation that some day machines will take on some tasks now done by humans in, say, nursing homes. This is particularly important in Japan, where the percentage of elderly citizens is rapidly increasing. So experiments at the Science University of Tokyo have created a 'face robot' - a life-size, soft plastic model of a female head with a video camera imbedded in the left eye - as a prototype. The researchers' goal is to create robots that people feel comfortable around. They are concentrating on the face because they believe facial expressions are the most important way to transfer emotional messages. We read those messages by interpreting expressions to decide whether a person is happy, frightened, angry, or nervous. Thus the Japanese robot is designed to detect emotions in the person it is 'looking at' by sensing changes in the spatial arrangement of the person's eyes, nose, eyebrows, and mouth. It compares those configurations with a database of standard facial expressions and guesses the emotion. The robot then uses an ensemble of tiny pressure pads to adjust its plastic face into an appropriate emotional response.`;

const paragraphG = `G . Other labs are taking a different approach, one that doesn't try to mimic human intelligence or emotions. Just as computer design has moved away from one central mainframe in favour of myriad individual workstations - and single processors have been replaced by arrays of smaller units that break a big problem into parts that are solved simultaneously - many experts are now investigating whether swarms of semi-smart robots can generate a collective intelligence that is greater than the sum of its parts. That's what beehives and ant colonies do, and several teams are betting that legions of mini-critters working together like an ant colony could be sent to explore the climate of planets or to inspect pipes in dangerous industrial situations.`;

const passageText = paragraphA + '\n\n' + paragraphB + '\n\n' + paragraphC + '\n\n' + paragraphD + '\n\n' + paragraphE + '\n\n' + paragraphF + '\n\n' + paragraphG;

// Build allSegments with stable string IDs
const allSegments = [
    { id: 'part-header',           text: 'Part 2' },
    { id: 'part-instructions',     text: 'Read the text and answer questions 14-27.' },
    { id: 'passage-title',         text: 'ROBOTS' },
    { id: 'passage-subtitle',      text: 'Since the dawn of human ingenuity, people have devised ever more cunning tools to cope with work that is dangerous, boring, onerous, or just plain nasty. That compulsion has culminated in robotics - the science of conferring various human capabilities on machines.' },
    { id: 'passage',               text: passageText },
    { id: 'paragraphA',            text: paragraphA },
    { id: 'paragraphB',            text: paragraphB },
    { id: 'paragraphC',            text: paragraphC },
    { id: 'paragraphD',            text: paragraphD },
    { id: 'paragraphE',            text: paragraphE },
    { id: 'paragraphF',            text: paragraphF },
    { id: 'paragraphG',            text: paragraphG },
    {id: 'q14-19-title',          text: 'Questions 14-19' },
    { id: 'q14-19-instructions',   text: 'Match each of the following statements with the correct heading, A-I.' },
    { id: 'q14-num',               text: '14' },
    { id: 'q14',                   text: 'Some success has resulted from observing how the brain functions.' },
    {id: 'q15-num',               text: '15' },
    { id: 'q15',                   text: 'Are we expecting too much from one robot?' },
    { id: 'q16-num',               text: '16' },
    { id: 'q16',                   text: 'Scientists are examining the humanistic possibilities.' },
    { id: 'q17-num',               text: '17' },
    { id: 'q17',                   text: 'There are judgements that robots cannot make.' },
    { id: 'q18-num',               text: '18' },
    { id: 'q18',                   text: 'Has the power of robots become too great?' },
    { id: 'q19-num',               text: '19' },
    { id: 'q19',                   text: 'Human skills have been heightened with the help of robotics.' },
    { id: 'q20-24-title',          text: 'Questions 20-24' },
    { id: 'q20-24-instructions',   text: 'Do the following statements agree with the information given in Reading Passage?' },
    { id: 'true-label',            text: 'TRUE' },
    { id: 'true-desc',             text: 'if the statement agrees with the information' },
    { id: 'false-label',           text: 'FALSE' },
    { id: 'false-desc',            text: 'if the statement contradicts the information' },
    { id: 'not-given-label',       text: 'NOT GIVEN' },
    { id: 'not-given-desc',        text: 'if there is no information on this in the passage' },
    { id: 'q20-num',               text: '20' },
    { id: 'q20',                   text: 'Karel Capek successfully predicted our current uses for robots.' },
    { id: 'q20-true',              text: 'TRUE' },
    { id: 'q20-false',             text: 'FALSE' },
    { id: 'q20-not-given',         text: 'NOT GIVEN' },
    { id: 'q21-num',               text: '21' },
    { id: 'q21',                   text: 'Lives were saved by the NASA robot, Dante.' },
    { id: 'q21-true',              text: 'TRUE' },
    { id: 'q21-false',             text: 'FALSE' },
    { id: 'q21-not-given',         text: 'NOT GIVEN' },
    { id: 'q22-num',               text: '22' },
    { id: 'q22',                   text: 'Robots are able to make fine visual judgements.' },
    { id: 'q22-true',              text: 'TRUE' },
    { id: 'q22-false',             text: 'FALSE' },
    { id: 'q22-not-given',         text: 'NOT GIVEN' },
    { id: 'q23-num',               text: '23' },
    { id: 'q23',                   text: 'The internal workings of the brain can be replicated by robots.' },
    { id: 'q23-true',              text: 'TRUE' },
    { id: 'q23-false',             text: 'FALSE' },
    { id: 'q23-not-given',         text: 'NOT GIVEN' },
    { id: 'q24-num',               text: '24' },
    { id: 'q24',                   text: 'The Japanese have the most advanced robot systems.' },
    { id: 'q24-true',              text: 'TRUE' },
    { id: 'q24-false',             text: 'FALSE' },
    { id: 'q24-not-given',         text: 'NOT GIVEN' },
    { id: 'q25-27-title',          text: 'Questions 25-27' },
    { id: 'q25-27-instructions-1', text: 'Complete the summary below with words taken from' },
    { id: 'q25-27-para-f',         text: 'paragraph F' },
    { id: 'q25-27-use',            text: 'Use' },
    { id: 'q25-27-word-limit',     text: 'NO MORE THAN THREE WORDS' },
    { id: 'q25-27-for-each',       text: 'for each answer.' },
    { id: 'q25-27-write',          text: 'Write your answers in boxes' },
    { id: 'q25-27-range',          text: '25\u201327' },
    { id: 'q25-27-on-sheet',       text: 'on your answer sheet.' },
    { id: 'q25-27-section-title',  text: 'Face Robot' },
    { id: 'q25-num',               text: '25' },
    { id: 'q25-before',            text: 'The prototype of the Japanese \u2018face robot\u2019 observes humans through a' },
    { id: 'q25-after',             text: 'which is planted in its head.' },
    { id: 'q26-num',               text: '26' },
    { id: 'q26-before',            text: 'It then refers to a' },
    { id: 'q26-after',             text: 'of typical \u2018looks\u2019 that the human face can have, to decide what emotion the person is feeling.' },
    { id: 'q27-num',               text: '27' },
    { id: 'q27-before',            text: 'To respond to this expression, the robot alters its own expression using a number of' },
    { id: 'q27-after',             text: '.' },
];

// Build textSegments with cumulative offsets
let currentOffset = 0;
const textSegments = ref(
    allSegments.map((segment) => {
        const result = { id: segment.id, text: segment.text, offset: currentOffset };
        currentOffset += segment.text.length;
        return result;
    })
);

// Drag and drop
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const getHeadingLabel1 = (value: string): string => {
    const option = headingOptions1.find(opt => opt.value === value);
    return option ? option.label : '';
};

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

// Helper: get segment by ID
const getSegmentById = (id: string) => textSegments.value.find((s) => s.id === id);

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

const getHighlightedSegment = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note,
        })),
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

const handleContentTextSelect = (event: MouseEvent) => {
    // Only handle selection from the element that fired — prevents cross-panel bleeding
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
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    notes.value.push({
        id: Date.now(), text: selectedText.value, selectedText: selectedText.value,
        note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Part 2',
    });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((note) => note.id !== id); };
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

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
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
                hoveredNoteId.value = note.id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (target.closest('mark[data-note-id]')) {
        if (relatedTarget && relatedTarget.closest('.note-hover-tooltip')) return;
        showNoteTooltip.value = false;
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
    }
};

const closeContextMenu = () => { showContextMenu.value = false; };
const handleDeleteHighlight = (id: number) => { deleteHighlight(id); };

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) showContextMenu.value = false;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

// Resize handlers
const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };

const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
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
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32"
    >
        <!-- Part 2 Header -->
        <div class="part-header-box mb-3 mx-5 mt-4 rounded border border-gray-200 px-4 py-3">
            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="part-header" v-html="getHighlightedSegment('part-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions" v-html="getHighlightedSegment('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ═══════════════════════════════════════════════════════
                     LEFT PANEL — Passage + Drag-drop zones (Q14–19)
                ═══════════════════════════════════════════════════════ -->
                <div
                    class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ width: `${leftPanelWidth}%`, flexShrink: 0 }"
                >
                    <!-- Passage Title -->
                    <div class="p-4 pb-2">
                        <h2 class="text-lg font-bold text-gray-900">
                            <span data-segment-id="passage-title" v-html="getHighlightedSegment('passage-title')"></span>
                        </h2>
                        <p class="mt-1 text-baseleading-relaxed text-gray-700">
                            <span data-segment-id="passage-subtitle" v-html="getHighlightedSegment('passage-subtitle')"></span>
                        </p>
                    </div>

                    <!-- Drag-and-drop instruction note -->


                    <!-- Paragraphs A–G with drop zones -->
                    <div class="px-4 pb-8 space-y-4" :style="contentZoom">

                        <!-- Paragraph A → Q14 -->
                        <div>
                            <div
                                id="question-14"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 14"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-basetransition-all cursor-pointer"
                                    :class="dragOverQuestion === 14
                                        ? 'border-blue-500 bg-blue-50'
                                        : answers.q14
                                            ? 'border-gray-500 bg-gray-50 font-medium text-gray-900'
                                            : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 14)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 14)"
                                    @click="clearHeadingAnswer(14)"
                                    :title="answers.q14 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q14">{{ getHeadingLabel1(answers.q14) }}</span>
                                    <span v-else class="font-bold text-gray-500">14 — Drop heading here</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 14)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(14)
                                        ? 'border-red-400 text-red-500 opacity-100'
                                        : hoveredQuestion === 14
                                            ? 'border-gray-400 text-gray-500 opacity-100'
                                            : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                    :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 text-justify leading-relaxed text-gray-700 text-sm">
                                <span data-segment-id="paragraphA" v-html="getHighlightedSegment('paragraphA')"></span>
                            </div>
                        </div>

                        <!-- Paragraph B → Q15 -->
                        <div>
                            <div
                                id="question-15"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 15"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-basetransition-all cursor-pointer"
                                    :class="dragOverQuestion === 15
                                        ? 'border-blue-500 bg-blue-50'
                                        : answers.q15
                                            ? 'border-gray-500 bg-gray-50 font-medium text-gray-900'
                                            : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 15)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 15)"
                                    @click="clearHeadingAnswer(15)"
                                    :title="answers.q15 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q15">{{ getHeadingLabel1(answers.q15) }}</span>
                                    <span v-else class="font-bold text-gray-500">15 — Drop heading here</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 15)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(15)
                                        ? 'border-red-400 text-red-500 opacity-100'
                                        : hoveredQuestion === 15
                                            ? 'border-gray-400 text-gray-500 opacity-100'
                                            : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                    :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 text-justify leading-relaxed text-gray-700 text-sm">
                                <span data-segment-id="paragraphB" v-html="getHighlightedSegment('paragraphB')"></span>
                            </div>
                        </div>

                        <!-- Paragraph C → Q16 -->
                        <div>
                            <div
                                id="question-16"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 16"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-basetransition-all cursor-pointer"
                                    :class="dragOverQuestion === 16
                                        ? 'border-blue-500 bg-blue-50'
                                        : answers.q16
                                            ? 'border-gray-500 bg-gray-50 font-medium text-gray-900'
                                            : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 16)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 16)"
                                    @click="clearHeadingAnswer(16)"
                                    :title="answers.q16 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q16">{{ getHeadingLabel1(answers.q16) }}</span>
                                    <span v-else class="font-bold text-gray-500">16 — Drop heading here</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 16)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(16)
                                        ? 'border-red-400 text-red-500 opacity-100'
                                        : hoveredQuestion === 16
                                            ? 'border-gray-400 text-gray-500 opacity-100'
                                            : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                    :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 text-justify leading-relaxed text-gray-700 text-sm">
                                <span data-segment-id="paragraphC" v-html="getHighlightedSegment('paragraphC')"></span>
                            </div>
                        </div>

                        <!-- Paragraph D → Q17 -->
                        <div>
                            <div
                                id="question-17"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 17"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-basetransition-all cursor-pointer"
                                    :class="dragOverQuestion === 17
                                        ? 'border-blue-500 bg-blue-50'
                                        : answers.q17
                                            ? 'border-gray-500 bg-gray-50 font-medium text-gray-900'
                                            : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 17)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 17)"
                                    @click="clearHeadingAnswer(17)"
                                    :title="answers.q17 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q17">{{ getHeadingLabel1(answers.q17) }}</span>
                                    <span v-else class="font-bold text-gray-500">17 — Drop heading here</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 17)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(17)
                                        ? 'border-red-400 text-red-500 opacity-100'
                                        : hoveredQuestion === 17
                                            ? 'border-gray-400 text-gray-500 opacity-100'
                                            : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                    :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 text-justify leading-relaxed text-gray-700 text-sm">
                                <span data-segment-id="paragraphD" v-html="getHighlightedSegment('paragraphD')"></span>
                            </div>
                        </div>

                        <!-- Paragraph E → Q18 -->
                        <div>
                            <div
                                id="question-18"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 18"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-basetransition-all cursor-pointer"
                                    :class="dragOverQuestion === 18
                                        ? 'border-blue-500 bg-blue-50'
                                        : answers.q18
                                            ? 'border-gray-500 bg-gray-50 font-medium text-gray-900'
                                            : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 18)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 18)"
                                    @click="clearHeadingAnswer(18)"
                                    :title="answers.q18 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q18">{{ getHeadingLabel1(answers.q18) }}</span>
                                    <span v-else class="font-bold text-gray-500">18 — Drop heading here</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 18)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(18)
                                        ? 'border-red-400 text-red-500 opacity-100'
                                        : hoveredQuestion === 18
                                            ? 'border-gray-400 text-gray-500 opacity-100'
                                            : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                    :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 text-justify leading-relaxed text-gray-700 text-sm">
                                <span data-segment-id="paragraphE" v-html="getHighlightedSegment('paragraphE')"></span>
                            </div>
                        </div>

                        <!-- Paragraph F → Q19 -->
                        <div>
                            <div
                                id="question-19"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 19"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-basetransition-all cursor-pointer"
                                    :class="dragOverQuestion === 19
                                        ? 'border-blue-500 bg-blue-50'
                                        : answers.q19
                                            ? 'border-gray-500 bg-gray-50 font-medium text-gray-900'
                                            : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 19)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 19)"
                                    @click="clearHeadingAnswer(19)"
                                    :title="answers.q19 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q19">{{ getHeadingLabel1(answers.q19) }}</span>
                                    <span v-else class="font-bold text-gray-500">19 — Drop heading here</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 19)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(19)
                                        ? 'border-red-400 text-red-500 opacity-100'
                                        : hoveredQuestion === 19
                                            ? 'border-gray-400 text-gray-500 opacity-100'
                                            : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                    :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 text-justify leading-relaxed text-gray-700 text-sm">
                                <span data-segment-id="paragraphF" v-html="getHighlightedSegment('paragraphF')"></span>
                            </div>
                        </div>

                        <!-- Paragraph G (no question, just passage) -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4 text-justify leading-relaxed text-gray-700 text-sm">
                            <span data-segment-id="paragraphG" v-html="getHighlightedSegment('paragraphG')"></span>
                        </div>

                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     RIGHT PANEL — Headings list (Q14-19) + Q20-27 answers
                ═══════════════════════════════════════════════════════ -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ width: `${100 - leftPanelWidth}%`, flexShrink: 0 }"
                >
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Q14-19: Draggable headings source list -->
                            <div class="rounded-xl  p-6 ">
                                <div class="mx-2 mb-3  border-gray-200  px-1 py-2  text-gray-600">
                                <h2><span class="font-bold text-gray-800" data-segment-id="q14-19-title" v-html="getHighlightedSegment('q14-19-title')"></span></h2>
                                <p><span data-segment-id="q14-19-instructions" v-html="getHighlightedSegment('q14-19-instructions')"></span></p>
                            </div>

                                <!-- Draggable headings list -->
                                <div class="rounded-xl border border-gray-200 bg-white p-4">
                                    <h4 class="mb-3 font-bold text-gray-800">List of headings</h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="option in availableHeadingOptions1"
                                            :key="option.value"
                                            class="flex cursor-grab items-center gap-2 rounded border border-gray-300 bg-white px-3 py-2 transition-all hover:border-gray-400 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-40': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd"
                                        >
                                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                            </svg>
                                            <span class="min-w-8 font-bold text-gray-700">{{ option.value }}</span>
                                            <span class="text-basetext-gray-700">{{ option.label }}</span>
                                        </div>
                                        <p v-if="availableHeadingOptions1.length === 0"
                                            class="py-2 text-center text-basefont-medium text-gray-600">
                                            ✓ All headings placed
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 20-24: TRUE / FALSE / NOT GIVEN -->
                            <div class="rounded-xl border border-gray-200 p-6 shadow-sm">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900">
                                        <span data-segment-id="q20-24-title" v-html="getHighlightedSegment('q20-24-title')"></span>
                                    </h3>
                                </div>
                                <div class="mb-6 border border-gray-300 p-4">
                                    <p class="text-basefont-medium leading-relaxed text-gray-800">
                                        <span data-segment-id="q20-24-instructions" v-html="getHighlightedSegment('q20-24-instructions')"></span>
                                    </p>
                                    <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900"
                                                data-segment-id="true-label" v-html="getHighlightedSegment('true-label')"></span>
                                            <span class="text-gray-700" data-segment-id="true-desc" v-html="getHighlightedSegment('true-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900"
                                                data-segment-id="false-label" v-html="getHighlightedSegment('false-label')"></span>
                                            <span class="text-gray-700" data-segment-id="false-desc" v-html="getHighlightedSegment('false-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                data-segment-id="not-given-label" v-html="getHighlightedSegment('not-given-label')"></span>
                                            <span class="text-gray-700" data-segment-id="not-given-desc" v-html="getHighlightedSegment('not-given-desc')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Q20 -->
                                    <div id="question-20" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900" data-segment-id="q20-num" v-html="getHighlightedSegment('q20-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-baseleading-relaxed text-gray-700">
                                                    <span data-segment-id="q20" v-html="getHighlightedSegment('q20')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q20" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q20-true" v-html="getHighlightedSegment('q20-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q20" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q20-false" v-html="getHighlightedSegment('q20-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q20" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q20-not-given" v-html="getHighlightedSegment('q20-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(20)" @click="emit('toggleFlag', 20)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q21 -->
                                    <div id="question-21" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900" data-segment-id="q21-num" v-html="getHighlightedSegment('q21-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-baseleading-relaxed text-gray-700">
                                                    <span data-segment-id="q21" v-html="getHighlightedSegment('q21')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q21" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q21-true" v-html="getHighlightedSegment('q21-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q21" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q21-false" v-html="getHighlightedSegment('q21-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q21" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q21-not-given" v-html="getHighlightedSegment('q21-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(21)" @click="emit('toggleFlag', 21)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q22 -->
                                    <div id="question-22" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900" data-segment-id="q22-num" v-html="getHighlightedSegment('q22-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-baseleading-relaxed text-gray-700">
                                                    <span data-segment-id="q22" v-html="getHighlightedSegment('q22')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q22" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q22-true" v-html="getHighlightedSegment('q22-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q22" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q22-false" v-html="getHighlightedSegment('q22-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q22" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q22-not-given" v-html="getHighlightedSegment('q22-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(22)" @click="emit('toggleFlag', 22)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q23 -->
                                    <div id="question-23" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900" data-segment-id="q23-num" v-html="getHighlightedSegment('q23-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-baseleading-relaxed text-gray-700">
                                                    <span data-segment-id="q23" v-html="getHighlightedSegment('q23')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q23" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q23-true" v-html="getHighlightedSegment('q23-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q23" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q23-false" v-html="getHighlightedSegment('q23-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q23" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q23-not-given" v-html="getHighlightedSegment('q23-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(23)" @click="emit('toggleFlag', 23)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q24 -->
                                    <div id="question-24" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900" data-segment-id="q24-num" v-html="getHighlightedSegment('q24-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-baseleading-relaxed text-gray-700">
                                                    <span data-segment-id="q24" v-html="getHighlightedSegment('q24')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q24" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q24-true" v-html="getHighlightedSegment('q24-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q24" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q24-false" v-html="getHighlightedSegment('q24-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q24" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span data-segment-id="q24-not-given" v-html="getHighlightedSegment('q24-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(24)" @click="emit('toggleFlag', 24)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 25-27: Fill in the blank -->
                            <div class="rounded-xl border border-gray-200 p-6 shadow-sm">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900">
                                        <span data-segment-id="q25-27-title" v-html="getHighlightedSegment('q25-27-title')"></span>
                                    </h3>
                                </div>
                                <p class="mb-4 text-basefont-medium leading-relaxed text-gray-800">
                                    <span data-segment-id="q25-27-instructions-1" v-html="getHighlightedSegment('q25-27-instructions-1')"></span>
                                    <strong data-segment-id="q25-27-para-f" v-html="getHighlightedSegment('q25-27-para-f')"></strong>.<br />
                                    <span data-segment-id="q25-27-use" v-html="getHighlightedSegment('q25-27-use')"></span>
                                    <strong data-segment-id="q25-27-word-limit" v-html="getHighlightedSegment('q25-27-word-limit')"></strong>
                                    <span data-segment-id="q25-27-for-each" v-html="getHighlightedSegment('q25-27-for-each')"></span><br />
                                    <span data-segment-id="q25-27-write" v-html="getHighlightedSegment('q25-27-write')"></span>
                                    <strong data-segment-id="q25-27-range" v-html="getHighlightedSegment('q25-27-range')"></strong>
                                    <span data-segment-id="q25-27-on-sheet" v-html="getHighlightedSegment('q25-27-on-sheet')"></span>
                                </p>

                                <div class="border border-gray-300 p-6">
                                    <p class="mb-4 text-center font-bold text-gray-800">
                                        <span data-segment-id="q25-27-section-title" v-html="getHighlightedSegment('q25-27-section-title')"></span>
                                    </p>
                                    <div class="space-y-5 text-baseleading-relaxed text-gray-800">

                                        <!-- Q25 -->
                                        <p id="question-25" class="leading-relaxed"
                                            @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                            <span data-segment-id="q25-before" v-html="getHighlightedSegment('q25-before')"></span>
                                            <span class="mx-1 inline-flex items-center gap-1">
                                                <input v-model="answers.q25" type="text" spellcheck="false" autocomplete="off"
                                                    class="w-40 border mr-4 border-gray-900 bg-transparent px-2 py-0.5 text-center text-basefocus:border-black focus:outline-none"
                                                    placeholder="25" />
                                                      <span data-segment-id="q25-after" v-html="getHighlightedSegment('q25-after')"></span>
                                                <button v-show="isBookmarkVisible(25)" @click.stop="emit('toggleFlag', 25)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(25) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                        </p>

                                        <!-- Q26 -->
                                        <p id="question-26" class="leading-relaxed"
                                            @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                            <span data-segment-id="q26-before" v-html="getHighlightedSegment('q26-before')"></span>
                                            <span class="mx-1 inline-flex items-center gap-1">
                                                <input v-model="answers.q26" type="text" spellcheck="false" autocomplete="off"
                                                    class="w-40 border mr-5  border-gray-900 bg-transparent px-2 py-0.5 text-center text-basefocus:border-black focus:outline-none"
                                                    placeholder="26" />

                                                <button v-show="isBookmarkVisible(26)" @click.stop="emit('toggleFlag', 26)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(26) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>

                                            </span>
<span data-segment-id="q26-after" v-html="getHighlightedSegment('q26-after')"></span>
                                        </p>

                                        <!-- Q27 -->
                                        <p id="question-27" class="leading-relaxed"
                                            @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                            <span data-segment-id="q27-before" v-html="getHighlightedSegment('q27-before')"></span>
                                            <span class="mx-1 inline-flex items-center gap-1">
                                                <input v-model="answers.q27" type="text" spellcheck="false" autocomplete="off"
                                                    class="w-40 border border-gray-900 bg-transparent px-2 py-0.5 text-center text-basefocus:border-black focus:outline-none"
                                                    placeholder="27" />
                                                    <span data-segment-id="q27-after" v-html="getHighlightedSegment('q27-after')"></span>
                                                <button v-show="isBookmarkVisible(27)" @click.stop="emit('toggleFlag', 27)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(27) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                        </p>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ TELEPORTED OVERLAYS ═══════ -->
        <Teleport to="body">
            <!-- Context Menu (Highlight / Note) -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
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
                            <span class="mt-1.5 text-xs font-medium leading-tight text-gray-600">Delete</span>
                            <span class="text-xs font-medium leading-tight text-gray-600">Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span class="max-w-[180px] break-words text-basetext-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip"
                            class="shrink-0 rounded p-1 transition-colors hover:bg-gray-100" title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs italic text-gray-600">
                        "{{ selectedText }}"
                    </p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-basefocus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                        class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                        Cancel
                    </button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                        Save Note
                    </button>
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

/* Highlight functionality styles */
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
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
</style>
