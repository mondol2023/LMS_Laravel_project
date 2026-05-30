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

// Reading Part 3 component

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

// Drag and drop functionality for questions 35-40
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const summaryOptions = [
    { value: 'A', label: 'action' },
    { value: 'B', label: 'controls' },
    { value: 'C', label: 'failure' },
    { value: 'D', label: 'fish catches' },
    { value: 'E', label: 'fish processing' },
    { value: 'F', label: 'fishing techniques' },
    { value: 'G', label: 'large boats' },
    { value: 'H', label: 'marine reserves' },
    { value: 'I', label: 'the land' },
    { value: 'J', label: 'the past' },
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

// Get label from option value for display
const getOptionLabel = (value: string): string => {
    const option = summaryOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Computed property to filter out used options

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Add highlight effect
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

// Highlight handlers
// Text: Walking on Water (Groundwater in Australia)
const walkingOnWaterText = `<strong>Walking on Water</strong>

The availability of groundwater has always been taken for granted by Australians. Groundwater supplies have in prior times been perceived as a resource of infinite bounds – the prevailing mindset was "out of sight, out of mind". This has all changed with the modern epoch. Persistent neglect has resulted in numerous complications for groundwater users, and many interest groups have a great stake in its management and allocation. Over-allocation of surface water and persistent water shortages mean that reliance on groundwater supplies is expected to swell over the coming years.

The main point of concern now is whether or not a groundwater source can deliver a sustainable yield. This relies on a proper management of discharge (outflow) and recharge (inflow) rates. Discharge occurs when humans extract water, as well as through vegetation and evaporation into the atmosphere. Sustainable use therefore depends on more than keeping within the recharge rate; if humans use water at precisely the recharge rate, discharge through other ways can be adversely affected.

Queensland has been one of the most active states in managing groundwater supplies. This is because the territory sits atop the Great Artesian Basin (GAB), an expansive underwater aquifer¹ that covers nearly one-fifth of the Australian continent. This resource has long been used by indigenous people and outback communities, particularly in times of drought (when surface water could dry up for hundreds of kilometres on end). Since farmers at Kerribee pioneered the use of bores² in the country, the number has spiralled beyond sustainable levels and caused water pressure and flow rates across the region to decline. Furthermore, estimates indicate that 80 per cent of GAB outflow is wasted because of inefficient and out-dated delivery systems. Open drains used to keep livestock hydrated are a particular scourge – much water is lost due to seepage and evaporation.

¹ A layer of rock, sand or gravel through which groundwater flows
² Holes drilled deep into the ground

A number of initiatives have been undertaken to help stem this problem. The Queensland Government declared in 2005 a moratorium³ on issuing new licences for water extraction from the GAB. A strategy group known as the Great Artesian Basin Consultative Council has also published a management plan that involves capping some bores (to prevent further declines in pressure) and rehabilitating hundreds of other bores and bore drains with troughs and polyester piping (to prevent water seeping into the earth).

It is now also apparent that corruption of groundwater supplies by humans is going to be an issue to contend with. In 2006, thousands of Sydney residents had their groundwater usage curtailed due to industrial pollution of the Botany Sands aquifer. Bore water for any domestic purposes has since been off limits due to chemical seepage from an estimated eight industrial sites.

Nevertheless, groundwater plans continue apace. Development of a controversial desalinisation plant has been postponed indefinitely while the feasibility of exploiting two aquifers near Sydney is explored. Authorities intend to use the aquifers to provide up to thirty gigalitres of water a year during dry spells and then leave them alone to replenish during higher rainfall years. But the proposed scheme is riddled with difficulties: low flow rates are hampering extraction; replenishment rates are lower than expected; and salinity imbalances caused by the procedure could wreak havoc on efforts to preserve wetland flora and fauna ecosystems that rely on a plentiful, clean and steady supply of water from the aquifers.

It is not too late to turn groundwater into a sustainable resource. Groundwater is renewable through surface run off (and, at a much slower rate, in organic springs where it is literally drip fed through rock on its way to the aquifers). At present, however, experts believe excessive amounts of ground water are being squandered on aesthetic projects such as keeping parks, gardens and golf courses green.

Aside from more judicious use of groundwater, many experts also believe that we need to look at harnessing other potential sources in order to meet our water needs. During rainy seasons, for example, urban areas are inundated with storm water and flash flooding that can bring cities to a standstill. Better storm water control mechanisms could potentially capture and preserve this rainwater for use at a later date.

³ A stop, postponement or delay`;

// Text segments with their cumulative offsets in the full text
const buildTextSegments = () => {
    const segments: { id: string; text: string; offset: number }[] = [];
    let currentOffset = 0;

    const addSegment = (id: string, text: string) => {
        const plainTextLength = text.replace(/<[^>]*>/g, '').length;
        segments.push({ id, text, offset: currentOffset });
        currentOffset += plainTextLength;
    };

    // SECTION 3 header
    addSegment('section3', 'Part 3');
    addSegment('questions-28-40', 'Questions 28–40');
    addSegment('read-text-28-40', 'Read the text below and answer Questions 28–40');
    addSegment('walking-on-water-title', 'Walking on Water');

    // Walking on Water passage
    addSegment('walking-on-water-text', walkingOnWaterText);

    // Questions 28-31 section
    addSegment('q28-31-title', 'Questions 28–31');
    addSegment('instructions-28-31', 'Choose FOUR letters, A–J.');
    addSegment('write-letters', 'Write the correct letter, A–J, in boxes 28–31 on your answer sheet.');
    addSegment(
        'q28-31-intro',
        'The writer mentions a number of uses of groundwater in Australia. Which FOUR of the following uses are mentioned by the writer of the text?',
    );

    // Answer options A-J for questions 28-31
    addSegment('option-a', 'Maintaining recreational areas');
    addSegment('option-b', 'Helping sewer systems function');
    addSegment('option-c', 'Providing opportunities for underground adventure sports');
    addSegment('option-d', 'Supporting wildlife habitats');
    addSegment('option-e', 'Storing excess amounts of surface water in cities');
    addSegment('option-f', 'Naturally removing salt content from water');
    addSegment('option-g', 'Personal household use');
    addSegment('option-h', 'Forming hot springs for bathing');
    addSegment('option-i', 'Providing water for animals');
    addSegment('option-j', 'Dumping toxic waste products');

    // Questions 32-35 section
    addSegment('q32-35-title', 'Questions 32–35');
    addSegment('instructions-32-35', 'Do the following statements agree with the information given in the text?');
    addSegment('write-boxes-32-35', 'In boxes 32–35 on your answer sheet write:');
    addSegment('true-label', 'TRUE');
    addSegment('true-desc', 'if the statement agrees with the information');
    addSegment('false-label', 'FALSE');
    addSegment('false-desc', 'if the statement contradicts the information');
    addSegment('not-given-label', 'NOT GIVEN');
    addSegment('not-given-desc', 'if there is no information on this');

    // Question statements 32-35
    addSegment('q32-statement', 'Australians have always seen groundwater as a precious resource.');
    addSegment('q33-statement', 'Use of groundwater is predicted to increase.');
    addSegment('q34-statement', 'Humans cannot alter the recharge rate of groundwater.');
    addSegment('q35-statement', 'Using water at the recharge rate or lower will ensure sustainable use.');

    // Questions 36-40 section
    addSegment('q36-40-title', 'Questions 36–40');
    addSegment(
        'instructions-36-40',
        'Complete each sentence with the correct ending, A–J, below. Write the correct letter, A–J, in boxes 36–40 on your answer sheet.',
    );

    // Question statements 36-40
    addSegment('q36-statement', 'Outback communities');
    addSegment('q37-statement', 'Farmers at Kerribee station');
    addSegment('q38-statement', 'In 2005, Queensland authorities');
    addSegment('q39-statement', 'The Great Artesian Basin Consultative Committee');
    addSegment('q40-statement', 'Some residents in Sydney');

    // Answer options A-J for questions 36-40
    addSegment('ending-a', 'took action to stop more people from being able to use groundwater.');
    addSegment('ending-b', 'released a plan to improve bores and lessen wasted water.');
    addSegment('ending-c', 'used groundwater to create artificial rivers.');
    addSegment('ending-d', 'began a formal register to control access to groundwater.');
    addSegment('ending-e', 'decreased the amount of water in movement.');
    addSegment('ending-f', 'used their bore holes to dispose of waste products.');
    addSegment('ending-g', 'were prevented from using ground water due to contamination.');
    addSegment('ending-h', 'relied on ground water during long periods of dry weather.');
    addSegment('ending-i', 'were the first to use a bore in Australia.');

    return segments;
};

const textSegments = ref(buildTextSegments());

// Create a map for quick segment lookup by text
const segmentMap = computed(() => {
    const map = new Map<string, { id: string; text: string; offset: number }>();
    for (const segment of textSegments.value) {
        map.set(segment.text, segment);
    }
    return map;
});

const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = textSegments.value.find(s => s.id === segmentId);
    if (!segment) return '';

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segment.text);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(h =>
        h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(n =>
        n.start < segmentEnd && n.end > segmentOffset
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segment.text;
    }

    const annotations = [
        ...overlappingHighlights.map(h => ({
            start: h.start_offset, end: h.end_offset,
            type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map(n => ({
            start: n.start, end: n.end,
            type: 'note' as const, color: 'blue', id: n.id,
        })),
    ].sort((a, b) => b.start - a.start);

    let result = segment.text;
    for (const annotation of annotations) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd   = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);
        if (plainStart >= plainEnd) continue;

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
    return result;
};
// Convert plain text offset to HTML offset
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;

    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            // Skip HTML tag entirely
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') {
                htmlIndex++;
            }
            htmlIndex++; // Skip closing '>'
        } else {
            plainIndex++;
            htmlIndex++;
        }
    }

    return htmlIndex;
};

// Get plain text length of HTML string
const getPlainTextLength = (htmlText: string): number => {
    return htmlText.replace(/<[^>]*>/g, '').length;
};

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentText: string) => {
    // Find this segment's offset
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    // Use plain text length for comparison
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    // Check if any highlights overlap with this segment (using plain text offsets)
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segmentText;
    }

    // Combine highlights and notes into a single list of annotations
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

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = segmentText;

    for (const annotation of sorted) {
        // Calculate the plain text position within this segment
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);

        if (plainStart < plainEnd) {
            // Convert plain text offsets to HTML offsets
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd = plainToHtmlOffset(result, plainEnd);

            const before = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after = result.substring(htmlEnd);

            if (annotation.type === 'note') {
                // Simple mark tag - tooltip is rendered via Teleport on hover
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }

    return result;
};

// ═══════════════════════════════════════════════════════════════════
// ADD to your script setup — place after your answers ref declaration
// ═══════════════════════════════════════════════════════════════════

// ── Q28-31: "Choose FOUR from A-J" drag state ─────────────────────
const multiDragging = ref<string | null>(null);
const multiDragOver  = ref<string | null>(null);

// A-J options — segment ids match your buildTextSegments()
const optionsAJ = [
    { value: 'A', segmentId: 'option-a' },
    { value: 'B', segmentId: 'option-b' },
    { value: 'C', segmentId: 'option-c' },
    { value: 'D', segmentId: 'option-d' },
    { value: 'E', segmentId: 'option-e' },
    { value: 'F', segmentId: 'option-f' },
    { value: 'G', segmentId: 'option-g' },
    { value: 'H', segmentId: 'option-h' },
    { value: 'I', segmentId: 'option-i' },
    { value: 'J', segmentId: 'option-j' },
];

const onMultiDragStart = (event: DragEvent, letter: string): void => {
    multiDragging.value = letter;
    event.dataTransfer?.setData('text/plain', letter);
    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'copy';
    // A-J options are REUSABLE — same letter can go into multiple boxes
};

const onMultiDrop = (event: DragEvent, questionKey: string): void => {
    const letter = event.dataTransfer?.getData('text/plain') ?? '';
    if (!letter) return;
    answers.value[questionKey] = letter;
    multiDragging.value = null;
    multiDragOver.value  = null;
};

// ── Q32-35: TRUE/FALSE/NOT GIVEN definitions (v-for) ──────────────
const questionsTFN = [
    { num: 32, key: 'q32', segmentId: 'q32-statement' },
    { num: 33, key: 'q33', segmentId: 'q33-statement' },
    { num: 34, key: 'q34', segmentId: 'q34-statement' },
    { num: 35, key: 'q35', segmentId: 'q35-statement' },
];

// ── Q36-40: Sentence ending match A-I drag state ──────────────────
const endingDragging = ref<string | null>(null);
const endingDragOver  = ref<string | null>(null);

const endingsAI = [
    { value: 'A', segmentId: 'ending-a' },
    { value: 'B', segmentId: 'ending-b' },
    { value: 'C', segmentId: 'ending-c' },
    { value: 'D', segmentId: 'ending-d' },
    { value: 'E', segmentId: 'ending-e' },
    { value: 'F', segmentId: 'ending-f' },
    { value: 'G', segmentId: 'ending-g' },
    { value: 'H', segmentId: 'ending-h' },
    { value: 'I', segmentId: 'ending-i' },
];

const questionsDragEnd = [
    { num: 36, key: 'q36', segmentId: 'q36-statement' },
    { num: 37, key: 'q37', segmentId: 'q37-statement' },
    { num: 38, key: 'q38', segmentId: 'q38-statement' },
    { num: 39, key: 'q39', segmentId: 'q39-statement' },
    { num: 40, key: 'q40', segmentId: 'q40-statement' },
];

const onEndingDragStart = (event: DragEvent, letter: string): void => {
    endingDragging.value = letter;
    event.dataTransfer?.setData('text/plain', letter);
    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    // Endings A-I are UNIQUE — each letter fills only one sentence
};

const onEndingDrop = (event: DragEvent, questionKey: string): void => {
    const letter = event.dataTransfer?.getData('text/plain') ?? '';
    if (!letter) return;
    // Clear the letter from whichever zone currently holds it
    for (const q of questionsDragEnd) {
        if (answers.value[q.key] === letter) {
            answers.value[q.key] = '';
        }
    }
    answers.value[questionKey] = letter;
    endingDragging.value = null;
    endingDragOver.value  = null;
};

// ── answers ref — all 13 keys ──────────────────────────────────────
const answers = ref<Record<string, string>>({
    q28: '', q29: '', q30: '', q31: '',
    q32: '', q33: '', q34: '', q35: '',
    q36: '', q37: '', q38: '', q39: '', q40: '',
});



const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    // Remove overlapping notes (last action wins - highlight overwrites note)
    notes.value = notes.value.filter((n) => {
        return !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start);
    });

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
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) {
            y = padding;
        }
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLTextAreaElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    // Remove overlapping highlights (last action wins - note overwrites highlight)
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    // Remove any existing notes that overlap with this range
    notes.value = notes.value.filter((n) => {
        const overlaps = n.start < newEnd && n.end > newStart;
        return !overlaps;
    });

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
    });

    // Clear and close
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

// Close delete tooltip
const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

// Handle keyboard events
const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
};

const contentTextRef = ref<HTMLDivElement | null>(null); // already declared, keep it

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
            let current: Node | null = node;
            if (current?.nodeType === Node.TEXT_NODE) current = current.parentNode;
            while (current) {
                if ((current as Element).hasAttribute?.('data-segment-id')) {
                    return current as Element;
                }
                current = (current as Node).parentNode;
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
        const startPlain  = preStartRange.toString().length;
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
                globalEnd = endSeg.offset + preEndRange.toString().length;
            }
        }

        if (globalEnd <= globalStart) return;

        selectedText.value   = selected;
        selectionRange.value = { start: globalStart, end: globalEnd };
    }, 10);
};

// Handle click on content area - check if clicking on a highlight mark
const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = {
                x: rect.left + rect.width / 2,
                y: rect.bottom + 8,
            };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) {
            closeDeleteTooltip();
        }
        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
    }
};

// Delete highlight
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(idToDelete);
    }
};

// Handle mouse enter on noted text to show tooltip
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

                if (y < 10) {
                    y = rect.bottom + 8;
                }

                noteTooltipPosition.value = {
                    x: rect.left + rect.width / 2,
                    y: y,
                };

                hoveredNoteId.value = noteIdNum;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

// Handle mouse leave from noted text
const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    if (relatedTarget?.closest('.note-hover-tooltip')) {
        return;
    }

    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

// Handle mouse leave from the tooltip itself
const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

// Delete note from hover tooltip
const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
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
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;

    // Set minimum and maximum widths (20% to 80%)
    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

// Watch notes and emit changes
watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

// Load saved answers when component mounts
onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);

    // Add resize event listeners
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

defineExpose({
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="section3"
                    v-html="getHighlightedSegmentById('section3')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="read-text-28-40"
                    v-html="getHighlightedSegmentById('read-text-28-40')"
                ></p>
            </div>
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- READING PASSAGE -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">

                        <div>

                        </div>
                        <div class="space-y-6 px-6" :style="contentZoom">
                            <div class="space-y-6 text-sm leading-relaxed select-text">
                                <div class="p-4">
                                    <div class="text-justify leading-relaxed whitespace-pre-line text-gray-700">
                                        <span data-segment-id="walking-on-water-text" class="text-base text-gray-700"
                                            v-html="getHighlightedSegmentById('walking-on-water-text')"></span>
                                    </div>
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

                <!-- QUESTIONS PANEL -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Q28-31: Choose FOUR from A-J (DRAG AND DROP) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q28-31-title" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('q28-31-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-28-31" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-28-31')"></span><br />
                                        <span data-segment-id="write-letters" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('write-letters')"></span><br />
                                        <span data-segment-id="q28-31-intro" class="mt-2 block font-bold text-gray-700"
                                            v-html="getHighlightedSegmentById('q28-31-intro')"></span>
                                    </p>
                                </div>

                                <div class="flex gap-4">
                                    <!-- Left: 4 drop zones -->
                                    <div class="flex-1 space-y-3">
                                        <template v-for="qNum in [28, 29, 30, 31]" :key="qNum">
                                            <div :id="`question-${qNum}`"
                                                class="relative flex items-center gap-3 bg-white p-2"
                                                @mouseenter="hoveredQuestion = qNum"
                                                @mouseleave="hoveredQuestion = null">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center ">
                                                    <span class="text-sm font-bold text-black">{{ qNum }}.</span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-9 min-w-[150px] items-center justify-center rounded border-2 border-dashed px-2 py-1 text-center text-sm transition-all"
                                                    :class="multiDragOver === `q${qNum}`
                                                        ? 'border-black bg-gray-100 scale-105'
                                                        : answers[`q${qNum}`]
                                                            ? 'border-black bg-white font-medium'
                                                            : 'border-gray-400 bg-gray-50 hover:border-gray-600'"
                                                    @dragover.prevent="multiDragOver = `q${qNum}`"
                                                    @dragleave="multiDragOver = null"
                                                    @drop.prevent="onMultiDrop($event, `q${qNum}`)">
                                                    <span v-if="answers[`q${qNum}`]" class="flex mb-3 items-center gap-1">
                                                        <span class="text-sm font-semibold mb-4 text-gray-900">{{ answers[`q${qNum}`] }}</span>
                                                        <button @click.stop="answers[`q${qNum}`] = ''"
                                                            class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-300 text-gray-600 hover:bg-red-200 hover:text-red-700"
                                                            title="Remove">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                    <span v-else class="text-xs text-gray-400 select-none">Drop</span>
                                                </span>
                                                <button v-show="hoveredQuestion === qNum || isQuestionFlagged(qNum)"
                                                    @click.stop="toggleFlag(qNum)"
                                                    class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(qNum) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(qNum) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Right: A-J draggable options (reusable) -->
                                    <div class="w-52 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-xs font-medium text-gray-600">Drag option to each box:</p>
                                        <div class="border border-gray-900 bg-white p-2">
                                            <div class="space-y-1">
                                                <template v-for="opt in optionsAJ" :key="opt.value">
                                                    <div class="flex cursor-grab items-start gap-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing select-none"
                                                        :class="{ 'opacity-50': multiDragging === opt.value }"
                                                        draggable="true"
                                                        @dragstart="onMultiDragStart($event, opt.value)"
                                                        @dragend="multiDragging = null">
                                                        <span class="font-bold text-gray-900 text-xs shrink-0">{{ opt.value }}</span>
                                                        <span :data-segment-id="opt.segmentId" class="text-gray-700 text-xs"
                                                            v-html="getHighlightedSegmentById(opt.segmentId)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Q32-35: TRUE / FALSE / NOT GIVEN (SELECT) -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q32-35-title" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('q32-35-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-32-35" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-32-35')"></span><br />
                                        <span data-segment-id="write-boxes-32-35" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('write-boxes-32-35')"></span>
                                    </p>
                                </div>

                                <div class="mb-6 border border-gray-900 p-4">
                                    <div class="grid grid-cols-1 gap-2 text-sm">
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="true-label" class="text-gray-700" v-html="getHighlightedSegmentById('true-label')"></span>
                                            </span>
                                            <span data-segment-id="true-desc" class="text-gray-700" v-html="getHighlightedSegmentById('true-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="false-label" class="text-gray-700" v-html="getHighlightedSegmentById('false-label')"></span>
                                            </span>
                                            <span data-segment-id="false-desc" class="text-gray-700" v-html="getHighlightedSegmentById('false-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="not-given-label" class="text-gray-700" v-html="getHighlightedSegmentById('not-given-label')"></span>
                                            </span>
                                            <span data-segment-id="not-given-desc" class="text-gray-700" v-html="getHighlightedSegmentById('not-given-desc')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <template v-for="q in questionsTFN" :key="q.key">
                                        <div :id="`question-${q.num}`" class="relative bg-white"
                                            @mouseenter="hoveredQuestion = q.num"
                                            @mouseleave="hoveredQuestion = null">
                                            <div class="flex items-start gap-4">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center ">
                                                    <span class="text-sm font-bold text-black">{{ q.num }}.</span>
                                                </div>
                                                <div class="grid flex-1 grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select v-model="answers[q.key]"
                                                            class="w-full border border-gray-900 px-3 py-1 text-sm focus:border-black focus:outline-none">
                                                            <option value="">Select</option>
                                                            <option value="TRUE">TRUE</option>
                                                            <option value="FALSE">FALSE</option>
                                                            <option value="NOT GIVEN">NOT GIVEN</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p class="text-sm leading-relaxed text-gray-700">
                                                            <span :data-segment-id="q.segmentId" class="text-gray-700"
                                                                v-html="getHighlightedSegmentById(q.segmentId)"></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <button v-show="hoveredQuestion === q.num || isQuestionFlagged(q.num)"
                                                @click.stop="toggleFlag(q.num)"
                                                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(q.num) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(q.num) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Q36-40: Sentence ending match A-I (DRAG AND DROP) -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q36-40-title" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('q36-40-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-36-40" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-36-40')"></span>
                                    </p>
                                </div>

                                <div class="flex gap-4">
                                    <!-- Left: statement + drop zone rows -->
                                    <div class="flex-1 space-y-3">
                                        <template v-for="q in questionsDragEnd" :key="q.key">
                                            <div :id="`question-${q.num}`"
                                                class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                                @mouseenter="hoveredQuestion = q.num"
                                                @mouseleave="hoveredQuestion = null">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center ">
                                                    <span class="text-sm font-bold text-black">{{ q.num }}.</span>
                                                </div>
                                                <span :data-segment-id="q.segmentId"
                                                    class="text-sm text-gray-700 select-text flex-1"
                                                    v-html="getHighlightedSegmentById(q.segmentId)"></span>
                                                <!-- Drop zone -->
                                                <span
                                                    class="inline-flex min-h-9 min-w-[90px] items-center mr-7 justify-center rounded border-2 border-dashed px-2 py-1 text-center text-xs transition-all"
                                                    :class="endingDragOver === q.key
                                                        ? 'border-black bg-gray-100 '
                                                        : answers[q.key]
                                                            ? 'border-black bg-white font-medium'
                                                            : 'border-gray-400 bg-gray-50 hover:border-gray-600'"
                                                    @dragover.prevent="endingDragOver = q.key"
                                                    @dragleave="endingDragOver = null"
                                                    @drop.prevent="onEndingDrop($event, q.key)">
                                                    <span v-if="answers[q.key]" class="flex items-center gap-1">
                                                        <span class="text-xs font-semibold  text-gray-900">{{ answers[q.key] }}</span>
                                                        <button @click.stop="answers[q.key] = ''"
                                                            class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-300 text-gray-600 hover:bg-red-200 hover:text-red-700"
                                                            title="Remove">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                    <span v-else class="text-xs text-gray-400 select-none">Drop</span>
                                                </span>
                                                <button v-show="hoveredQuestion === q.num || isQuestionFlagged(q.num)"
                                                    @click.stop="toggleFlag(q.num)"
                                                    class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(q.num) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(q.num) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Right: A-I draggable endings -->
                                    <div class="w-52 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-xs font-medium text-gray-600">Drag an ending to each sentence:</p>
                                        <div class="border border-gray-900 bg-white p-2">
                                            <div class="space-y-1">
                                                <template v-for="end in endingsAI" :key="end.value">
                                                    <div class="flex cursor-grab items-start gap-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing select-none"
                                                        :class="{ 'opacity-50': endingDragging === end.value }"
                                                        draggable="true"
                                                        @dragstart="onEndingDragStart($event, end.value)"
                                                        @dragend="endingDragging = null">
                                                        <span class="font-bold text-gray-900 text-xs shrink-0">{{ end.value }}</span>
                                                        <span :data-segment-id="end.segmentId" class="text-gray-700 text-xs"
                                                            v-html="getHighlightedSegmentById(end.segmentId)"></span>
                                                    </div>
                                                </template>
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

    <!-- PORTALS -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
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

        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
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

        <div v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
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
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
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

.note-highlight:hover {
    border-bottom-color: #000;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
