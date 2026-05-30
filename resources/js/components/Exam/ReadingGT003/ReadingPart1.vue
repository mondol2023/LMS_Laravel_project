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

// Reading Part 1 component

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
    q14: '',

});

// Text segments with their cumulative offsets in the full text
const enrolmentSACText = `

<strong>Who can enrol?</strong> Any permanent or temporary migrant (anyone on a student visa is only eligible if an individual exemption is granted).

<strong>Where to enrol?</strong> The Advance School of English 4th floor, J Block, Main Campus 120 Portsmouth Road, PORTSMITH.

<strong>How to enrol? </strong> Ask at the ESOL Enquiries Counter (Room 404, 4th floor, J Block).

What does it cost? $0.50 per hour for holders of concession cards
$1.00 per hour for permanent visas (casual)
$5.00 per hour for temporary visas (casual).

<strong>How many hours?</strong>  You can choose how many hours per week or per month you attend. A concession card is issued to anyone on a permanent visa who wishes to pay for a minimum of 50 hours in advance (50 hours x 50c = $25). All other visitors to the SAC are charged the higher casual fee.

<strong>What does enrolment in the SAC give access to?</strong>

- All books, CDs, DVDs, and CALL materials (CALL = computer assisted language learning). A booking system is used with resources in high demand.

- Speaking practice with Conversation Tutors:
  Monday–Thursday: 11.30 a.m.–1.30 p.m. / Friday: 12.30–3 p.m.

- Word processing – a self-paced, internet-based program
  Tuesday: 9 a.m. – 3 p.m.
  Wednesday: 1 p.m. – 3 p.m.
  Thursday: 9 a.m. – 3 p.m.

<strong>Who can help?</strong>  The SAC Manager and SAC facilitators (who are all ESOL teachers) are on duty every day from 9 a.m. – 3 p.m.

<strong>Procedure:</strong>  First, enrol (as above).
Register your attendance at the SAC Information Desk when you arrive – please note: you pay for a minimum of 2 hours each time.

Use the SAC any day or time that suits YOU!`;

// Text 2: Energy saving tips passage
const energySavingTipsText = `

<strong>Home appliances</strong>

<strong>Refrigerator</strong>
Turn the thermostat down so that your refrigerator temperature is around 3 degrees C and the freezer is as close to minus 16 degrees as possible. Check the door seals for a tight fit and leave the door open for no longer than absolutely necessary. These appliances account for about 20% of household electricity.

<strong>Water cylinder</strong>
Turn down the thermostat to 60° (or even 50° is usually sufficient). This is also a much safer temperature for water – it reduces the likelihood of a household member getting scalded when using a hot tap.

<strong>Washing machine</strong>
Use cold water – around 20 degrees. Anything less than 15 degrees is too cold and won't get your laundry clean. Only start the machine when it is full and when possible hang your laundry outside instead of using a dryer.

<strong>Dishwasher</strong>
Only use it when it is full and turn off the drying cycle – allowing the contents to air dry will save 20% of the dishwasher's total energy use.

<strong>Home heating & cooling</strong>

<strong>Thermostat settings</strong>
In winter, set the temperature at 20 degrees during the day and reduce it to 12 degrees before you go to bed. You can save energy by wearing an extra layer of clothing or putting another blanket on the bed. In summer, use blinds and drapes to keep the sun out and don't set your thermostat lower than 24.

<strong>Filters</strong>
Clean air filters regularly – energy is wasted when air conditioners have to work harder to draw air through dirty filters.

<strong>A small investment Compact fluorescent lights</strong> These cost more initially but they use only 25% of the energy of an incandescent light bulb and they last ten times longer.`;
const buildTextSegments = () => {
    const segments: { id: string; text: string; offset: number }[] = [];
    let currentOffset = 0;

    const addSegment = (id: string, text: string) => {
        const plainTextLength = text.replace(/<[^>]*>/g, '').length;
        segments.push({ id, text, offset: currentOffset });
        currentOffset += plainTextLength;
    };

    // Section header
    addSegment('part1-title', 'READING – PART 1');
    addSegment('part1-desc', 'Read and answer questions 1–14.');
    addSegment('section1', 'SECTION 1');
    addSegment('questions-1-14', 'Questions 1–14');
    addSegment('read-text-1-9', 'Read the text below and answer Questions 1–9');
    addSegment('enrolment-title', 'ENROLMENT IN THE SELF-ACCESS CENTRE (SAC)');

    // Enrolment SAC passage
    addSegment('enrolment-sac', enrolmentSACText);

    // Section for Energy saving tips
    addSegment('read-text-10-14', 'Read the text below and answer Questions 10–14');
    addSegment('energy-title', 'Energy saving tips');

    // Energy saving tips passage
    addSegment('energy-text', energySavingTipsText);

    // Questions 1-4 section
    addSegment('q1-4-title', 'Questions 1–4');
    addSegment(
        'instructions-match',
        'Complete each sentence with the correct ending, A–H, below. Write the correct letter, A–H, in boxes 1–4 on your answer sheet.',
    );

    // Question statements 1-4
    addSegment('q1-statement', 'Students on a student visa need special permission to be able to');
    addSegment('q2-statement', 'Students who want to enrol in the SAC should');
    addSegment('q3-statement', 'Only permanent migrants can');
    addSegment('q4-statement', 'Temporary migrants must');

    // Answer options A-H
    addSegment('option-a', 'enrol in the School of English');
    addSegment('option-b', 'go to room 404 in J Block');
    addSegment('option-c', 'have access to DVDs');
    addSegment('option-d', 'pay more per visit');
    addSegment('option-e', 'pay their fees at the Enquiries Counter');
    addSegment('option-f', 'purchase a concession card');
    addSegment('option-g', 'reserve popular materials');
    addSegment('option-h', 'use the SAC');

    // Questions 5-9 section
    addSegment('q5-9-title', 'Questions 5–9');
    addSegment('instructions-tfn', 'Do the following statements agree with the information given in the text?');
    addSegment('write-boxes', 'In boxes 5–9 on your answer sheet write:');
    addSegment('true-label', 'TRUE');
    addSegment('true-desc', 'if the statement agrees with the information');
    addSegment('false-label', 'FALSE');
    addSegment('false-desc', 'if the statement contradicts the information');
    addSegment('not-given-label', 'NOT GIVEN');
    addSegment('not-given-desc', 'if there is no information on this');

    // Question statements 5-9
    addSegment('q5-statement', 'The tutors who take conversation groups are not paid.');
    addSegment('q6-statement', 'Conversation tutors are unavailable on Wednesdays.');
    addSegment('q7-statement', 'Students cannot access the word processing program on Wednesday morning.');
    addSegment('q8-statement', 'The SAC facilitators are also teachers at the Advance School of English.');
    addSegment('q9-statement', 'Students cannot use the SAC for more than two hours at a time.');



    // Questions 10-14 section
    addSegment('q10-14-title', 'Questions 10–14');
    addSegment('instructions-complete', 'Complete the sentences below.');
    addSegment('words-number', 'Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the text for each answer.');
    addSegment('write-answers', 'Write your answers in boxes 10–14 on your answer sheet.');

    // In the buildTextSegments function, add these segments for questions 10-14:

    // Question 10 parts
    addSegment('q10-part1', 'The most energy-efficient temperature for ensuring food stays frozen is');
    addSegment('q10-part2', 'degrees C.');

    // Question 11 parts
    addSegment('q11-part1', 'You can get your clothes clean quite economically by washing them in water no hotter than');
    addSegment('q11-part2', 'degrees C.');

    // Question 12 parts
    addSegment('q12-part1', "On a cold night you can save energy and still stay warm by setting your heater's thermostat at");
    addSegment('q12-part2', 'degrees C.');

    // Question 13 parts
    addSegment('q13-part1', 'Letting your dishes');
    addSegment('q13-part2', 'saves energy.');

    // Question 14 parts
    addSegment('q14-part1', 'Buying');
    addSegment('q14-part2', 'light bulbs costs less in the long term.');
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

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(plainText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
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

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (!rect) return;

        // Position tooltip above selection
        const vw = window.innerWidth;
        const menuHeight = 50;
        contextMenuPosition.value = {
            x: Math.min(Math.max(rect.left + rect.width / 2, 90), vw - 90),
            y: Math.max(rect.top - menuHeight - 8, 10),
        };
        showContextMenu.value = true;

        if (!selection || !range) return;

        // Walk up from a node to find nearest [data-segment-id] element
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

        // Plain-text offset of selection start within its segment
        const preStartRange = document.createRange();
        preStartRange.selectNodeContents(startSegmentEl);
        preStartRange.setEnd(range.startContainer, range.startOffset);
        const startPlain = preStartRange.toString().length;
        const globalStart = startSeg.offset + startPlain;

        // End offset: same-segment vs cross-segment
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

    const modalWidth = 320; // w-80 = 20rem = 320px
    const modalHeight = 180; // Approximate height of the modal
    const padding = 10; // Minimum distance from viewport edges

    // Get fresh position based on current selection
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

    // Viewport boundary checking
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Check horizontal bounds (modal is centered, so check half-width on each side)
    const halfWidth = modalWidth / 2;
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    // Check vertical bounds - if modal goes below viewport, show above selection
    if (y + modalHeight > viewportHeight - padding) {
        // Try to position above the selection
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        // If still outside viewport (too close to top), position at top with padding
        if (y < padding) {
            y = padding;
        }
    }

    noteInputPosition.value = { x, y };

    showNoteInput.value = true;
    showContextMenu.value = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
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

// Handle click on content area - check if clicking on a highlight mark
const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        // Clicked on a highlight - show delete tooltip
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
        // Clicked outside highlight - close tooltips
        if (showDeleteTooltip.value) {
            closeDeleteTooltip();
        }
        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
    }
};

// Delete highlight - simple and direct
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        // Close tooltip and clear state first
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        // Then delete
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

                // Position tooltip ABOVE the noted text with arrow pointing DOWN
                const tooltipHeight = 50; // Approximate tooltip height
                let y = rect.top - tooltipHeight - 8; // 8px gap above text

                // If not enough space above, show below
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

    // Check if we're moving to the tooltip itself
    if (relatedTarget?.closest('.note-hover-tooltip')) {
        return;
    }

    // Check if leaving a note mark
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

const draggingLetter = ref(null)
const dragOverTarget = ref(null)   // qNum of the hovered drop zone

// Static data for Q1–4
const dragOptions = [
    { letter: 'A', text: 'enrol in the School of English' },
    { letter: 'B', text: 'go to room 404 in J Block' },
    { letter: 'C', text: 'have access to DVDs' },
    { letter: 'D', text: 'pay more per visit' },
    { letter: 'E', text: 'pay their fees at the Enquiries Counter' },
    { letter: 'F', text: 'purchase a concession card' },
    { letter: 'G', text: 'reserve popular materials' },
    { letter: 'H', text: 'use the SAC' },
]

const q1to4Statements = [
    { qNum: 1, segId: 'q1-statement', text: 'Students on a student visa need special permission to be able to' },
    { qNum: 2, segId: 'q2-statement', text: 'Students who want to enrol in the SAC should' },
    { qNum: 3, segId: 'q3-statement', text: 'Only permanent migrants can' },
    { qNum: 4, segId: 'q4-statement', text: 'Temporary migrants must' },
]

const q5to9Statements = [
    { qNum: 5, segId: 'q5-statement', text: 'The tutors who take conversation groups are not paid.' },
    { qNum: 6, segId: 'q6-statement', text: 'Conversation tutors are unavailable on Wednesdays.' },
    { qNum: 7, segId: 'q7-statement', text: 'Students cannot access the word processing program on Wednesday morning.' },
    { qNum: 8, segId: 'q8-statement', text: 'The SAC facilitators are also teachers at the Advance School of English.' },
    { qNum: 9, segId: 'q9-statement', text: 'Students cannot use the SAC for more than two hours at a time.' },
]

// Derived: which letters are already placed in answers.q1–q4
const isOptionPlaced = (letter: string) => {
    return ['q1', 'q2', 'q3', 'q4'].some(k => answers.value[k as keyof typeof answers.value] === letter)
}
const allOptionsPlaced = computed(() => dragOptions.every(o => isOptionPlaced(o.letter)))

// Drag handlers
function onDragStart(event, letter) {
    draggingLetter.value = letter
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', letter)
}
function onDragEnd() {
    draggingLetter.value = null
    dragOverTarget.value = null
}
function onDragOver(event, qNum) {
    event.dataTransfer.dropEffect = 'move'
    dragOverTarget.value = qNum
}
function onDragLeave() {
    dragOverTarget.value = null
}
function onDrop(event: DragEvent, qNum: number) {
    const letter = event.dataTransfer?.getData('text/plain')
    if (!letter) return
    // Clear the letter from any other question first
    ;(['q1', 'q2', 'q3', 'q4'] as const).forEach(k => {
        if (answers.value[k] === letter) answers.value[k] = ''
    })
    answers.value[`q${qNum}` as keyof typeof answers.value] = letter
    draggingLetter.value = null
    dragOverTarget.value = null
}
function removeAnswer(key: string) {
    answers.value[key as keyof typeof answers.value] = ''
}
//

const stopResize = () => {
    isResizing.value = false;
};

// Load saved answers when component mounts
onMounted(async () => {
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part1-title"
                    v-html="getHighlightedSegmentById('part1-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part1-desc"
                    v-html="getHighlightedSegmentById('part1-desc')"
                ></p>
            </div>
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">


                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">


                        <div>
                            <p class="tracking-wide select-text">
                                <span data-segment-id="read-text-1-9" v-html="getHighlightedSegmentById('read-text-1-9')"></span>
                            </p>
                        </div>

                        <div class="mt-6 bg-gray-300 text-center text-xl font-bold">
                            <p class="select-text">
                                <span data-segment-id="enrolment-title" v-html="getHighlightedSegmentById('enrolment-title')"></span>
                            </p>
                        </div>

                        <!-- ENROLMENT IN THE SELF-ACCESS CENTRE (SAC) Text -->
                        <div class="space-y-6" :style="contentZoom">
                            <div class="space-y-6 text-base leading-relaxed select-text">
                                <div class="p-4">
                                    <div class="text-justify leading-relaxed whitespace-pre-line text-gray-700">
                                        <span data-segment-id="enrolment-sac" class="text-base text-gray-700"
                                            v-html="getHighlightedSegmentById('enrolment-sac')"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Text 2: Energy saving tips -->
                        <div class="mt-8 border-t-2 border-gray-300 px-6 pt-6">
                            <div class="mb-4">
                                <p class="text-base font-bold tracking-wide select-text">
                                    <span data-segment-id="read-text-10-14" v-html="getHighlightedSegmentById('read-text-10-14')"></span>
                                </p>
                            </div>

                            <div class="bg-gray-300 text-center text-xl font-bold">
                                <p class="select-text">
                                    <span data-segment-id="energy-title" v-html="getHighlightedSegmentById('energy-title')"></span>
                                </p>
                            </div>

                            <div class="space-y-4 select-text" :style="contentZoom">
                                <div class="text-justify leading-relaxed whitespace-pre-line text-gray-700">
                                    <span data-segment-id="energy-text" class="text-base text-gray-700"
                                        v-html="getHighlightedSegmentById('energy-text')"></span>
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
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ═══════════════════════════════════════ -->
                            <!-- QUESTIONS 1–4 · DRAG & DROP            -->
                            <!-- ═══════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <span data-segment-id="q1-4-title" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('q1-4-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-match" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-match')"></span>
                                    </p>
                                </div>

                                <!-- Side-by-side layout: questions left, options right -->
                                <div class="flex gap-4">

                                    <!-- Left: Question rows 1–4 with drop zones -->
                                    <div class="flex-1 space-y-3">
                                        <template v-for="stmt in q1to4Statements" :key="stmt.qNum">
                                            <div
                                                :id="`question-${stmt.qNum}`"
                                                class="relative bg-white p-2"
                                                @mouseenter="hoveredQuestion = stmt.qNum"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <!-- Number badge -->
                                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center  border-gray-900 ">
                                                        <span class="text-base font-bold text-black">{{ stmt.qNum }}</span>
                                                    </div>

                                                    <div class="flex flex-1 flex-wrap items-center gap-2">
                                                        <!-- Question statement text -->
                                                        <span :data-segment-id="stmt.segId" class="text-base text-gray-700 select-text"
                                                            v-html="getHighlightedSegmentById(stmt.segId)"></span>

                                                        <!-- Drop zone -->
                                                        <div
                                                            class="drop-zone flex h-9 min-w-[60px] items-center justify-center border-2 border-dashed transition-colors"
                                                            :class="[
                                                                dragOverTarget === stmt.qNum
                                                                    ? 'border-black bg-yellow-50'
                                                                    : answers[`q${stmt.qNum}`]
                                                                    ? 'border-gray-900 bg-white'
                                                                    : 'border-gray-400 bg-gray-50',
                                                            ]"
                                                            @dragover.prevent="onDragOver($event, stmt.qNum)"
                                                            @dragleave="onDragLeave"
                                                            @drop.prevent="onDrop($event, stmt.qNum)"
                                                        >
                                                            <div
                                                                v-if="answers[`q${stmt.qNum}`]"
                                                                class="placed-chip flex cursor-pointer items-center gap-1 px-3 py-1 text-base font-bold text-gray-900 select-none"
                                                                @click="removeAnswer(`q${stmt.qNum}`)"
                                                                title="Click to remove"
                                                            >
                                                                {{ answers[`q${stmt.qNum}`] }}
                                                                <svg class="h-3 w-3 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </div>
                                                            <span v-else class="text-xs text-gray-400">drop here</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Bookmark button -->
                                                <button
                                                    v-show="hoveredQuestion === stmt.qNum || isQuestionFlagged(stmt.qNum)"
                                                    @click.stop="toggleFlag(stmt.qNum)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(stmt.qNum) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(stmt.qNum) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Right: Draggable option chips (sticky) -->
                                    <div class="w-44 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-xs font-medium text-gray-600">Drag an option to each question:</p>
                                        <div class="border border-gray-900 bg-white p-2">
                                            <p class="mb-2 text-base font-bold text-gray-900">Options</p>
                                            <div class="space-y-1">
                                                <template v-for="opt in dragOptions" :key="opt.letter">
                                                    <div
                                                        v-if="!isOptionPlaced(opt.letter)"
                                                        :draggable="true"
                                                        @dragstart="onDragStart($event, opt.letter)"
                                                        @dragend="onDragEnd"
                                                        class="flex cursor-grab items-center gap-1.5 rounded border border-gray-300 px-2 py-1 text-xs transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing select-none"
                                                        :class="{ 'opacity-50': draggingLetter === opt.letter }"
                                                    >
                                                        <span class="font-bold text-gray-900 shrink-0">{{ opt.letter }}</span>
                                                        <span class="text-gray-600">·</span>
                                                        <span :data-segment-id="`option-${opt.letter.toLowerCase()}`" class="text-gray-700"
                                                            v-html="getHighlightedSegmentById(`option-${opt.letter.toLowerCase()}`)"></span>
                                                    </div>
                                                </template>
                                                <p v-if="allOptionsPlaced" class="text-xs italic text-gray-400">All options placed.</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════ -->
                            <!-- QUESTIONS 5–9 · TRUE / FALSE / NOT GIVEN           -->
                            <!-- ═══════════════════════════════════════════════════ -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <span data-segment-id="q5-9-title" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('q5-9-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-tfn" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-tfn')"></span>
                                        <br />
                                        <span data-segment-id="write-boxes" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('write-boxes')"></span>
                                    </p>
                                </div>

                                <!-- TFN legend -->
                                <div class="mb-6 border border-gray-900 p-4">
                                    <div class="grid grid-cols-1 gap-2 text-base">
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="true-label" class="text-gray-700"
                                                    v-html="getHighlightedSegmentById('true-label')"></span>
                                            </span>
                                            <span data-segment-id="true-desc" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('true-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="false-label" class="text-gray-700"
                                                    v-html="getHighlightedSegmentById('false-label')"></span>
                                            </span>
                                            <span data-segment-id="false-desc" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('false-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="not-given-label" class="text-gray-700"
                                                    v-html="getHighlightedSegmentById('not-given-label')"></span>
                                            </span>
                                            <span data-segment-id="not-given-desc" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('not-given-desc')"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Q5–9 radio rows -->
                                <div class="space-y-6">
                                    <template v-for="stmt in q5to9Statements" :key="stmt.qNum">
                                        <div
                                            :id="`question-${stmt.qNum}`"
                                            class="relative bg-white p-2"
                                            @mouseenter="hoveredQuestion = stmt.qNum"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <div class="mb-2 flex items-start gap-2">
                                                <span class="text-base font-bold text-gray-900">{{ stmt.qNum }}</span>
                                                <p class="text-base text-gray-900 select-text">
                                                    <span :data-segment-id="stmt.segId" class="text-gray-700"
                                                        v-html="getHighlightedSegmentById(stmt.segId)"></span>
                                                </p>
                                            </div>
                                            <div class="ml-6 space-y-2 select-none">
                                                <label v-for="opt in ['TRUE', 'FALSE', 'NOT GIVEN']" :key="opt" class="flex cursor-pointer items-center gap-2">
                                                    <input
                                                        type="radio"
                                                        :name="`q${stmt.qNum}`"
                                                        v-model="answers[`q${stmt.qNum}`]"
                                                        :value="opt"
                                                        class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                    />
                                                    <span class="text-base text-gray-900">{{ opt }}</span>
                                                </label>
                                            </div>

                                            <button
                                                v-show="hoveredQuestion === stmt.qNum || isQuestionFlagged(stmt.qNum)"
                                                @click.stop="toggleFlag(stmt.qNum)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(stmt.qNum) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(stmt.qNum) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════ -->
                            <!-- QUESTIONS 10–14 · FILL IN THE BLANK                -->
                            <!-- ═══════════════════════════════════════════════════ -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <span data-segment-id="q10-14-title" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('q10-14-title')"></span>
                                    </h3>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-complete" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-complete')"></span><br />
                                        <span data-segment-id="words-number" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('words-number')"></span><br />
                                        <span data-segment-id="write-answers" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('write-answers')"></span>
                                    </p>
                                </div>

                                <div class=" border-gray-900 bg-gray-20 p-6 space-y-6">

                                    <!-- Q10 -->
                                    <div id="question-10" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center ">
                                                <span class="text-base font-bold text-black">10</span>
                                            </div>
                                            <p class="flex-1 flex flex-wrap items-center gap-1 text-base leading-relaxed text-gray-800">
                                                <span data-segment-id="q10-part1" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q10-part1')"></span>
                                                <input type="text" v-model="answers.q10" spellcheck="false" autocomplete="off" placeholder="10"
                                                    class="mx-2 inline-block w-32 border-2 text-center border-gray-900 px-2 py-1 text-base focus:border-black focus:outline-none" />
                                                <span data-segment-id="q10-part2" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q10-part2')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q11 -->
                                    <div id="question-11" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center  border-gray-900 ">
                                                <span class="text-base font-bold text-black">11</span>
                                            </div>
                                            <p class="flex-1 flex flex-wrap items-center gap-1 text-base leading-relaxed text-gray-800">
                                                <span data-segment-id="q11-part1" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q11-part1')"></span>
                                                <input type="text" v-model="answers.q11" spellcheck="false" autocomplete="off" placeholder="11"
                                                    class="mx-2 inline-block w-32 border-2 border-gray-900 text-center px-2 py-1 text-base focus:border-black focus:outline-none" />
                                                <span data-segment-id="q11-part2" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q11-part2')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q12 -->
                                    <div id="question-12" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center  border-gray-900 ">
                                                <span class="text-base font-bold text-black">12</span>
                                            </div>
                                            <p class="flex-1 flex flex-wrap items-center gap-1 text-base leading-relaxed text-gray-800">
                                                <span data-segment-id="q12-part1" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q12-part1')"></span>
                                                <input type="text" v-model="answers.q12" spellcheck="false" autocomplete="off" placeholder="12"
                                                    class="mx-2 inline-block w-32 border-2 border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none" />
                                                <span data-segment-id="q12-part2" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q12-part2')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q13 -->
                                    <div id="question-13" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center  border-gray-900 ">
                                                <span class="text-base font-bold text-black">13</span>
                                            </div>
                                            <p class="flex-1 flex flex-wrap items-center gap-1 text-base leading-relaxed text-gray-800">
                                                <span data-segment-id="q13-part1" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q13-part1')"></span>
                                                <input type="text" v-model="answers.q13" spellcheck="false" autocomplete="off" placeholder="13"
                                                    class="mx-2 inline-block w-32 border-2 border-gray-900 px-2 py-1 text-base text-center focus:border-black focus:outline-none" />
                                                <span data-segment-id="q13-part2" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q13-part2')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q14 -->
                                    <div id="question-14" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center ">
                                                <span class="text-base font-bold text-black">14</span>
                                            </div>
                                            <p class="flex-1 flex flex-wrap items-center gap-1 text-base leading-relaxed text-gray-800">
                                                <span data-segment-id="q14-part1" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q14-part1')"></span>
                                                <input type="text" v-model="answers.q14" spellcheck="false" autocomplete="off" placeholder="14"
                                                    class="mx-2 inline-block w-32 border-2 border-gray-900 px-2 py-1 text-base text-center focus:border-black focus:outline-none" />
                                                <span data-segment-id="q14-part2" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('q14-part2')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
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

    <!-- ════════════════════════════════════ -->
    <!-- TELEPORTED OVERLAYS                  -->
    <!-- ════════════════════════════════════ -->
    <Teleport to="body">
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

        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="max-w-[180px] text-base break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
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
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-base focus:border-black focus:outline-none"
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
/* Drag chip hover effect */
.drag-chip {
    transition: box-shadow 0.15s, transform 0.1s;
}
.drag-chip:hover {
    box-shadow: 2px 2px 0 #111;
    transform: translateY(-1px);
}
.drag-chip:active {
    transform: translateY(0);
    box-shadow: none;
}

/* Drop zone highlight */
.drop-zone {
    transition: background-color 0.15s, border-color 0.15s;
}

/* Placed chip remove button visibility */
.placed-chip svg {
    opacity: 0;
    transition: opacity 0.15s;
}
.placed-chip:hover svg {
    opacity: 1;
}
</style>

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
</style>
