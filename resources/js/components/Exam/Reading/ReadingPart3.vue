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
const passageText =
    ref(`The face of the ocean has changed completely since the first commercial fishers cast their nets and hooks over a thousand years ago. Fisheries intensified over the centuries, but even by the nineteenth century, it was still felt, justifiably, that the plentiful resources of the sea were for the most part beyond the reach of fishing, and so there was little need to restrict fishing or create protected areas. The twentieth century heralded an escalation in fishing intensity that is unprecedented in the history of the oceans, and modern fishing technologies leave fish no place to hide. Today, the only refuges from fishing are those we deliberately create. Unhappily, the sea trails far behind the land in terms of the area and the quality of protection given.

For centuries, as fishing and commerce have expanded, we have held onto the notion that the sea is different from the land. We still view it as a place where people and nations should be free to come and go at will, as well as somewhere that should be free for us to exploit. Perhaps this is why we have been so reluctant to protect the sea. On land, protected areas have proliferated as human populations have grown. Here, compared to the sea, we have made greater headway in our struggle to maintain the richness and variety of wildlife and landscape. Twelve percent of the world's land is now contained in protected areas, whereas the corresponding figure for the sea is but three-fifths of one percent. Worse still, most marine protected areas allow some fishing to continue. Areas off-limits to all exploitation cover something like one five-thousandth of the total area of the world's seas.

Today, we are belatedly coming to realise that 'natural refuges' from fishing have played a critical role in sustaining fisheries, and maintaining healthy and diverse marine ecosystems. This does not mean that marine reserves can rebuild fisheries on their own - other management measures are also required for that. However, places that are off-limits to fishing constitute the last and most important part of our package of reform for fisheries management. They underpin and enhance all our other efforts. There are limits to protection though.

Reserves cannot bring back what has died out. We can never resurrect globally extinct species, and restoring locally extinct animals may require reintroductions from elsewhere, if natural dispersal from remaining populations is insufficient. We are also seeing, in cases such as northern cod in Canada, that fishing can shift marine ecosystems into different states, where different mixes of species prevail. In many cases, these species are less desirable, since the prime fishing targets have gone or are much reduced in numbers, and changes may be difficult to reverse, even with a complete moratorium on fishing. The Mediterranean sailed by Ulysses, the legendary king of ancient Greece, supported abundant monk seals, loggerhead turtles and porpoises. Their disappearance through hunting and overfishing has totally restructured food webs, and recovery is likely to be much harder to achieve than their destruction was. This means that the sooner we act to protect marine life, the more certain will be our success.

To some people, creating marine reserves is an admission of failure. According to their logic, reserves should not be necessary if we have done our work properly in managing the uses we make of the sea. Many fisheries managers are still wedded to the idea that one day their models will work, and politicians will listen to their advice. Just give the approach time, and success will be theirs. How much time have we got? This approach has been tried and refined for the last 50 years. There have been few successes which to feather the managers' caps, but a growing litany of failure. The Common Fisheries Policy, the European Union's instrument for the management of fisheries and aquaculture, exemplifies the worst pitfalls: flawed models, flawed advice, watered-down recommendations from government bureaucrats and then the disregard of much of this advice by politicians. When it all went wrong, as it inevitably had to, Europe sent its boats to other countries in order to obtain fish for far less than they were actually worth.

We are squandering the wealth of oceans. If we don't break out of this cycle of failure, humanity will lose a key source of protein, and much more besides. Disrupting natural ecosystem processes, such as water purification, nutrient cycling, and carbon storage, could have ramifications for human life itself. We can go a long way to avoiding this catastrophic mistake with simple common sense management. Marine reserves lie at the heart of the reform. But they will not be sufficient if they are implemented only here and there to shore up the crumbling edifice of the 'rational fisheries management' envisioned by scientists in the 1940s and 1950s. They have to be placed centre stage as a fundamental underpinning for everything we do in the oceans. Reserves are a first resort, not a final resort when all else fails.`);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    // Part 3 header text segments
    { id: 'part-header', text: 'Part 3', offset: -100 },
    { id: 'part-instructions', text: 'Read the text and answer questions 27–40.', offset: -93 },
    { id: 'header-title', text: 'Marine reserves', offset: -51 },
    { id: 'passage-title', text: 'The Future of fish', offset: -35 },
    // Passage text segment
    {
        id: 'passage',
        text: passageText.value,
        offset: 0,
    },
    // Questions 27-31
    { id: 'q27-31-title', text: 'Questions 27-31', offset: passageText.value.length + 1 },
    { id: 'q27-31-inst-choose', text: 'Choose', offset: passageText.value.length + 17 },
    { id: 'q27-31-inst-yes', text: 'YES', offset: passageText.value.length + 24 },
    { id: 'q27-31-inst-yes-desc', text: 'if the statement agrees with the claims of the writer, choose', offset: passageText.value.length + 28 },
    { id: 'q27-31-inst-no', text: 'NO', offset: passageText.value.length + 90 },
    { id: 'q27-31-inst-no-desc', text: 'if the statement contradicts the claims of the writer, or choose', offset: passageText.value.length + 93 },
    { id: 'q27-31-inst-notgiven', text: 'NOT GIVEN', offset: passageText.value.length + 158 },
    { id: 'q27-31-inst-notgiven-desc', text: 'if it is impossible to say what the writer thinks about this.', offset: passageText.value.length + 168 },
    // Question numbers for 27-31
    { id: 'q27-num', text: '27.', offset: passageText.value.length + 332 },
    {
        id: 'q27',
        text: 'It is more than a thousand years since people started to catch fish for commercial use.',
        offset: passageText.value.length + 336,
    },
    { id: 'q28-num', text: '28.', offset: passageText.value.length + 424 },
    { id: 'q28', text: 'In general, open access to the oceans is still regarded as desirable.', offset: passageText.value.length + 428 },
    { id: 'q29-num', text: '29.', offset: passageText.value.length + 498 },
    { id: 'q29', text: 'Sea fishing is now completely banned in the majority of protected areas.', offset: passageText.value.length + 502 },
    { id: 'q30-num', text: '30.', offset: passageText.value.length + 575 },
    {
        id: 'q30',
        text: 'People should be encouraged to reduce the amount of fish they eat.',
        offset: passageText.value.length + 579,
    },
    { id: 'q31-num', text: '31.', offset: passageText.value.length + 646 },
    {
        id: 'q31',
        text: 'The re-introduction of certain mammals to the Mediterranean is a straightforward task.',
        offset: passageText.value.length + 650,
    },
    // Questions 32-34
    { id: 'q32-34-title', text: 'Questions 32-34', offset: passageText.value.length + 738 },
    {
        id: 'q32-34-instructions',
        text: 'Choose the correct letter A, B, C or D and write them in boxes 32, 33 & 34 on your answer sheet.',
        offset: passageText.value.length + 754,
    },
    { id: 'q32-num', text: '32.', offset: passageText.value.length + 851 },
    {
        id: 'q32',
        text: "What does the writer mean with the question, 'How much time have we got?' in the fifth paragraph?",
        offset: passageText.value.length + 855,
    },
    { id: 'q32-A', text: 'Fisheries policies are currently based on uncertain estimates.', offset: passageText.value.length + 953 },
    { id: 'q32-B', text: 'Accurate predictions will allow governments to plan properly.', offset: passageText.value.length + 1018 },
    { id: 'q32-C', text: 'Fisheries managers should provide clearer information.', offset: passageText.value.length + 1082 },
    { id: 'q32-D', text: 'Action to protect fish stocks is urgently needed.', offset: passageText.value.length + 1139 },
    { id: 'q33-num', text: '33.', offset: passageText.value.length + 1191 },
    { id: 'q33', text: "What is the writer's comment on the Common Fisheries Policy?", offset: passageText.value.length + 1195 },
    { id: 'q33-A', text: 'Measures that it advocated were hastily implemented.', offset: passageText.value.length + 1256 },
    { id: 'q33-B', text: 'Officials exaggerated some of its recommendations.', offset: passageText.value.length + 1311 },
    { id: 'q33-C', text: 'It was based on predictions which were inaccurate.', offset: passageText.value.length + 1364 },
    { id: 'q33-D', text: 'The policy makers acquired a good reputation.', offset: passageText.value.length + 1417 },
    { id: 'q34-num', text: '34.', offset: passageText.value.length + 1465 },
    { id: 'q34', text: "What is the writer's conclusion concerning the decline of marine resources?", offset: passageText.value.length + 1469 },
    { id: 'q34-A', text: 'The means of avoiding the worst outcomes needs to be prioritised.', offset: passageText.value.length + 1545 },
    { id: 'q34-B', text: 'Measures already taken to avoid a crisis are probably sufficient.', offset: passageText.value.length + 1613 },
    { id: 'q34-C', text: 'The situation is now so severe that there is no likely solution.', offset: passageText.value.length + 1681 },
    { id: 'q34-D', text: 'It is no longer clear which measures would be most effective.', offset: passageText.value.length + 1748 },
    // Questions 35-40
    { id: 'q35-40-title', text: 'Questions 35-40', offset: passageText.value.length + 1812 },
    {
        id: 'q35-40-instructions',
        text: 'Complete the summary using the list of words/phrases, A-J, below. Select your answers from the right list  & Place it to the boxes 35-40.',
        offset: passageText.value.length + 1828,
    },
    // Options A-J for Questions 35-40
    { id: 'opt-A', text: 'A action', offset: passageText.value.length + 1950 },
    { id: 'opt-B', text: 'B controls', offset: passageText.value.length + 1959 },
    { id: 'opt-C', text: 'C failure', offset: passageText.value.length + 1970 },
    { id: 'opt-D', text: 'D fish catches', offset: passageText.value.length + 1980 },
    { id: 'opt-E', text: 'E fish processing', offset: passageText.value.length + 1995 },
    { id: 'opt-F', text: 'F fishing techniques', offset: passageText.value.length + 2013 },
    { id: 'opt-G', text: 'G large boats', offset: passageText.value.length + 2034 },
    { id: 'opt-H', text: 'H marine reserves', offset: passageText.value.length + 2048 },
    { id: 'opt-I', text: 'I the land', offset: passageText.value.length + 2066 },
    { id: 'opt-J', text: 'J the past', offset: passageText.value.length + 2077 },
    { id: 'summary-title', text: 'Measures to protect the oceans', offset: passageText.value.length + 2088 },
    {
        id: 'summary-p1-part1',
        text: "Up till the twentieth century the world's supply of fish was sufficient for its needs. It was unnecessary to introduce",
        offset: passageText.value.length + 2119,
    },
    { id: 'q35-num', text: '35.', offset: passageText.value.length + 2238 },
    { id: 'summary-p1-part2', text: 'of any kind, because large areas of the oceans were inaccessible.', offset: passageText.value.length + 2241 },
    { id: 'summary-p2-part1', text: 'However, as', offset: passageText.value.length + 2307 },
    { id: 'q36-num', text: '36.', offset: passageText.value.length + 2319 },
    {
        id: 'summary-p2-part2',
        text: 'improved, this situation changed, and in the middle of the twentieth century, policies were introduced to regulate',
        offset: passageText.value.length + 2322,
    },
    { id: 'q37-num', text: '37.', offset: passageText.value.length + 2437 },
    { id: 'summary-p2-part3', text: '.', offset: passageText.value.length + 2440 },
    { id: 'summary-p3-part1', text: 'These policies have not succeeded. Today, by comparison with', offset: passageText.value.length + 2442 },
    { id: 'q38-num', text: '38.', offset: passageText.value.length + 2503 },
    { id: 'summary-p3-part2', text: ', the oceans have very little legal protection.', offset: passageText.value.length + 2506 },
    { id: 'summary-p4-part1', text: 'Despite the doubts that many officials have about the concept of', offset: passageText.value.length + 2554 },
    { id: 'q39-num', text: '39.', offset: passageText.value.length + 2619 },
    {
        id: 'summary-p4-part2',
        text: ', these should be at the heart of any action taken. The consequences of further',
        offset: passageText.value.length + 2622,
    },
    { id: 'q40-num', text: '40.', offset: passageText.value.length + 2702 },
    { id: 'summary-p4-part3', text: 'are very serious, and may even affect our continuing existence.', offset: passageText.value.length + 2705 },
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

const handlePassageTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && (passageTextRef.value || questionsTextRef.value)) {
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

            if (selection && range) {
                // Find which text segment this selection belongs to
                // by checking which span element contains the selection
                let targetSpan = range.startContainer;

                // Traverse up to find the span with v-html
                while (targetSpan && targetSpan.nodeType !== Node.ELEMENT_NODE) {
                    targetSpan = targetSpan.parentNode;
                }

                // Look for spans with text-gray-700, text-gray-800, text-gray-900, select-text, or text-lg classes (for passage)
                while (
                    targetSpan &&
                    !(targetSpan as Element).classList?.contains('text-gray-700') &&
                    !(targetSpan as Element).classList?.contains('text-gray-800') &&
                    !(targetSpan as Element).classList?.contains('text-gray-900') &&
                    !(targetSpan as Element).classList?.contains('select-text') &&
                    !(targetSpan as Element).classList?.contains('text-lg')
                ) {
                    const parent = targetSpan.parentNode;
                    if (!parent) break;
                    targetSpan = parent;
                }

                if (targetSpan) {
                    // Get the text content of this span (this strips HTML tags automatically)
                    const spanText = (targetSpan as Element).textContent || '';

                    // Check if this is the main passage text (contains text-lg class)
                    const isPassageText = (targetSpan as Element).classList?.contains('text-lg') || spanText.length > 500; // Passage is long

                    let segment = null;

                    if (isPassageText) {
                        // For passage text, use the passageText segment directly (index 3)
                        segment = textSegments.value[4];
                    } else {
                        // Find which segment this is by exact text match first, then fallback to contains
                        segment = textSegments.value.find((s) => s.text === spanText.trim());

                        // If exact match fails, try to find by checking if the span text contains the segment
                        if (!segment) {
                            segment = textSegments.value.find((s) => {
                                // Remove whitespace differences for comparison
                                const normalizedSpan = spanText.trim().replace(/\s+/g, ' ');
                                const normalizedSegment = s.text.trim().replace(/\s+/g, ' ');
                                return (
                                    normalizedSpan === normalizedSegment ||
                                    normalizedSpan.includes(normalizedSegment) ||
                                    normalizedSegment.includes(normalizedSpan)
                                );
                            });
                        }
                    }

                    if (segment) {
                        const normalizedSelected = selected.trim();

                        // Use Range API to get exact offset within the container
                        const preSelectionRange = document.createRange();
                        preSelectionRange.selectNodeContents(targetSpan as Element);
                        preSelectionRange.setEnd(range.startContainer, range.startOffset);

                        // Get plain text offset (this is the offset in the rendered text)
                        const plainTextOffset = preSelectionRange.toString().length;

                        // Store plain text offsets - these will be used for both storage and rendering
                        const startOffset = segment.offset + plainTextOffset;
                        const endOffset = startOffset + normalizedSelected.length;

                        selectedText.value = normalizedSelected;
                        selectionRange.value = { start: startOffset, end: endOffset };

                        console.log('Selection:', { selected: normalizedSelected, startOffset, endOffset, plainTextOffset, isPassageText });
                    }
                }
            }
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
    <div class="pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 part-header-box border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2" @mouseup="handlePassageTextSelect" @click="handleContentClick">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegment('Part 3')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegment('Read the text and answer questions 27–40.')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="header-title" v-html="getHighlightedSegment('Marine reserves')"></span>
                        </h2>
                    </div>

                    <div class="space-y-2" :style="contentZoom">
                        <div ref="passageTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <h3 class="mb-4 flex items-center text-lg font-bold text-gray-800">
                                    <span
                                        class="select-text"
                                        data-segment-id="passage-title"
                                        v-html="getHighlightedSegment('The Future of fish')"
                                    ></span>
                                </h3>

                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span
                                        class="text-lg select-text"
                                        data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[4].text)"
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
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4"
                                transform="rotate(90 12 12)"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <!-- Scrollable Questions Content -->
                    <div ref="questionsTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">
                            <!-- Questions 27-30 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="select-text"
                                                data-segment-id="q27-31-title"
                                                v-html="getHighlightedSegment('Questions 27-31')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q27-31-inst-choose" v-html="getHighlightedSegment('Choose')"></span>
                                        <span class="select-text font-bold mx-0.5 text-gray-900" data-segment-id="q27-31-inst-yes" v-html="getHighlightedSegment('YES')"></span>
                                        <span class="select-text" data-segment-id="q27-31-inst-yes-desc" v-html="getHighlightedSegment('if the statement agrees with the claims of the writer, choose')"></span>
                                        <span class="select-text font-bold mx-0.5 text-gray-900" data-segment-id="q27-31-inst-no" v-html="getHighlightedSegment('NO')"></span>
                                        <span class="select-text" data-segment-id="q27-31-inst-no-desc" v-html="getHighlightedSegment('if the statement contradicts the claims of the writer, or choose')"></span>
                                        <span class="select-text font-bold mx-0.5 text-gray-900" data-segment-id="q27-31-inst-notgiven" v-html="getHighlightedSegment('NOT GIVEN')"></span>
                                        <span class="select-text" data-segment-id="q27-31-inst-notgiven-desc" v-html="getHighlightedSegment('if it is impossible to say what the writer thinks about this.')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 27 -->
                                    <div
                                        id="question-27"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 27"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q27-num" v-html="getHighlightedSegment('27.')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q27" v-html="getHighlightedSegment('It is more than a thousand years since people started to catch fish for commercial use.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q27" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q27" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q27" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                    </div>

                                    <!-- Question 28 -->
                                    <div
                                        id="question-28"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 28"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q28-num" v-html="getHighlightedSegment('28.')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q28" v-html="getHighlightedSegment('In general, open access to the oceans is still regarded as desirable.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q28" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q28" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q28" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                    </div>

                                    <!-- Question 29 -->
                                    <div
                                        id="question-29"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 29"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q29-num" v-html="getHighlightedSegment('29.')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q29" v-html="getHighlightedSegment('Sea fishing is now completely banned in the majority of protected areas.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q29" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q29" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q29" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                    </div>

                                    <!-- Question 30 -->
                                    <div
                                        id="question-30"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 30"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q30-num" v-html="getHighlightedSegment('30.')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q30" v-html="getHighlightedSegment('People should be encouraged to reduce the amount of fish they eat.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q30" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q30" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q30" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                    </div>

                                    <!-- Question 31 -->
                                    <div
                                        id="question-31"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 31"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q31-num" v-html="getHighlightedSegment('31.')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q31" v-html="getHighlightedSegment('The re-introduction of certain mammals to the Mediterranean is a straightforward task.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q31" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q31" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q31" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 32-34 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="select-text"
                                                data-segment-id="q32-34-title"
                                                v-html="getHighlightedSegment('Questions 32-34')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="select-text"
                                            data-segment-id="q32-34-instructions"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Choose the correct letter A, B, C or D and write them in boxes 32, 33 & 34 on your answer sheet.',
                                                )
                                            "
                                        ></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        id="question-32"
                                        class="relative p-2"
                                        @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                            @click.stop="toggleFlag(32)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700">
                                                <span class="font-bold text-gray-900 select-text mx-1" v-html="getHighlightedSegment('32.')"></span>
                                                <span class="select-text" v-html="getHighlightedSegment('What does the writer mean with the question, \'How much time have we got?\' in the fifth paragraph?')"></span>
                                            </p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q32" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('Fisheries policies are currently based on uncertain estimates.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q32" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('Accurate predictions will allow governments to plan properly.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q32" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('Fisheries managers should provide clearer information.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q32" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('Action to protect fish stocks is urgently needed.')"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        id="question-33"
                                        class="relative p-2"
                                        @mouseenter="hoveredQuestion = 33"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                            @click.stop="toggleFlag(33)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700">
                                                <span class="font-bold text-gray-900 select-text mx-1" v-html="getHighlightedSegment('33.')"></span>
                                                <span class="select-text" v-html="getHighlightedSegment('What is the writer\'s comment on the Common Fisheries Policy?')"></span>
                                            </p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q33" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('Measures that it advocated were hastily implemented.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q33" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('Officials exaggerated some of its recommendations.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q33" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('It was based on predictions which were inaccurate.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q33" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('The policy makers acquired a good reputation.')"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        id="question-34"
                                        class="relative p-2"
                                        @mouseenter="hoveredQuestion = 34"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                            @click.stop="toggleFlag(34)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700">
                                                <span class="font-bold text-gray-900 select-text mx-1" v-html="getHighlightedSegment('34.')"></span>
                                                <span class="select-text" v-html="getHighlightedSegment('What is the writer\'s conclusion concerning the decline of marine resources?')"></span>
                                            </p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q34" type="radio" value="A" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('The means of avoiding the worst outcomes needs to be prioritised.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q34" type="radio" value="B" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('Measures already taken to avoid a crisis are probably sufficient.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q34" type="radio" value="C" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('The situation is now so severe that there is no likely solution.')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q34" type="radio" value="D" class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" v-html="getHighlightedSegment('It is no longer clear which measures would be most effective.')"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 35-40 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="select-text"
                                                data-segment-id="q35-40-title"
                                                v-html="getHighlightedSegment('Questions 35-40')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed font-medium text-gray-800">
                                        <span
                                            class="select-text"
                                            data-segment-id="q35-40-instructions"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Complete the summary using the list of words/phrases, A-J, below. Select your answers from the right list  & Place it to the boxes 35-40.',
                                                )
                                            "
                                        ></span>
                                    </p>
                                </div>

                                <!-- Side by side layout: Summary (left) + Options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left side: Summary with drop zones -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                    <h4 class="mb-4 text-base font-bold text-gray-800">
                                        <span
                                            class="select-text"
                                            data-segment-id="summary-title"
                                            v-html="getHighlightedSegment('Measures to protect the oceans')"
                                        ></span>
                                    </h4>
                                    <div class="space-y-4 text-sm leading-relaxed text-gray-800">
                                        <p>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p1-part1"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Up till the twentieth century the world\'s supply of fish was sufficient for its needs. It was unnecessary to introduce',
                                                    )
                                                "
                                            ></span>
                                            <span
                                                class="mx-2 inline-flex items-center space-x-2"
                                                @mouseenter="hoveredQuestion = 35"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="font-bold text-gray-900 text-base select-text" v-html="getHighlightedSegment('35.')"></span>
                                                <span
                                                    id="question-35"
                                                    class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                    :class="dragOverQuestion === 35 ? 'border-blue-500 bg-blue-50' : answers.q35 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 35)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 35)"
                                                    @click="clearAnswer(35)"
                                                    :title="answers.q35 ? 'Click to clear' : 'Drop answer here'"
                                                >
                                                    {{ answers.q35 ? getOptionLabel(answers.q35) : '' }}
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(35)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(35) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p1-part2"
                                                v-html="getHighlightedSegment('of any kind, because large areas of the oceans were inaccessible.')"
                                            ></span>
                                        </p>

                                        <p>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p2-part1"
                                                v-html="getHighlightedSegment('However, as')"
                                            ></span>
                                            <span
                                                class="mx-2 inline-flex items-center space-x-2"
                                            >
                                                <span class="font-bold text-gray-900 text-base select-text" v-html="getHighlightedSegment('36.')"></span>
                                                <span
                                                    id="question-36"
                                                    class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                    :class="dragOverQuestion === 36 ? 'border-blue-500 bg-blue-50' : answers.q36 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 36)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 36)"
                                                    @click="clearAnswer(36)"
                                                    :title="answers.q36 ? 'Click to clear' : 'Drop answer here'"
                                                >
                                                    {{ answers.q36 ? getOptionLabel(answers.q36) : '' }}
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(36)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(36) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p2-part2"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'improved, this situation changed, and in the middle of the twentieth century, policies were introduced to regulate',
                                                    )
                                                "
                                            ></span>
                                            <span
                                                class="mx-2 inline-flex items-center space-x-2"
                                            >
                                                <span class="font-bold text-gray-900 text-base select-text" v-html="getHighlightedSegment('37.')"></span>
                                                <span
                                                    id="question-37"
                                                    class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                    :class="dragOverQuestion === 37 ? 'border-blue-500 bg-blue-50' : answers.q37 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 37)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 37)"
                                                    @click="clearAnswer(37)"
                                                    :title="answers.q37 ? 'Click to clear' : 'Drop answer here'"
                                                >
                                                    {{ answers.q37 ? getOptionLabel(answers.q37) : '' }}
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(37)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(37) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                            <span class="select-text" data-segment-id="summary-p2-part3" v-html="getHighlightedSegment('.')"></span>
                                        </p>

                                        <p>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p3-part1"
                                                v-html="getHighlightedSegment('These policies have not succeeded. Today, by comparison with')"
                                            ></span>
                                            <span
                                                class="mx-2 inline-flex items-center space-x-2"
                                            >
                                                <span class="font-bold text-base text-gray-900 select-text" v-html="getHighlightedSegment('38.')"></span>
                                                <span
                                                    id="question-38"
                                                    class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                    :class="dragOverQuestion === 38 ? 'border-blue-500 bg-blue-50' : answers.q38 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 38)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 38)"
                                                    @click="clearAnswer(38)"
                                                    :title="answers.q38 ? 'Click to clear' : 'Drop answer here'"
                                                >
                                                    {{ answers.q38 ? getOptionLabel(answers.q38) : '' }}
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(38)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(38) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p3-part2"
                                                v-html="getHighlightedSegment(', the oceans have very little legal protection.')"
                                            ></span>
                                        </p>

                                        <p>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p4-part1"
                                                v-html="getHighlightedSegment('Despite the doubts that many officials have about the concept of')"
                                            ></span>
                                            <span
                                                class="mx-2 inline-flex items-center space-x-2"
                                            >
                                                <span class="font-bold text-gray-900 select-text text-base" v-html="getHighlightedSegment('39.')"></span>
                                                <span
                                                    id="question-39"
                                                    class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                    :class="dragOverQuestion === 39 ? 'border-blue-500 bg-blue-50' : answers.q39 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 39)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 39)"
                                                    @click="clearAnswer(39)"
                                                    :title="answers.q39 ? 'Click to clear' : 'Drop answer here'"
                                                >
                                                    {{ answers.q39 ? getOptionLabel(answers.q39) : '' }}
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(39)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(39) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p4-part2"
                                                v-html="
                                                    getHighlightedSegment(
                                                        ', these should be at the heart of any action taken. The consequences of further',
                                                    )
                                                "
                                            ></span>
                                            <span
                                                class="mx-2 inline-flex items-center space-x-2"
                                            >
                                                <span class="font-bold text-gray-900 select-text text-base" v-html="getHighlightedSegment('40.')"></span>
                                                <span
                                                    id="question-40"
                                                    class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                    :class="dragOverQuestion === 40 ? 'border-blue-500 bg-blue-50' : answers.q40 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 40)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 40)"
                                                    @click="clearAnswer(40)"
                                                    :title="answers.q40 ? 'Click to clear' : 'Drop answer here'"
                                                >
                                                    {{ answers.q40 ? getOptionLabel(answers.q40) : '' }}
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(40)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(40) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                            <span
                                                class="select-text"
                                                data-segment-id="summary-p4-part3"
                                                v-html="getHighlightedSegment('are very serious, and may even affect our continuing existence.')"
                                            ></span>
                                        </p>
                                    </div>
                                    </div>

                                    <!-- Right side: Draggable options -->
                                    <div class="w-44 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium  text-bold ">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div
                                                    v-for="option in availableOptions"
                                                    :key="option.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd"
                                                >
                                                    <span class="font-bold text-gray-900 text-xs">{{ option.value }}</span>
                                                    <span class="text-gray-700 text-xs">{{ option.label }}</span>
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
