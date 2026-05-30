<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';


const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

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

// Get passage text for template (avoids hardcoded index)
const getPassageText = () => {
    return passageText;
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
});

// Text segments with their cumulative offsets in the full text
const passageText = `The development of a large inland water-transport network of rivers and canals in England in the 18th century was a direct result of the industrial revolution, which needed the support of an effective transport system. The use of inland water transport in England, where rivers are not large compared to elsewhere in Europe, may seem unexpected because no location in England is further than about 150 kilometres from the sea. Thus road transport would seem to have been more viable. However, the small size of rivers may have been an advantage. In the early 18th century, hydraulic technology was still in its infancy and the smaller scale of works necessary for canal and river navigations in England would certainly have made it easier for the engineers of the time to organise them and carry them out.

Another factor in favour of the development of water transport in England is that the roads of the period were insufficient in number and badly maintained, particularly in the steep hills and valleys of the north, and in general they were incapable of carrying the vast quantities of heavy materials needed to supply the growing demands of industry. Merchants who owned factories or who sold the goods produced needed cheap transport for raw materials and for finished products. It was the English merchants who paid for the construction of the first highly successful English canals, unlike many of the European canals, which were financed by the aristocracy.

By the second half of the 18th century, most of England's canal system had been completed. Civil engineering had improved, and a few early canals which had had long, winding routes were made more direct, thus reducing the overall length of the canal. An additional engineering task was the building of locks, which enabled canals to be built in areas where there were hills. Locks basically acted as steps or lifts to raise or lower a boat from one canal level to another. When a boat was in the central chamber and both sets of lock gates were shut no water could enter or escape through the gates as they were totally waterproof. However, water could enter or leave the lock through the sluice gates. Sluice gates were located below the lock gates and could be opened to allow water to flood into the lock to raise a boat or to flood out of the lock to lower a boat. When the water levels both inside and outside the lock were equal, the lock gates in front of the boat could be opened and it could continue on its journey.

However, there were factors associated with the development of late-18th-century canals which were to cause continual problems for the entire system. To keep down costs, many canals and locks in industrial arena were constructed to small dimensions; often locks were as narrow as 2.15 meters wide. A consequence of this was that boats also had to be narrow. That there would be rapid growth in the amount of produce transported on canals was not appreciated by canal builders of the time. In contrast, canals leading to coastal rivers and ports were wider and had larger boats and locks (around 4.3 meters wide) than the canals in industrial areas.This lack of uniformity of size resulted in a fragmented transport system.

Many of the new canals planned in the 1790s were looked upon just as a means of making money by speculators. These people were not involved with local industries and sometimes lived in other parts of the country. They expected that canals would continue to be successful irrespective of where they were built. However, only a few of them were profitable. Those that were, were usually wide canals in northern England, where they provided a vital link between industry and transport. Many canals of this period were built in agricultural regions, but canals depend on industry for their supply of cargo and so these canals barely repaid their construction costs. By the early 19th century, most were unable to compete with the new railways.To reduce competition, railway companies purchased many of the canals but did not maintain them properly, so several were abandoned

Inland water transport is no longer widely used in England, with most goods being carried by road. During the latter half of the 20th century, the GDI campaigned to increase national recognition of canals and the role they could play in recreation. It was a difficult task as many influential people in the community thought most canals were already virtually derelict and often parents tried to have them drained because they considered canals a danger to children. However, the increasing publicity which was being given to canals brought them to the attention of a wider and wider audience. Some people were interested in the historic aspects of canals. Because there had been so little investment in them, many canals had remained virtually unchanged from the days, 200 years previously, when they were built. Today, as a result of canal conservation and restoration, they are seen as places of both historical and environmental interest'`;

// Text segments with unique, non-overlapping offsets
// Each offset = previous offset + previous text length + gap (10-15 chars)
const textSegments = ref([
    // Part 1 Header (shown at top of component)
    { id: 'part1-title', text: "Part 1", offset: 0 },                                            // len=6, ends=6
    { id: 'part1-desc', text: "Read the text and answer questions 1–13.", offset: 20 },          // len=41, ends=61
    // Passage Panel headers
    { id: 'header-1', text: "READING PASSAGE 1", offset: 75 },                                   // len=17, ends=92
    { id: 'header-2', text: "You should spend about 20 minutes on Questions 1-13, which are based on Reading Passage 1 below.", offset: 105 }, // len=97, ends=202
    { id: 'header-3', text: "The English canal system", offset: 215 },                           // len=24, ends=239
    // Main passage starts at 260
    { id: 'passage', text: passageText, offset: 260 },                                           // ~4500 chars, ends ~4760
    // Questions section starts at 5000 (well after passage ends at ~4760)
    { id: 'q-section', text: "QUESTIONS", offset: 5320 },                                        // len=9, ends=5329
    { id: 'q-instruction', text: "Answer all questions based on Reading Passage 1", offset: 5345 }, // len=47, ends=5392
    // Questions 1–5
    { id: 'q1-5-title', text: "Questions 1-5", offset: 5440 },                                   // len=13, ends=5453
    { id: 'q1-5-inst1', text: "Do the following statements agree with the information given in Reading Passage 1?", offset: 5460 }, // len=82, ends=5542
    { id: 'q1-5-inst2', text: "In boxes 1-5 on your answer sheet, write:", offset: 5560 },       // len=41, ends=5601
    { id: 'true-label', text: "TRUE", offset: 5620 },                                            // len=4, ends=5624
    { id: 'true-desc', text: "if the statement agrees with the information", offset: 5640 },     // len=44, ends=5684
    { id: 'false-label', text: "FALSE", offset: 5700 },                                          // len=5, ends=5705
    { id: 'false-desc', text: "if the statement contradicts the information", offset: 5720 },    // len=44, ends=5764
    { id: 'ng-label', text: "NOT GIVEN", offset: 5785 },                                         // len=9, ends=5794
    { id: 'ng-desc', text: "if there is no information on this", offset: 5810 },                 // len=34, ends=5844
    { id: 'q1-num', text: "1.", offset: 5860 },                                                  // len=2, ends=5862
    { id: 'q1-text', text: "Larger rivers would have made the work of 18th-century hydraulic engineers more difficult.", offset: 5880 }, // len=90, ends=5970
    { id: 'q2-num', text: "2.", offset: 5990 },                                                  // len=2, ends=5992
    { id: 'q2-text', text: "Merchants refused to pay the high costs associated with road transport.", offset: 6010 }, // len=71, ends=6081
    { id: 'q3-num', text: "3.", offset: 6100 },                                                  // len=2, ends=6102
    { id: 'q3-text', text: "18th-century European and English canals were funded differently.", offset: 6120 }, // len=65, ends=6185
    { id: 'q4-num', text: "4.", offset: 6205 },                                                  // len=2, ends=6207
    { id: 'q4-text', text: "Canals were narrower in some places than in others.", offset: 6225 }, // len=51, ends=6276
    { id: 'q5-num', text: "5.", offset: 6295 },                                                  // len=2, ends=6297
    { id: 'q5-text', text: "The GDI tried to get support for canal closures.", offset: 6315 },   // len=48, ends=6363
    // Questions 6–9
    { id: 'q6-9-title', text: "Questions 6-9", offset: 6385 },                                   // len=13, ends=6398
    { id: 'q6-9-inst1', text: "Complete the notes below.", offset: 6415 },                       // len=25, ends=6440
    { id: 'q6-9-inst2', text: "Choose NO MORE THAN TWO WORDS from the passage for each answer.", offset: 6460 }, // len=64, ends=6524
    { id: 'q6-9-inst3', text: "Write your answers in boxes 6-9 on your answer sheet.", offset: 6545 }, // len=53, ends=6598
    { id: 'locks-title', text: "Canal locks", offset: 6620 },                                    // len=11, ends=6631
    { id: 'reasons-label', text: "Reasons for:", offset: 6650 },                                 // len=12, ends=6662
    { id: 'q6-text', text: "locks allowed canal boats had", offset: 6680 },                      // len=29, ends=6374
    { id: 'operate-label', text: "How locks operate:", offset: 6709 },                           // len=18, ends=6413
    { id: 'q7-text1', text: "a boat is shut in the", offset: 6727 },                             // len=21, ends=6456
    { id: 'q7-text2', text: "of the lock .", offset: 6748 },                                     // len=13, ends=6488
    { id: 'q8-text', text: "open or close to raise or lower the level of the water", offset: 6761 }, // len=54, ends=6564
    { id: 'q9-text1', text: "water levels have to be", offset: 6815 },                           // len=23, ends=6608
    { id: 'q9-text2', text: "before the boat can exit", offset: 6839 },                          // len=24, ends=6654
    // Questions 10–13
    { id: 'q10-13-title', text: "Questions 10-13", offset: 6863 },                               // len=15, ends=6695
    { id: 'q10-13-inst1', text: "Answer the questions below.", offset: 6890 },                   // len=27, ends=6742
    { id: 'q10-13-inst2', text: "Choose NO MORE THAN TWO WORDS from the passage for each answer.", offset: 6917 }, // len=64, ends=6829
    { id: 'q10-13-inst3', text: "Write your answers in boxes 10-13 on your answer sheet.", offset: 6971 }, // len=55, ends=6905
    { id: 'q10-num', text: "10.", offset: 7026 },                                                // len=3, ends=6928
    { id: 'q10-text', text: "Which group of people from outside the local community saw canals as a way of getting rich?", offset: 7029 }, // len=92, ends=7037
    { id: 'q11-num', text: "11.", offset: 7124 },                                                // len=3, ends=7063
    { id: 'q11-text', text: "In which geographical area did the canals make more money?", offset: 7127 }, // len=58, ends=7138
    { id: 'q12-num', text: "12.", offset: 7181 },                                                // len=3, ends=7163
    { id: 'q12-text', text: "What were the wrong kinds of areas to build profitable canals in?", offset: 7184 }, // len=65, ends=7245
    { id: 'q13-num', text: "13.", offset: 7265 },                                                // len=3, ends=7268
    { id: 'q13-text', text: "Who bought the canals that were not making a profit?", offset: 7285 }, // len=52, ends=7337
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

const getPanelFromNode = (node: Node): Element | null => {
const container = node.nodeType === Node.ELEMENT_NODE
? node as Element
: node.parentElement;
return container?.closest('.passage-panel, .answer-panel') || null;
};

const startPanel = getPanelFromNode(range.startContainer);
const endPanel = getPanelFromNode(range.endContainer);



// If endpoints are in different panels (or outside panels entirely), cancel
if (!startPanel || !endPanel || startPanel !== endPanel) {
showContextMenu.value = false;
window.getSelection()?.removeAllRanges();
return;
}



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

// Add this temporarily for testing


// Call this after applying a highlight to verify:
// debugHighlightOffsets();

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

const stopResize = () => {
    isResizing.value = false;
};

// Load saved answers when component mounts
onMounted(async () => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
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

// 🛠️ Expose debug helper to global window object for console testing

</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 1 Header -->
         <!-- 🔍 DEBUG BUTTON - Place this at the very top of your <template> -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 part-header-box  px-4 py-2">
    <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part1-title" v-html="getHighlightedSegmentById('part1-title')"></p>
    <p class="text-sm text-gray-700 select-text" data-segment-id="part1-desc" v-html="getHighlightedSegmentById('part1-desc')"></p>
</div>

<div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
    <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
        <!-- Reading Passage -->

        <div class="passage-panel whitespace-pre-line mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
            <div class="p-6">
                <h2 class="text-xl font-bold">
                    <span class="text-gray-900 select-text" data-segment-id="header-3" v-html="getHighlightedSegmentById('header-3')"></span>
                </h2>
            </div>

            <div class="space-y-6 p-6" :style="contentZoom">
                <div class="space-y-6 text-sm leading-relaxed select-text">
                    <div class="p-4">
                        <div class="text-justify leading-relaxed text-gray-700">
                            <span class="text-lg text-gray-700" data-segment-id="passage" v-html="getHighlightedSegmentById('passage')"></span>
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

                    <!-- Questions 1-5 (True/False/Not Given) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span class="text-gray-700 select-text" data-segment-id="q1-5-title" v-html="getHighlightedSegmentById('q1-5-title')"></span>
                                </h3>
                            </div>
                            <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                <span class="text-gray-700 select-text" data-segment-id="q1-5-inst1" v-html="getHighlightedSegmentById('q1-5-inst1')"></span><br />
                                <span class="text-gray-700 select-text" data-segment-id="q1-5-inst2" v-html="getHighlightedSegmentById('q1-5-inst2')"></span>
                            </p>
                        </div>

                        <div class="mb-6 border border-gray-900 p-4">
                            <div class="grid grid-cols-1 gap-2 text-sm">
                                <div class="flex items-center gap-4">
                                    <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                        <span class="text-gray-700 select-text" data-segment-id="true-label" v-html="getHighlightedSegmentById('true-label')"></span>
                                    </span>
                                    <span class="text-gray-700 select-text" data-segment-id="true-desc" v-html="getHighlightedSegmentById('true-desc')"></span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                        <span class="text-gray-700 select-text" data-segment-id="false-label" v-html="getHighlightedSegmentById('false-label')"></span>
                                    </span>
                                    <span class="text-gray-700 select-text" data-segment-id="false-desc" v-html="getHighlightedSegmentById('false-desc')"></span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                        <span class="text-gray-700 select-text" data-segment-id="ng-label" v-html="getHighlightedSegmentById('ng-label')"></span>
                                    </span>
                                    <span class="text-gray-700 select-text" data-segment-id="ng-desc" v-html="getHighlightedSegmentById('ng-desc')"></span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Question 1 -->
                            <div
                                id="question-1"
                                class="relative flex-col items-center gap-2 bg-white p-2"
                                @mouseenter="hoveredQuestion = 1"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q1-num" v-html="getHighlightedSegmentById('q1-num')"></span>

                                <span class="text-sm text-gray-700 select-text" data-segment-id="q1-text" v-html="getHighlightedSegmentById('q1-text')"></span>
                                <select v-model="answers.q1" class="border border-gray-900 ml-4 mr-3 bg-transparent px-2 py-0.5 text-sm focus:outline-none">
                                    <option value="">Select</option>
                                    <option value="TRUE">TRUE</option>
                                    <option value="FALSE">FALSE</option>
                                    <option value="NOT GIVEN">NOT GIVEN</option>
                                </select>

                                <button
                                    v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                    @click.stop="toggleFlag(1)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 2 -->
                            <div
                                id="question-2"
                                class="relative flex-col items-center gap-2 bg-white p-2"
                                @mouseenter="hoveredQuestion = 2"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q2-num" v-html="getHighlightedSegmentById('q2-num')"></span>

                                <span class="text-sm text-gray-700 select-text" data-segment-id="q2-text" v-html="getHighlightedSegmentById('q2-text')"></span>
                                <select v-model="answers.q2" class="border border-gray-900 ml-5 mr-3 bg-transparent px-2 py-0.5 text-sm focus:outline-none">
                                    <option value="">Select</option>
                                    <option value="TRUE">TRUE</option>
                                    <option value="FALSE">FALSE</option>
                                    <option value="NOT GIVEN">NOT GIVEN</option>
                                </select>
                                <button
                                    v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                    @click.stop="toggleFlag(2)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 3 -->
                            <div
                                id="question-3"
                                class="relative flex-col items-center gap-2 bg-white p-2"
                                @mouseenter="hoveredQuestion = 3"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q3-num" v-html="getHighlightedSegmentById('q3-num')"></span>

                                <span class="text-sm text-gray-700 select-text" data-segment-id="q3-text" v-html="getHighlightedSegmentById('q3-text')"></span>


                                <button
                                    v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                    @click.stop="toggleFlag(3)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <select v-model="answers.q3" class="border border-gray-900 mr-3 ml-6 bg-transparent px-2 py-0.5 text-sm focus:outline-none">
                                    <option value="">Select</option>
                                    <option value="TRUE">TRUE</option>
                                    <option value="FALSE">FALSE</option>
                                    <option value="NOT GIVEN">NOT GIVEN</option>
                                </select>
                            </div>

                            <!-- Question 4 -->
                            <div
                                id="question-4"
                                class="relative flex items-center gap-2 bg-white p-2"
                                @mouseenter="hoveredQuestion = 4"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q4-num" v-html="getHighlightedSegmentById('q4-num')"></span>

                                <span class="text-sm text-gray-700 select-text" data-segment-id="q4-text" v-html="getHighlightedSegmentById('q4-text')"></span>
                                <select v-model="answers.q4" class="border border-gray-900 bg-transparent px-2 py-0.5 text-sm focus:outline-none">
                                    <option value="">Select</option>
                                    <option value="TRUE">TRUE</option>
                                    <option value="FALSE">FALSE</option>
                                    <option value="NOT GIVEN">NOT GIVEN</option>
                                </select>

                                <button
                                    v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                    @click.stop="toggleFlag(4)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>

                            </div>

                            <!-- Question 5 -->
                            <div
                                id="question-5"
                                class="relative flex items-center gap-2 bg-white p-2"
                                @mouseenter="hoveredQuestion = 5"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q5-num" v-html="getHighlightedSegmentById('q5-num')"></span>

                                <span class="text-sm text-gray-700 select-text" data-segment-id="q5-text" v-html="getHighlightedSegmentById('q5-text')"></span>
                                <select v-model="answers.q5" class="border border-gray-900 bg-transparent px-2 py-0.5 text-sm focus:outline-none">
                                    <option value="">Select</option>
                                    <option value="TRUE">TRUE</option>
                                    <option value="FALSE">FALSE</option>
                                    <option value="NOT GIVEN">NOT GIVEN</option>
                                </select>

                                <button
                                    v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                    @click.stop="toggleFlag(5)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Questions 6-9 (Notes Completion) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span class="text-gray-700 select-text" data-segment-id="q6-9-title" v-html="getHighlightedSegmentById('q6-9-title')"></span>
                                </h3>
                            </div>
                            <p class="mb-2 text-sm text-gray-700">
                                <span class="text-gray-700 select-text" data-segment-id="q6-9-inst1" v-html="getHighlightedSegmentById('q6-9-inst1')"></span>
                            </p>
                            <p class="mb-4 text-sm text-gray-700">
                                <span class="text-gray-700 select-text" data-segment-id="q6-9-inst2" v-html="getHighlightedSegmentById('q6-9-inst2')"></span>
                            </p>
                        </div>

                        <div class="border border-gray-900 p-4 space-y-4 text-sm text-gray-700">
                            <h4 class="font-bold text-gray-900">
                                <span class="text-gray-900 select-text" data-segment-id="locks-title" v-html="getHighlightedSegmentById('locks-title')"></span>
                            </h4>

                            <div>
                                <p class="font-semibold mb-2">
                                    <span class="text-gray-700 select-text" data-segment-id="reasons-label" v-html="getHighlightedSegmentById('reasons-label')"></span>
                                </p>
                                <!-- Question 6 -->
                                <div
                                    id="question-6"
                                    class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                    @mouseenter="hoveredQuestion = 6"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-500">•</span>
                                    <span class="text-gray-700 select-text" data-segment-id="q6-text" v-html="getHighlightedSegmentById('q6-text')"></span>
                                    <input
                                        v-model="answers.q6"
                                        type="text"
                                        class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 140px"
                                        placeholder="6"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <button
                                        v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                        @click.stop="toggleFlag(6)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="font-semibold mb-2">
                                    <span class="text-gray-700 select-text" data-segment-id="operate-label" v-html="getHighlightedSegmentById('operate-label')"></span>
                                </p>

                                <!-- Question 7 -->
                                <div
                                    id="question-7"
                                    class="relative flex flex-wrap items-center gap-2 bg-white p-2 mb-2"
                                    @mouseenter="hoveredQuestion = 7"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-500">•</span>
                                    <span class="text-gray-700 select-text" data-segment-id="q7-text1" v-html="getHighlightedSegmentById('q7-text1')"></span>
                                    <input
                                        v-model="answers.q7"
                                        type="text"
                                        class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 140px"
                                        placeholder="7"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <span class="text-gray-700 select-text" data-segment-id="q7-text2" v-html="getHighlightedSegmentById('q7-text2')"></span>
                                    <button
                                        v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                        @click.stop="toggleFlag(7)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Question 8 (static bullet) -->

                                <div
                                    id="question-8"
                                    class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                    @mouseenter="hoveredQuestion = 8"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-500">•</span>

                                    <input
                                        v-model="answers.q8"
                                        type="text"
                                        class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 140px"
                                        placeholder="8"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <span class="text-gray-700 select-text" data-segment-id="q8-text" v-html="getHighlightedSegmentById('q8-text')"></span>
                                    <button
                                        v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                        @click.stop="toggleFlag(8)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Question 9 -->
                                <div
                                    id="question-9"
                                    class="relative flex flex-wrap items-center gap-2 bg-white p-2"
                                    @mouseenter="hoveredQuestion = 9"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-500">•</span>
                                    <span class="text-gray-700 select-text" data-segment-id="q9-text1" v-html="getHighlightedSegmentById('q9-text1')"></span>
                                    <input
                                        v-model="answers.q9"
                                        type="text"
                                        class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 140px"
                                        placeholder="9"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <span class="text-gray-700 select-text" data-segment-id="q9-text2" v-html="getHighlightedSegmentById('q9-text2')"></span>
                                    <button
                                        v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                        @click.stop="toggleFlag(9)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Questions 10-13 (Short Answer) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span class="text-gray-700 select-text" data-segment-id="q10-13-title" v-html="getHighlightedSegmentById('q10-13-title')"></span>
                                </h3>
                            </div>
                            <p class="mb-2 text-sm text-gray-700">
                                <span class="text-gray-700 select-text" data-segment-id="q10-13-inst1" v-html="getHighlightedSegmentById('q10-13-inst1')"></span>
                            </p>
                            <p class="mb-4 text-sm text-gray-700">
                                <span class="text-gray-700 select-text" data-segment-id="q10-13-inst2" v-html="getHighlightedSegmentById('q10-13-inst2')"></span>
                            </p>
                        </div>

                        <div class="space-y-6">
                            <!-- Question 10 -->
                            <div
                                id="question-10"
                                class="relative bg-white p-2"
                                @mouseenter="hoveredQuestion = 10"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex flex-wrap items-start gap-2">
                                    <span class="text-sm text-gray-700 flex-1 select-text" data-segment-id="q10-text" v-html="getHighlightedSegmentById('q10-text')"></span>
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        v-model="answers.q10"
                                        type="text"
                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 200px"
                                        placeholder="10"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                </div>
                                <button
                                    v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                    @click.stop="toggleFlag(10)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 11 -->
                            <div
                                id="question-11"
                                class="relative bg-white p-2"
                                @mouseenter="hoveredQuestion = 11"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex flex-wrap items-start gap-2">
                                    <span class="text-sm text-gray-700 flex-1 select-text" data-segment-id="q11-text" v-html="getHighlightedSegmentById('q11-text')"></span>
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        v-model="answers.q11"
                                        type="text"
                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 200px"
                                        placeholder="11"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                </div>
                                <button
                                    v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                    @click.stop="toggleFlag(11)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 12 -->
                            <div
                                id="question-12"
                                class="relative bg-white p-2"
                                @mouseenter="hoveredQuestion = 12"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex flex-wrap items-start gap-2">
                                    <span class="text-sm text-gray-700 flex-1 select-text" data-segment-id="q12-text" v-html="getHighlightedSegmentById('q12-text')"></span>
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        v-model="answers.q12"
                                        type="text"
                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 200px"
                                        placeholder="12"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                </div>
                                <button
                                    v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                    @click.stop="toggleFlag(12)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 13 -->
                            <div
                                id="question-13"
                                class="relative bg-white p-2"
                                @mouseenter="hoveredQuestion = 13"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex flex-wrap items-start gap-2">
                                    <span class="text-sm text-gray-700 flex-1 select-text" data-segment-id="q13-text" v-html="getHighlightedSegmentById('q13-text')"></span>
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        v-model="answers.q13"
                                        type="text"
                                        class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                        style="width: 200px"
                                        placeholder="13"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                </div>
                                <button
                                    v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                    @click.stop="toggleFlag(13)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
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

    <!-- Context Menu for Highlighting -->
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
                <!-- Tooltip Content -->
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <!-- Note Button -->
                    <button
                        @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note"
                    >
                        <!-- Quote marks icon like in image -->
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"
                            />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <!-- Highlight Button -->
                    <button
                        @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight"
                    >
                        <!-- Bold pen/marker icon -->
                        <svg
                            class="h-5 w-5 text-gray-900"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                        >
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
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
            >
                <!-- Arrow pointing UP -->
                <div class="tooltip-arrow-up"></div>
                <!-- Tooltip Content -->
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button
                        @click="confirmDeleteHighlight"
                        type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                    >
                        <!-- Trash/Bin icon -->
                        <svg
                            class="h-5 w-5 text-gray-700"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
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

        <!-- Note Hover Tooltip - Appears ABOVE noted text -->
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
                <!-- Tooltip Content -->
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <!-- Note Icon -->
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            ></path>
                        </svg>
                    </span>
                    <!-- Note Text -->
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <!-- Delete Button -->
                    <button
                        @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                        title="Delete note"
                    >
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            ></path>
                        </svg>
                    </button>
                </div>
                <!-- Arrow pointing DOWN -->
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
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
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
                <button
                    @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                >
                    Cancel
                </button>
                <button @click="saveNote" class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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

/* In your styles */
.passage-panel, .answer-panel {
    position: relative;
    isolation: isolate; /* Creates new stacking context */
}

.passage-panel mark, .answer-panel mark {
    pointer-events: auto; /* Ensure marks are clickable within their panel */
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
