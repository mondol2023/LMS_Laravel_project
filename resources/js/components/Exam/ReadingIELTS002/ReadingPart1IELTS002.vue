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
const PANEL_WIDTH_KEY = 'reading-ielts002-part1-panel-width';
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
    q14: '',
});

// Text segments with their cumulative offsets in the full text
const passageText =
    ref(`<b>A.</b> For the past 100 years, special high-grade white sand dug from the ground at Leighton Buzzard in the UK has been used to filter tap water to remove bacteria and impurities but this may no longer be necessary. A new factory that turns used wine bottles into green sand could revolutionise the recycling industry and help to filter Britain's drinking water. Backed by $1.6m from the European Union and the Department for Environment, Food and Rural Affairs (Defra), a company based in Scotland is building the factory, which will turn beverage bottles back into the sand from which they were made in the first place. The green sand has already been successfully tested by water companies and is being used in 50 swimming pools in Scotland to keep the water clean.

<b>B.</b> The idea is not only to avoid using up an increasingly scarce natural resource, sand but also to solve a crisis in the recycling industry. Britain uses 5.5m tonnes of glass a year, but recycles only 750,000 tonnes of it. The problem is that half the green bottle glass in Britain is originally from imported wine and beer bottles. Because there is so much of it, and it is used less in domestic production than other types, green glass is worth only $25 a tonne. Clear glass, which is melted down and used for whisky bottles, mainly for export, is worth double that amount.

<b>C.</b> Howard Dryden, a scientist and managing director of the company, Dryden Aqua, of Bonnyrigg, near Edinburgh, has spent six years working on the product he calls Active Filtration Media, or AFM. He concedes that he has given what is basically recycled glass a 'fancy name' to remove the stigma of what most people would regard as an inferior product. He says he needs bottles that have already contained drinkable liquids to be sure that drinking water filtered through the AFM would not be contaminated. Crushed down beverage glass has fewer impurities than real sand and it performed better in trials. 'The fact is that tests show that AFM does the job better than sand, it is easier to clean and reuse and has all sorts of properties that make it ideal for other applications.' he claimed.

<b>D.</b> The factory is designed to produce 100 tonnes of AFM a day, although Mr Dryden regards this as a large-scale pilot project rather than full production. Current estimates of the UK market for this glass for filtering drinking water, sewage, industrial water, swimming pools and fish farming are between 175.000 to 217.000 tonnes a year, which will use up most of the glass available near the factory. So he intends to build five or six factories in cities where there are large quantities of bottles, in order to cut down on transport costs.

<b>E.</b> The current factory will be completed this month and is expected to go into full production on January 14th next year. Once it is providing a 'regular' product, the government's drinking water inspectorate will be asked to perform tests and approve it for widespread use by water companies. A Defra spokesman said it was hoped that AFM could meet approval within six months. The only problem that they could foresee was possible contamination if some glass came from sources other than beverage bottles.

<b>F.</b> Among those who have tested the glass already is Caroline Fitzpatrick of the civil and environmental engineering department of University College London. 'We have looked at a number of batches and it appears to do the job.' she said. 'Basically, sand is made of glass and Mr Dryden is turning bottles back into sand. It seems a straightforward idea and there is no reason we can think of why it would not work. Since glass from wine bottles and other beverages has no impurities and clearly did not leach any substances into the contents of the bottles, there was no reason to believe there would be a problem,' Dr Fitzpatrick added.

<b>G.</b> Mr Dryden has set up a network of agents round the world to sell AFM. It is already in use in central America to filter water on banana plantations where the fruit has to be washed before being despatched to European markets. It is also in use in sewage works to filter water before it is returned to rivers, something which is becoming legally necessary across the European Union because of tighter regulations on sewage works. So there are a great number of applications involving cleaning up water. Currently, however, AFM costs $670 a tonne, about four times as much as good quality sand. 'Hut that is because we haven't got large-scale production. Obviously, when we get going it will cost a lot less, and be competitive with sand in price as well.' Mr Dryden said. 'I believe it performs better and lasts longer than sand, so it is going to be better valued too.'

<b>H.</b> If AFM takes off as a product it will be a big boost for the government agency which is charged with finding a market for recycled products. Crushed glass is already being used in road surfacing and in making tiles and bricks. Similarly. AFM could prove to have a widespread use and give green glass a cash value.`);

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts = [
    // Passage Panel
    'Part 1',
    'Read the text and answer questions 1–13.',
    'Green virtues of green sand',
    'Revolution in glass recycling could help keep water clean',
    passageText.value,

    // Questions Section
    'QUESTIONS',
    'Answer all questions based on Reading Passage 1',
    'Questions 1-10',
    'Reading Passage 1 has 8 paragraphs labelled A-H.',
    'Which paragraph contains the following information?.',
    'Write the correct letter A-H in boxes 1-10 on your answer sheet.',
    'NB You may use any letter more than once.',
    'a description of plans to expand production of AFM',
    'the identification of a potential danger in the raw material for AFM.',
    'an example of AFM use in the export market',
    'a comparison of the value of green glass and other types of glass',
    'a list of potential applications of AFM in the domestic market',
    'the conclusions drawn from laboratory checks on the process of AFM production',
    'identification of current funding for the production of green sand',
    'an explanation of the chosen brand name for crushed green glass',
    'a description of plans for exporting AFM',
    'a description of what has to happen before AFM is accepted for general use',
    'Questions 11-14',
    'Complete the summary below.',
    'Choose NO MORE THAN TWO WORDS from the passage for each answer.',
    'Write your answers in boxes 11-14 on your answer sheet.',
    'Green sand',
    'The use of crushed green glass (AFM) may have two significant impacts: it may help to save a diminishing',
    'while at the same time solving a major problem for the',
    'in the UK. However, according to Howard Dryden, only glass from bottles that have been used for',
    'can be used in the production process. AFM is more effective than',
    'as a water filter, and also has other uses.',
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

let currentOffset = 0;
const newTextSegments = allStaticTexts.map((text, index) => {
    const segment = {
        id: `segment_${index}`, // Generate a unique ID for each segment
        text: text,
        offset: currentOffset,
    };
    currentOffset += text.length;
    return segment;
});

const textSegments = ref(newTextSegments);

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
        <!-- Part 1 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="segment_0"
                v-html="getHighlightedSegment(allStaticTexts[0])"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="segment_1"
                v-html="getHighlightedSegment(allStaticTexts[1])"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-0.5">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="pt-6">
                        <h2 class="text-lg text-center font-bold text-gray-900">
                            <span data-segment-id="segment_2" v-html="getHighlightedSegment(allStaticTexts[2])"></span>
                            <br />
                            <span data-segment-id="segment_3" v-html="getHighlightedSegment(allStaticTexts[3])"></span>
                        </h2>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto px-4" :style="contentZoom">
                        <div class="space-y-6 text-base leading-relaxed select-text sm:text-base">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span class="text-base text-gray-700 select-text" data-segment-id="segment_4"
                                        v-html="getHighlightedSegment(allStaticTexts[4])"></span>
                                </div>
                            </div>

                            <!-- Highlights List -->
                            <!-- <div v-if="highlights.length > 0" class="mt-4 border-t border-gray-200 pt-4">
                                <h3 class="mb-2 text-base font-semibold text-gray-700 sm:text-base">Your Highlights</h3>
                                <div class="space-y-2">
                                    <div v-for="highlight in highlights" :key="highlight.id"
                                        class="flex items-start gap-2 rounded border border-gray-200 p-2">
                                        <span class="mt-0.5 inline-block h-4 w-4 rounded" :class="{
                                            'bg-yellow-200': highlight.color === 'yellow',
                                            'bg-green-200': highlight.color === 'green',
                                            'bg-blue-200': highlight.color === 'blue',
                                            'bg-pink-200': highlight.color === 'pink',
                                            'bg-orange-200': highlight.color === 'orange',
                                        }"></span>
                                        <p class="flex-1 text-base text-gray-700 sm:text-base">{{ highlight.text }}</p>
                                        <button @click="handleDeleteHighlight(highlight.id)"
                                            class="text-red-500 hover:text-red-700" title="Delete highlight">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div> -->

                            <!-- Notes List -->
                            <!-- <div v-if="notes.length > 0" class="mt-4 border-t border-gray-200 pt-4">
                                <h3
                                    class="mb-2 flex items-center gap-2 text-base font-semibold text-gray-700 sm:text-base">
                                    <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Your Notes
                                </h3>
                                <div class="space-y-3">
                                    <div v-for="note in notes" :key="note.id"
                                        class="relative rounded-lg border border-indigo-100 bg-indigo-50/30 p-3">
                                        <div class="mb-2 border-b border-indigo-200 pb-2">
                                            <p class="text-sm text-indigo-600 italic">"{{ note.selectedText }}"</p>
                                        </div>
                                        <p class="text-base font-medium text-gray-800 sm:text-base">{{ note.note }}</p>
                                        <button @click="deleteNote(note.id)"
                                            class="absolute top-2 right-2 text-red-500 hover:text-red-700"
                                            title="Delete note">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div> -->
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
                            <!-- Questions 1-10 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_7"
                                                v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_8"
                                            v-html="getHighlightedSegment(allStaticTexts[8])"></span><br />
                                        <span data-segment-id="segment_9"
                                            v-html="getHighlightedSegment(allStaticTexts[9])"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_10"
                                            v-html="getHighlightedSegment(allStaticTexts[10])"></span><br />
                                        <strong data-segment-id="segment_11"
                                            v-html="getHighlightedSegment(allStaticTexts[11])"></strong>
                                    </p>
                                </div>
                                <div class="space-y-4">
                                    <!-- Question 1-->
                                    <div id="question-1" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_32"
                                            v-html="getHighlightedSegment(allStaticTexts[32])"></span>
                                        <select v-model="answers.q1"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_12"
                                                v-html="getHighlightedSegment(allStaticTexts[12])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                            @click.stop="toggleFlag(1)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- Question 2-->
                                    <div id="question-2" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_33"
                                            v-html="getHighlightedSegment(allStaticTexts[33])"></span>
                                        <select v-model="answers.q2"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_13"
                                                v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                            @click.stop="toggleFlag(2)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 3-->
                                    <div id="question-3" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_34"
                                            v-html="getHighlightedSegment(allStaticTexts[34])"></span>
                                        <select v-model="answers.q3"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_14"
                                                v-html="getHighlightedSegment(allStaticTexts[14])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                            @click.stop="toggleFlag(3)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 4-->
                                    <div id="question-4" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_35"
                                            v-html="getHighlightedSegment(allStaticTexts[35])"></span>
                                        <select v-model="answers.q4"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_15"
                                                v-html="getHighlightedSegment(allStaticTexts[15])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                            @click.stop="toggleFlag(4)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 5-->
                                    <div id="question-5" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_36"
                                            v-html="getHighlightedSegment(allStaticTexts[36])"></span>
                                        <select v-model="answers.q5"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_16"
                                                v-html="getHighlightedSegment(allStaticTexts[16])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                            @click.stop="toggleFlag(5)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 6-->
                                    <div id="question-6" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 6" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_37"
                                            v-html="getHighlightedSegment(allStaticTexts[37])"></span>
                                        <select v-model="answers.q6"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_17"
                                                v-html="getHighlightedSegment(allStaticTexts[17])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                            @click.stop="toggleFlag(6)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 7-->
                                    <div id="question-7" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_38"
                                            v-html="getHighlightedSegment(allStaticTexts[38])"></span>
                                        <select v-model="answers.q7"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_18"
                                                v-html="getHighlightedSegment(allStaticTexts[18])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                            @click.stop="toggleFlag(7)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 8-->
                                    <div id="question-8" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_39"
                                            v-html="getHighlightedSegment(allStaticTexts[39])"></span>
                                        <select v-model="answers.q8"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_19"
                                                v-html="getHighlightedSegment(allStaticTexts[19])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                            @click.stop="toggleFlag(8)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 9-->
                                    <div id="question-9" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_40"
                                            v-html="getHighlightedSegment(allStaticTexts[40])"></span>
                                        <select v-model="answers.q9"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_20"
                                                v-html="getHighlightedSegment(allStaticTexts[20])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="toggleFlag(9)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!--                                Question 10-->
                                    <div id="question-10" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900" data-segment-id="segment_41"
                                            v-html="getHighlightedSegment(allStaticTexts[41])"></span>
                                        <select v-model="answers.q10"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_21"
                                                v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 11-14 -->
                            <div class="p-6">
                                <div>
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="segment_22"
                                                v-html="getHighlightedSegment(allStaticTexts[22])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_23"
                                            v-html="getHighlightedSegment(allStaticTexts[23])"></span><br />
                                        <strong data-segment-id="segment_24"
                                            v-html="getHighlightedSegment(allStaticTexts[24])"></strong><br />
                                        <span data-segment-id="segment_25"
                                            v-html="getHighlightedSegment(allStaticTexts[25])"></span>
                                    </p>
                                </div>
                                <div class="space-y-4">
                                    <p class="text-center font-bold select-text">
                                        <span class="select-text" data-segment-id="segment_26"
                                            v-html="getHighlightedSegment(allStaticTexts[26])"></span>
                                    </p>
                                    <div class="space-y-4 text-base leading-relaxed text-gray-800 sm:text-base">
                                        <p id="question-11" class="relative pr-10" @mouseenter="hoveredQuestion = 11"
                                            @mouseleave="hoveredQuestion = null">
                                            <span class="select-text" data-segment-id="segment_27"
                                                v-html="getHighlightedSegment(allStaticTexts[27])"></span>

                                            <span class="mx-2 inline-flex items-center">
                                                <input v-model="answers.q11" type="text" placeholder="11"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
                                            </span>
                                            <span class="select-text" data-segment-id="segment_28"
                                                v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                            <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                                @click.stop="toggleFlag(11)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </p>
                                        <p id="question-12" class="relative pr-10" @mouseenter="hoveredQuestion = 12"
                                            @mouseleave="hoveredQuestion = null">
                                            <span class="mx-2 inline-flex items-center">
                                                <input v-model="answers.q12" type="text" placeholder="12"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
                                            </span>
                                            <span class="select-text" data-segment-id="segment_29"
                                                v-html="getHighlightedSegment(allStaticTexts[29])"></span>
                                            <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                                @click.stop="toggleFlag(12)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </p>
                                        <p id="question-13" class="relative pr-10" @mouseenter="hoveredQuestion = 13"
                                            @mouseleave="hoveredQuestion = null">
                                            <span class="mx-2 inline-flex items-center">
                                                <input v-model="answers.q13" type="text" placeholder="13"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
                                            </span>
                                            <span class="select-text" data-segment-id="segment_30"
                                                v-html="getHighlightedSegment(allStaticTexts[30])"></span>
                                            <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                                @click.stop="toggleFlag(13)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </p>
                                        <p id="question-14" class="relative pr-10" @mouseenter="hoveredQuestion = 14"
                                            @mouseleave="hoveredQuestion = null">
                                            <span class="mx-2 inline-flex items-center">
                                                <input v-model="answers.q14" type="text" placeholder="14"
                                                    class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
                                            </span>
                                            <span class="select-text" data-segment-id="segment_31"
                                                v-html="getHighlightedSegment(allStaticTexts[31])"></span>
                                            <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                                @click.stop="toggleFlag(14)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </p>
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
                            <!-- Quote marks icon like in image -->
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
                            <!-- Bold pen/marker icon -->
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

            <!-- Delete Highlight Tooltip - Simple approach with backdrop -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
                <!-- Tooltip positioned above the backdrop click area -->
                <div class="delete-highlight-tooltip fixed z-9999" :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }">
                    <!-- Arrow pointing UP -->
                    <div class="tooltip-arrow-up"></div>
                    <!-- Tooltip Content -->
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                        @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <!-- Trash/Bin icon -->
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

            <!-- Note Hover Tooltip - Appears ABOVE noted text -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
                <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <!-- Tooltip Content -->
                    <div
                        class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <!-- Note Icon -->
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                        </span>
                        <!-- Note Text -->
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{
                            hoveredNoteText }}</span>
                        <!-- Delete Button -->
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
                    <!-- Arrow pointing DOWN -->
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
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
                        class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                        class="border border-gray-900 bg-white px-3 py-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                        Cancel
                    </button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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
