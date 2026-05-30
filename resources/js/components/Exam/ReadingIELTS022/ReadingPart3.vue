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

const answers = ref({
    q27: '',
    q28: '',
    q29: '',
    q30: '',
    q31: '',
    q32_34: [] as string[],
    q35: '',
    q36: '',
    q37: '',
    q38: '',
    q39: '',
    q40: '',
});

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
const availableOptions = computed(() => {
    const usedOptions = [
        answers.value.q35,
        answers.value.q36,
        answers.value.q37,
        answers.value.q38,
        answers.value.q39,
        answers.value.q40,
    ].filter(Boolean);
    return summaryOptions.filter((opt) => !usedOptions.includes(opt.value));
});

// Expose methods for parent component
const getAnswers = () => {
    return {
        ...answers.value,
        q32: answers.value.q32_34[0] ?? '',
        q33: answers.value.q32_34[1] ?? '',
        q34: answers.value.q32_34[2] ?? '',
    };
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
const passageText =
    ref(`Scientists have been researching the way to get employees motivated for many years. This research Is a relational study which builds the fundamental and comprehensive model for study. This Is especially true when the business goal Is to turn unmotivated teams Into productive ones. But their researchers have limitations. It Is like studying the movements of car without taking out the engine.
Motivation Is what drives people to succeed and plays a vital role In enhancing an organizational development. It Is Important to study the motivation of employees because It Is related to the emotion and behavior of employees. Recent studies show there are four drives for motivation. They are the drive to acquire, the drive to bond, the drive to comprehend and the drive to defend.

The Drive to Acquire

The drive to acquire must be met to optimize the acquire aspect as well as the achievement element. Thus the way that outstanding performance Is recognized, the type of perks that are provided to polish the career path. But sometimes a written letter of appreciation generates more motivation than a thousand dollar check, which can serve as the Invisible power to boost business engagement. Successful organizations and leaders not only need to focus on the optimization of physical reward but also on moving other levers within the organization that can drive motivation.

The Drive to Bond

The drive to bond Is also key to driving motivation. There are many kinds of bonds between people, like friendship, family. In company, employees also want to be an essential part of company. They want to belong to the company. Employees will be motivated If they find personal belonging to the company. In the meantime, the most commitment will be achieved by the employee on condition that the force of motivation within the employee affects the direction, Intensity and persistence of decision and behavior In company.


The Drive to Comprehend

The drive to comprehend motivates many employees to higher performance. For years, It has been known that setting stretch goals can greatly Impact performance. Organizations need to ensure that the various Job roles provide employees with simulation that challenges them or allow them to grow. Employees don't want to do meaningless things or monotonous Job. If the Job didn't provide them with personal meaning and fulfillment, they will leave the company.

The Drive to Defend

The drive to defend Is often the hardest lever to pull. This drive manifests Itself as a quest to create and promote Justice, falrness, and the ability to express ourselves freely. The organizational lever for this basic human motivator Is resource allocation. This drive Is also met through an employee feeling connection to a company. If their companies are merged with another, they wlll show worries.

Two studies have been done to find the relations between the four drives and motivation. The article based on two studies was finally published in Harvard Business Review. Most authors' arguments have laid emphasis on four-drive theory and actual Investigations. Using the results of the surveys which executed with employees from Fortune 500 companies and other two global businesses (P company and H company), the article mentions about how Independent drives Influence employees' behavior and how organizational levers boost employee motivation.

The studies show that the drive to bond Is most related to fulfilling commitment, while the drive to comprehend Is most related to how much effort employees spend on works. The drive to acquire can be satisfied by a rewarding system which ties rewards to performances, and gives the best people opportunities for advancement. For drive to defend, a study on the merging of P company and H company shows that employees In former company show an unusual cooperating attitude.

The key to successfully motivate employees Is to meet all drives. Each of these drives Is Important If we are to understand employee motivation. These four drives, while not necessarily the only human drives, are the ones that are central to unified understanding of modern human life.
`);

// Text segments with unique, non-overlapping offsets
// Each offset = previous offset + previous text length + gap (10-15 chars)
// Using fixed offsets for consistency (passage is ~4200 chars)
const PASSAGE_END = 5600; // Buffer after passage ends

const textSegments = ref([
    // Part header (start at 0)
    { id: 'part3-title', text: 'Part 3', offset: 0 },                                            // len=6, ends=6
    { id: 'part3-desc', text: 'Read the text and answer questions 27–40.', offset: 20 },        // len=42, ends=62
    // Passage header
    { id: 'passage-header', text: 'READING PASSAGE 3', offset: 80 },                             // len=17, ends=97
    { id: 'header-title', text: 'Motivating Drives', offset: 110 },                              // len=17, ends=127

    // Main passage (starts at 200)
    { id: 'passage', text: passageText.value, offset: 127 },                                     // ~4200 chars, ends ~4400

    // Questions 27–31 (start at PASSAGE_END + 100 = 4600)
    { id: 'q27-31-title', text: 'Questions 27–31', offset: PASSAGE_END + 100 },                  // len=15, ends=4615
    { id: 'q27-31-instruction', text: 'Choose the correct letter A, B, C or D. Write the correct letter in boxes 27–31 on your answer sheet.', offset: PASSAGE_END + 130 }, // len=102, ends=4732

    { id: 'q27-num', text: '27.', offset: PASSAGE_END + 250 },                                   // len=3, ends=4753
    { id: 'q27', text: 'According to the passage, what are we told about the study of motivation?', offset: PASSAGE_END + 265 }, // len=74, ends=4839
    { id: 'q27-A', text: 'A. The theory of motivating employees is starting to catch attention in organizations in recent years.', offset: PASSAGE_END + 355 }, // len=102, ends=4957
    { id: 'q27-B', text: 'B. It is very important for managers to know how to motivate their subordinates because it is related to the salary of employees.', offset: PASSAGE_END + 475 }, // len=129, ends=5104
    { id: 'q27-C', text: 'C. Researchers have tended to be too theoretical in their study.', offset: PASSAGE_END + 620 }, // len=64, ends=5184
    { id: 'q27-D', text: 'D. The goal of employee motivation is to increase the profit of organizations.', offset: PASSAGE_END + 700 }, // len=79, ends=5279

    { id: 'q28-num', text: '28.', offset: PASSAGE_END + 800 },                                   // len=3, ends=5303
    { id: 'q28', text: "What can be inferred from the passage about the study of people's drives?", offset: PASSAGE_END + 815 }, // len=73, ends=5388
    { id: 'q28-A', text: "A. Satisfying employees' drives can positively lead to the change of behavior.", offset: PASSAGE_END + 905 }, // len=78, ends=5483
    { id: 'q28-B', text: "B. Satisfying employees' drives will negatively affect their emotions.", offset: PASSAGE_END + 1000 }, // len=70, ends=5570
    { id: 'q28-C', text: "C. Satisfying employees' drives can increase companies' productions.", offset: PASSAGE_END + 1085 }, // len=68, ends=5653
    { id: 'q28-D', text: "D. Satisfying employees' drives will result in employees' outstanding performance.", offset: PASSAGE_END + 1170 }, // len=82, ends=5752

    { id: 'q29-num', text: '29.', offset: PASSAGE_END + 1270 },                                  // len=3, ends=5773
    { id: 'q29', text: 'According to paragraph three, in order to optimize employees\' performance, are needed.', offset: PASSAGE_END + 1285 }, // len=87, ends=5872
    { id: 'q29-A', text: 'A. drive to acquire and achievement element', offset: PASSAGE_END + 1390 }, // len=43, ends=5933
    { id: 'q29-B', text: 'B. outstanding performance and recognition', offset: PASSAGE_END + 1450 }, // len=42, ends=5992
    { id: 'q29-C', text: 'C. career fulfillment and a thousand dollar check', offset: PASSAGE_END + 1510 }, // len=49, ends=6059
    { id: 'q29-D', text: 'D. financial incentive and recognition', offset: PASSAGE_END + 1575 }, // len=38, ends=6113

    { id: 'q30-num', text: '30.', offset: PASSAGE_END + 1640 },                                  // len=3, ends=6143
    { id: 'q30', text: 'According to paragraph five, how does "the drive to comprehend" help employees perform better?', offset: PASSAGE_END + 1655 }, // len=95, ends=6250
    { id: 'q30-A', text: 'A. It can help employees better understand the development of their organizations.', offset: PASSAGE_END + 1770 }, // len=82, ends=6352
    { id: 'q30-B', text: 'B. It can help employees feel their task is meaningful to their companies.', offset: PASSAGE_END + 1870 }, // len=74, ends=6444
    { id: 'q30-C', text: 'C. It can help employees set higher goals.', offset: PASSAGE_END + 1960 }, // len=42, ends=6502
    { id: 'q30-D', text: 'D. It can provide employees with repetitive tasks.', offset: PASSAGE_END + 2020 }, // len=50, ends=6570

    { id: 'q31-num', text: '31.', offset: PASSAGE_END + 2090 },                                  // len=3, ends=6593
    { id: 'q31', text: 'According to paragraph six, which of the following is true about "drive to defend"?', offset: PASSAGE_END + 2105 }, // len=84, ends=6689
    { id: 'q31-A', text: 'A. Organizational resource is the most difficult to allocate.', offset: PASSAGE_END + 2205 }, // len=61, ends=6766
    { id: 'q31-B', text: 'B. It is more difficult to implement than the drive to comprehend.', offset: PASSAGE_END + 2285 }, // len=66, ends=6851
    { id: 'q31-C', text: 'C. Employees think it is very important to voice their own opinions.', offset: PASSAGE_END + 2370 }, // len=68, ends=6938
    { id: 'q31-D', text: 'D. Employees think it is very important to connect with a merged corporation.', offset: PASSAGE_END + 2455 }, // len=77, ends=7032

    // Questions 32–34
    { id: 'q32-34-title', text: 'Questions 32–34', offset: PASSAGE_END + 2550 },                 // len=15, ends=7065
    { id: 'q32-34-instruction', text: 'Choose THREE letters, A–F. Write the correct letters in boxes 32–34 on your answer sheet.', offset: PASSAGE_END + 2580 }, // len=90, ends=7170
    { id: 'q32-34-question', text: 'Which THREE of the following statements are true of the study of drives?', offset: PASSAGE_END + 2690 }, // len=72, ends=7262

    { id: 'opt-A', text: 'A. Employees will be motivated if they feel belonged to the company.', offset: PASSAGE_END + 2780 }, // len=68, ends=7348
    { id: 'opt-B', text: 'B. If employees get an opportunity of training and development program, their motivation will be enhanced.', offset: PASSAGE_END + 2865 }, // len=106, ends=7471
    { id: 'opt-C', text: "C. If employees' working goals are complied with organizational objectives, their motivation will be reinforced.", offset: PASSAGE_END + 2990 }, // len=112, ends=7602
    { id: 'opt-D', text: "D. If employees' motivation is very low, companies should find a way to increase their salary as their first priority.", offset: PASSAGE_END + 3130 }, // len=119, ends=7749
    { id: 'opt-E', text: 'E. If employees find their work lacking challenging, they will leave the company.', offset: PASSAGE_END + 3270 }, // len=81, ends=7851
    { id: 'opt-F', text: 'F. Employees will worry if their company is sold.', offset: PASSAGE_END + 3370 }, // len=49, ends=7919

    // Questions 35–40
    { id: 'q35-40-title', text: 'Questions 35–40', offset: PASSAGE_END + 3450 },                 // len=15, ends=7965
    { id: 'q35-40-instruction', text: 'Do the following statements agree with the claims of the writer in Reading Passage?', offset: PASSAGE_END + 3480 }, // len=84, ends=8064

    { id: 'q35-40-yes', text: 'YES if the statement agrees', offset: PASSAGE_END + 3580 },       // len=27, ends=8107
    { id: 'q35-40-no', text: 'NO if it contradicts', offset: PASSAGE_END + 3620 },               // len=20, ends=8140
    { id: 'q35-40-notgiven', text: 'NOT GIVEN if impossible to say', offset: PASSAGE_END + 3660 }, // len=30, ends=8190

    { id: 'q35-num', text: '35.', offset: PASSAGE_END + 3710 },
    { id: 'q35', text: 'Increasing pay can lead to the high work motivation.', offset: PASSAGE_END + 3725 },
    { id: 'q36-num', text: '36.', offset: PASSAGE_END + 3785 },
    { id: 'q36', text: 'Local companies benefit more from global companies through the study.', offset: PASSAGE_END + 3800 },
    { id: 'q37-num', text: '37.', offset: PASSAGE_END + 3875 },
    { id: 'q37', text: 'Employees achieve the most commitment if their drive to comprehend is met.', offset: PASSAGE_END + 3890 },
    { id: 'q38-num', text: '38.', offset: PASSAGE_END + 3970 },
    { id: 'q38', text: 'The employees in former company presented unusual attitude toward the merging of two companies.', offset: PASSAGE_END + 3985 },
    { id: 'q39-num', text: '39.', offset: PASSAGE_END + 4090 },
    { id: 'q39', text: 'The two studies are done to analyze the relationship between the natural drives and the attitude of employees.', offset: PASSAGE_END + 4105 },
    { id: 'q40-num', text: '40.', offset: PASSAGE_END + 4225 },
    { id: 'q40', text: 'Rewarding system cause the company to lose profit', offset: PASSAGE_END + 4240 }
]);

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
const getHighlightedSegmentById = (segmentId: number | string) => {
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

const handlePassageTextSelect = () => {
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

        // Function to get absolute offset for a node position
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

        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            // Position tooltip ABOVE the selection with arrow pointing down
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50; // Approximate tooltip height
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
    <div class="pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 part-header-box px-4 py-2" @mouseup="handlePassageTextSelect" @click="handleContentClick">
    <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part3-title" v-html="getHighlightedSegmentById('part3-title')"></p>
    <p class="text-sm text-gray-700 select-text" data-segment-id="part3-desc" v-html="getHighlightedSegmentById('part3-desc')"></p>
</div>

<div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
    <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

        <!-- Reading Passage -->
        <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <span class="select-text" data-segment-id="header-title" v-html="getHighlightedSegmentById('header-title')"></span>
                </h2>
            </div>

            <div class="space-y-2" :style="contentZoom">
                <div ref="passageTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="space-y-6 leading-relaxed select-text">
                    <div class="p-4">
                        <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                            <span
                                class="text-lg select-text"
                                data-segment-id="passage"
                                v-html="getHighlightedSegmentById('passage')"
                            ></span>
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

        <!-- Questions Section -->
        <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
            <div ref="questionsTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="flex-1 overflow-y-auto pb-32">
                <div class="space-y-8 p-4" :style="contentZoom">

                    <!-- Questions 27-31 (Multiple Choice A/B/C/D) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span
                                        class="select-text"
                                        data-segment-id="q27-31-title"
                                        v-html="getHighlightedSegmentById('q27-31-title')"
                                    ></span>
                                </h3>
                            </div>
                            <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                <span
                                    class="select-text"
                                    data-segment-id="q27-31-instruction"
                                    v-html="getHighlightedSegmentById('q27-31-instruction')"
                                ></span>
                            </p>
                        </div>

                        <div class="space-y-6">

                            <!-- Question 27 -->
                            <div
                                id="question-27"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 27"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <button
                                    v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                    @click.stop="toggleFlag(27)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="text-base">
                                    <p class="mb-3 text-gray-700">
                                        <span class="font-bold text-gray-900 select-text mx-1" data-segment-id="q27-num" v-html="getHighlightedSegmentById('q27-num')"></span>
                                        <span class="select-text" data-segment-id="q27" v-html="getHighlightedSegmentById('q27')"></span>
                                    </p>
                                    <div class="space-y-2 ml-6">
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q27" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q27-A" v-html="getHighlightedSegmentById('q27-A')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q27" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q27-B" v-html="getHighlightedSegmentById('q27-B')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q27" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q27-C" v-html="getHighlightedSegmentById('q27-C')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q27" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q27-D" v-html="getHighlightedSegmentById('q27-D')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 28 -->
                            <div
                                id="question-28"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 28"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <button
                                    v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                    @click.stop="toggleFlag(28)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="text-base">
                                    <p class="mb-3 text-gray-700">
                                        <span class="font-bold text-gray-900 select-text mx-1" data-segment-id="q28-num" v-html="getHighlightedSegmentById('q28-num')"></span>
                                        <span class="select-text" data-segment-id="q28" v-html="getHighlightedSegmentById('q28')"></span>
                                    </p>
                                    <div class="space-y-2 ml-6">
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q28" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q28-A" v-html="getHighlightedSegmentById('q28-A')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q28" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q28-B" v-html="getHighlightedSegmentById('q28-B')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q28" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q28-C" v-html="getHighlightedSegmentById('q28-C')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q28" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q28-D" v-html="getHighlightedSegmentById('q28-D')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 29 -->
                            <div
                                id="question-29"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 29"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <button
                                    v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                    @click.stop="toggleFlag(29)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="text-base">
                                    <p class="mb-3 text-gray-700">
                                        <span class="font-bold text-gray-900 select-text mx-1" data-segment-id="q29-num" v-html="getHighlightedSegmentById('q29-num')"></span>
                                        <span class="select-text" data-segment-id="q29" v-html="getHighlightedSegmentById('q29')"></span>
                                    </p>
                                    <div class="space-y-2 ml-6">
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q29" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q29-A" v-html="getHighlightedSegmentById('q29-A')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q29" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q29-B" v-html="getHighlightedSegmentById('q29-B')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q29" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q29-C" v-html="getHighlightedSegmentById('q29-C')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q29" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q29-D" v-html="getHighlightedSegmentById('q29-D')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 30 -->
                            <div
                                id="question-30"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 30"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <button
                                    v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                    @click.stop="toggleFlag(30)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="text-base">
                                    <p class="mb-3 text-gray-700">
                                        <span class="font-bold text-gray-900 select-text mx-1" data-segment-id="q30-num" v-html="getHighlightedSegmentById('q30-num')"></span>
                                        <span class="select-text" data-segment-id="q30" v-html="getHighlightedSegmentById('q30')"></span>
                                    </p>
                                    <div class="space-y-2 ml-6">
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q30" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q30-A" v-html="getHighlightedSegmentById('q30-A')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q30" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q30-B" v-html="getHighlightedSegmentById('q30-B')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q30" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q30-C" v-html="getHighlightedSegmentById('q30-C')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q30" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q30-D" v-html="getHighlightedSegmentById('q30-D')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 31 -->
                            <div
                                id="question-31"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 31"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <button
                                    v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                    @click.stop="toggleFlag(31)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="text-base">
                                    <p class="mb-3 text-gray-700">
                                        <span class="font-bold text-gray-900 select-text mx-1" data-segment-id="q31-num" v-html="getHighlightedSegmentById('q31-num')"></span>
                                        <span class="select-text" data-segment-id="q31" v-html="getHighlightedSegmentById('q31')"></span>
                                    </p>
                                    <div class="space-y-2 ml-6">
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q31" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q31-A" v-html="getHighlightedSegmentById('q31-A')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q31" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q31-B" v-html="getHighlightedSegmentById('q31-B')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q31" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q31-C" v-html="getHighlightedSegmentById('q31-C')"></span>
                                        </label>
                                        <label class="flex items-center space-x-3">
                                            <input v-model="answers.q31" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                            <span class="select-text" data-segment-id="q31-D" v-html="getHighlightedSegmentById('q31-D')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Questions 32-34 (Choose THREE letters A–F) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span
                                        class="select-text"
                                        data-segment-id="q32-34-title"
                                        v-html="getHighlightedSegmentById('q32-34-title')"
                                    ></span>
                                </h3>
                            </div>
                            <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                <span
                                    class="select-text"
                                    data-segment-id="q32-34-instruction"
                                    v-html="getHighlightedSegmentById('q32-34-instruction')"
                                ></span>
                            </p>
                            <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                <span
                                    class="select-text"
                                    data-segment-id="q32-34-question"
                                    v-html="getHighlightedSegmentById('q32-34-question')"
                                ></span>
                            </p>
                        </div>

                        <div class="space-y-3">
                            <label
                                id="question-32"
                                class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors"
                                @mouseenter="hoveredQuestion = 32"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <input
                                    type="checkbox"
                                    value="A"
                                    v-model="answers.q32_34"
                                    :disabled="!answers.q32_34.includes('A') && answers.q32_34.length >= 3"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="opt-A" v-html="getHighlightedSegmentById('opt-A')"></span>
                                <button
                                    v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                    @click.stop="toggleFlag(32)"
                                    class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </label>

                            <label
                                class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    value="B"
                                    v-model="answers.q32_34"
                                    :disabled="!answers.q32_34.includes('B') && answers.q32_34.length >= 3"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="opt-B" v-html="getHighlightedSegmentById('opt-B')"></span>
                            </label>

                            <label
                                class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    value="C"
                                    v-model="answers.q32_34"
                                    :disabled="!answers.q32_34.includes('C') && answers.q32_34.length >= 3"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="opt-C" v-html="getHighlightedSegmentById('opt-C')"></span>
                            </label>

                            <label
                                class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    value="D"
                                    v-model="answers.q32_34"
                                    :disabled="!answers.q32_34.includes('D') && answers.q32_34.length >= 3"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="opt-D" v-html="getHighlightedSegmentById('opt-D')"></span>
                            </label>

                            <label
                                class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    value="E"
                                    v-model="answers.q32_34"
                                    :disabled="!answers.q32_34.includes('E') && answers.q32_34.length >= 3"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="opt-E" v-html="getHighlightedSegmentById('opt-E')"></span>
                            </label>

                            <label
                                class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    value="F"
                                    v-model="answers.q32_34"
                                    :disabled="!answers.q32_34.includes('F') && answers.q32_34.length >= 3"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="opt-F" v-html="getHighlightedSegmentById('opt-F')"></span>
                            </label>

                            <!-- Selected count indicator -->
                            <p class="text-xs text-gray-500 mt-2">
                                Selected: {{ answers.q32_34.length }} / 3
                            </p>
                        </div>
                    </div>

                    <!-- Questions 35-40 (YES / NO / NOT GIVEN) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span
                                        class="select-text"
                                        data-segment-id="q35-40-title"
                                        v-html="getHighlightedSegmentById('q35-40-title')"
                                    ></span>
                                </h3>
                            </div>
                            <p class="mb-4 leading-relaxed text-gray-700">
                                <span
                                    class="select-text"
                                    data-segment-id="q35-40-instruction"
                                    v-html="getHighlightedSegmentById('q35-40-instruction')"
                                ></span>
                                <br /><br />
                                <span class="select-text" data-segment-id="q35-40-yes" v-html="getHighlightedSegmentById('q35-40-yes')"></span><br />
                                <span class="select-text" data-segment-id="q35-40-no" v-html="getHighlightedSegmentById('q35-40-no')"></span><br />
                                <span class="select-text" data-segment-id="q35-40-notgiven" v-html="getHighlightedSegmentById('q35-40-notgiven')"></span>
                            </p>
                        </div>

                        <div class="space-y-4">

                            <!-- Question 35 -->
                            <div
                                id="question-35"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 35"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q35-num" v-html="getHighlightedSegmentById('q35-num')"></span>
                                    <select
                                        v-model="answers.q35"
                                        class="border-2 border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                    </select>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q35" v-html="getHighlightedSegmentById('q35')"></span>
                                    </p>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                    @click.stop="toggleFlag(35)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 36 -->
                            <div
                                id="question-36"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 36"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q36-num" v-html="getHighlightedSegmentById('q36-num')"></span>
                                    <select
                                        v-model="answers.q36"
                                        class="border-2 border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                    </select>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q36" v-html="getHighlightedSegmentById('q36')"></span>
                                    </p>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                    @click.stop="toggleFlag(36)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 37 -->
                            <div
                                id="question-37"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 37"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q37-num" v-html="getHighlightedSegmentById('q37-num')"></span>
                                    <select
                                        v-model="answers.q37"
                                        class="border-2 border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                    </select>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q37" v-html="getHighlightedSegmentById('q37')"></span>
                                    </p>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                    @click.stop="toggleFlag(37)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 38 -->
                            <div
                                id="question-38"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 38"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q38-num" v-html="getHighlightedSegmentById('q38-num')"></span>
                                    <select
                                        v-model="answers.q38"
                                        class="border-2 border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                    </select>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q38" v-html="getHighlightedSegmentById('q38')"></span>
                                    </p>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                    @click.stop="toggleFlag(38)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 39 -->
                            <div
                                id="question-39"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 39"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q39-num" v-html="getHighlightedSegmentById('q39-num')"></span>
                                    <select
                                        v-model="answers.q39"
                                        class="border-2 border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                    </select>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q39" v-html="getHighlightedSegmentById('q39')"></span>
                                    </p>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                    @click.stop="toggleFlag(39)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 40 -->
                            <div
                                id="question-40"
                                class="relative p-2"
                                @mouseenter="hoveredQuestion = 40"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q40-num" v-html="getHighlightedSegmentById('q40-num')"></span>
                                    <select
                                        v-model="answers.q40"
                                        class="border-2 border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                    </select>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q40" v-html="getHighlightedSegmentById('q40')"></span>
                                    </p>
                                </div>
                                <button
                                    v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                    @click.stop="toggleFlag(40)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
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

    <!-- Context Menu for Highlighting - Speech Bubble Style -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button
                        @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note"
                    >
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"
                            />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button
                        @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight"
                    >
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
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button
                        @click="confirmDeleteHighlight"
                        type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                    >
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
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-9999"
                :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @mouseleave="handleTooltipMouseLeave"
                @click.stop
            >
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

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }"
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
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
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
