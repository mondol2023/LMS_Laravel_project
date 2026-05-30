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

// Define emits
const emit = defineEmits<{
    notesChange: [
        notes: Array<{
            id: number;
            text: string;
            selectedText: string;
            note: string;
            start: number;
            end: number;
        }>,
    ];
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
const PANEL_WIDTH_KEY = 'reading-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const questionsTextRef = ref<HTMLDivElement | null>(null);
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
const notes = ref<
    Array<{
        id: number;
        text: string;
        selectedText: string;
        note: string;
        start: number;
        end: number;
    }>
>([]);

const answers = ref({
    q27: '',
    q28: '',
    q29: '',
    q30: '',
    q31: '',
    q32: '',
    q33: '',
    q34: '',
    q35: '',
    q36: '',
    q37: '',
    q38: '',
    q39: '',
    q40: '',
});

// ── DRAG AND DROP ─────────────────────────────────────────────────
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i',    label: 'The reaction of the Inuit community to climate change' },
    { value: 'ii',   label: 'Understanding of climate change remains limited' },
    { value: 'iii',  label: 'Alternative sources of essential supplies' },
    { value: 'iv',   label: 'Respect for Inuit opinion grows' },
    { value: 'v',    label: 'A healthier choice of food' },
    { value: 'vi',   label: 'A difficult landscape' },
    { value: 'vii',  label: 'Negative effects on well-being' },
    { value: 'viii', label: 'Alarm caused by unprecedented events in the Arctic' },
    { value: 'ix',   label: 'The benefits of an easier existence' },
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
        // Clear this value from any other question first (each heading is unique)
        for (const key of ['q27', 'q28', 'q29', 'q30', 'q31', 'q32'] as const) {
            if (answers.value[key] === option) answers.value[key] = '';
        }
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

// Get label from option value for display
const getOptionLabel = (value: string): string => {
    const option = headingOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Computed property to filter out used options
const availableOptions = computed(() => {
    const usedOptions = [
        answers.value.q27,
        answers.value.q28,
        answers.value.q29,
        answers.value.q30,
        answers.value.q31,
        answers.value.q32,
    ].filter(Boolean);
    return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
});

// Expose methods for parent component
const getAnswers = () => answers.value;

// Save panel width to localStorage
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

// ── PASSAGE TEXT ─────────────────────────────────────────────────
const buildTextSegments = () => {
    const segments: { id: string; text: string; offset: number }[] = [];
    let currentOffset = 0;

    const addSegment = (id: string, text: string) => {
        const plainTextLength = text.replace(/<[^>]*>/g, '').length;
        segments.push({ id, text, offset: currentOffset });
        currentOffset += plainTextLength + 500;
    };

    addSegment('section3', 'Part 3');
    addSegment('questions-27-40', 'Read the text and answer questions 27-40.');
    addSegment('passage-title', 'Climate change and the Inuit');
    addSegment('passage-subtitle', 'The threat posed by climate change in the Arctic and the problems faced by Canada\'s Inuit people');

    addSegment('para-a', `Unusual incidents are being reported across the Arctic. Inuit families going off on snowmobiles to prepare their summer hunting camps have found themselves cut off from home by a sea of mud, following early thaws. There are reports of igloos losing their insulating properties as the snow drips and refreezes, of lakes draining into the sea as permafrost melts, and sea ice breaking up earlier than usual, carrying seals beyond the reach of hunters. Climate change may still be a rather abstract idea to most of us, but in the Arctic, it is already having dramatic effects - if summertime ice continues to shrink at its present rate, the Arctic Ocean could soon become virtually ice-free in summer. The knock-on effects are likely to include more warming, cloudier skies, increased precipitation and higher sea levels. Scientists are increasingly keen to find out what's going on because they consider the Arctic the 'canary in the mine' for global warming - a warning of what's in store for the rest of the world.`);

    addSegment('para-b', `For the Inuit the problem is urgent. They live in precarious balance with one of the toughest environments on earth. Climate change, whatever its causes, is a direct threat to their way of life. Nobody knows the Arctic as well as the locals, which is why they are not content simply to stand back and let outside experts tell them what's happening. In Canada, where the Inuit people are jealously guarding their hard-won autonomy in the country's newest territory, Nunavut, they believe their best hope of survival in this changing environment lies in combining their ancestral knowledge with the best of modern science. This is a challenge in itself.`);

    addSegment('para-c', `The Canadian Arctic is a vast, treeless polar desert that's covered with snow for most of the year. Venture into this terrain and you get some idea of the hardships facing anyone who calls this home. Farming is out of the question and nature offers meagre pickings. Humans first settled in the Arctic a mere 4,500 years ago, surviving by exploiting sea mammals and fish. The environment tested them to the limits: sometimes the colonists were successful, sometimes they failed and vanished. But around a thousand years ago, one group emerged that was uniquely well adapted to cope with the Arctic environment. These Thule people moved in from Alaska, bringing kayaks, sleds, dogs, pottery and iron tools. They are the ancestors of today's Inuit people.`);

    addSegment('para-d', `Life for the descendants of the Thule people is still harsh. Nunavut is 1.9 million square kilometres of rock and ice, and a handful of islands around the North Pole. It's currently home to 2,500 people, all but a handful of them indigenous Inuit. Over the past 40 years, most have abandoned their nomadic ways and settled in the territory's 28 isolated communities, but they still rely heavily on nature to provide food and clothing. Provisions available in local shops have to be flown into Nunavut on one of the most costly air networks in the world, or brought by supply ship during the few ice-free weeks of summer. It would cost a family around £7,000 a year to replace meat they obtained themselves through hunting with imported meat. Economic opportunities are scarce, and for many people state benefits are their only income.`);

    addSegment('para-e', `While the Inuit may not actually starve if hunting and trapping are curtailed by climate change, there has certainly been an impact on people's health. Obesity, heart disease and diabetes are beginning to appear in a people for whom these have never before been problems. There has been a crisis of identity as the traditional skills of hunting, trapping and preparing skins have begun to disappear. In Nunavut's 'igloo and email' society, where adults who were born in igloos have children who may never have been out on the land, there's a high incidence of depression.`);

    addSegment('para-f', `With so much at stake, the Inuit are determined to play a key role in teasing out the mysteries of climate change in the Arctic. Having survived there for centuries, they believe their wealth of traditional knowledge is vital to the task. And Western scientists are starting to draw on this wisdom, increasingly referred to as 'Inuit Qaujimajatugangit', or IQ. 'In the early days, scientists ignored us when they came up here to study anything. They just figured these people don't know very much so we won't ask them,' says John Amagoalik, an Inuit leader and politician. 'But in recent years IQ has had much more credibility and weight.' In fact it is now a requirement for anyone hoping to get permission to do research that they consult the communities, who are helping to set the research agenda to reflect their most important concerns. They can turn down applications from scientists they believe will work against their interests or research projects that will impinge too much on their daily lives and traditional activities.`);

    addSegment('para-g', `Some scientists doubt the value of traditional knowledge because the occupation of the Arctic doesn't go back far enough. Others, however, point out that the first weather stations in the far north date back just 50 years. There are still huge gaps in our environmental knowledge, and despite the scientific onslaught, many predictions are no more than best guesses. IQ could help to bridge the gap and resolve the tremendous uncertainty about how much of what we're seeing is natural capriciousness and how much is the consequence of human activity.`);

    addSegment('q27-32-title', 'Questions 27–32');
    addSegment('instructions-27-32', 'This Reading Passage 03 has seven paragraphs, A-G. Choose the correct heading for paragraphs B-G from the list of headings below. Write the correct number i-ix, in boxes 27-32 on your answer sheet.');
    addSegment('headings-list-title', 'List of Headings');
    addSegment('heading-i',    'i.   The reaction of the Inuit community to climate change');
    addSegment('heading-ii',   'ii.  Understanding of climate change remains limited');
    addSegment('heading-iii',  'iii. Alternative sources of essential supplies');
    addSegment('heading-iv',   'iv.  Respect for Inuit opinion grows');
    addSegment('heading-v',    'v.   A healthier choice of food');
    addSegment('heading-vi',   'vi.  A difficult landscape');
    addSegment('heading-vii',  'vii. Negative effects on well-being');
    addSegment('heading-viii', 'viii.Alarm caused by unprecedented events in the Arctic');
    addSegment('heading-ix',   'ix.  The benefits of an easier existence');
    addSegment('example-note', 'Example: Paragraph A = viii');

    addSegment('q33-40-title', 'Questions 33–40');
    addSegment('instructions-33-40', 'Complete the summary below. Choose NO MORE THAN TWO WORDS from paragraphs for each answer. Write your answers in boxes 33-40 on your answer sheet.');

    addSegment('summary-intro', 'If you visit the Canadian Arctic, you immediately appreciate the problems faced by people for whom this is home. It would clearly be impossible for the people to engage in');
    addSegment('summary-33-after', 'as a means of supporting themselves. For thousands of years they have had to rely on catching');
    addSegment('summary-34-after', 'and');
    addSegment('summary-35-after', 'as a means of sustenance. The harsh surroundings saw many who tried to settle there pushed to their limits, although some were successful. The');
    addSegment('summary-36-after', 'people were an example of the latter and for them the environment did not prove unmanageable. For the present inhabitants, life continues to be a struggle. The territory of Nunavut consists of little more than ice, rock and a few');
    addSegment('summary-37-after', '. In recent years, many of them have been obliged to give up their');
    addSegment('summary-38-after', 'lifestyle, but they continue to depend mainly on');
    addSegment('summary-39-after', 'their food and clothes.');
    addSegment('summary-40-before', '');
    addSegment('summary-40-after', 'produce is particularly expensive.');

    return segments;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (!rect) return;

        const vw = window.innerWidth;
        const menuHeight = 50;
        contextMenuPosition.value = {
            x: Math.min(Math.max(rect.left + rect.width / 2, 90), vw - 90),
            y: Math.max(rect.top - menuHeight - 8, 10),
        };
        showContextMenu.value = true;

        if (!selection || !range) return;

        const findSegmentEl = (node: Node | null): Element | null => {
            let current = node;
            if (current?.nodeType === Node.TEXT_NODE) current = current.parentNode;
            while (current) {
                if ((current as Element).hasAttribute?.('data-segment-id')) return current as Element;
                current = (current as Element).parentElement ?? null;
            }
            return null;
        };

        const startSegmentEl = findSegmentEl(range.startContainer);
        const endSegmentEl   = findSegmentEl(range.endContainer);
        if (!startSegmentEl) return;

        const startSegId = startSegmentEl.getAttribute('data-segment-id')!;
        const startSeg   = textSegments.value.find(s => s.id === startSegId);
        if (!startSeg) return;

        const preStartRange = document.createRange();
        preStartRange.selectNodeContents(startSegmentEl);
        preStartRange.setEnd(range.startContainer, range.startOffset);
        const startPlain = preStartRange.toString().length;
        const globalStart = startSeg.offset + startPlain;

        let globalEnd: number;
        if (!endSegmentEl || endSegmentEl === startSegmentEl) {
            globalEnd = globalStart + selected.length;
        } else {
            const endSegId = endSegmentEl.getAttribute('data-segment-id')!;
            const endSeg   = textSegments.value.find(s => s.id === endSegId);
            if (!endSeg) {
                globalEnd = globalStart + selected.length;
            } else {
                const preEndRange = document.createRange();
                preEndRange.selectNodeContents(endSegmentEl);
                preEndRange.setEnd(range.endContainer, range.endOffset);
                const endPlain = preEndRange.toString().length;
                globalEnd = endSeg.offset + endPlain;
            }
        }

        if (globalEnd <= globalStart) return;
        selectedText.value   = selected;
        selectionRange.value = { start: globalStart, end: globalEnd };
    }, 10);
};

const textSegments = ref(buildTextSegments());

const segmentMap = computed(() => {
    const map = new Map<string, { id: string; text: string; offset: number }>();
    for (const segment of textSegments.value) map.set(segment.text, segment);
    return map;
});

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

const getHighlightedSegmentById = (segmentId: string) => {
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
        const plainEnd   = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);
        if (plainStart < plainEnd) {
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd   = plainToHtmlOffset(result, plainEnd);
            const before    = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after     = result.substring(htmlEnd);
            if (annotation.type === 'note') {
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }
    return result;
};

const handlePassageTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (!rect) return;

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

        if (!selection || !range) return;

        let node: Node | null = range.startContainer;
        let segmentEl: Element | null = null;
        while (node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                const el = node as Element;
                if (el.hasAttribute('data-segment-id')) { segmentEl = el; break; }
            }
            node = node.parentNode;
        }

        if (!segmentEl) { showContextMenu.value = false; return; }

        const segmentId = segmentEl.getAttribute('data-segment-id')!;
        const segment   = textSegments.value.find((s) => s.id === segmentId);
        if (!segment) { showContextMenu.value = false; return; }

        const preSelectionRange = document.createRange();
        preSelectionRange.selectNodeContents(segmentEl);
        preSelectionRange.setEnd(range.startContainer, range.startOffset);
        const plainTextOffset = preSelectionRange.toString().length;

        selectedText.value   = selected;
        selectionRange.value = { start: segment.offset + plainTextOffset, end: segment.offset + plainTextOffset + selected.length };
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

    const modalWidth  = 320;
    const modalHeight = 180;
    const padding     = 10;

    const selection = window.getSelection();
    let x: number, y: number;

    if (selection && selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const rect  = range.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }

    const viewportWidth  = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const halfWidth      = modalWidth / 2;

    if (x - halfWidth < padding) x = halfWidth + padding;
    else if (x + halfWidth > viewportWidth - padding) x = viewportWidth - halfWidth - padding;

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect  = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) y = padding;
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value     = true;
    showContextMenu.value   = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLTextAreaElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd   = selectionRange.value.end;

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));

    const noteId = Date.now();
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });

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
    showDeleteTooltip.value  = false;
    highlightToDelete.value  = null;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const handleContentClick = (event: MouseEvent) => {
    const target        = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value     = parseInt(highlightId, 10);
            showDeleteTooltip.value     = true;
            showContextMenu.value       = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value)   showContextMenu.value = false;
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
    const target   = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note      = notes.value.find((n) => n.id === noteIdNum);
            if (note) {
                const rect         = noteMark.getBoundingClientRect();
                const tooltipHeight = 50;
                let y = rect.top - tooltipHeight - 8;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value       = noteIdNum;
                hoveredNoteText.value     = note.note;
                showNoteTooltip.value     = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target        = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (relatedTarget?.closest('.note-hover-tooltip')) return;
    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value   = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value   = null;
    hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value   = null;
        hoveredNoteText.value = '';
    }
};

// Resize handlers
const startResize = (event: MouseEvent) => {
    isResizing.value = true;
    event.preventDefault();
};

const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX       = event.clientX - containerRect.left;
    const newLeftWidth  = (offsetX / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};

const stopResize = () => { isResizing.value = false; };

watch(notes, (newNotes) => { emit('notesChange', newNotes); }, { deep: true });

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
<div class="mx-5 mt-4 bg-gray-100 px-4 py-2" style="background-color: #F1F2EC;">
    <p class="text-sm font-bold text-gray-900 select-text">
        <span data-segment-id="section3" v-html="getHighlightedSegmentById('section3')"></span>
    </p>
    <p class="text-sm text-gray-700 select-text">
        <span data-segment-id="questions-27-40" v-html="getHighlightedSegmentById('questions-27-40')"></span>
    </p>
</div>
        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

               <!-- ══════════════════════════════════════════════════
     READING PASSAGE (left panel)
═══════════════════════════════════════════════════ -->
<div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
    :style="{ '--panel-width': `${leftPanelWidth}%` }">
    <div class="p-6">

        <!-- Passage title -->
        <div class="mb-2 text-center text-lg font-bold">
            <p class="select-text">
                <span data-segment-id="passage-title"
                    v-html="getHighlightedSegmentById('passage-title')"></span>
            </p>
        </div>
        <!-- Passage subtitle -->
        <div class="mb-4 text-center text-sm text-gray-600 italic">
            <p class="select-text">
                <span data-segment-id="passage-subtitle"
                    v-html="getHighlightedSegmentById('passage-subtitle')"></span>
            </p>
        </div>

        <!-- Passage body -->
        <div class="space-y-4" :style="contentZoom">

            <!-- Paragraph A (no drop zone - example given) -->
            <div class="px-4">
                <div class="mb-2 text-xs text-gray-500 italic">Example: Paragraph A = viii</div>
                <div class="text-justify leading-relaxed text-gray-700">
                    <span class="font-bold">A. </span>
                    <span class="select-text" data-segment-id="para-a"
                        v-html="getHighlightedSegmentById('para-a')"></span>
                </div>
            </div>

            <!-- Paragraph B with drop zone (Q27) -->
            <div class="px-4">
                <div id="question-27" class="group mb-2 flex items-center gap-2">
                    <span
                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                        :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                        @dragover="handleDragOver($event, 27)"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop($event, 27)"
                        @click="clearAnswer(27)"
                        :title="answers.q27 ? 'Click to clear' : 'Drop heading here'">
                        <span v-if="answers.q27">{{ getOptionLabel(answers.q27) }}</span>
                        <span v-else class="font-bold text-gray-500">27</span>
                    </span>
                    <button @click.stop="emit('toggleFlag', 27)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                        :class="isQuestionFlagged(27) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
                <div class="text-justify leading-relaxed text-gray-700">
                    <span class="font-bold">B. </span>
                    <span class="select-text" data-segment-id="para-b"
                        v-html="getHighlightedSegmentById('para-b')"></span>
                </div>
            </div>

            <!-- Paragraph C with drop zone (Q28) -->
            <div class="px-4">
                <div id="question-28" class="group mb-2 flex items-center gap-2">
                    <span
                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                        :class="dragOverQuestion === 28 ? 'border-blue-500 bg-blue-50' : answers.q28 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                        @dragover="handleDragOver($event, 28)"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop($event, 28)"
                        @click="clearAnswer(28)"
                        :title="answers.q28 ? 'Click to clear' : 'Drop heading here'">
                        <span v-if="answers.q28">{{ getOptionLabel(answers.q28) }}</span>
                        <span v-else class="font-bold text-gray-500">28</span>
                    </span>
                    <button @click.stop="emit('toggleFlag', 28)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                        :class="isQuestionFlagged(28) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
                <div class="text-justify leading-relaxed text-gray-700">
                    <span class="font-bold">C. </span>
                    <span class="select-text" data-segment-id="para-c"
                        v-html="getHighlightedSegmentById('para-c')"></span>
                </div>
            </div>

            <!-- Paragraph D with drop zone (Q29) -->
            <div class="px-4">
                <div id="question-29" class="group mb-2 flex items-center gap-2">
                    <span
                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                        :class="dragOverQuestion === 29 ? 'border-blue-500 bg-blue-50' : answers.q29 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                        @dragover="handleDragOver($event, 29)"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop($event, 29)"
                        @click="clearAnswer(29)"
                        :title="answers.q29 ? 'Click to clear' : 'Drop heading here'">
                        <span v-if="answers.q29">{{ getOptionLabel(answers.q29) }}</span>
                        <span v-else class="font-bold text-gray-500">29</span>
                    </span>
                    <button @click.stop="emit('toggleFlag', 29)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                        :class="isQuestionFlagged(29) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
                <div class="text-justify leading-relaxed text-gray-700">
                    <span class="font-bold">D. </span>
                    <span class="select-text" data-segment-id="para-d"
                        v-html="getHighlightedSegmentById('para-d')"></span>
                </div>
            </div>

            <!-- Paragraph E with drop zone (Q30) -->
            <div class="px-4">
                <div id="question-30" class="group mb-2 flex items-center gap-2">
                    <span
                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                        :class="dragOverQuestion === 30 ? 'border-blue-500 bg-blue-50' : answers.q30 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                        @dragover="handleDragOver($event, 30)"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop($event, 30)"
                        @click="clearAnswer(30)"
                        :title="answers.q30 ? 'Click to clear' : 'Drop heading here'">
                        <span v-if="answers.q30">{{ getOptionLabel(answers.q30) }}</span>
                        <span v-else class="font-bold text-gray-500">30</span>
                    </span>
                    <button @click.stop="emit('toggleFlag', 30)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                        :class="isQuestionFlagged(30) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
                <div class="text-justify leading-relaxed text-gray-700">
                    <span class="font-bold">E. </span>
                    <span class="select-text" data-segment-id="para-e"
                        v-html="getHighlightedSegmentById('para-e')"></span>
                </div>
            </div>

            <!-- Paragraph F with drop zone (Q31) -->
            <div class="px-4">
                <div id="question-31" class="group mb-2 flex items-center gap-2">
                    <span
                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                        :class="dragOverQuestion === 31 ? 'border-blue-500 bg-blue-50' : answers.q31 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                        @dragover="handleDragOver($event, 31)"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop($event, 31)"
                        @click="clearAnswer(31)"
                        :title="answers.q31 ? 'Click to clear' : 'Drop heading here'">
                        <span v-if="answers.q31">{{ getOptionLabel(answers.q31) }}</span>
                        <span v-else class="font-bold text-gray-500">31</span>
                    </span>
                    <button @click.stop="emit('toggleFlag', 31)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                        :class="isQuestionFlagged(31) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
                <div class="text-justify leading-relaxed text-gray-700">
                    <span class="font-bold">F. </span>
                    <span class="select-text" data-segment-id="para-f"
                        v-html="getHighlightedSegmentById('para-f')"></span>
                </div>
            </div>

            <!-- Paragraph G with drop zone (Q32) -->
            <div class="px-4">
                <div id="question-32" class="group mb-2 flex items-center gap-2">
                    <span
                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                        :class="dragOverQuestion === 32 ? 'border-blue-500 bg-blue-50' : answers.q32 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                        @dragover="handleDragOver($event, 32)"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop($event, 32)"
                        @click="clearAnswer(32)"
                        :title="answers.q32 ? 'Click to clear' : 'Drop heading here'">
                        <span v-if="answers.q32">{{ getOptionLabel(answers.q32) }}</span>
                        <span v-else class="font-bold text-gray-500">32</span>
                    </span>
                    <button @click.stop="emit('toggleFlag', 32)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                        :class="isQuestionFlagged(32) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                        :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
                <div class="text-justify leading-relaxed text-gray-700">
                    <span class="font-bold">G. </span>
                    <span class="select-text" data-segment-id="para-g"
                        v-html="getHighlightedSegmentById('para-g')"></span>
                </div>
            </div>

        </div>
    </div>
</div>

                <!-- ── Resize Handle ─────────────────────────────── -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════
                     QUESTIONS PANEL (right panel)
                ═══════════════════════════════════════════════════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════════════════════════════════════════
     QUESTIONS 27–32 — draggable headings list
═════════════════════════════════════════ -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-base font-bold text-gray-900">
                <span data-segment-id="q27-32-title" class="select-text"
                    v-html="getHighlightedSegmentById('q27-32-title')"></span>
            </h3>
        </div>
        <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
            <span data-segment-id="instructions-27-32" class="select-text"
                v-html="getHighlightedSegmentById('instructions-27-32')"></span>
        </p>
    </div>

    <!-- Draggable List of Headings -->
    <div class="border border-gray-300 p-4">
        <h4 class="mb-3 text-base font-bold text-gray-900 sm:text-base">
            <span data-segment-id="headings-list-title"
                v-html="getHighlightedSegmentById('headings-list-title')"></span>
        </h4>
        <div class="space-y-1.5 text-sm">
            <div
                v-for="option in availableOptions"
                :key="option.value"
                class="flex cursor-grab items-center gap-2 rounded border border-gray-300 bg-white px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                :class="{ 'opacity-50': draggedOption === option.value }"
                draggable="true"
                @dragstart="handleDragStart($event, option.value)"
                @dragend="handleDragEnd"
            >
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8h16M4 16h16" />
                </svg>
                <span class="font-bold text-gray-900">{{ option.value }}</span>
                <span class="text-gray-700">{{ option.label }}</span>
            </div>
        </div>
    </div>
</div>

                            <!-- ════════════════════════════════════════
                                 QUESTIONS 33–40
                                 Inline summary completion with text inputs
                            ═════════════════════════════════════════ -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q33-40-title" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('q33-40-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-33-40" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-33-40')"></span>
                                    </p>
                                </div>

                                <div class="leading-relaxed text-gray-800 text-sm">
                                    <!-- Summary paragraph with inline inputs -->
                                    <p class="leading-8">
                                        <span data-segment-id="summary-intro" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-intro')"></span>

                                        <!-- Q33 -->
                                        <span id="question-33" class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 33"
                                            @mouseleave="hoveredQuestion = null">
                                            <input type="text" v-model="answers.q33"
                                                spellcheck="false" autocomplete="off" placeholder="33"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                                @click.stop="toggleFlag(33)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(33) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-33-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-33-after')"></span>

                                        <!-- Q34 -->
                                        <span id="question-34" class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 34"
                                            @mouseleave="hoveredQuestion = null">
                                            <input type="text" v-model="answers.q34"
                                                spellcheck="false" autocomplete="off" placeholder="34"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                                @click.stop="toggleFlag(34)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(34) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-34-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-34-after')"></span>

                                        <!-- Q35 -->
                                        <span id="question-35" class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 35"
                                            @mouseleave="hoveredQuestion = null">
                                            <input type="text" v-model="answers.q35"
                                                spellcheck="false" autocomplete="off" placeholder="35"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                @click.stop="toggleFlag(35)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(35) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-35-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-35-after')"></span>

                                        <!-- Q36 -->
                                        <span id="question-36" class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 36"
                                            @mouseleave="hoveredQuestion = null">
                                            <input type="text" v-model="answers.q36"
                                                spellcheck="false" autocomplete="off" placeholder="36"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                @click.stop="toggleFlag(36)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(36) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-36-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-36-after')"></span>

                                        <!-- Q37 -->
                                        <span id="question-37" class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 37"
                                            @mouseleave="hoveredQuestion = null">
                                            <input type="text" v-model="answers.q37"
                                                spellcheck="false" autocomplete="off" placeholder="37"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                @click.stop="toggleFlag(37)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(37) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-37-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-37-after')"></span>

                                        <!-- Q38 -->
                                        <span id="question-38" class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 38"
                                            @mouseleave="hoveredQuestion = null">
                                            <input type="text" v-model="answers.q38"
                                                spellcheck="false" autocomplete="off" placeholder="38"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                @click.stop="toggleFlag(38)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(38) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-38-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-38-after')"></span>

                                        <!-- Q39 -->
                                        <span id="question-39" class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 39"
                                            @mouseleave="hoveredQuestion = null">
                                            <input type="text" v-model="answers.q39"
                                                spellcheck="false" autocomplete="off" placeholder="39"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                @click.stop="toggleFlag(39)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(39) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="summary-39-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-39-after')"></span>
                                    </p>

                                    <!-- Q40 on its own line -->
                                    <p class="mt-2 leading-8" id="question-40"
                                        @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null">
                                        <span class="relative inline-flex items-center">
                                            <input type="text" v-model="answers.q40"
                                                spellcheck="false" autocomplete="off" placeholder="40"
                                                class="mx-1 inline-block w-28 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                            <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                @click.stop="toggleFlag(40)"
                                                class="absolute -top-3 -right-3 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(40) ? 'border-red-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span data-segment-id="summary-40-after" class="select-text text-gray-700"
                                            v-html="getHighlightedSegmentById('summary-40-after')"></span>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ======================== PORTALS ======================== -->
    <Teleport to="body">

        <!-- Context Menu (Highlight + Note) — speech-bubble style -->
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
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
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
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
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

.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question { animation: highlightPulse 2s ease-in-out; }

@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,0.2); transform: scale(1.02); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}

.highlight-tooltip .tooltip-arrow {
    position: absolute;
    left: 50%;
    bottom: -8px;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: '';
    position: absolute;
    left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: '';
    position: absolute;
    left: -9px; top: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db;
    z-index: -1;
}

.note-hover-tooltip .tooltip-arrow-down {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: '';
    position: absolute;
    left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}
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