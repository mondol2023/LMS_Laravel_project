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

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part2-panel-width';
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

// Drag and drop state for Q23-26
const dragItem = ref<string | null>(null);
const dragOver = ref<number | null>(null);

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

// Word bank options for Q23-26
const wordBankOptions = [
    { id: 'A', label: 'A - tariffs' },
    { id: 'B', label: 'B - components' },
    { id: 'C', label: 'C - container ships' },
    { id: 'D', label: 'D - output' },
    { id: 'E', label: 'E - employees' },
    { id: 'F', label: 'F - insurance costs' },
    { id: 'G', label: 'G - trade' },
    { id: 'H', label: 'H - freight' },
    { id: 'I', label: 'I - fares' },
    { id: 'J', label: 'J - software' },
    { id: 'K', label: 'K - international standards' },
];

// Computed: which options are already used in Q23-26
const usedOptions = computed(() => {
    return new Set([answers.value.q23, answers.value.q24, answers.value.q25, answers.value.q26].filter(Boolean));
});

// Available options (not yet placed)
const availableOptions = computed(() => {
    return wordBankOptions.filter(opt => !usedOptions.value.has(opt.id));
});

// Drop zone labels
const dropZones = [
    { key: 'q23', num: 23 },
    { key: 'q24', num: 24 },
    { key: 'q25', num: 25 },
    { key: 'q26', num: 26 },
];

// Drag handlers
const onDragStart = (optionId: string) => {
    dragItem.value = optionId;
};

const onDragOver = (e: DragEvent, zoneNum: number) => {
    e.preventDefault();
    dragOver.value = zoneNum;
};

const onDragLeave = () => {
    dragOver.value = null;
};

const onDrop = (e: DragEvent, key: string) => {
    e.preventDefault();
    dragOver.value = null;
    if (!dragItem.value) return;
    // If this zone already has a value, put it back to available (swap)
    const currentVal = (answers.value as any)[key];
    if (currentVal) {
        // Just overwrite - the old value returns to available automatically via computed
    }
    (answers.value as any)[key] = dragItem.value;
    dragItem.value = null;
};

const onDragFromZone = (key: string) => {
    dragItem.value = (answers.value as any)[key];
    (answers.value as any)[key] = '';
};

const removeFromZone = (key: string) => {
    (answers.value as any)[key] = '';
};

const getOptionLabel = (id: string) => {
    const opt = wordBankOptions.find(o => o.id === id);
    return opt ? opt.label : '';
};

const passageText = ref(`<b>A</b>  International trade is growing at a startling pace. While the global economy has been expanding at a bit over 3% a year, the volume of trade has been rising at a compound annual rate of about twice that. Foreign products, from meat to machinery, play a more important role in almost every economy in the world, and foreign markets now tempt businesses that never much worried about sales beyond their nation's borders.

<b>B</b>  What lies behind this explosion in international commerce? The general worldwide decline in trade barriers, such as customs duties and import quotas, is surely one explanation. The economic opening of countries that have traditionally been minor players is another. But one force behind the import-export boom has passed all but unnoticed: the rapidly falling cost of getting goods to market. Theoretically, in the world of trade, shipping costs do not matter. Goods, once they have been made, are assumed to move instantly and at no cost from place to place. The real world, however, is full of frictions. Cheap labour may make Chinese clothing competitive in America, but if delays in shipment tie up working capital and cause winter coats to arrive in spring, trade may lose its advantages.

<b>C</b>  At the turn of the 20th century, agriculture and manufacturing were the two most important sectors almost everywhere, accounting for about 70% of total output in Germany, Italy and France, and 40-50% in America, Britain and Japan. International commerce was therefore dominated by raw materials, such as wheat, wood and iron ore, or processed commodities, such as meat and steel. But these sorts of products are heavy and bulky and the cost of transporting them relatively high.

<b>D</b>  Countries still trade disproportionately with their geographic neighbours. Over time, however, world output has shifted into goods whose worth is unrelated to their size and weight. Today, it is finished manufactured products that dominate the flow of trade, and, thanks to technological advances such as lightweight components, manufactured goods themselves have tended to become lighter and less bulky. As a result, less transportation is required for every dollar's worth of imports or exports.

<b>E</b>  To see how this influences trade, consider the business of making disk drives for computers. Most of the world's disk-drive manufacturing is concentrated in South-east Asia. This is possible only because disk drives, while valuable, are small and light and so cost little to ship. Computer manufacturers in Japan or Texas will not face hugely bigger freight bills if they import drives from Singapore rather than purchasing them on the domestic market. Distance, therefore, poses no obstacle to the globalisation of the disk-drive industry.

<b>F</b>  This is even more true of the fast-growing information industries. Films and compact discs cost little to transport, even by aeroplane. Computer software can be 'exported' without ever loading it onto a ship, simply by transmitting it over telephone lines from one country to another, so freight rates and cargo-handling schedules become insignificant factors in deciding where to make the product. Businesses can locate based on other considerations, such as the availability of labour, while worrying less about the cost of delivering their output.

<b>G</b>  In many countries deregulation has helped to drive the process along. But, behind the scenes, a series of technological innovations known broadly as containerisation and inter-modal transportation has led to swift productivity improvements in cargo-handling. Forty years ago, the process of exporting or importing involved a great many stages of handling, which risked portions of the shipment being damaged or stolen along the way. The invention of the container crane made it possible to load and unload containers without capsizing the ship and the adoption of standard container sizes allowed almost any box to be transported on any ship. By 1967, dual-purpose ships, carrying loose cargo in the hold and containers on the deck, were giving way to all-container vessels that moved thousands of boxes at a time.

<b>H</b>  The shipping container transformed ocean shipping into a highly efficient, intensely competitive business. But getting the cargo to and from the dock was a different story. National governments, by and large, kept a much firmer hand on truck and railroad tariffs than on charges for ocean freight. This started changing, however, in the mid-1970s, when America began to deregulate its transportation industry. First airlines, then road hauliers and railways, were freed from restrictions on what they could carry, where they could haul it and at what price they could charge. Big productivity gains resulted. Between 1985 and 1996, for example, America's freight railways dramatically reduced their employment, trackage, and their fleets of locomotives - while increasing the amount of cargo they hauled. Europe's railways have also shown marked, albeit smaller, productivity improvements.

<b>I</b>  In America the period of huge productivity gains in transportation may be almost over, but in most countries, the process still has far to go. State ownership of railways and airlines, regulation of freight rates and toleration of anti-competitive practices, such as cargo-handling monopolies, all keep the cost of shipping unnecessarily high and deter international trade. Bringing these barriers down would help the world's economies grow even closer.`);

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts = [
    // Passage Panel
    'Part 2',                                                           // 0
    'Read the text and answer questions 14–26.',                        // 1
    'Delivering The Goods',                                             // 2
    'The vast expansion in international trade owes much to a revolution in the business of moving freight', // 3
    passageText.value,                                                  // 4

    // Questions Section
    'QUESTIONS',                                                        // 5
    'Answer all questions based on Reading Passage 2',                  // 6
    'Questions 14-17',                                                  // 7
    'Reading Passage 2 has nine sections, A-I.',                        // 8
    'Which paragraph contains the following information?',              // 9
    'Write the correct letter A-I in boxes 14-17 on your answer sheet.', // 10

    // Q14-17 descriptions
    'a suggestion for improving trade in the future',                   // 11
    'the effects of the introduction of electronic delivery',           // 12
    'the similar cost involved in transporting a product from abroad or from a local supplier', // 13
    'the weakening relationship between the value of goods and the cost of their delivery', // 14

    // Q18-22
    'Questions 18-22',                                                  // 15
    'Do the following statements agree with the information given in Reading Passage 2?', // 16
    'In boxes 18-22 on your answer sheet, write -',                     // 17
    'TRUE if the statement agrees with the information',                // 18
    'FALSE if the statement contradicts the information',               // 19
    'NOT GIVEN if there is no information on this',                     // 20

    // Q18-22 statements
    'International trade is increasing at a greater rate than the world economy.', // 21
    'Cheap labour guarantees effective trade conditions.',               // 22
    'Japan imports more meat and steel than France.',                    // 23
    'Most countries continue to prefer to trade with nearby nations.',  // 24
    'Small computer components are manufactured in Germany.',           // 25

    // Q23-26
    'Questions 23-26',                                                  // 26
    'Complete the summary using the list of words, A-K, below.',        // 27
    'Write the correct letter, A-K, in boxes 23-26 on your answer sheet.', // 28
    'THE TRANSPORT REVOLUTION',                                         // 29
    'Modern cargo-handling methods have had a significant effect on',   // 30
    'as the business of moving freight around the world becomes increasingly streamlined. Manufacturers of computers, for instance, are able to import', // 31
    'from overseas, rather than having to rely on a local supplier. The introduction of', // 32
    'has meant that bulk cargo can be safely and efficiently moved over long distances. While international shipping is now efficient, there is still a need for governments to reduce', // 33
    'in order to free up the domestic cargo sector.',                   // 34

    // Question number labels
    '<b>14.</b>',  // 35
    '<b>15.</b>',  // 36
    '<b>16.</b>',  // 37
    '<b>17.</b>',  // 38
    '<b>18.</b>',  // 39
    '<b>19.</b>',  // 40
    '<b>20.</b>',  // 41
    '<b>21.</b>',  // 42
    '<b>22.</b>',  // 43
];

let currentOffset = 0;
const newTextSegments = allStaticTexts.map((text, index) => {
    const segment = {
        id: `segment_${index}`,
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
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') {
                htmlIndex++;
            }
            htmlIndex++;
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
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segmentText;
    }

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

    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = segmentText;

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

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

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

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

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

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(idToDelete);
    }
};

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

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

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

    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
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
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
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
                            <span class="text-sm font-normal italic" data-segment-id="segment_3" v-html="getHighlightedSegment(allStaticTexts[3])"></span>
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

                            <!-- Questions 14-17 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_7"
                                                v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_8"
                                            v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                                    </p>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_9"
                                            v-html="getHighlightedSegment(allStaticTexts[9])"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_10"
                                            v-html="getHighlightedSegment(allStaticTexts[10])"></span>
                                    </p>
                                </div>
                                <div class="space-y-4">
                                    <!-- Question 14 -->
                                    <div id="question-14" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_35"
                                            v-html="getHighlightedSegment(allStaticTexts[35])"></span>
                                        <select v-model="answers.q14"
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
                                            <option value="I">I</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_11"
                                                v-html="getHighlightedSegment(allStaticTexts[11])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 15 -->
                                    <div id="question-15" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_36"
                                            v-html="getHighlightedSegment(allStaticTexts[36])"></span>
                                        <select v-model="answers.q15"
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
                                            <option value="I">I</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_12"
                                                v-html="getHighlightedSegment(allStaticTexts[12])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 16 -->
                                    <div id="question-16" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_37"
                                            v-html="getHighlightedSegment(allStaticTexts[37])"></span>
                                        <select v-model="answers.q16"
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
                                            <option value="I">I</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_13"
                                                v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 17 -->
                                    <div id="question-17" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_38"
                                            v-html="getHighlightedSegment(allStaticTexts[38])"></span>
                                        <select v-model="answers.q17"
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
                                            <option value="I">I</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_14"
                                                v-html="getHighlightedSegment(allStaticTexts[14])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 18-22 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="segment_15"
                                                v-html="getHighlightedSegment(allStaticTexts[15])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_16"
                                            v-html="getHighlightedSegment(allStaticTexts[16])"></span>
                                    </p>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_17"
                                            v-html="getHighlightedSegment(allStaticTexts[17])"></span>
                                    </p>
                                    <p class="text-base text-gray-700 sm:text-base pl-4">
                                        <span class="font-semibold" data-segment-id="segment_18"
                                            v-html="getHighlightedSegment(allStaticTexts[18])"></span>
                                    </p>
                                    <p class="text-base text-gray-700 sm:text-base pl-4">
                                        <span class="font-semibold" data-segment-id="segment_19"
                                            v-html="getHighlightedSegment(allStaticTexts[19])"></span>
                                    </p>
                                    <p class="mb-4 text-base text-gray-700 sm:text-base pl-4">
                                        <span class="font-semibold" data-segment-id="segment_20"
                                            v-html="getHighlightedSegment(allStaticTexts[20])"></span>
                                    </p>
                                </div>
                                <div class="space-y-4">
                                    <!-- Question 18 -->
                                    <div id="question-18" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="text-gray-900 shrink-0 font-medium" data-segment-id="segment_39"
                                                v-html="getHighlightedSegment(allStaticTexts[39])"></span>
                                            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                                <span class="select-text" data-segment-id="segment_21"
                                                    v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                                            </p>
                                        </div>
                                        <div class="mt-2 ml-8 flex items-center gap-4">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q18" value="TRUE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q18" value="FALSE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q18" value="NOT GIVEN"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="toggleFlag(18)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 19 -->
                                    <div id="question-19" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="text-gray-900 shrink-0 font-medium" data-segment-id="segment_40"
                                                v-html="getHighlightedSegment(allStaticTexts[40])"></span>
                                            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                                <span class="select-text" data-segment-id="segment_22"
                                                    v-html="getHighlightedSegment(allStaticTexts[22])"></span>
                                            </p>
                                        </div>
                                        <div class="mt-2 ml-8 flex items-center gap-4">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q19" value="TRUE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q19" value="FALSE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q19" value="NOT GIVEN"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                            @click.stop="toggleFlag(19)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 20 -->
                                    <div id="question-20" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="text-gray-900 shrink-0 font-medium" data-segment-id="segment_41"
                                                v-html="getHighlightedSegment(allStaticTexts[41])"></span>
                                            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                                <span class="select-text" data-segment-id="segment_23"
                                                    v-html="getHighlightedSegment(allStaticTexts[23])"></span>
                                            </p>
                                        </div>
                                        <div class="mt-2 ml-8 flex items-center gap-4">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q20" value="TRUE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q20" value="FALSE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q20" value="NOT GIVEN"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                            @click.stop="toggleFlag(20)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 21 -->
                                    <div id="question-21" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="text-gray-900 shrink-0 font-medium" data-segment-id="segment_42"
                                                v-html="getHighlightedSegment(allStaticTexts[42])"></span>
                                            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                                <span class="select-text" data-segment-id="segment_24"
                                                    v-html="getHighlightedSegment(allStaticTexts[24])"></span>
                                            </p>
                                        </div>
                                        <div class="mt-2 ml-8 flex items-center gap-4">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q21" value="TRUE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q21" value="FALSE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q21" value="NOT GIVEN"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                            @click.stop="toggleFlag(21)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 22 -->
                                    <div id="question-22" class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="text-gray-900 shrink-0 font-medium" data-segment-id="segment_43"
                                                v-html="getHighlightedSegment(allStaticTexts[43])"></span>
                                            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                                <span class="select-text" data-segment-id="segment_25"
                                                    v-html="getHighlightedSegment(allStaticTexts[25])"></span>
                                            </p>
                                        </div>
                                        <div class="mt-2 ml-8 flex items-center gap-4">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q22" value="TRUE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">TRUE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q22" value="FALSE"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">FALSE</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" v-model="answers.q22" value="NOT GIVEN"
                                                    class="w-4 h-4 border-gray-900 accent-gray-900" />
                                                <span class="text-sm font-medium text-gray-700">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 23-26 (Inline Text Input) -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-base font-bold text-gray-900">
                <span data-segment-id="segment_26"
                    v-html="getHighlightedSegment(allStaticTexts[26])"></span>
            </h3>
        </div>
        <p class="mb-1 text-base leading-relaxed text-gray-700 sm:text-base">
            <span data-segment-id="segment_27"
                v-html="getHighlightedSegment(allStaticTexts[27])"></span>
        </p>
        <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
            <span data-segment-id="segment_28"
                v-html="getHighlightedSegment(allStaticTexts[28])"></span>
        </p>
    </div>

    <!-- Summary Text with Inline Inputs -->
    <div class="space-y-4 text-base leading-relaxed text-gray-800 sm:text-base border border-gray-200 bg-gray-50 rounded p-4">
        <p class="text-center font-bold select-text">
            <span data-segment-id="segment_29" v-html="getHighlightedSegment(allStaticTexts[29])"></span>
        </p>

        <p class="leading-loose">
            <span class="select-text" data-segment-id="segment_30"
                v-html="getHighlightedSegment(allStaticTexts[30])"></span>

            <!-- Q23 inline -->
            <span class="group inline-flex items-center gap-1 align-middle">
                <input
                    id="question-23"
                    v-model="answers.q23"
                    type="text"
                    placeholder="23"
                    class="mx-2 w-32 border border-gray-900 px-2 py-0.5 text-center text-base font-medium  transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                    spellcheck="false"
                    autocomplete="off"
                />
                <button
                    @click.stop="toggleFlag(23)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                    :class="isQuestionFlagged(23) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                    :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </span>

            <span class="select-text" data-segment-id="segment_31"
                v-html="getHighlightedSegment(allStaticTexts[31])"></span>

            <!-- Q24 inline -->
            <span class="group inline-flex items-center gap-1 align-middle">
                <input
                    id="question-24"
                    v-model="answers.q24"
                    type="text"
                    placeholder="24"
                    class="mx-2 w-32 border border-gray-900 px-2 py-0.5 text-center text-base font-medium  transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                    spellcheck="false"
                    autocomplete="off"
                />
                <button
                    @click.stop="toggleFlag(24)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                    :class="isQuestionFlagged(24) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                    :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </span>

            <span class="select-text" data-segment-id="segment_32"
                v-html="getHighlightedSegment(allStaticTexts[32])"></span>

            <!-- Q25 inline -->
            <span class="group inline-flex items-center gap-1 align-middle">
                <input
                    id="question-25"
                    v-model="answers.q25"
                    type="text"
                    placeholder="25"
                    class="mx-2 w-32 border border-gray-900 px-2 py-0.5 text-center text-base font-medium  transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                    spellcheck="false"
                    autocomplete="off"
                />
                <button
                    @click.stop="toggleFlag(25)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                    :class="isQuestionFlagged(25) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </span>

            <span class="select-text" data-segment-id="segment_33"
                v-html="getHighlightedSegment(allStaticTexts[33])"></span>

            <!-- Q26 inline -->
            <span class="group inline-flex items-center gap-1 align-middle">
                <input
                    id="question-26"
                    v-model="answers.q26"
                    type="text"
                    placeholder="26"
                    class="mx-2 w-32 border border-gray-900 px-2 py-0.5 text-center text-base font-medium  transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                    spellcheck="false"
                    autocomplete="off"
                />
                <button
                    @click.stop="toggleFlag(26)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                    :class="isQuestionFlagged(26) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </span>

            <span class="select-text" data-segment-id="segment_34"
                v-html="getHighlightedSegment(allStaticTexts[34])"></span>
        </p>
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
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                            title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
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
                    <div class="tooltip-arrow"></div>
                </div>
            </div>

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

/* Drag chip */
.drag-chip {
    border-radius: 2px;
    font-size: 0.8rem;
    line-height: 1.5;
    user-select: none;
}

/* Inline drop zone */
.drop-zone-inline {
    display: inline-flex;
    align-items: center;
    min-width: 3.5rem;
    vertical-align: baseline;
    border: 1.5px dashed #9ca3af;
    border-radius: 2px;
    background: #f9fafb;
    padding: 0 4px;
    transition: border-color 0.15s, background 0.15s;
}

.drop-zone-inline--over {
    border-color: #374151;
    background: #f3f4f6;
}

.drop-zone-placeholder {
    font-size: 0.75rem;
    color: #9ca3af;
    font-weight: 600;
    padding: 1px 4px;
    min-width: 1.8rem;
    text-align: center;
}

.drop-zone-filled {
    display: inline-flex;
    align-items: center;
    font-size: 0.8rem;
    font-weight: 600;
    color: #1f2937;
    background: #e5e7eb;
    border-radius: 2px;
    padding: 1px 5px;
    gap: 2px;
    white-space: nowrap;
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

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>