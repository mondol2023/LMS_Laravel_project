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

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

// Hover state for showing bookmark buttons
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

// Check if bookmark should be visible (on hover or when flagged)
const isBookmarkVisible = (questionNumber: number): boolean => {
    return hoveredQuestion.value === questionNumber || isQuestionFlagged(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Reading Part 2 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part2-panel-width';
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
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

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

// Drag and drop functionality for questions 20-23
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const peopleOptions = [
    { value: 'A', label: 'Alex Munroe' },
    { value: 'B', label: 'Paul Donald' },
    { value: 'C', label: 'Robert Rice' },
    { value: 'D', label: 'John Rappole' },
    { value: 'E', label: 'Stacey Philpott' },
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
const getPersonLabel = (value: string): string => {
    const option = peopleOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Computed property to get available options for Q20-23 (removes used options)
const availableOptions = computed(() => {
    const usedOptions = [answers.value.q20, answers.value.q21, answers.value.q22, answers.value.q23].filter(Boolean);
    return peopleOptions.filter((opt) => !usedOptions.includes(opt.value));
});

// Options for questions 24-27 (always available, can be reused)
const methodOptions = [
    { value: 'A', label: 'the shade-grown method' },
    { value: 'B', label: 'the full-sun method' },
    { value: 'C', label: 'both shade-grown and full-sun methods' },
];

// Get label from method option value for display
const getMethodLabel = (value: string): string => {
    const option = methodOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

const passageText =
    ref(`What's the connection between your morning coffee, wintering North American birds and the cool shade of a tree? Actually, unite a lot, says Simon

When scientists from London's Natural History Museum descended on the coffee farms of the tiny Central American republic of El Salvador, they were astonished to find such diversity of insect and plant species. During 18 months' work on 12 farms, they found a third more species of parasitic wasp than are known to exist in the whole country of Costa Rica. They described four new species and are aware of a fifth. On 24 farms they found nearly 300 species of tree when they had expected to find about 100.

El Salvador has lost much of its natural forest, with coffee farms covering nearly 10% of the country. Most of them use the 'shade-grown' method of production, which utilises a semi-natural forest ecosystem. Alex Munro, the museum's botanist on the expedition, says: 'Our findings amazed our insect specialist. There's a very sophisticated food web present. The wasps, for instance, may depend on specific species of tree.

It's the same the world over. Species diversity is much higher where coffee is grown in shade conditions. In addition, coffee (and chocolate) is usually grown in tropical rainforest regions that are biodiversity hotspots. 'These habitats support up to 70% of the planets plant and animal species, and so the production methods of cocoa and coffee can have a hugely significant impact,' explains Dr Paul Donald of the Royal Society for the. Protection of Birds.

So what does 'shade-grown' mean, and why is it good for wildlife? Most of the world's coffee is produced by poor farmers in the developing world. Traditionally they have grown coffee (and cocoa) under the shade of selectively thinned tracts of rain forest in a genuinely sustainable form of farming. Leaf fall from the canopy provides a supply of nutrients and acts as a mulch that suppresses weeds. The insects that live in the canopy pollinate the cocoa and coffee and prey on pests. The trees also provide farmers with fruit and wood for fuel.

Bird diversity in shade-grown coffee plantations rivals that found in natural forests in the same region.' says Robert Rice from the Smithsonian Migratory Bird Center. In Ghana, West Africa. - one of the world's biggest producers of cocoa - 90% of the cocoa is grown under shade, and these forest plantations are a vital habitat for wintering European migrant birds. In the same way. The coffee forests of Central and South America are a refuge for wintering North American migrants.

More recently, a combination of the collapse in the world market for coffee and cocoa and a drive to increase yields by producer countries has led to huge swathes of shade-grown coffee and cocoa being cleared to make way for a highly intensive, monoculture pattern of production known as 'full sun'. But this system not only reduces the diversity of flora and fauna, it also requires huge amounts of pesticides and fertilisers. In Cote d'Ivoire, which produces more than half the world's cocoa, more than a third of the crop is now grown in full-sun conditions.

The loggers have been busy in the Americas too, where nearly 70% of all Colombian coffee is now produced using full-sun production. One study carried out in Colombia and Mexico found that, compared with shade coffee, full-sun plantations have 95% fewer species of birds.

In LI Salvador. Alex Munro says shade-coffee farms have a cultural as well as ecological significance and people are not happy to see them go. But the financial pressures are great, and few of these coffee farms make much money. 'One farm we studied, a cooperative of 100 families, made just S 10,000 a year S100 per family and that's not taking labour costs into account.'

The loss of shade-coffee forests has so alarmed a number of North American wildlife organisations that they're now harnessing consumer power to help save these threatened habitats. They are promoting a 'certification' system that can indicate to consumers that the beans have been grown on shade plantations. Bird-friendly coffee, for instance, is marketed by the Smithsonian Migratory Bird Center. The idea is that the small extra cost is passed directly on to the coffee farmers as a financial incentive to maintain their shade-coffee farms.

Not all conservationists agree with such measures, however. Some say certification could be leading to the loss not preservation of natural forests. John Rappole of the Smithsonian Conservation and Research Center, for example, argues that shade- grown marketing provides 'an incentive to convert existing areas of primary forest that are too remote or steep to be converted profitably to other forms of cultivation into shade-coffee plantations'.

Other conservationists, such as Stacey Philpott and colleagues, argue the case for shade coffee. But there are different types of shade growing. Those used by subsistence farmers are virtually identical to natural forest (and have a corresponding diversity), while systems that use coffee plants as the understorey and cacao or citrus trees as the overstorey may be no more diverse than full-sun farms. Certification procedures need to distinguish between the two. and Ms Philpott argues that as long as the process is rigorous and offers financial gains to the producers, shade growing does benefit the environment.`);

// Define all text segments in order of appearance
const allSegments = [
    // Part 2 Header
    { id: 'part-header', text: 'Part 2' },
    { id: 'part-instructions', text: 'Read the text and answer questions 15-27.' },
    // Passage Panel
    { id: 'passage-title', text: 'NATURAL CHOICE Coffee and chocolate' },
    // Passage text
    { id: 'passage', text: passageText.value },

    // Questions 15-19
    { id: 'q15-19-title', text: 'Questions 15-19' },
    { id: 'q15-19-instructions', text: 'Do the following statements agree with the information given in Reading Passage 2?' },
    { id: 'q15-19-boxes', text: 'In boxes ' },
    { id: 'q15-19-numbers', text: ' 15-19' },

    { id: 'true-label', text: 'TRUE' },
    { id: 'true-desc', text: 'if the statement agrees with the information' },
    { id: 'false-label', text: 'FALSE' },
    { id: 'false-desc', text: 'if the statement contradicts the information' },
    { id: 'not-given-label', text: 'NOT GIVEN' },
    { id: 'not-given-desc', text: 'if there is no information on this' },

    // Question 15
    { id: 'q15-num', text: '15.' },
    { id: 'q15', text: 'More species survive on the farms studied by the researchers than in the natural El Salvador forests.' },
    { id: 'q15-true', text: 'TRUE' },
    { id: 'q15-false', text: 'FALSE' },
    { id: 'q15-not-given', text: 'NOT GIVEN' },

    // Question 16
    { id: 'q16-num', text: '16.' },
    { id: 'q16', text: 'Nearly three-quarters of the Earth s wildlife species can be found in shade coffee plantations' },
    { id: 'q16-true', text: 'TRUE' },
    { id: 'q16-false', text: 'FALSE' },
    { id: 'q16-not-given', text: 'NOT GIVEN' },

    // Question 17
    { id: 'q17-num', text: '17.' },
    { id: 'q17', text: 'Farmers in El Salvador who have tried both methods prefer shade-grown plantations.' },
    { id: 'q17-true', text: 'TRUE' },
    { id: 'q17-false', text: 'FALSE' },
    { id: 'q17-not-given', text: 'NOT GIVEN' },

    // Question 18
    { id: 'q18-num', text: '18.' },
    { id: 'q18', text: 'Shade plantations are important for migrating birds in both Africa and the Americas.' },
    { id: 'q18-true', text: 'TRUE' },
    { id: 'q18-false', text: 'FALSE' },
    { id: 'q18-not-given', text: 'NOT GIVEN' },

    // Question 19
    { id: 'q19-num', text: '19.' },
    { id: 'q19', text: 'Full-sun cultivation can increase the costs of farming.' },
    { id: 'q19-true', text: 'TRUE' },
    { id: 'q19-false', text: 'FALSE' },
    { id: 'q19-not-given', text: 'NOT GIVEN' },

    // Questions 20-23
    { id: 'q20-23-title', text: 'Questions 20-23' },
    { id: 'q20-23-opinions', text: 'Look at the following opinions ' },
    { id: 'q20-23-questions', text: '(Questions 20-23)' },
    { id: 'q20-23-people', text: ' and the list of people below.' },
    { id: 'q20-23-match', text: 'Match each opinion to the person credited with it.' },
    { id: 'q20-23-write', text: 'Write the correct letter ' },
    { id: 'q20-23-letters', text: 'A-E' },
    { id: 'q20-23-boxes', text: ' in boxes 20-23 on your answer sheet.' },
    { id: 'q20-23-nb', text: 'NB. You can write any letter more than once.' },

    { id: 'options-title', text: 'Choose from the following options:' },
    { id: 'option-a', text: 'A' },
    { id: 'option-a-name', text: 'Alex Munroe' },
    { id: 'option-b', text: 'B' },
    { id: 'option-b-name', text: 'Paul Donald' },
    { id: 'option-c', text: 'C' },
    { id: 'option-c-name', text: 'Robert Rice' },
    { id: 'option-d', text: 'D' },
    { id: 'option-d-name', text: 'John Rappole' },
    { id: 'option-e', text: 'E' },
    { id: 'option-e-name', text: 'Stacey Philpott' },

    // Question 20
    { id: 'q20-num', text: '20.' },
    { id: 'q20', text: 'Encouraging shade growing may lead to farmers using the natural forest for their plantations.' },

    // Question 21
    { id: 'q21-num', text: '21.' },
    { id: 'q21', text: 'If shade-coffee farms match the right criteria, they can be good for wildlife' },

    // Question 22
    { id: 'q22-num', text: '22.' },
    { id: 'q22', text: 'There may be as many species of bird found on shade farms in a particular area, as in natural habitats there' },

    // Question 23
    { id: 'q23-num', text: '23.' },
    { id: 'q23', text: 'Currently, many shade-coffee farmers earn very little.' },

    // Questions 24-27
    { id: 'q24-27-title', text: 'Questions 24-27' },
    { id: 'q24-27-classify', text: 'Classify the features described below as applying to' },

    { id: 'q24-27-options-title', text: 'Choose from the following options:' },
    { id: 'q24-27-option-a', text: 'A' },
    { id: 'q24-27-option-a-desc', text: 'the shade-grown method' },
    { id: 'q24-27-option-b', text: 'B' },
    { id: 'q24-27-option-b-desc', text: 'the full-sun method' },
    { id: 'q24-27-option-c', text: 'C' },
    { id: 'q24-27-option-c-desc', text: 'both shade-grown and full-sun methods' },

    { id: 'q24-27-write', text: 'Write the correct letter ' },
    { id: 'q24-27-letters', text: 'A-C' },
    { id: 'q24-27-in-boxes', text: ' in boxes ' },
    { id: 'q24-27-numbers', text: '24-27' },
    { id: 'q24-27-answer-sheet', text: ' on your answer sheet.' },

    // Question 24
    { id: 'q24-num', text: '24.' },
    { id: 'q24', text: 'can be used on either coffee or cocoa plantations' },

    // Question 25
    { id: 'q25-num', text: '25.' },
    { id: 'q25', text: 'is expected to produce bigger crops' },

    // Question 26
    { id: 'q26-num', text: '26.' },
    { id: 'q26', text: 'documentation may be used to encourage sales' },

    // Question 27
    { id: 'q27-num', text: '27.' },
    { id: 'q27', text: 'can reduce wildlife diversity' },
];

// Calculate cumulative offsets dynamically
let currentOffset = 0;
const textSegments = ref(
    allSegments.map((segment) => {
        const segmentWithOffset = {
            id: segment.id,
            text: segment.text,
            offset: currentOffset,
        };
        currentOffset += segment.text.length;
        return segmentWithOffset;
    })
);

// Helper function to get segment by ID
const getSegmentById = (id: string) => {
    return textSegments.value.find((s) => s.id === id);
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

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegment = (segmentId: string) => {
    // Find this segment by ID
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;

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

    // Check vertical bounds
    if (y + modalHeight > viewportHeight - padding) {
        // Not enough space below, try to position above the selection
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        // If still off-screen at top, just use the bottom with scroll
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
        part: 'Part 2',
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

// Confirm delete highlight
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
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
                hoveredNoteId.value = note.id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

// Handle mouse leave on note highlight
const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    // Check if we're leaving a note mark
    if (target.closest('mark[data-note-id]')) {
        // Check if we're entering the tooltip
        if (relatedTarget && relatedTarget.closest('.note-hover-tooltip')) {
            return; // Don't hide - we're entering the tooltip
        }
        showNoteTooltip.value = false;
    }
};

// Handle mouse leave on tooltip itself
const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
};

// Delete note from tooltip
const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
    }
};

const closeContextMenu = () => {
    showContextMenu.value = false;
};

const handleDeleteHighlight = (id: number) => {
    deleteHighlight(id);
};

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
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
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);

    // Add resize event listeners
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
        <!-- Part 2 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header"
                v-html="getHighlightedSegment('part-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions"
                v-html="getHighlightedSegment('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <!-- Passage Title -->
                    <div class="p-6">
                        <h2 class="text-lg text-center font-bold text-gray-900">
                            <span data-segment-id="passage-title"
                                v-html="getHighlightedSegment('passage-title')"></span><br />
                        </h2>
                    </div>

                    <div class="space-y-6 px-4" :style="contentZoom">
                        <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-base">
                            <div class="px-4">
                                <div class="text-justify leading-relaxed  whitespace-pre-wrap ">
                                    <span class="text-base text-gray-700 select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment('passage')"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <!-- Scrollable Questions Content -->
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">
                            <!-- Questions 15-19 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q15-19-title"
                                                v-html="getHighlightedSegment('q15-19-title')"></span>
                                        </h3>
                                    </div>
                                    <div class="border border-gray-300 p-6">
                                        <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-base">
                                            <span data-segment-id="q15-19-instructions"
                                                v-html="getHighlightedSegment('q15-19-instructions')"></span>
                                            <span data-segment-id="q15-19-boxes"
                                                v-html="getHighlightedSegment('q15-19-boxes')"></span><strong
                                                class="text-gray-900" data-segment-id="q15-19-numbers"
                                                v-html="getHighlightedSegment('q15-19-numbers')"></strong>
                                        </p>
                                        <div class="grid grid-cols-1 gap-2 pt-4 text-base sm:text-base">
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900"
                                                    data-segment-id="true-label"
                                                    v-html="getHighlightedSegment('true-label')"></span>
                                                <span class="text-gray-700 select-text" data-segment-id="true-desc"
                                                    v-html="getHighlightedSegment('true-desc')"></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold"
                                                    data-segment-id="false-label"
                                                    v-html="getHighlightedSegment('false-label')"></span>
                                                <span class="text-gray-700 select-text" data-segment-id="false-desc"
                                                    v-html="getHighlightedSegment('false-desc')"></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                    data-segment-id="not-given-label"
                                                    v-html="getHighlightedSegment('not-given-label')"></span>
                                                <span class="text-gray-700 select-text" data-segment-id="not-given-desc"
                                                    v-html="getHighlightedSegment('not-given-desc')"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-6">
                                    <!-- Question 15 -->
                                    <div id="question-15" class="relative pr-10" @mouseenter="hoveredQuestion = 15"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q15-num"
                                                v-html="getHighlightedSegment('q15-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q15"
                                                        v-html="getHighlightedSegment('q15')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q15" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q15-true"
                                                            v-html="getHighlightedSegment('q15-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q15" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q15-false"
                                                            v-html="getHighlightedSegment('q15-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q15" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q15-not-given"
                                                            v-html="getHighlightedSegment('q15-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(15)" @click="emit('toggle-flag', 15)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 16 -->
                                    <div id="question-16" class="relative pr-10" @mouseenter="hoveredQuestion = 16"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q16-num"
                                                v-html="getHighlightedSegment('q16-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q16"
                                                        v-html="getHighlightedSegment('q16')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q16" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q16-true"
                                                            v-html="getHighlightedSegment('q16-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q16" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q16-false"
                                                            v-html="getHighlightedSegment('q16-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q16" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q16-not-given"
                                                            v-html="getHighlightedSegment('q16-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(16)" @click="emit('toggle-flag', 16)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 17 -->
                                    <div id="question-17" class="relative pr-10" @mouseenter="hoveredQuestion = 17"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q17-num"
                                                v-html="getHighlightedSegment('q17-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q17"
                                                        v-html="getHighlightedSegment('q17')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q17" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q17-true"
                                                            v-html="getHighlightedSegment('q17-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q17" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q17-false"
                                                            v-html="getHighlightedSegment('q17-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q17" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q17-not-given"
                                                            v-html="getHighlightedSegment('q17-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(17)" @click="emit('toggle-flag', 17)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 18 -->
                                    <div id="question-18" class="relative pr-10" @mouseenter="hoveredQuestion = 18"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q18-num"
                                                v-html="getHighlightedSegment('q18-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q18"
                                                        v-html="getHighlightedSegment('q18')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q18" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q18-true"
                                                            v-html="getHighlightedSegment('q18-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q18" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q18-false"
                                                            v-html="getHighlightedSegment('q18-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q18" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q18-not-given"
                                                            v-html="getHighlightedSegment('q18-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(18)" @click="emit('toggle-flag', 18)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 19 -->
                                    <div id="question-19" class="relative pr-10" @mouseenter="hoveredQuestion = 19"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q19-num"
                                                v-html="getHighlightedSegment('q19-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q19"
                                                        v-html="getHighlightedSegment('q19')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q19" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q19-true"
                                                            v-html="getHighlightedSegment('q19-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q19" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q19-false"
                                                            v-html="getHighlightedSegment('q19-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q19" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q19-not-given"
                                                            v-html="getHighlightedSegment('q19-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(19)" @click="emit('toggle-flag', 19)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 20-23 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="q20-23-title"
                                                v-html="getHighlightedSegment('q20-23-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q20-23-opinions"
                                            v-html="getHighlightedSegment('q20-23-opinions')"></span><strong
                                            class="text-gray-900" data-segment-id="q20-23-questions"
                                            v-html="getHighlightedSegment('q20-23-questions')"></strong>
                                        <span data-segment-id="q20-23-people"
                                            v-html="getHighlightedSegment('q20-23-people')"></span><br />
                                        <span data-segment-id="q20-23-match"
                                            v-html="getHighlightedSegment('q20-23-match')"></span><br />
                                        <span data-segment-id="q20-23-write"
                                            v-html="getHighlightedSegment('q20-23-write')"></span><strong
                                            class="text-gray-900" data-segment-id="q20-23-letters"
                                            v-html="getHighlightedSegment('q20-23-letters')"></strong>
                                        <span data-segment-id="q20-23-boxes"
                                            v-html="getHighlightedSegment('q20-23-boxes')"></span><br />
                                        <strong class="text-gray-900" data-segment-id="q20-23-nb"
                                            v-html="getHighlightedSegment('q20-23-nb')"></strong>
                                    </p>
                                </div>

                                <!-- Side by side layout: Questions (left) + Options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left side: Questions with drop zones -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                        <div class="space-y-4 text-sm leading-relaxed text-gray-800">

                                            <!-- Question 20 -->
                                            <div id="question-20" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q20-num"
                                                    v-html="getHighlightedSegment('q20-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q20"
                                                        v-html="getHighlightedSegment('q20')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 20 ? 'border-blue-500 bg-blue-50' : answers.q20 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 20)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 20)" @click="clearAnswer(20)"
                                                    :title="answers.q20 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q20 ? getPersonLabel(answers.q20) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(20)"
                                                    @click.stop="emit('toggle-flag', 20)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 21 -->
                                            <div id="question-21" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q21-num"
                                                    v-html="getHighlightedSegment('q21-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q21"
                                                        v-html="getHighlightedSegment('q21')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 21 ? 'border-blue-500 bg-blue-50' : answers.q21 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 21)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 21)" @click="clearAnswer(21)"
                                                    :title="answers.q21 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q21 ? getPersonLabel(answers.q21) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(21)"
                                                    @click.stop="emit('toggle-flag', 21)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 22 -->
                                            <div id="question-22" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q22-num"
                                                    v-html="getHighlightedSegment('q22-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q22"
                                                        v-html="getHighlightedSegment('q22')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 22 ? 'border-blue-500 bg-blue-50' : answers.q22 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 22)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 22)" @click="clearAnswer(22)"
                                                    :title="answers.q22 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q22 ? getPersonLabel(answers.q22) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(22)"
                                                    @click.stop="emit('toggle-flag', 22)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 23 -->
                                            <div id="question-23" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q23-num"
                                                    v-html="getHighlightedSegment('q23-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q23"
                                                        v-html="getHighlightedSegment('q23')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 23 ? 'border-blue-500 bg-blue-50' : answers.q23 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 23)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 23)" @click="clearAnswer(23)"
                                                    :title="answers.q23 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q23 ? getPersonLabel(answers.q23) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(23)"
                                                    @click.stop="emit('toggle-flag', 23)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Right side: Draggable options -->
                                    <div class="w-48 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium text-bold">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div v-for="option in availableOptions" :key="option.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true" @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd">
                                                    <span class="font-bold text-gray-900 text-xs">{{ option.value
                                                        }}</span>
                                                    <span class="text-gray-700 text-xs">{{ option.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 24-27 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="q24-27-title"
                                                v-html="getHighlightedSegment('q24-27-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q24-27-classify"
                                            v-html="getHighlightedSegment('q24-27-classify')"></span><br />
                                        <span data-segment-id="q24-27-write"
                                            v-html="getHighlightedSegment('q24-27-write')"></span><strong
                                            class="text-gray-900" data-segment-id="q24-27-letters"
                                            v-html="getHighlightedSegment('q24-27-letters')"></strong>
                                        <span data-segment-id="q24-27-in-boxes"
                                            v-html="getHighlightedSegment('q24-27-in-boxes')"></span><strong
                                            class="text-gray-900" data-segment-id="q24-27-numbers"
                                            v-html="getHighlightedSegment('q24-27-numbers')"></strong>
                                        <span data-segment-id="q24-27-answer-sheet"
                                            v-html="getHighlightedSegment('q24-27-answer-sheet')"></span>
                                    </p>
                                </div>

                                <!-- Side by side layout: Questions (left) + Options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left side: Questions with drop zones -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                        <div class="space-y-4 text-sm leading-relaxed text-gray-800">

                                            <!-- Question 24 -->
                                            <div id="question-24" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q24-num"
                                                    v-html="getHighlightedSegment('q24-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q24"
                                                        v-html="getHighlightedSegment('q24')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 24 ? 'border-blue-500 bg-blue-50' : answers.q24 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 24)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 24)" @click="clearAnswer(24)"
                                                    :title="answers.q24 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q24 ? getMethodLabel(answers.q24) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(24)"
                                                    @click.stop="emit('toggle-flag', 24)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 25 -->
                                            <div id="question-25" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q25-num"
                                                    v-html="getHighlightedSegment('q25-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q25"
                                                        v-html="getHighlightedSegment('q25')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 25 ? 'border-blue-500 bg-blue-50' : answers.q25 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 25)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 25)" @click="clearAnswer(25)"
                                                    :title="answers.q25 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q25 ? getMethodLabel(answers.q25) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(25)"
                                                    @click.stop="emit('toggle-flag', 25)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 26 -->
                                            <div id="question-26" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q26-num"
                                                    v-html="getHighlightedSegment('q26-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q26"
                                                        v-html="getHighlightedSegment('q26')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 26 ? 'border-blue-500 bg-blue-50' : answers.q26 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 26)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 26)" @click="clearAnswer(26)"
                                                    :title="answers.q26 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q26 ? getMethodLabel(answers.q26) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(26)"
                                                    @click.stop="emit('toggle-flag', 26)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 27 -->
                                            <div id="question-27" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text"
                                                    data-segment-id="q27-num"
                                                    v-html="getHighlightedSegment('q27-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q27"
                                                        v-html="getHighlightedSegment('q27')"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 27)" @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 27)" @click="clearAnswer(27)"
                                                    :title="answers.q27 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q27 ? getMethodLabel(answers.q27) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(27)"
                                                    @click.stop="emit('toggle-flag', 27)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Right side: Draggable options (always available, never removed) -->
                                    <div class="w-56 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium text-bold">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div v-for="option in methodOptions" :key="option.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1.5 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true" @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd">
                                                    <span class="font-bold text-gray-900 text-xs">{{ option.value
                                                        }}</span>
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

        <!-- Context Menu for Highlighting - Speech Bubble Style -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div class="highlight-tooltip pointer-events-auto fixed z-9999" :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @click.stop>
                    <!-- Tooltip Content -->
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <!-- Note Button -->
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                            title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <!-- Highlight Button -->
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                            title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                        </button>
                    </div>
                    <!-- Arrow pointing down -->
                    <div class="tooltip-arrow"></div>
                </div>
            </div>

            <!-- Delete Highlight Tooltip -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
                <div class="delete-highlight-tooltip fixed z-9999" :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                        @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
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
                <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div
                        class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{
                            hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                            title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{
                    left: `${noteInputPosition.x}px`,
                    top: `${noteInputPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @click.stop>
                <div class="mb-3">
                    <p
                        class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
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

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>
