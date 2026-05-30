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

// Hover state for showing bookmark buttons
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Reading Part 3 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts003-part2-panel-width';
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
    { value: 'i', label: ' A description of the procedure' },
    { value: 'ii', label: 'An international research project' },
    { value: 'iii', label: 'An experiment to investigate consumer responses' },
    { value: 'iv', label: 'Marketing an alternative name' },
    { value: 'v', label: 'misleading name' },
    { value: 'vi', label: 'A potentially profitable line of research' },
    { value: 'vii', label: 'Medical dangers of the technique' },
    { value: 'viii', label: 'Drawbacks to marketing tools' },
    { value: 'ix', label: 'Broadening applications' },
    { value: 'x', label: 'What is neuromarketing?' },
];

// Filter out used heading options
const availableHeadingOptions = computed(() => {
    const usedOptions = [answers.value.q14, answers.value.q15, answers.value.q16, answers.value.q17, answers.value.q18, answers.value.q19].filter(Boolean);
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

// Paragraph texts for A-F (split from passage)
const paragraphA =`<b>A</b>. Marketing people are no longer prepared to take your word for it that you favour one product over another. They want to scan your brain to see which one you really prefer. Using the tools of neuroscientists, such as electroencephalogram (EEG) mapping and functional magnetic-resonance imaging (fMRI), they are trying to learn more about the mental processes behind purchasing decisions. The resulting fusion of neuroscience and marketing is inevitably, being  called 'neuromarketing’.`

const paragraphB = `<b>B</b>. The first person to apply brain-imaging technology in this way was Gerry Zaltman of Harvard University, in the late 1990s. The idea remained in obscurity until 2001, when BrightHouse, a marketing consultancy based in Atlanta, Georgia, set up a dedicated neuromarketing arm, BrightHouse Neurostrategies Group. (BrightHouse lists Coca -Cola, Delta Airlines and Home Depot among its clients.) But the company's name may itself simply be an example of clever marketing. BrightHouse does not scan people while showing them specific products or campaign ideas, but bases its work on the results of more general f MRI -based research into consumer preferences and decision-making carried out at Emory University in Atlanta.`
const paragraphC = `<b>C</b>. Can brain scanning really be applied to marketing? The basic principle is not that different from focus groups and other traditional forms of market research. A volunteer lies in an f MRI machine and is shown images or video clips. In place of an interview or questionnaire, the subject's response is evaluated by monitoring brain activity. f MRI provides real-time images of brain activity, in which different areas “light up” depending on the level of blood flow. This provides clues to the subje ct's subconscious thought patterns. Neuroscientists know, for example, that the sense of self is associated with an area of the brain known as the medial prefrontal cortex. A flow of blood to that area while the subject is looking at a particular logo suggests that he or she identifies with that brand.`
const paragraphD = `<b>D</b>. At first, it seemed that only companies in Europe were prepared to admit that they used neuromarketing. Two carmakers, DaimlerChrysler in Germany and Ford's European arm, ran pilot studies in 2003. But more recently, American companies have become more open about their use of neuromarketing. Lieberman Research Worldwide, a marketing firm based in Los Angeles, is collaborating with the California Institute of Technology (Caltech) to enable movie studios to market-test film trailers. More controversially, the New York Times recently reported that a political consultancy, FKF Research, has been studying the effectiveness of campaign commercials using neuromarketing techniques.`
const paragraphE = `<b>E</b>. Whether all this is any more than a modern-day version of phrenology, the Victorian obsession with linking lumps and bumps in the skull to personality traits, is unclear. There have been no large-scale studies, so scans of a handful of subjects may not be a reliable guide to consumer behaviour in general. Of course, focus groups and surveys are flawed too: strong personalities can steer the outcomes of focus groups, and people do not always tell opinion pollsters the truth. And even honest people cannot always explain their preferences.`
const paragraphF = `<b>F</b>. That is perhaps where neuromarketing has the most potential. When asked about cola drinks, most people claim to have a favourite brand, but cannot say why they prefer that brand’s taste. An unpublished study of attitudes towards two well- known cola drinks. Brand A and Brand 13.carried out last year in a college of medicine in the US found that most subjects preferred Brand B in a blind tasting fMRI scanning showed that drinking Brand B lit up a region called the ventral putamen, which is one of the brain s ‘reward centres’, far more brightly than Brand A. But when told which drink was which, most subjects said they preferred Brand A, which suggests that its stronger brand outweighs the more pleasant taste of the ot her drink.`
const paragraphG = `<b>G</b>.“ People form many unconscious attitudes that are obviously beyond traditional methods that utilise introspection,” says Steve n Quartz, a neuroscientist at Caltech who is collaborating with Lieberman Research. With over $100 billion spent each year on marketing in America alone, any firm that can more accurately analyse how customers respond to products, brands and advertising could make a fortune.`
const paragraphH = `<b>H</b>. Consumer advocates are wary. Gary Ruskin of Commercial Alert, a lobby group, thinks existing marketing techniques are powerful enough. “Already, marketing is deeply implicated in many serious pathologies,” he says. “That is especially true of children, who are suffering from an epidemic of marketing- related diseases, including obesity and type-2 diabetes. Neuromarketing is a tool to amplify these trends.”`
const paragraphI = `<b>I</b>. Dr Quartz counters that neuromarketing techniques could equally be used for benign purposes. “There are ways to utilise these technologies to create more responsible advertising,” he says. Brain-scanning could, for example, be used to determine when people are capable of making free choices, to ensure that advertising falls within those bounds.`
const paragraphJ = `<b>J</b>. Another worry is that brain-scanning is an invasion of privacy and that information on the preferences of specific individuals will be misused. But neuromarketing studies rely on small numbers of volunteer subjects, so that seems implausible. Critics also object to the use of medical equipment for frivolous rather than medical purposes. But as Tim Ambler, a neuromarketing researcher at the London Business School, says: ‘A tool is a tool, and if the owner of the tool gets a decent rent for hiring it out, then that subsidises the cost of the equipment, and everybody wins.’ Perhaps more brain- scanning will some day explain why some people like the idea of neuromarketing, but others do not.`;

const passageText = `A.
Marketing people are no longer prepared to take your word for it that you favour one product over another. They want to scan your brain to see which one you really prefer. Using the tools of neuroscientists, such as electroencephalogram (EEG) mapping and functional magnetic-resonance imaging (fMRI), they are trying to learn more about the mental processes behind purchasing decisions. The resulting fusion of neuroscience and marketing is inevitably, being  called 'neuromarketing’.

B.
The first person to apply brain-imaging technology in this way was Gerry Zaltman of Harvard University, in the late 1990s. The idea remained in obscurity until 2001, when BrightHouse, a marketing consultancy based in Atlanta, Georgia, set up a dedicated neuromarketing arm, BrightHouse Neurostrategies Group. (BrightHouse lists Coca -Cola, Delta Airlines and Home Depot among its clients.) But the company's name may itself simply be an example of clever marketing. BrightHouse does not scan people while showing them specific products or campaign ideas, but bases its work on the results of more general f MRI -based research into consumer preferences and decision-making carried out at Emory University in Atlanta.

C.
Can brain scanning really be applied to marketing? The basic principle is not that different from focus groups and other traditional forms of market research. A volunteer lies in an f MRI machine and is shown images or video clips. In place of an interview or questionnaire, the subject's response is evaluated by monitoring brain activity. f MRI provides real-time images of brain activity, in which different areas “light up” depending on the level of blood flow. This provides clues to the subje ct's subconscious thought patterns. Neuroscientists know, for example, that the sense of self is associated with an area of the brain known as the medial prefrontal cortex. A flow of blood to that area while the subject is looking at a particular logo suggests that he or she identifies with that brand.

D.
At first, it seemed that only companies in Europe were prepared to admit that they used neuromarketing. Two carmakers, DaimlerChrysler in Germany and Ford's European arm, ran pilot studies in 2003. But more recently, American companies have become more open about their use of neuromarketing. Lieberman Research Worldwide, a marketing firm based in Los Angeles, is collaborating with the California Institute of Technology (Caltech) to enable movie studios to market-test film trailers. More controversially, the New York Times recently reported that a political consultancy, FKF Research, has been studying the effectiveness of campaign commercials using neuromarketing techniques.

E.
Whether all this is any more than a modern-day version of phrenology, the Victorian obsession with linking lumps and bumps in the skull to personality traits, is unclear. There have been no large-scale studies, so scans of a handful of subjects may not be a reliable guide to consumer behaviour in general. Of course, focus groups and surveys are flawed too: strong personalities can steer the outcomes of focus groups, and people do not always tell opinion pollsters the truth. And even honest people cannot always explain their preferences.

F.
That is perhaps where neuromarketing has the most potential. When asked about cola drinks, most people claim to have a favourite brand, but cannot say why they prefer that brand’s taste. An unpublished study of attitudes towards two well- known cola drinks. Brand A and Brand 13.carried out last year in a college of medicine in the US found that most subjects preferred Brand B in a blind tasting fMRI scanning showed that drinking Brand B lit up a region called the ventral putamen, which is one of the brain s ‘reward centres’, far more brightly than Brand A. But when told which drink was which, most subjects said they preferred Brand A, which suggests that its stronger brand outweighs the more pleasant taste of the ot her drink.

G.
“People form many unconscious attitudes that are obviously beyond traditional methods that utilise introspection,” says Steve n Quartz, a neuroscientist at Caltech who is collaborating with Lieberman Research. With over $100 billion spent each year on marketing in America alone, any firm that can more accurately analyse how customers respond to products, brands and advertising could make a fortune.

H.
Consumer advocates are wary. Gary Ruskin of Commercial Alert, a lobby group, thinks existing marketing techniques are powerful enough. “Already, marketing is deeply implicated in many serious pathologies,” he says. “That is especially true of children, who are suffering from an epidemic of marketing- related diseases, including obesity and type-2 diabetes. Neuromarketing is a tool to amplify these trends.”

I.
Dr Quartz counters that neuromarketing techniques could equally be used for benign purposes. “There are ways to utilise these technologies to create more responsible advertising,” he says. Brain-scanning could, for example, be used to determine when people are capable of making free choices, to ensure that advertising falls within those bounds.

J.
Another worry is that brain-scanning is an invasion of privacy and that information on the preferences of specific individuals will be misused. But neuromarketing studies rely on small numbers of volunteer subjects, so that seems implausible. Critics also object to the use of medical equipment for frivolous rather than medical purposes. But as Tim Ambler, a neuromarketing researcher at the London Business School, says: ‘A tool is a tool, and if the owner of the tool gets a decent rent for hiring it out, then that subsidises the cost of the equipment, and everybody wins.’ Perhaps more brain- scanning will some day explain why some people like the idea of neuromarketing, but others do not.`;

const allSegments = [
    // ── Part 2 Header ──────────────────────────────────────────────────────────
    { id: 'part-header',       text: 'Part 2' },
    { id: 'part-instructions', text: 'Read the text and answer questions 15-27.' },

    // ── Passage title ──────────────────────────────────────────────────────────
    {
        id: 'title',
        text: 'Inside the mind of the consumer\nCould brain-scanning technology provide an accurate way to assess the appeal of new products and the effectiveness of advertising?',
    },

    // ── Full passage (kept for backwards-compat with stored highlight offsets) ─
    { id: 'passage', text: passageText },

    // ── Individual paragraph segments (FIX 1) ──────────────────────────────────
    // Each paragraph variable is now registered separately so that
    // getHighlightedSegment(paragraphX) can find a matching entry via text equality.
    { id: 'para-a', text: paragraphA },
    { id: 'para-b', text: paragraphB },
    { id: 'para-c', text: paragraphC },
    { id: 'para-d', text: paragraphD },
    { id: 'para-e', text: paragraphE },
    { id: 'para-f', text: paragraphF },
    { id: 'para-g', text: paragraphG },
    { id: 'para-h', text: paragraphH },
    { id: 'para-i', text: paragraphI },
    { id: 'para-j', text: paragraphJ },

    // ── Questions 14–19 ────────────────────────────────────────────────────────
    { id: 'q14-19-title',         text: 'Questions 14-19' },
    { id: 'q14-19-instructions',  text: 'Reading Passage 2 has nine paragraphs A-J.' },
    { id: 'q14-19-instructions-2',text: 'Choose the most suitable heading for paragraphs B-G from the list of headings below.' },
    { id: 'q14-19-boxes',         text: 'Write the correct number ' },
    { id: 'q14-19-range',         text: '(i-x)' },
    { id: 'q14-19-boxes-2',       text: ' in boxes ' },
    { id: 'q14-19-numbers',       text: '14-19' },
    { id: 'q14-19-boxes-3',       text: ' on your answer sheet.' },

    { id: 'list-headings-title',  text: 'List of headings:' },
    // Note: the draggable heading items render via {{ option.label }} directly
    // and do not go through getHighlightedSegment, so they are not listed here.

    { id: 'q14-text', text: 'Paragraph B' },
    { id: 'q15-text', text: 'Paragraph C' },
    { id: 'q16-text', text: 'Paragraph D' },
    { id: 'q17-text', text: 'Paragraph E' },
    { id: 'q18-text', text: 'Paragraph F' },
    { id: 'q19-text', text: 'Paragraph G' },

    // ── Questions 20–22 ────────────────────────────────────────────────────────
    { id: 'q20-22-title',         text: 'Questions 20-22' },
    { id: 'q20-22-instructions',  text: 'Look at the following opinions (Questions 20-22) and the list of people below.' },
    { id: 'q20-22-instructions-2',text: 'Match each opinion to the person credited with it.' },
    { id: 'q20-22-boxes',         text: 'Write the correct letter A-F in boxes ' },
    { id: 'q20-22-numbers',       text: '20-22' },
    { id: 'q20-22-boxes-2',       text: ' on your answer sheet.' },

    { id: 'list-opinions-title',  text: 'List of opinions' },

    // FIX 2: prefix "A. " / "B. " etc. stripped — must match what the template passes.
    // Template renders the letter separately as <strong>A</strong>, then calls
    // getHighlightedSegment('Neuromarketing could be used to...') without the prefix.
    { id: 'opinion-a', text: 'Neuromarketing could be used to contribute towards the cost of medical technology.' },
    { id: 'opinion-b', text: 'Neuromarketing could use introspection as a tool in marketing research.' },
    { id: 'opinion-c', text: 'Neuromarketing could be a means of treating medical problems.' },
    { id: 'opinion-d', text: 'Neuromarketing could make an existing problem worse.' },
    { id: 'opinion-e', text: 'Neuromarketing could lead to the misuse of medical equipment.' },
    { id: 'opinion-f', text: 'Neuromarketing could be used to prevent the exploitation of consumers.' },

    { id: 'q20-person', text: 'Steven Quartz' },
    { id: 'q21-person', text: 'Gary Ruskin' },
    { id: 'q22-person', text: 'Tim Ambler' },

    // ── Questions 23–26 ────────────────────────────────────────────────────────
    { id: 'q23-26-title',         text: 'Questions 23-26' },
    { id: 'q23-26-instructions1', text: 'Choose ONE WORD ONLY from the passage for each answer.' },
    { id: 'q23-26-instructions2', text: 'Write your answers in boxes ' },
    { id: 'q23-26-numbers',       text: '23-26' },
    { id: 'q23-26-instructions3', text: ' on your answer sheet.' },

    { id: 'summary-1', text: 'Neuromarketing can provide valuable information on attitudes to particular' },
    { id: 'summary-2', text: 'It may be more reliable than surveys, where people can be' },
    {
        id: 'summary-3',
        text: "or focus groups, where they may be influenced by others. It also allows researchers to identify the subject's",
    },
    {
        id: 'summary-4',
        text: 'thought patterns. However, some people are concerned that it could lead to problems such as an increase in disease among',
    },
];

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
// Calculate cumulative offsets dynamically
let currentOffset = 0;
const textSegments = ref(
allSegments.map((segment) => {
const segmentWithOffset = {
id: segment.id,
text: segment.text,
offset: currentOffset,
};
currentOffset += getPlainTextLength(segment.text);
return segmentWithOffset;
})
);
// Convert plain text offset to HTML offset


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
            const segmentId = segmentIdAttr; // always a string
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <!-- Part 2 Header --><div class="flex min-h-screen w-full flex-col" >

            <!-- Part Header Box -->

        <div class="part-header-box mb-3 ml-1 rounded border border-gray-200 px-4 py-3">
            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="part-header" v-html="getHighlightedSegment('Part 2')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions" v-html="getHighlightedSegment('Read the text and answer questions 15-27.')"></p>
        </div>


        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ══════════════════════════════════════
                     Reading Passage Panel
                     ══════════════════════════════════════ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-base font-bold text-gray-900">
                            <span
                                data-segment-id="title"
                                v-html="getHighlightedSegment('Inside the mind of the consumer\nCould brain-scanning technology provide an accurate way to assess the appeal of new products and the effectiveness of advertising?')"
                            ></span>
                        </h2>
                    </div>

                    <div class="space-y-4 px-4 pb-6" :style="contentZoom">
                        <div class="select-text text-base leading-relaxed">

                            <!-- ── Paragraph A (Example – no drop zone) ── -->
                            <div class="px-4">
                                <p class="mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">
                                    Example: Paragraph A → x
                                </p>
                                <div class="text-justify leading-relaxed font-medium text-gray-700">

                                    <span class="select-text" data-segment-id="para-a" v-html="getHighlightedSegment(paragraphA)"></span>
                                </div>
                            </div>

                            <!-- ── Paragraph B → Q14 drop zone ── -->
                            <div class="px-4 pt-4">
                                <div
                                    id="question-14"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 14"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 14 ? 'border-blue-500 bg-blue-50' : answers.q14 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 14)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 14)"
                                        @click.stop="clearHeadingAnswer(14)"
                                        :title="answers.q14 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q14" class="font-medium">{{ getHeadingLabel(answers.q14) }}</span>
                                        <span v-else class="font-bold text-gray-500">14</span>
                                    </span>
                                    <!-- Flag button – always rendered, fades when not hovered/flagged -->
                                    <button
                                        @click.stop="toggleFlag(14)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(14) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 14 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed font-medium text-gray-700">

                                    <span class="select-text" data-segment-id="para-b" v-html="getHighlightedSegment(paragraphB)"></span>
                                </div>
                            </div>

                            <!-- ── Paragraph C → Q15 drop zone ── -->
                            <div class="px-4 pt-4">
                                <div
                                    id="question-15"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 15"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 15 ? 'border-blue-500 bg-blue-50' : answers.q15 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 15)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 15)"
                                        @click.stop="clearHeadingAnswer(15)"
                                        :title="answers.q15 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q15" class="font-medium">{{ getHeadingLabel(answers.q15) }}</span>
                                        <span v-else class="font-bold text-gray-500">15</span>
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(15)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(15) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 15 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed font-medium text-gray-700">

                                    <span class="select-text" data-segment-id="para-c" v-html="getHighlightedSegment(paragraphC)"></span>
                                </div>
                            </div>

                            <!-- ── Paragraph D → Q16 drop zone ── -->
                            <div class="px-4 pt-4">
                                <div
                                    id="question-16"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 16"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 16 ? 'border-blue-500 bg-blue-50' : answers.q16 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 16)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 16)"
                                        @click.stop="clearHeadingAnswer(16)"
                                        :title="answers.q16 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q16" class="font-medium">{{ getHeadingLabel(answers.q16) }}</span>
                                        <span v-else class="font-bold text-gray-500">16</span>
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(16)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(16) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 16 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed font-medium text-gray-700">

                                    <span class="select-text" data-segment-id="para-d" v-html="getHighlightedSegment(paragraphD)"></span>
                                </div>
                            </div>

                            <!-- ── Paragraph E → Q17 drop zone ── -->
                            <div class="px-4 pt-4">
                                <div
                                    id="question-17"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 17"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 17 ? 'border-blue-500 bg-blue-50' : answers.q17 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 17)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 17)"
                                        @click.stop="clearHeadingAnswer(17)"
                                        :title="answers.q17 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q17" class="font-medium">{{ getHeadingLabel(answers.q17) }}</span>
                                        <span v-else class="font-bold text-gray-500">17</span>
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(17)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(17) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 17 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed font-medium text-gray-700">

                                    <span class="select-text" data-segment-id="para-e" v-html="getHighlightedSegment(paragraphE)"></span>
                                </div>
                            </div>

                            <!-- ── Paragraph F → Q18 drop zone ── -->
                            <div class="px-4 pt-4">
                                <div
                                    id="question-18"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 18"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 18 ? 'border-blue-500 bg-blue-50' : answers.q18 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 18)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 18)"
                                        @click.stop="clearHeadingAnswer(18)"
                                        :title="answers.q18 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q18" class="font-medium">{{ getHeadingLabel(answers.q18) }}</span>
                                        <span v-else class="font-bold text-gray-500">18</span>
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(18)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(18) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 18 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed font-medium text-gray-700">

                                    <span class="select-text" data-segment-id="para-f" v-html="getHighlightedSegment(paragraphF)"></span>
                                </div>
                            </div>

                            <!-- ── Paragraph G → Q19 drop zone ── -->
                            <div class="px-4 pt-4">
                                <div
                                    id="question-19"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 19"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="inline-flex min-h-8 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 19 ? 'border-blue-500 bg-blue-50' : answers.q19 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 19)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 19)"
                                        @click.stop="clearHeadingAnswer(19)"
                                        :title="answers.q19 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q19" class="font-medium">{{ getHeadingLabel(answers.q19) }}</span>
                                        <span v-else class="font-bold text-gray-500">19</span>
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(19)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(19) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 19 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed font-medium text-gray-700">

                                    <span class="select-text" data-segment-id="para-g" v-html="getHighlightedSegment(paragraphG)"></span>
                                </div>
                            </div>

                            <!-- ── Paragraphs H, I, J (no drop zones) ── -->
                            <div class="px-4 pt-4 text-justify leading-relaxed font-medium text-gray-700">

                                <span class="select-text" data-segment-id="para-h" v-html="getHighlightedSegment(paragraphH)"></span>
                            </div>
                            <div class="px-4 pt-4 text-justify leading-relaxed font-medium text-gray-700">

                                <span class="select-text" data-segment-id="para-i" v-html="getHighlightedSegment(paragraphI)"></span>
                            </div>
                            <div class="px-4 pt-4 text-justify leading-relaxed font-medium text-gray-700">

                                <span class="select-text" data-segment-id="para-j" v-html="getHighlightedSegment(paragraphJ)"></span>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════
                     Resize Handle
                     ══════════════════════════════════════ -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ══════════════════════════════════════
                     Questions Panel
                     ══════════════════════════════════════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════════════════════════════════════
                                 Questions 14–19: Draggable Heading List
                                 (drop zones are on the PASSAGE side above each paragraph)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-base font-bold text-gray-900">
                                        <span data-segment-id="q14-19-title" v-html="getHighlightedSegment('Questions 14-19')"></span>
                                    </h3>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q14-19-instructions" v-html="getHighlightedSegment('Reading Passage 2 has nine paragraphs A-J.')"></span><br />
                                        <span data-segment-id="q14-19-instructions-2" v-html="getHighlightedSegment('Choose the most suitable heading for paragraphs B-G from the list of headings below.')"></span><br />
                                        <span data-segment-id="q14-19-boxes" v-html="getHighlightedSegment('Write the correct number ')"></span>
                                        <strong data-segment-id="q14-19-range" v-html="getHighlightedSegment('(i-x)')"></strong>
                                        <span data-segment-id="q14-19-boxes-2" v-html="getHighlightedSegment(' in boxes ')"></span>
                                        <strong data-segment-id="q14-19-numbers" v-html="getHighlightedSegment('14-19')"></strong>
                                        <span data-segment-id="q14-19-boxes-3" v-html="getHighlightedSegment(' on your answer sheet.')"></span>
                                    </p>
                                    <p class="mb-3 text-sm text-gray-500 italic">
                                        Drag a heading from the list below and drop it onto the paragraph on the left. Click a filled drop zone to clear it.
                                    </p>
                                </div>

                                <!-- Draggable List of Headings (filters out already-used ones) -->
                                <div class=" p-4">
                                    <h4 class="mb-3 text-base font-bold text-gray-900">
                                        <span data-segment-id="list-headings-title" v-html="getHighlightedSegment('List of headings:')"></span>
                                    </h4>
                                    <div class="space-y-1.5 text-sm">
                                        <div
                                            v-for="option in availableHeadingOptions"
                                            :key="option.value"
                                            class="flex cursor-grab items-center gap-2 rounded border border-gray-300 bg-white px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-40': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd"
                                        >
                                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                            </svg>
                                            <span class="font-bold text-gray-900">{{ option.value }}</span>
                                            <span class="text-gray-700">{{ option.label }}</span>
                                        </div>

                                        <!-- Empty state when all headings are placed -->
                                        <p v-if="availableHeadingOptions.length === 0" class="py-2 text-center text-sm text-gray-400 italic">
                                            All headings have been placed.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════
                                 Questions 20–22: Opinion Matching
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-base font-bold text-gray-900">
                                        <span data-segment-id="q20-22-title" v-html="getHighlightedSegment('Questions 20-22')"></span>
                                    </h3>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q20-22-instructions" v-html="getHighlightedSegment('Look at the following opinions (Questions 20-22) and the list of people below.')"></span><br />
                                        <span data-segment-id="q20-22-instructions-2" v-html="getHighlightedSegment('Match each opinion to the person credited with it.')"></span><br />
                                        <span data-segment-id="q20-22-boxes" v-html="getHighlightedSegment('Write the correct letter A-F in boxes ')"></span>
                                        <strong data-segment-id="q20-22-numbers" v-html="getHighlightedSegment('20-22')"></strong>
                                        <span data-segment-id="q20-22-boxes-2" v-html="getHighlightedSegment(' on your answer sheet.')"></span>
                                    </p>

                                    <!-- List of Opinions -->
                                    <div class="border border-gray-300 p-4">
                                        <h4 class="mb-3 text-base font-bold text-gray-900">
                                            <span data-segment-id="list-opinions-title" v-html="getHighlightedSegment('List of opinions')"></span>
                                        </h4>
                                        <div class="space-y-2 text-base text-gray-700">
                                            <div class="flex gap-2"><strong>A</strong><span data-segment-id="opinion-a" v-html="getHighlightedSegment('Neuromarketing could be used to contribute towards the cost of medical technology.')"></span></div>
                                            <div class="flex gap-2"><strong>B</strong><span data-segment-id="opinion-b" v-html="getHighlightedSegment('Neuromarketing could use introspection as a tool in marketing research.')"></span></div>
                                            <div class="flex gap-2"><strong>C</strong><span data-segment-id="opinion-c" v-html="getHighlightedSegment('Neuromarketing could be a means of treating medical problems.')"></span></div>
                                            <div class="flex gap-2"><strong>D</strong><span data-segment-id="opinion-d" v-html="getHighlightedSegment('Neuromarketing could make an existing problem worse.')"></span></div>
                                            <div class="flex gap-2"><strong>E</strong><span data-segment-id="opinion-e" v-html="getHighlightedSegment('Neuromarketing could lead to the misuse of medical equipment.')"></span></div>
                                            <div class="flex gap-2"><strong>F</strong><span data-segment-id="opinion-f" v-html="getHighlightedSegment('Neuromarketing could be used to prevent the exploitation of consumers.')"></span></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <!-- Q20 -->
                                    <div id="question-20" class="relative flex items-center gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                        <span class="w-6 shrink-0 font-bold text-gray-900">20.</span>
                                        <span class="flex-1 text-base text-gray-700" data-segment-id="q20-person" v-html="getHighlightedSegment('Steven Quartz')"></span>
                                        <select v-model="answers.q20"
                                            class="w-24 border border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black">
                                            <option value="">Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)" @click.stop="toggleFlag(20)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                    <!-- Q21 -->
                                    <div id="question-21" class="relative flex items-center gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                        <span class="w-6 shrink-0 font-bold text-gray-900">21.</span>
                                        <span class="flex-1 text-base text-gray-700" data-segment-id="q21-person" v-html="getHighlightedSegment('Gary Ruskin')"></span>
                                        <select v-model="answers.q21"
                                            class="w-24 border border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black">
                                            <option value="">Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)" @click.stop="toggleFlag(21)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                    <!-- Q22 -->
                                    <div id="question-22" class="relative flex items-center gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <span class="w-6 shrink-0 font-bold text-gray-900">22.</span>
                                        <span class="flex-1 text-base text-gray-700" data-segment-id="q22-person" v-html="getHighlightedSegment('Tim Ambler')"></span>
                                        <select v-model="answers.q22"
                                            class="w-24 border border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black">
                                            <option value="">Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)" @click.stop="toggleFlag(22)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════
                                 Questions 23–26: Summary Completion
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-base font-bold text-gray-900">
                                        <span data-segment-id="q23-26-title" v-html="getHighlightedSegment('Questions 23-26')"></span>
                                    </h3>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q23-26-instructions1" v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q23-26-instructions2" v-html="getHighlightedSegment('Write your answers in boxes ')"></span>
                                        <strong data-segment-id="q23-26-numbers" v-html="getHighlightedSegment('23-26')"></strong>
                                        <span data-segment-id="q23-26-instructions3" v-html="getHighlightedSegment(' on your answer sheet.')"></span>
                                    </p>
                                </div>

                                <div class="flex  p-4">
                                    <div class="space-y-3 text-base leading-relaxed text-gray-800">
                                        <!-- Q23 -->
                                        <p>
                                            <span data-segment-id="summary-1" v-html="getHighlightedSegment('Neuromarketing can provide valuable information on attitudes to particular')"></span>
                                            <span
                                                id="question-23"
                                                class="relative inline-flex items-center mx-2"
                                                @mouseenter="hoveredQuestion = 23"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input v-model="answers.q23" type="text" placeholder="23"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none" />
                                                <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)" @click.stop="toggleFlag(23)"
                                                    class="ml-1 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(23) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                                </button>
                                            </span>
                                        </p>
                                        <!-- Q24 -->
                                        <p>
                                            <span data-segment-id="summary-2" v-html="getHighlightedSegment('It may be more reliable than surveys, where people can be')"></span>
                                            <span
                                                id="question-24"
                                                class="relative inline-flex items-center mx-2"
                                                @mouseenter="hoveredQuestion = 24"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input v-model="answers.q24" type="text" placeholder="24"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none" />
                                                <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)" @click.stop="toggleFlag(24)"
                                                    class="ml-1 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(24) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                                </button>
                                            </span>
                                        </p>
                                        <!-- Q25 -->
                                        <p>
                                            <span data-segment-id="summary-3" v-html="getHighlightedSegment(`or focus groups, where they may be influenced by others. It also allows researchers to identify the subject's`)"></span>
                                            <span
                                                id="question-25"
                                                class="relative inline-flex items-center mx-2"
                                                @mouseenter="hoveredQuestion = 25"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input v-model="answers.q25" type="text" placeholder="25"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none" />
                                                <button
                                                    @click.stop="toggleFlag(25)"
                                                    class="absolute left-full top-1/2 ml-1 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded border transition-all"
                                                    :class="[
                                                        isQuestionFlagged(25)
                                                            ? 'border-gray-400 text-red-500 opacity-100'
                                                            : hoveredQuestion === 25
                                                            ? 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-100'
                                                            : 'border-gray-300 text-gray-400 opacity-0 pointer-events-none'
                                                    ]"
                                                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                                </button>
                                            </span>
                                        </p>
                                        <!-- Q26 -->
                                        <p>
                                            <span data-segment-id="summary-4" v-html="getHighlightedSegment('thought patterns. However, some people are concerned that it could lead to problems such as an increase in disease among')"></span>
                                            <span
                                                id="question-26"
                                                class="relative inline-flex items-center mx-2"
                                                @mouseenter="hoveredQuestion = 26"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input v-model="answers.q26" type="text" placeholder="26"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none" />
                                                <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)" @click.stop="toggleFlag(26)"
                                                    class="ml-1 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(26) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
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
        </div>

        <!-- ══════════════════════════════════════
             Context Menu / Tooltips / Note Modal
             ══════════════════════════════════════ -->
        <Teleport to="body">

            <!-- Highlight + Note context menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"/>
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
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop
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
                    <button @click="cancelNote" class="border border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote" class="bg-black px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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
