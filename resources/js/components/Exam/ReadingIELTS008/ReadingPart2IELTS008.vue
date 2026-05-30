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

// Hover state for showing bookmark buttons
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));



// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Delete highlight tooltip state
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note hover tooltip state
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

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
});

// Drag and drop functionality for questions 28-33
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i', label: 'evidence that a significant number of airports provide meeting facilities.' },
    { value: 'ii', label: 'a statement regarding the fact that no further developments are possible in some areas of airport trade.' },
    { value: 'iii', label: 'reference to the low level of income that meeting facilities produce for airports.' },
    { value: 'iv', label: 'mention of the impact of budget airlines on airport income.' },
    { value: 'v', label: 'mention of the impact of budget airlines on airport income.' },
];

// Filter out used heading options
const availableHeadingOptions = computed(() => {
    const usedOptions = [answers.value.q14, answers.value.q15, answers.value.q16, answers.value.q17, answers.value.q18].filter(Boolean);
    return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
});

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
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
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

// Get label from heading option value for display
const getHeadingLabel = (value: string): string => {
    const option = headingOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Add this helper BEFORE textSegments definition in Part 2:


// Paragraph texts for A-F (split from passage)
const paragraphA = `In recent times developing commercial revenues has become more challenging for airports due to a combination of factors, such as increased competition from Internet shopping, restrictions on certain sales, such as tobacco, and new security procedures that have had an impact on the dwell time of passengers. Moreover, the global economic downturn has caused a reduction in passenger numbers while those that are travelling generally have less money to spend. This has meant that the share subsequently declined slightly. Meanwhile, the pressures to control the level of aeronautical revenues are as strong as ever due to the poor financial health of many airlines and the rapid rise of the low-cost carrier sector.`;

const paragraphB = `Some of the more obvious solutions to growing commercial revenues, such as extending the merchandising space or expanding the variety of shopping opportunities, have already been tried to their limit at many airports. A more radical solution is to find new sources of commercial revenue within the terminal, and this has been explored by many airports over the last decade or so. As a result, many terminals are now much more than just shopping malls and offer an array of entertainment, leisure, and beauty and wellness facilities. At this stage of facilities provision, the airport also has the possibility of talking on the role of the final destination rather than merely a facilitator of access.`;

const paragraphC = `At the same time, airports have been developing and expanding the range of services that they provide specifically for the business traveller in the terminal. This includes offering business centres that supply support services, meeting or conference rooms and other space for special events. Within this context, Jarach (2001) discusses how dedicated meetings facilities located within the terminal and managed directly by the airport operator may be regarded as an expansion of the concept of airline lounges or as a way to reconvert abandoned or underused areas of terminal and managed directly by the airport hotels and other facilities offered in the surrounding area of the airport that had the potential to take on this role and become active as a business space (McNeill, 2009).`;

const paragraphD = `When an airport location can be promoted as a business venue, this may increase the overall appeal of the airport and help it become more competitive in both attracting and retaining airlines and their passengers. In particular, the presence of meeting facilities could become one of the determining factors taken into consideration when business people are choosing airlines and where they change their planes. This enhanced attractiveness itself may help to improve the airport operator’s financial position and future prospects, but clearly, this will be dependent on the competitive advantage that the airport is able to achieve in comparison with other venues.`;

const paragraphE = `In 2011, an online airport survey was conducted and some of the areas investigated included the provision and use of meeting facilities at airports and the perceived role and importance of these facilities in generating income and raising passenger numbers. In total, there were responses from staff at 154 airports and 68% of these answered “yes” to the question: Does your airport own and have meetings facilities available for hire? The existence of meeting facilities, therefore, seems high at airports. In addition, 28% of respondents that did not have meeting facilities stared that they were likely to invest in them during the next five years. The survey also asked to what extent respondents agreed or disagreed with a number of statements about asked the meeting facilities at their airport. 49% of respondents agreed that they would invest more in the immediate future. These are fairly high proportions considering the recent economic climate.`;

const paragraphF = `The survey also asked airport with meeting facilities to estimate what proportion of users are from the local area. i.e. within a 90-minute drive from the airport, or from abroad. Their findings show that meeting facilities provided by the majority of respondents tend to serve local versus non-local or foreign needs. 63% of respondents estimated that over 60% of users are from the local area. Only 3% estimated that over 80% of users are from abroad. It is therefore not surprising that the facilities are of limited importance when it comes to increasing use of fights at the airports: 16% of respondents estimated that none of the users of their meeting facilities uses fights when travelling to or from them, while 56% estimated that 20% or fewer of the users of their facilities use flights.`;

const paragraphG = `The survey asked respondents with meeting facilities to estimate how much revenue their airport earned from its meeting facilities during the last financial year. Average revenue per airport was just $12,959. Meeting facilities are effectively a non-aeronautical source of airport revenue. Only 1% of respondents generated more than 20% non-aeronautical revenue from their meetings facilities; none generated more than 40%. Given the focus on local demand, it is not surprising that less than a third of respondents agreed that their meeting facilities support business and tourism development in their home region or country.`;

const paragraphH = `The findings of this study suggest that few airports provide meetings facilities as a serious commercial venture. It may be that, as owners of large property, space is available for meeting facilities at airports and could play an important role in serving the needs of the airport, its partners, and stakeholders such as government and the local community. Thus, while the local orientation means that competition with other airports is likely to be minimal, competition with local providers of meetings facilities is likely to be much greater.`;


const passageText = `<b>A</b> In recent times developing commercial revenues has become more challenging for airports due to a combination of factors, such as increased competition from Internet shopping, restrictions on certain sales, such as tobacco, and new security procedures that have had an impact on the dwell time of passengers. Moreover, the global economic downturn has caused a reduction in passenger numbers while those that are travelling generally have less money to spend. This has meant that the share subsequently declined slightly. Meanwhile, the pressures to control the level of aeronautical revenues are as strong as ever due to the poor financial health of many airlines and the rapid rise of the low-cost carrier sector.

<b>B</b> Some of the more obvious solutions to growing commercial revenues, such as extending the merchandising space or expanding the variety of shopping opportunities, have already been tried to their limit at many airports. A more radical solution is to find new sources of commercial revenue within the terminal, and this has been explored by many airports over the last decade or so. As a result, many terminals are now much more than just shopping malls and offer an array of entertainment, leisure, and beauty and wellness facilities. At this stage of facilities provision, the airport also has the possibility of talking on the role of the final destination rather than merely a facilitator of access.

<b>C</b> At the same time, airports have been developing and expanding the range of services that they provide specifically for the business traveller in the terminal. This includes offering business centres that supply support services, meeting or conference rooms and other space for special events. Within this context, Jarach (2001) discusses how dedicated meetings facilities located within the terminal and managed directly by the airport operator may be regarded as an expansion of the concept of airline lounges or as a way to reconvert abandoned or underused areas of terminal and managed directly by the airport hotels and other facilities offered in the surrounding area of the airport that had the potential to take on this role and become active as a business space (McNeill, 2009).

<b>D</b> When an airport location can be promoted as a business venue, this may increase the overall appeal of the airport and help it become more competitive in both attracting and retaining airlines and their passengers. In particular, the presence of meeting facilities could become one of the determining factors taken into consideration when business people are choosing airlines and where they change their planes. This enhanced attractiveness itself may help to improve the airport operator’s financial position and future prospects, but clearly, this will be dependent on the competitive advantage that the airport is able to achieve in comparison with other venues.

<b>E</b> In 2011, an online airport survey was conducted and some of the areas investigated included the provision and use of meeting facilities at airports and the perceived role and importance of these facilities in generating income and raising passenger numbers. In total, there were responses from staff at 154 airports and 68% of these answered “yes” to the question: Does your airport own and have meetings facilities available for hire? The existence of meeting facilities, therefore, seems high at airports. In addition, 28% of respondents that did not have meeting facilities stared that they were likely to invest in them during the next five years. The survey also asked to what extent respondents agreed or disagreed with a number of statements about asked the meeting facilities at their airport. 49% of respondents agreed that they would invest more in the immediate future. These are fairly high proportions considering the recent economic climate.

<b>F</b> The survey also asked airport with meeting facilities to estimate what proportion of users are from the local area. i.e. within a 90-minute drive from the airport, or from abroad. Their findings show that meeting facilities provided by the majority of respondents tend to serve local versus non-local or foreign needs. 63% of respondents estimated that over 60% of users are from the local area. Only 3% estimated that over 80% of users are from abroad. It is therefore not surprising that the facilities are of limited importance when it comes to increasing use of fights at the airports: 16% of respondents estimated that none of the users of their meeting facilities uses fights when travelling to or from them, while 56% estimated that 20% or fewer of the users of their facilities use flights.

<b>G</b> The survey asked respondents with meeting facilities to estimate how much revenue their airport earned from its meeting facilities during the last financial year. Average revenue per airport was just $12,959. Meeting facilities are effectively a non-aeronautical source of airport revenue. Only 1% of respondents generated more than 20% non-aeronautical revenue from their meetings facilities; none generated more than 40%. Given the focus on local demand, it is not surprising that less than a third of respondents agreed that their meeting facilities support business and tourism development in their home region or country.

<b>H</b> The findings of this study suggest that few airports provide meetings facilities as a serious commercial venture. It may be that, as owners of large property, space is available for meeting facilities at airports and could play an important role in serving the needs of the airport, its partners, and stakeholders such as government and the local community. Thus, while the local orientation means that competition with other airports is likely to be minimal, competition with local providers of meetings facilities is likely to be much greater.`;


const getPlainText = (html: string) => html.replace(/<[^>]*>/g, '');

const passagePlain = getPlainText(passageText);

// Helper: find plain-text offset of paragraphX within passagePlain
const paraOffset = (paraText: string): number => {
    // paragraphX has no HTML, search directly
    const idx = passagePlain.indexOf(paraText.substring(0, 50));
    return idx === -1 ? 0 : idx;
};

const textSegments = ref([
    // Add to textSegments.value array in Part 2:
     { id: 'segment_1', text: passageText, offset: 0 },
    { id: 'para-a', text: paragraphA, offset: paraOffset(paragraphA) },
{ id: 'para-b', text: paragraphB, offset: paraOffset(paragraphB) },
{ id: 'para-c', text: paragraphC, offset: paraOffset(paragraphC) },
{ id: 'para-d', text: paragraphD, offset: paraOffset(paragraphD) },
{ id: 'para-e', text: paragraphE, offset: paraOffset(paragraphE) },
{ id: 'para-f', text: paragraphF, offset: paraOffset(paragraphF) },
{ id: 'para-g', text: paragraphG, offset: paraOffset(paragraphG) },
{ id: 'para-h', text: paragraphH, offset: paraOffset(paragraphH) },
   { id:'segment_2', text: 'READING PASSAGE 2', offset: 4652 },
    { id:'segment_3', text: 'You should spend about 20 minutes on Questions 14–26, which are based on the reading passage below.', offset: 4671 },
    { id:'segment_4', text: 'The changing role of airports', offset: 4770 },
    {
        id:'segment_5',
        text: 'Airports continue to diversify their role in an effort to generate income. Are business meeting facilities the next step? Nigel Halpern, Anne Graham and Rob Davidson investigate.',
        offset: 4800,
    },
    { id:'segment_6', text: 'Questions 14–18', offset: 4966 },
    { id:'segment_7', text: 'Which paragraph contains the following information?', offset: 4983 },
    { id:'segment_8', text: 'Write the correct letter, A–H, in boxes 14–18 on your answer sheet.', offset: 5035 },
    { id:'segment_9', text: 'evidence that a significant number of airports provide meeting facilities.', offset: 5105 },
    { id:'segment_10', text: 'a statement regarding the fact that no further developments are possible in some areas of airport trade.', offset: 5186 },
    { id:'segment_11', text: 'reference to the low level of income that meeting facilities produce for airports.', offset: 5302 },
    { id:'segment_12', text: 'mention of the impact of budget airlines on airport income.', offset: 5389 },
    { id:'segment_13', text: 'examples of airport premises that might be used for business purposes.', offset: 5454 },
    { id:'segment_14', text: 'Questions 19–22', offset: 5530 },
    { id:'segment_15', text: 'Complete the sentences below.', offset: 5547 },
    { id:'segment_16', text: 'Choose NO MORE THAN TWO WORDS from the text for each answer.', offset: 5577 },
    { id:'segment_17', text: 'Write your answers in boxes 19–22 on your answer sheet.', offset: 5640 },
    { id:'segment_18', text: 'The length of time passengers spend shopping at airports has been affected by updated', offset: 5695 },
    { id:'segment_19', text: 'Airports with a wide range of recreational facilities can become a', offset: 5783 },
    { id:'segment_20', text: 'for people rather than a means to travel.', offset: 5850 },
    { id:'segment_21', text: 'Both passengers and', offset: 5890 },
    { id:'segment_22', text: 'may feel encouraged to use and develop a sense of loyalty towards airports that market their business services.', offset: 5911 },
    { id:'segment_23', text: 'Airports that supply meeting facilities may need to develop a', offset: 6020 },
    { id:'segment_24', text: 'over other venues.', offset: 6091 },
    { id:'segment_25', text: 'Questions 23–26', offset: 6110 },
    { id:'segment_26', text: 'Complete the summary below.', offset: 6127 },
    { id:'segment_27', text: 'Choose NO MORE THAN TWO WORDS from the text for each answer.', offset: 6155 },
    { id:'segment_28', text: 'Survey Findings', offset: 6218 },
    { id:'segment_29', text: 'Despite financial constraints due to the', offset: 6235 },
    { id:'segment_30', text: ', a significant percentage of airport provide and wish to further support business meeting facilities.', offset: 6275 },
    { id:'segment_31', text: 'Also, just under 30% of the airports surveyed plan to provide these facilities within', offset: 6381 },
    { id:'segment_32', text: 'however, the main users of the facilities are', offset: 6468 },
    { id: 'segment_33', text: 'and as many as 16% of respondents to the survey stated that their users did not take any', offset: 6515 },
    { id: 'segment_34', text: 'at the airport.', offset: 6605 },
]);

const allStaticTexts = computed(() => {
    return textSegments.value.map(seg => seg.text);
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
        if (!selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        // Helper function to get absolute offset using TreeWalker for multiline support
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-id]');

            if (!segmentEl) {
                return null;
            }

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            // Handle both string and number segment IDs
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) {
                return null;
            }

            // Use TreeWalker to traverse all text nodes within the segment
            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) {
                    offsetInSegment += offsetInNode;
                    break;
                } else {
                    offsetInSegment += currentNode.textContent?.length || 0;
                }
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

        // Handle reversed selection (selecting from right to left)
        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            // Position tooltip ABOVE the selection with arrow pointing down
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70; // Approximate tooltip height
            const menuY = rect.top - menuHeight - 8; // 8px gap above selection

            const viewportWidth = window.innerWidth;
            const menuWidth = 140; // Smaller width for new design

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10), // Ensure it doesn't go above viewport
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

    // Position note input below the context menu
    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    // Focus the input after a small delay
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
        part: 'Part 3',
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

// Handle note hover to show tooltip
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
                hoveredNoteId.value = note.id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
};

// Confirm delete highlight
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

// Cancel delete highlight
const cancelDeleteHighlight = () => {
    closeDeleteTooltip();
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

const closeContextMenu = () => {
    showContextMenu.value = false;
};

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

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
                y: rect.bottom + 8, // Position below the highlight
            };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
        }
    } else {
        // Clicked elsewhere - close delete tooltip
        closeDeleteTooltip();
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) {
        showContextMenu.value = false;
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

// Save panel width to localStorage
// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32"
    >
        <!-- Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p
                class="text-sm font-bold text-gray-900 select-text"
                data-segment-id="segment_2"
                v-html="getHighlightedSegment(allStaticTexts[9])"
            ></p>
            <p
                class="text-sm text-gray-700 select-text"
                data-segment-id="segment_3"
                v-html="getHighlightedSegment(allStaticTexts[10])"
            ></p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ═══════════════════════════════════
                     READING PASSAGE PANEL (left)
                ═══════════════════════════════════ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-shrink-0 border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900"
                            data-segment-id="segment_4"
                            v-html="getHighlightedSegment(allStaticTexts[11])"></h2>
                        <p class="mt-2 text-sm text-gray-700"
                            data-segment-id="segment_5"
                            v-html="getHighlightedSegment(allStaticTexts[12])"></p>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text sm:text-base">

                            <!-- Paragraph A + Q14 drop zone -->
                            <div class="px-4">
                                <div id="question-14" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 14 ? 'border-gray-500 bg-gray-50' : answers.q14 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 14)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 14)"
                                        @click="clearHeadingAnswer(14)"
                                        :title="answers.q14 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q14">{{ getHeadingLabel(answers.q14) }}</span>
                                        <span v-else class="font-bold text-gray-500">14</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 14)"
                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500 opacity-100' : hoveredQuestion === 14 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">A </span>
                                    <span class="select-text" data-segment-id="para-a"
                                        v-html="getHighlightedSegment(allStaticTexts[1])"></span>
                                </div>
                            </div>

                            <!-- Paragraph B + Q15 drop zone -->
                            <div class="px-4">
                                <div id="question-15" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 15 ? 'border-gray-500 bg-gray-50' : answers.q15 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 15)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 15)"
                                        @click="clearHeadingAnswer(15)"
                                        :title="answers.q15 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q15">{{ getHeadingLabel(answers.q15) }}</span>
                                        <span v-else class="font-bold text-gray-500">15</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 15)"
                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500 opacity-100' : hoveredQuestion === 15 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">B </span>
                                    <span class="select-text" data-segment-id="para-b"
                                        v-html="getHighlightedSegment(allStaticTexts[2])"></span>
                                </div>
                            </div>

                            <!-- Paragraph C + Q16 drop zone -->
                            <div class="px-4">
                                <div id="question-16" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 16 ? 'border-gray-500 bg-gray-50' : answers.q16 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 16)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 16)"
                                        @click="clearHeadingAnswer(16)"
                                        :title="answers.q16 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q16">{{ getHeadingLabel(answers.q16) }}</span>
                                        <span v-else class="font-bold text-gray-500">16</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 16)"
                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500 opacity-100' : hoveredQuestion === 16 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">C </span>
                                    <span class="select-text" data-segment-id="para-c"
                                        v-html="getHighlightedSegment(allStaticTexts[3])"></span>
                                </div>
                            </div>

                            <!-- Paragraph D + Q17 drop zone -->
                            <div class="px-4">
                                <div id="question-17" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 17 ? 'border-gray-500 bg-gray-50' : answers.q17 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 17)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 17)"
                                        @click="clearHeadingAnswer(17)"
                                        :title="answers.q17 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q17">{{ getHeadingLabel(answers.q17) }}</span>
                                        <span v-else class="font-bold text-gray-500">17</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 17)"
                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(17) ? 'border-gray-400 text-red-500 opacity-100' : hoveredQuestion === 17 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">D </span>
                                    <span class="select-text" data-segment-id="para-d"
                                        v-html="getHighlightedSegment(allStaticTexts[4])"></span>
                                </div>
                            </div>

                            <!-- Paragraph E + Q18 drop zone -->
                            <div class="px-4">
                                <div id="question-18" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 18 ? 'border-gray-500 bg-gray-50' : answers.q18 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 18)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 18)"
                                        @click="clearHeadingAnswer(18)"
                                        :title="answers.q18 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q18">{{ getHeadingLabel(answers.q18) }}</span>
                                        <span v-else class="font-bold text-gray-500">18</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 18)"
                                        class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(18) ? 'border-gray-400 text-red-500 opacity-100' : hoveredQuestion === 18 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">E </span>
                                    <span class="select-text" data-segment-id="para-e"
                                        v-html="getHighlightedSegment(allStaticTexts[5])"></span>
                                </div>
                            </div>

                            <!-- Paragraph F (no drop zone) -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">F </span>
                                <span class="select-text" data-segment-id="para-f"
                                    v-html="getHighlightedSegment(allStaticTexts[6])"></span>
                            </div>

                            <!-- Paragraph G -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">G </span>
                                <span class="select-text" data-segment-id="para-g"
                                    v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                            </div>

                            <!-- Paragraph H -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">H </span>
                                <span class="select-text" data-segment-id="para-h"
                                    v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)"/>
                        </svg>
                    </div>
                </div>

                <!-- ═══════════════════════════════════
                     QUESTIONS PANEL (right)
                ═══════════════════════════════════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- Questions 14-18: Paragraph Headings -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="segment_6"
                                            v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                    </h3>
                                    <p class="text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="segment_7"
                                            v-html="getHighlightedSegment(allStaticTexts[14])"></span><br />
                                        <span data-segment-id="segment_8"
                                            v-html="getHighlightedSegment(allStaticTexts[15])"></span>
                                    </p>
                                </div>
                                <div class="border border-gray-300 p-4">
                                    <h4 class="mb-3 text-base font-bold text-gray-900">List of headings:</h4>
                                    <div class="space-y-1.5 text-sm">
                                        <div v-for="option in availableHeadingOptions" :key="option.value"
                                            class="flex cursor-grab items-start gap-2 rounded border border-gray-300 bg-white px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-50': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd">
                                            <svg class="h-4 w-4 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                            <div>
                                                <span class="font-bold text-gray-900">{{ option.value }}</span>
                                                <span class="text-gray-700 block mt-0.5">{{ option.label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 19-22: Sentence Completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="segment_14"
                                            v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                                    </h3>
                                    <p class="text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="segment_15"
                                            v-html="getHighlightedSegment(allStaticTexts[22])"></span><br />
                                        <span data-segment-id="segment_16"
                                            v-html="getHighlightedSegment(allStaticTexts[23])"></span><br />
                                        <span data-segment-id="segment_17"
                                            v-html="getHighlightedSegment(allStaticTexts[24])"></span>
                                    </p>
                                </div>
                                <div class="border border-gray-300 p-6">
                                    <div class="space-y-4 text-base leading-relaxed text-gray-800">
                                        <!-- Q19 -->
                                        <p id="question-19" class="flex flex-wrap gap-2 items-baseline">
                                            <span class="font-bold text-gray-900">19.</span>
                                            <span data-segment-id="segment_18"
                                                v-html="getHighlightedSegment(allStaticTexts[25])"></span>
                                            <input v-model="answers.q19" type="text" placeholder="____"
                                                class="w-40 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                            <span class="text-gray-700">.</span>
                                        </p>
                                        <!-- Q20 -->
                                        <p id="question-20" class="flex flex-wrap gap-2 items-baseline">
                                            <span class="font-bold text-gray-900">20.</span>
                                            <span data-segment-id="segment_19"
                                                v-html="getHighlightedSegment(allStaticTexts[26])"></span>
                                            <input v-model="answers.q20" type="text" placeholder="____"
                                                class="w-40 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                            <span data-segment-id="segment_20"
                                                v-html="getHighlightedSegment(allStaticTexts[27])"></span>
                                        </p>
                                        <!-- Q21 -->
                                        <p id="question-21" class="flex flex-wrap gap-2 items-baseline">
                                            <span class="font-bold text-gray-900">21.</span>
                                            <span data-segment-id="segment_21"
                                                v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                            <input v-model="answers.q21" type="text" placeholder="____"
                                                class="w-40 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                            <span data-segment-id="segment_22"
                                                v-html="getHighlightedSegment(allStaticTexts[29])"></span>
                                        </p>
                                        <!-- Q22 -->
                                        <p id="question-22" class="flex flex-wrap gap-2 items-baseline">
                                            <span class="font-bold text-gray-900">22.</span>
                                            <span data-segment-id="segment_23"
                                                v-html="getHighlightedSegment(allStaticTexts[30])"></span>
                                            <input v-model="answers.q22" type="text" placeholder="____"
                                                class="w-40 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                            <span data-segment-id="segment_24"
                                                v-html="getHighlightedSegment(allStaticTexts[31])"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 23-26: Summary Completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-2">
                                        <span data-segment-id="segment_25"
                                            v-html="getHighlightedSegment(allStaticTexts[32])"></span>
                                    </h3>
                                    <p class="text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="segment_26"
                                            v-html="getHighlightedSegment(allStaticTexts[33])"></span><br />
                                        <span data-segment-id="segment_27"
                                            v-html="getHighlightedSegment(allStaticTexts[34])"></span>
                                    </p>
                                    <p class="mt-2 text-center font-semibold text-gray-900"
                                        data-segment-id="segment_28"
                                        v-html="getHighlightedSegment(allStaticTexts[35])"></p>
                                </div>
                                <div class="border border-gray-300 p-6">
                                    <p class="text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="segment_29"
                                            v-html="getHighlightedSegment(allStaticTexts[36])"></span>
                                        <span class="mx-1 inline-flex items-center gap-1">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">23</span>
                                            <input v-model="answers.q23" type="text" placeholder="____"
                                                class="w-32 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                        </span>
                                        <span data-segment-id="segment_30"
                                            v-html="getHighlightedSegment(allStaticTexts[37])"></span>
                                        <span class="mx-1 inline-flex items-center gap-1">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">24</span>
                                            <input v-model="answers.q24" type="text" placeholder="____"
                                                class="w-32 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                        </span>
                                        <span data-segment-id="segment_31"
                                            v-html="getHighlightedSegment(allStaticTexts[38])"></span>
                                        <span class="mx-1 inline-flex items-center gap-1">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">25</span>
                                            <input v-model="answers.q25" type="text" placeholder="____"
                                                class="w-32 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                        </span>
                                        <span data-segment-id="segment_32"
                                            v-html="getHighlightedSegment(allStaticTexts[39])"></span>
                                        <span class="mx-1 inline-flex items-center gap-1">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">26</span>
                                            <input v-model="answers.q26" type="text" placeholder="____"
                                                class="w-32 border-b-2 border-gray-400 bg-gray-50 px-2 py-1 text-center text-sm focus:border-gray-600 focus:outline-none"/>
                                        </span>
                                        <span data-segment-id="segment_33"
                                            v-html="getHighlightedSegment(allStaticTexts[40])"></span>
                                        <span data-segment-id="segment_34"
                                            v-html="getHighlightedSegment(allStaticTexts[41])"></span>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport overlays -->
        <Teleport to="body">
            <!-- Context menu -->
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
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
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
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Delete Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
                <div class="note-hover-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span><svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg></span>
                        <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="rounded p-1 hover:bg-red-50" title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                        "{{ selectedText }}"
                    </p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote"/>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                        class="border border-gray-900 bg-white px-3 py-0.5 text-xs font-medium text-gray-900 hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-0.5 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
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
