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

// Reading Part 2 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part2-panel-width';
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

const q15to21Statements = [
    { qNum: 15, segId: 'q15-statement', text: 'The company wants its workers to wear comfortable clothes.' },
    { qNum: 16, segId: 'q16-statement', text: 'Employees dealing with external visitors should wear smart-casual attire.' },
    { qNum: 17, segId: 'q17-statement', text: 'Lab workers must keep their fingernails short.' },
    { qNum: 18, segId: 'q18-statement', text: 'Sturdy footwear is compulsory for warehouse workers.' },
    { qNum: 19, segId: 'q19-statement', text: 'Clothing should always be clean and pressed.' },
    { qNum: 20, segId: 'q20-statement', text: 'Machine operators must remove all jewellery.' },
    { qNum: 21, segId: 'q21-statement', text: 'Employees who come into contact with customers must not have visible tattoos.' },
]

// Drag and drop functionality for questions 14-18
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const speciesOptions = [
    { value: 'A', label: 'Homo neanderthalensis', fullText: 'A Homo neanderthalensis' },
    { value: 'B', label: 'Homo sapiens', fullText: 'B Homo sapiens' },
    { value: 'C', label: 'Both species', fullText: 'C Both species' },
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
const getSpeciesLabel = (value: string): string => {
    const option = speciesOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Text segments with their cumulative offsets in the full text
const dressCodePolicyText = `


<strong>Policy Statement</strong>
Employees are representatives of the company and should dress appropriately. It is recognised that employees dealing with customers should dress accordingly, whereas those working in the office, laboratories or warehouses should dress in a manner consistent with the nature of their work and Health and Safety regulations.

<strong>Dress Code Requirements</strong>
An employee's schedule of duties should largely determine his or her work attire. The company's objective is to allow employees to work comfortably, so smart-casual attire is the standard except in the following circumstances:

When employees are meeting with clients, community visitors or interviewing candidates, in order to project a professional image, they should dress in a conventional business-like manner.

Employees must abide by the safety procedures of their departments and wear whatever protective clothing and/or safety equipment is necessary.

<strong>In the Laboratory</strong>
Long-sleeved shirts and trousers offer the best protection. Shorts and short skirts do not give adequate protection to your legs.

Loose-fitting clothing and long hair may create a fire hazard when burners are in use: loose-fitting clothing is unacceptable but long hair can be secured with a rubber band.

Sandals and open-toed shoes are not suitable footwear.

Clothing such as ties, scarves or long jewelry, which could droop in chemicals or a flame, should be removed.

Hair spray is highly flammable and should not be used before entering the lab.

Synthetic fingernails are also highly flammable and therefore not permissible.

<strong>In the Warehouse</strong>
Dress codes for warehouse employees are also based on safety concerns: thus the requirement for steel-toed boots and durable trousers (jeans are acceptable) or overalls. Workers may wear T-shirts as long as they conform to the rules.

<strong>Inappropriate clothing</strong>
Sport-related clothing including T-shirts or ties with slogans or club insignia
T-shirts or tops displaying offensive pictures or bad language
Revealing clothing that exposes too much cleavage, your back, chest, feet, stomach or your underwear
Wrinkled, torn, dirty, or frayed clothing


<strong>Some common issues – piercings, tattoos, hairstyle, jewellery</strong>
Any jewellery that could pose a safety hazard for employees operating machinery must be removed.
Employees wearing a ‘business’ standard of dress may be asked to remove certain piercings (such as nose and eyebrow rings) and cover up tattoos.
Hairstyles should always be tidy and hair should never hang over the eyes.

All employees of the company should adhere to the dress code policy at all times.


`;

// Text 2: Customer Service Tips
const customerServiceText = `

Many complaints are made by telephone. In this case, there is a simple 7-step procedure to follow. At the outset, you should make a note of the person's phone number and explain that you have done this in case the call should be disconnected for any reason. Getting cut off is a major source of upset and distress, so demonstrating that you have guarded against this is a positive first step. This is especially important if the customer has been transferred, made to wait or if there have been previous attempts at resolving the problem.

State your name clearly because customers are tired of automated answer-phone menu systems and anonymous voices at the end of the line. You should also provide the customer with your direct line so that he feels he can make contact with you quickly and easily in the future. Establishing fast personal responsibility in this way is another positive step.

Explain to the customer that you will deal with the issue until it is resolved. Your making a personal commitment to do this lightens the pressure on a frustrated customer. Once you have done this, just listen – be sure to listen with empathy and feeling – and let the customer unload his problem. He is most likely feeling very angry and exasperated and you can help him calm down and make him feel better by listening patiently and trying to understand how he feels.

Take notes – get the facts and write them down, even if it takes time. The customer will appreciate this as it shows you are treating his problem seriously and with respect. Asking the person to focus on the facts can also help defuse an emotional situation.

If the complaint is justified and shows that our company's product is defective or our service is to blame, then you should immediately acknowledge the problem and unreservedly apologise. Finally, quickly move on to settling the issue. However, it is a mistake to guarantee remedial action or compensation that you or the company will be unable to deliver.`;

const buildTextSegments = () => {
    const segments: { id: string; text: string; offset: number }[] = [];
    let currentOffset = 0;

    const addSegment = (id: string, text: string) => {
        const plainTextLength = text.replace(/<[^>]*>/g, '').length;
        segments.push({ id, text, offset: currentOffset });
        currentOffset += plainTextLength;
    };

    // SECTION 2 header
    addSegment('section2', 'Part 2');
    addSegment('questions-15-27', 'Read and answer Questions 15–27');
    addSegment('read-text-15-21', 'Read the text below and answer Questions 15–21');
    addSegment('dress-code-title', 'Company Dress Code Policy');

    // Dress Code Policy passage
    addSegment('dress-code-text', dressCodePolicyText);

     // Section for Customer Service tips
    addSegment('read-text-22-27', 'Read the text below and answer Questions 22–27');
    addSegment('customer-service-title', 'Customer service – tips for handling complaints');

    // Customer Service text
    addSegment('customer-service-text', customerServiceText);

    // Questions 15-21 section
    addSegment('q15-21-title', 'Questions 15–21');
    addSegment('instructions-15-21', 'Do the following statements agree with the information given in the text?');
    addSegment('write-boxes-15-21', 'In boxes 15–21 on your answer sheet write:');
    addSegment('true-label', 'TRUE');
    addSegment('true-desc', 'if the statement agrees with the information');
    addSegment('false-label', 'FALSE');
    addSegment('false-desc', 'if the statement contradicts the information');
    addSegment('not-given-label', 'NOT GIVEN');
    addSegment('not-given-desc', 'if there is no information on this');

    // Question statements 15-21
    addSegment('q15-statement', 'The company wants its workers to wear comfortable clothes.');
    addSegment('q16-statement', 'Employees dealing with external visitors should wear smart-casual attire.');
    addSegment('q17-statement', 'Lab workers must keep their fingernails short.');
    addSegment('q18-statement', 'Sturdy footwear is compulsory for warehouse workers.');
    addSegment('q19-statement', 'Clothing should always be clean and pressed.');
    addSegment('q20-statement', 'Machine operators must remove all jewellery.');
    addSegment('q21-statement', 'Employees who come into contact with customers must not have visible tattoos.');



    // Questions 22-27 section
    addSegment('q22-27-title', 'Questions 22–27');
    addSegment('instructions-flowchart', 'Complete the flow-chart below.');
    addSegment(
        'words-number',
        'Choose NO MORE THAN TWO WORDS from the text for each answer. Write your answers in boxes 22–27 on your answer sheet.',
    );

    // In the buildTextSegments function, add these segments for questions 22-27:

    // Step titles
    addSegment('step1', 'Step 1');
    addSegment('step2', 'Step 2');
    addSegment('step3', 'Step 3');
    addSegment('step4', 'Step 4');
    addSegment('step5', 'Step 5');
    addSegment('step6', 'Step 6');
    addSegment('step7', 'Step 7');

    // Step descriptions
    addSegment('step1-text', "Write down the caller's");
    addSegment('step2-text', 'Give your own name and');
    addSegment('step3-text', 'Promise to see the problem through to the end.');
    addSegment('step6-text', 'If the company is at fault, accept and');
    addSegment('step7-text', "Try to resolve the situation but don't promise what you can't");
    return segments;
};

const textSegments = ref(buildTextSegments());

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
                globalEnd = endSeg.offset + preEndRange.toString().length;
            }
        }

        if (globalEnd <= globalStart) return;

        selectedText.value   = selected;
        selectionRange.value = { start: globalStart, end: globalEnd };
    }, 10);
};

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">



        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
             <!-- Section Header -->
        <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
            <p class="text-base font-bold text-gray-900 select-text">
                <span data-segment-id="section2" v-html="getHighlightedSegmentById('section2')"></span>
            </p>
            <p class="text-sm text-gray-700 select-text">
                <span data-segment-id="questions-15-27" v-html="getHighlightedSegmentById('questions-15-27')"></span>
            </p>
        </div>
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage ── -->
                <div class="passage-panel mb-2 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">

                        <!-- Text 1: Dress Code -->
                        <div class="mb-1">
                            <p class="text-sm tracking-wide select-text">
                                <span data-segment-id="read-text-15-21" v-html="getHighlightedSegmentById('read-text-15-21')"></span>
                            </p>
                        </div>
                        <div class="mb-1 bg-gray-300 text-center text-xl font-bold">
                            <p class="select-text">
                                <span data-segment-id="dress-code-title" v-html="getHighlightedSegmentById('dress-code-title')"></span>
                            </p>
                        </div>
                        <div class="space-y-6" :style="contentZoom">
                            <div class="space-y-6 text-sm leading-relaxed select-text">
                                <div class="p-4">
                                    <div class="text-justify leading-relaxed whitespace-pre-line text-gray-700">
                                        <span data-segment-id="dress-code-text" class="text-base text-gray-700"
                                            v-html="getHighlightedSegmentById('dress-code-text')"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Text 2: Customer Service -->
                        <div class="mt-8 border-t-2 border-gray-300 pt-6">
                            <div class="mb-4">
                                <p class="text-sm font-bold tracking-wide select-text">
                                    <span data-segment-id="read-text-22-27" v-html="getHighlightedSegmentById('read-text-22-27')"></span>
                                </p>
                            </div>
                            <div class="bg-gray-300 text-center text-xl font-bold">
                                <p class="select-text">
                                    <span data-segment-id="customer-service-title" v-html="getHighlightedSegmentById('customer-service-title')"></span>
                                </p>
                            </div>
                            <div class="mt-4 space-y-4 select-text" :style="contentZoom">
                                <div class="text-justify leading-relaxed whitespace-pre-line text-gray-700">
                                    <span data-segment-id="customer-service-text" class="text-base text-gray-700"
                                        v-html="getHighlightedSegmentById('customer-service-text')"></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Panel ── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Q15–21 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q15-21-title" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('q15-21-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-15-21" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-15-21')"></span><br />
                                        <span data-segment-id="write-boxes-15-21" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('write-boxes-15-21')"></span>
                                    </p>
                                </div>

                                <!-- TFN legend -->
                                <div class="mb-6 border border-gray-900 p-4">
                                    <div class="grid grid-cols-1 gap-2 text-sm">
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

                                <!-- Q15–Q21 rows -->
                                <div class="space-y-4">
                                    <template v-for="stmt in q15to21Statements" :key="stmt.qNum">
                                        <div :id="`question-${stmt.qNum}`"
                                            class="relative bg-white p-2"
                                            @mouseenter="hoveredQuestion = stmt.qNum"
                                            @mouseleave="hoveredQuestion = null">

                                            <div class="flex items-start gap-4">
                                                <!-- Question Number -->
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center">
                                                    <span class="text-base font-bold text-black">{{ stmt.qNum }}</span>
                                                </div>

                                                <!-- Changed to flex-col for vertical stacking -->
                                                <div class="flex flex-1 flex-col gap-2">
                                                    <!-- Question Text -->
                                                    <div>
                                                        <p class="text-base leading-relaxed text-gray-700 select-text">
                                                            <span :data-segment-id="stmt.segId" class="text-gray-700"
                                                                v-html="getHighlightedSegmentById(stmt.segId)"></span>
                                                        </p>
                                                    </div>

                                                    <!-- Input Box (Select) -->
                                                    <div>
                                                        <select v-model="answers[`q${stmt.qNum}`]"
                                                            class="w-1/4 border border-gray-900 px-3 py-1 text-sm focus:border-black focus:outline-none">
                                                            <option value="">Select</option>
                                                            <option value="TRUE">TRUE</option>
                                                            <option value="FALSE">FALSE</option>
                                                            <option value="NOT GIVEN">NOT GIVEN</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Bookmark Button -->
                                            <button v-show="hoveredQuestion === stmt.qNum || isQuestionFlagged(stmt.qNum)"
                                                @click.stop="toggleFlag(stmt.qNum)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(stmt.qNum) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(stmt.qNum) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Q22–27 flowchart -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q22-27-title" class="text-gray-700"
                                                v-html="getHighlightedSegmentById('q22-27-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-flowchart" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('instructions-flowchart')"></span><br />
                                        <span data-segment-id="words-number" class="text-gray-700"
                                            v-html="getHighlightedSegmentById('words-number')"></span>
                                    </p>
                                </div>

                                <div class="border border-gray-900 bg-gray-50 p-6">

                                    <!-- Step 1 – Q22 -->
                                    <div id="question-22" class="relative mb-6"
                                        @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">

                                            <div class="flex-1">

                                                <div class="flex flex-wrap items-center justify-center gap-2">
                                                    <span data-segment-id="step1-text" class="text-gray-700 select-text"
                                                        v-html="getHighlightedSegmentById('step1-text')"></span>
                                                    <input type="text" v-model="answers.q22" spellcheck="false" autocomplete="off" placeholder="22"
                                                        class="w-40 border border-gray-900 px-3 py-1.5 text-sm focus:border-black focus:outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <div class="mb-4 flex justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <!-- Step 2 – Q23 -->
                                    <div id="question-23" class="relative mb-6"
                                        @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">

                                            <div class="flex-1">

                                                <div class="flex flex-wrap items-center justify-center gap-2">
                                                    <span data-segment-id="step2-text" class="text-gray-700 select-text"
                                                        v-html="getHighlightedSegmentById('step2-text')"></span>
                                                    <input type="text" v-model="answers.q23" spellcheck="false" autocomplete="off" placeholder="23"
                                                        class="w-40 border border-gray-900 px-3 py-1.5 text-sm focus:border-black focus:outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                            @click.stop="toggleFlag(23)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <div class="mb-4 flex justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <!-- Step 3 – no question -->
                                    <div class="mb-6">
                                        <div class="flex items-start gap-4">
                                            <div class="h-8 w-8 shrink-0 opacity-0"></div>
                                            <div class="flex-1">

                                                <p class="text-base text-center text-gray-700 select-text">
                                                    <span data-segment-id="step3-text" class="text-gray-700"
                                                        v-html="getHighlightedSegmentById('step3-text')"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4 flex justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <!-- Step 4 – Q24 -->
                                    <div id="question-24" class="relative mb-6"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start  gap-4">

                                            <div class="flex-1 text-center  gap-2">

                                                <input type="text" v-model="answers.q24" spellcheck="false" autocomplete="off" placeholder="24"
                                                    class="w-40 border border-gray-900 px-3 py-1.5 items-center justify-center text-sm focus:border-black focus:outline-none" />
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                            @click.stop="toggleFlag(24)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <div class="mb-4 flex justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <!-- Step 5 – Q25 -->
                                    <div id="question-25" class="relative mb-6"
                                        @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">

                                            <div class="flex-1 text-center gap-2">

                                                <input type="text" v-model="answers.q25" spellcheck="false" autocomplete="off" placeholder="25"
                                                    class="w-40 border border-gray-900 px-3 py-1.5 text-sm items-center justify-centerfocus:border-black focus:outline-none" />
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                            @click.stop="toggleFlag(25)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <div class="mb-4 flex justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <!-- Step 6 – Q26 -->
                                    <div id="question-26" class="relative mb-6"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">

                                            <div class="flex-1 text-center gap-2">

                                                <div class="flex flex-wrap text-center items-center justify-center gap-2">
                                                    <span data-segment-id="step6-text" class="text-gray-700 select-text"
                                                        v-html="getHighlightedSegmentById('step6-text')"></span>
                                                    <input type="text" v-model="answers.q26" spellcheck="false" autocomplete="off" placeholder="26"
                                                        class="w-40 border border-gray-900 px-3 py-1.5  text-sm focus:border-black focus:outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                            @click.stop="toggleFlag(26)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <div class="mb-4 flex justify-center">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <!-- Step 7 – Q27 -->
                                    <div id="question-27" class="relative"
                                        @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-4">

                                            <div class="flex-1">

                                                <div class="flex flex-wrap items-center justify-center gap-2">
                                                    <span data-segment-id="step7-text" class="text-gray-700 select-text"
                                                        v-html="getHighlightedSegmentById('step7-text')"></span>
                                                    <input type="text" v-model="answers.q27" spellcheck="false" autocomplete="off" placeholder="27"
                                                        class="w-38 border border-gray-900 px-3 py-1.5 text-sm focus:border-black focus:outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                            @click.stop="toggleFlag(27)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
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

        <!-- TELEPORTED OVERLAYS -->
        <Teleport to="body">

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
                                <line x1="10" y1="11" x2="10" y2="17" />
                                <line x1="14" y1="11" x2="14" y2="17" />
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
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="border border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
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
</style>
