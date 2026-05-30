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

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Reading Part 1 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts003-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Delete highlight tooltip
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note tooltip (hover)
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

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
});


// Text segments with their cumulative offsets in the full text
const passageText = `Africa owes its termite mounds a lot. Trees and shrubs take root in them. Prospectors mine them, looking for specks of gold carried up by termites from hundreds of metres below. And of course, they are a special treat to aardvarks and other insectivores.
<br/>
Now, Africa is paying an offbeat tribute to these towers of mud. The extraordinary Eastgate Building in Harare, Zimbabwe’s capital city, is said to be the only one in the world to use the same cooling and heating principles as the termite mound.
<br/>
Termites in Zimbabwe build gigantic mounds inside which they farm a fungus that is their primary food source. This must be kept at exactly 30.5°C, while the temperatures on the African yeld outside can range from 1.5°C at night only just above freezing to a baking hot 40°C during the day. The termites achieve this remarkable feat by building a system of vents in the mound. Those at the base lead down into chambers cooled by wet mud carried up from water tables far below, and others lead up through a Hue to the peak of the mound. By constantly opening and closing these heating and cooling vents over the course of the day the termites succeed in keeping the temperature constant in spite of the wide fluctuations outside.
<br/>
Architect Mick Pearce used precisely the same strategy when designing the Eastgate Building, which has no air conditioning and virtually no heating. The building the country's largest commercial and shopping complex uses less than I0% of the energy of a conventional building ns size. These efficiencies translated directly to the bottom line: the Eastgate’s owners saved $3.5 million on a $36 million building because an air- conditioning plant didn't have to be imported. These savings were also passed on to tenants: rents are 20% lower than in a new building next door.
<br/>
The complex is actually two buildings linked by bridges across a shady, glass-roofed atrium open to the breezes. Fans suck fresh air in from the atrium, blow it upstairs through hollow spaces under the floors and from there into each office through baseboard vents. As it rises and warms, it is drawn out via ceiling vents and finally exits through forty- eight brick chimneys.
<br/>
To keep the harsh, high yeld sun from heating the interior, no more than 25% of the outside is glass, and all the windows are screened by cement arches that just out more than a metre.
<br/>
During summer’s cool nights, big fans flush air through the building seven times an hour to chill the hollow floors. By day, smaller fans blow two changes of air an hour through the building, to circulate the air which has been in contact with the cool floors. For winter days, there are small heaters in the vents.
<br/>
This is all possible only because Harare is 1600 feet above sea level, has cloudless skies, little humidity and rapid temperature swings days as warm as 3l°C commonly drop to 14°C at night. ‘You couldn’t do this in New York, with its fantastically hot summers and fantastically cold winters,’ Pearce said. But then his eyes lit up at the challenge.' Perhaps you could store the summer's heat in water somehow.
<br/>
 The engineering firm of Ove Amp & Partners, which worked with him on the design, monitors daily temperatures outside, under the floors and at knee, desk and ceiling level. Ove Arup's graphs show that the temperature of the building has generally stayed between 23"C and 25°C. with the exception of the annual hot spell just before the summer rains in October, and three days in November, when a janitor accidentally switched off the fans at night. The atrium, which funnels the winds through, can be much cooler. And the air is fresh far more so than in air-conditioned buildings, where up to 30% of the air is recycled.
<br/>
Pearce, disdaining smooth glass skins as ‘igloos in the Sahara’, calls his building, with its exposed girders and pipes, ‘spiky’. The design of the entrances is based on the porcupine-quill headdresses of the local Shona tribe. Elevators are designed to look like the mineshaft cages used in Zimbabwe's diamond mines. The shape of the fan covers, and the stone used in their construction, are echoes of Great Zimbabwe, the ruins that give the country its name.
<br/>
Standing on a roof catwalk, peering down inside at people as small as termites below. Pearce said he hoped plants would grow wild in the atrium and pigeons and bats would move into it, like that termite fungus, further extending the whole 'organic machine’ metaphor. The architecture, he says, is a regionalised style that responds to the biosphere, to the ancient traditional stone architecture of Zimbabwe's past, and to local human resources.`;

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts =[
    // Passage Panel
    'Part 1',
    'Read the text and answer questions 1–13.',
    'Sustainable Architecture – lessons from the ant Termite mounds were the inspiration for an innovative design in sustainable living',
    passageText,

    // Questions Section
    'QUESTIONS',
    'Answer all questions based on Reading Passage 1',
    'Questions 1-5',
    'Choose the correct answer, A, B, C or D.',
    '1. Why do termite mounds have a system of vents?',
    'A. to allow the termites to escape from predators',
    'B. to enable the termites to produce food',
    'C. to allow the termites to work efficiently',
    'D. to enable the termites to survive at night',

    '2. Why was Eastgate cheaper to build than a conventional building?',
    'A. Very few materials were imported.',
    'B. Its energy consumption was so low.',
    'C. Its tenants contributed to the costs.',
    'D. No air conditioners were needed.',

    '3. Why would a building like Eastgate not work efficiently in New York?',
    'A. Temperature change occurs seasonally rather than daily.',
    'B. Pollution affects the storage of heat in the atmosphere.',
    'C. Summer and winter temperatures are too extreme.',
    'D. Levels of humidity affect cloud coverage.',

    '4. What does Ove Arup’s data suggest about Eastgate’s temperature control system?',
    'A. It allows a relatively wide range of temperatures.',
    'B. The only problems are due to human error.',
    'C. It functions well for most of the year.',
    'D. The temperature in the atrium may fall too low.',

    '5. Pearce believes that his building would be improved by',
    'A. becoming more of a habitat for wildlife.',
    'B. even closer links with the history of Zimbabwe.',
    'C. giving people more space to interact with nature.',
    'D. better protection from harmful organisms.',

    'Questions 6-10',//33
    'Use NO MORE THAN THREE WORDS for each answer.',//34
    'Warm air leaves the offices through',//35
    'The warm air leaves the building through',//36
    'Heat from the sun is prevented from reaching the windows by',//37
    'When the outside temperature drops',//38
    'bring air in from outside.',//39
    'On cold days',//40
    'raise the temperature in the offices.',//41

    'Questions 11-13',
    'Answer the question below, using NO MORE THAN THREE WORDS from the passage for each answer.',
    'Write your answers in boxes 11-13 on your answer sheet.',
    'Which three parts of the Eastgate Building reflect important features of Zimbabwe’s history and culture?',
    'A. entrances',
    'B. quill',
    'C. cages',
    'D. elevators',
    'E. fan covers',
    'F. stone',
    // Question numbers 1-10
    '<b>1.</b>',
    '<b>2.</b>',
    '<b>3.</b>',
    '<b>4.</b>',
    '<b>5.</b>',
    '<b>6.</b>',
    '<b>7.</b>',
    '<b>8.</b>',
    '<b>9.</b>',
    '<b>10.</b>',
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


const textSegments = computed(() => {
    let currentOffset = 0;

    return allStaticTexts.map((text, index) => {
        const safeText = text ?? '';

        const segment = {
            id: `segment_${index}`,
            text: safeText,
            offset: currentOffset,
        };

        // Use plain text length, not HTML string length
        currentOffset += getPlainTextLength(safeText);

        return segment;
    });
});

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
    const allAnswers = { ...answers.value };

    // Handle multiple choice questions 28-30
    if (multipleAnswers.value.q11_13?.length > 0) {
        // Store the selected options as comma-separated string for each question
        const selectedOptions = multipleAnswers.value.q11_13;
        allAnswers.q11 = selectedOptions[0] || '';
        allAnswers.q12 = selectedOptions[1] || '';
        allAnswers.q13 = selectedOptions[2] || '';
    }

    return allAnswers;
};

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        // Remove if already selected
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 3) {
        // Add if less than 3 selected (for questions 11-13)
        currentAnswers.push(option);
    }
};

const multipleAnswers = ref<{ q11_13: string[] }>({
    q11_13: [],
});

// Watch for changes and enforce max 3 selections
watch(
    () => multipleAnswers.value.q11_13,
    (newVal, oldVal) => {
        if (newVal.length > 3) {
            // Revert to previous valid state
            multipleAnswers.value.q11_13 = oldVal;
            // Optional: show user feedback
            alert('Maximum 3 selections allowed.');
        }
    },
    { deep: true }
);

const handleOptionToggle = (val: string) => {
    const arr = multipleAnswers.value.q11_13;
    if (arr.includes(val)) {
        multipleAnswers.value.q11_13 = arr.filter(v => v !== val);
    } else if (arr.length < 3) {
        multipleAnswers.value.q11_13 = [...arr, val]; // trigger reactivity
    }
};

const toggleOption = (val: string) => {
    const arr = multipleAnswers.value.q11_13;

    if (arr.includes(val)) {
        multipleAnswers.value.q11_13 = arr.filter(v => v !== val);
    } else if (arr.length < 3) {
        multipleAnswers.value.q11_13.push(val);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
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
            const segmentId = segmentIdAttr;
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

// const highlightedPassageText = computed(() => {
//     return applyHighlightsToText(passageText.value);
// });

const handlePassageTextSelect = (event: MouseEvent) => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && passageTextRef.value) {
            // Use fixed positioning relative to viewport
            // Position menu ABOVE the selection
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70; // Approximate tooltip height
            const menuY = rect.top - menuHeight - 8; // 8px gap above selection

            // Ensure menu stays within viewport
            const viewportWidth = window.innerWidth;
            const menuWidth = 140; // Smaller width for new design

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10), // Ensure it doesn't go above viewport
            };
            showContextMenu.value = true;

            if (selection) {
                const preSelectionRange = range!.cloneRange();
                const container = passageTextRef.value;
                preSelectionRange.selectNodeContents(container);
                preSelectionRange.setEnd(range!.startContainer, range!.startOffset);

                const plainTextBefore = preSelectionRange.toString();
                const startOffset = plainTextBefore.length;
                const endOffset = startOffset + selected.length;

                selectedText.value = selected;
                selectionRange.value = { start: startOffset, end: endOffset };
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
        part: 'Part 1',
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
        <!-- Part 1 Header -->
        <div class="part-header-box mb-3 rounded border ml-2  border-gray-200 px-4 py-3">
            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="segment_0" v-html="getHighlightedSegment(allStaticTexts[0])"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="segment_1" v-html="getHighlightedSegment(allStaticTexts[1])"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-0.5">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- Reading Passage Panel -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="pt-6">
                        <h2 class="text-base font-bold text-gray-900 px-4">
                            <span data-segment-id="segment_2" v-html="getHighlightedSegment(allStaticTexts[2])"></span>
                        </h2>
                    </div>
                    <div class="flex-1 space-y-6 overflow-y-auto px-4" :style="contentZoom">
                        <div class="space-y-6 text-base leading-relaxed select-text sm:text-base">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span
                                        class="text-base text-gray-700 select-text"
                                        data-segment-id="segment_3"
                                        v-html="getHighlightedSegment(allStaticTexts[3])"
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
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Panel -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 1–5: MCQ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-2 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="segment_6" v-html="getHighlightedSegment(allStaticTexts[6])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="segment_7" v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Q1 -->
                                    <div id="question-1" class="relative pr-10" @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-2 text-base font-semibold text-gray-900">

                                            <span class="ml-1" data-segment-id="segment_8" v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                                        </p>
                                        <div class="space-y-1 pl-4">
                                            <label v-for="(opt, idx) in [allStaticTexts[9], allStaticTexts[10], allStaticTexts[11], allStaticTexts[12]]" :key="idx" class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q1" :value="['A','B','C','D'][idx]" class="mt-1 h-4 w-4 accent-black shrink-0" />
                                                <span class="text-base text-gray-700 select-text" v-html="getHighlightedSegment(opt)"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 1 || isQuestionFlagged(1)" @click.stop="toggleFlag(1)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(1) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q2 -->
                                    <div id="question-2" class="relative pr-10" @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-2 text-base font-semibold text-gray-900">
                                            <span class="ml-1" data-segment-id="segment_13" v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                        </p>
                                        <div class="space-y-1 pl-4">
                                            <label v-for="(opt, idx) in [allStaticTexts[14], allStaticTexts[15], allStaticTexts[16], allStaticTexts[17]]" :key="idx" class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q2" :value="['A','B','C','D'][idx]" class="mt-1 h-4 w-4 accent-black shrink-0" />
                                                <span class="text-base text-gray-700 select-text" v-html="getHighlightedSegment(opt)"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 2 || isQuestionFlagged(2)" @click.stop="toggleFlag(2)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(2) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q3 -->
                                    <div id="question-3" class="relative pr-10" @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-2 text-base font-semibold text-gray-900">
                                            <span class="ml-1" data-segment-id="segment_18" v-html="getHighlightedSegment(allStaticTexts[18])"></span>
                                        </p>
                                        <div class="space-y-1 pl-4">
                                            <label v-for="(opt, idx) in [allStaticTexts[19], allStaticTexts[20], allStaticTexts[21], allStaticTexts[22]]" :key="idx" class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q3" :value="['A','B','C','D'][idx]" class="mt-1 h-4 w-4 accent-black shrink-0" />
                                                <span class="text-base text-gray-700 select-text" v-html="getHighlightedSegment(opt)"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 3 || isQuestionFlagged(3)" @click.stop="toggleFlag(3)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(3) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q4 -->
                                    <div id="question-4" class="relative pr-10" @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-2 text-base font-semibold text-gray-900">
                                            <span class="ml-1" data-segment-id="segment_23" v-html="getHighlightedSegment(allStaticTexts[23])"></span>
                                        </p>
                                        <div class="space-y-1 pl-4">
                                            <label v-for="(opt, idx) in [allStaticTexts[24], allStaticTexts[25], allStaticTexts[26], allStaticTexts[27]]" :key="idx" class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q4" :value="['A','B','C','D'][idx]" class="mt-1 h-4 w-4 accent-black shrink-0" />
                                                <span class="text-base text-gray-700 select-text" v-html="getHighlightedSegment(opt)"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 4 || isQuestionFlagged(4)" @click.stop="toggleFlag(4)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(4) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q5 -->
                                    <div id="question-5" class="relative pr-10" @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                                        <p class="mb-2 text-base font-semibold text-gray-900">
                                            <span class="ml-1" data-segment-id="segment_28" v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                        </p>
                                        <div class="space-y-1 pl-4">
                                            <label v-for="(opt, idx) in [allStaticTexts[29], allStaticTexts[30], allStaticTexts[31],allStaticTexts[32]]" :key="idx" class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q5" :value="['A','B','C'][idx]" class="mt-1 h-4 w-4 accent-black shrink-0" />
                                                <span class="text-base text-gray-700 select-text" v-html="getHighlightedSegment(opt)"></span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 5 || isQuestionFlagged(5)" @click.stop="toggleFlag(5)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(5) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 6–10: Short Answer / Fill in the blank -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-2 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <!-- index not in allStaticTexts, hardcoded -->
                                            <span class="font-base text-gray-900 shrink-0" data-segment-id="segment_33" v-html="getHighlightedSegment(allStaticTexts[33])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span class="font-base text-gray-900 shrink-0" data-segment-id="segment_34" v-html="getHighlightedSegment(allStaticTexts[34])"></span>
                                    </p>
                                </div>

                                <div class="space-y-5">
                                    <!-- Q6: "Warm air leaves the offices through ____" -->
                                    <div id="question-6" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 6" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span class="font-base text-gray-900 shrink-0" data-segment-id="segment_35" v-html="getHighlightedSegment(allStaticTexts[35])"></span>
                                            <input v-model="answers.q6" type="text"
                                                class="mx-2 inline-block placeholder:font-bold w-40 border text-center border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black"
                                                placeholder="6" />
                                        </p>
                                        <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)" @click.stop="toggleFlag(6)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(6) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q7: "The warm air leaves the building through ____" -->
                                    <div id="question-7" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span data-segment-id="segment_36" v-html="getHighlightedSegment(allStaticTexts[36])"></span>
                                            <input v-model="answers.q7" type="text"
                                                class="mx-2 inline-block w-40 border placeholder:font-bold text-center border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black"
                                                placeholder="7" />
                                        </p>
                                        <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)" @click.stop="toggleFlag(7)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q8: "Heat from the sun is prevented from reaching the windows by ____" -->
                                    <div id="question-8" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span data-segment-id="segment_37" v-html="getHighlightedSegment(allStaticTexts[37])"></span>
                                            <input v-model="answers.q8" type="text"
                                                class="mx-2 inline-block w-40 border placeholder:font-bold text-center border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black"
                                                placeholder="8" />
                                        </p>
                                        <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)" @click.stop="toggleFlag(8)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(8) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q9: "When the outside temperature drops ____ bring air in from outside." -->
                                    <div id="question-9" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                            <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span data-segment-id="segment_38" v-html="getHighlightedSegment(allStaticTexts[38])"></span>
                                            <input v-model="answers.q9" type="text"
                                                class="mx-2 inline-block w-40 border placeholder:font-bold text-center border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black"
                                                placeholder="9" />
                                        </p>
                                        <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)" @click.stop="toggleFlag(9)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>

                                    </div>
                                     <span data-segment-id="segment_39" v-html="getHighlightedSegment(allStaticTexts[39])"></span>

                                    <!-- Q10: "On cold days ____ raise the temperature in the offices." -->
                                    <div id="question-10" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">

                                        <p class="flex-1 text-base leading-relaxed text-gray-700">
                                            <span data-segment-id="segment_40" v-html="getHighlightedSegment(allStaticTexts[40])"></span>
                                            <input v-model="answers.q10" type="text"
                                                class="mx-2 inline-block w-40 border placeholder:font-bold text-center border-gray-900 px-2 py-0.5 text-base focus:outline-none focus:border-black"
                                                placeholder="10" />

                                        </p>
                                        <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)" @click.stop="toggleFlag(10)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                    <span data-segment-id="segment_41" v-html="getHighlightedSegment(allStaticTexts[41])"></span>
                                </div>
                            </div>

                            <!-- Questions 11–13: Checkbox (max 3 selections) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-2 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <!-- allStaticTexts doesn't have a Q11-13 title index exposed; using hardcoded label -->
                                            <span data-segment-id="segment_42" v-html="getHighlightedSegment(allStaticTexts[42])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="segment_43" v-html="getHighlightedSegment(allStaticTexts[43])"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="segment_44" v-html="getHighlightedSegment(allStaticTexts[44])"></span>
                                    </p>
                                    <p class="mb-4 text-base font-semibold text-gray-900">
                                        <span data-segment-id="segment_45" v-html="getHighlightedSegment(allStaticTexts[45])"></span>
                                    </p>

                                </div>

                                <div id="question-11" class="space-y-3">
                                    <!--
                                        Options from allStaticTexts:
                                        index 45 = 'A. entrances'
                                        index 46 = 'B. quill'
                                        index 47 = 'C. cages'
                                        index 48 = 'D. elevators'
                                        index 49 = 'E. fan covers'
                                        index 50 = 'F. stone'
                                    -->
                                    <label
                                        v-for="(opt, idx) in [
                                            { seg: 46, val: 'A' },
                                            { seg: 47, val: 'B' },
                                            { seg: 48, val: 'C' },
                                            { seg: 49, val: 'D' },
                                            { seg: 50, val: 'E' },
                                            { seg: 51, val: 'F' },
                                        ]"
                                        :key="opt.val"
                                        class="flex items-center gap-3"
                                        :class="{ 'opacity-50 select-text': multipleAnswers.q11_13.length >= 3 && !multipleAnswers.q11_13.includes(opt.val) }"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="multipleAnswers.q11_13.includes(opt.val)"
                                            :value="opt.val"
                                            @change="handleOptionToggle(opt.val)"
                                            class="h-4 w-4 accent-black shrink-0"
                                            :disabled="multipleAnswers.q11_13.length >= 3 && !multipleAnswers.q11_13.includes(opt.val)"
                                        />
                                        <!-- ✅ Add :data-segment-id here -->
                                        <span
                                            class="text-base text-gray-700 select-text"
                                            :data-segment-id="`segment_${opt.seg}`"
                                            v-html="getHighlightedSegment(allStaticTexts[opt.seg])"
                                        ></span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu (Highlight / Note) -->
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

            <!-- Note Hover Tooltip -->
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

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
