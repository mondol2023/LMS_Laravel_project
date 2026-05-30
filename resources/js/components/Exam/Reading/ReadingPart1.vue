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

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

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
const passageText = `<b>Roads</b><br/>
Although there were highway links in Mesopotamia from as early as 3500 bc, the Romans were probably the first road-builders with fixed engineering standards. At the peak of the Roman Empire in the first century ad, Rome had road connections totalling about 85,000 kilometres.
<br/><br/>
Roman roads were constructed with a deep stone surface for stability and load-bearing. They had straight alignments and therefore were often hilly. The Roman roads remained the main arteries of European transport for many centuries, and even today many roads follow the Roman routes. New roads were generally of inferior quality, and the achievements of Roman builders were largely unsurpassed until the resurgence of road-building in the eighteenth century.
<br/><br/>
With horse-drawn coaches in mind, eighteenth-century engineers preferred to curve their roads to avoid hills. The road surface was regarded as merely a face to absorb wear, the load-bearing strength being obtained from a properly prepared and well-drained foundation. Immediately above this, the Scottish engineer John McAdam (1756-1836) typically laid crushed stone, to which stone dust mixed with water was added, and which was compacted to a thickness of just five centimetres, and then rolled. McAdam's surface layer - hot tar onto which a layer of stone chips was laid - became known as 'tarmacadam', or tarmac. Roads of this kind were known as flexible pavements.
<br/><br/>
By the early nineteenth century - the start of the railway age - men such as John McAdam and Thomas Telford had created a British road network totalling some 200,000 km, of which about one sixth was privately owned toll roads called turnpikes. In the first half of the nineteenth century, many roads in the US were built to the new standards, of which the National Pike from West Virginia to Illinois was perhaps the most notable.
<br/><br/>
In the twentieth century, the ever-increasing use of motor vehicles threatened to break up roads built to nineteenth-century standards, so new techniques had to be developed.
<br/><br/>
On routes with heavy traffic, flexible pavements were replaced by rigid pavements, in which the top layer was concrete, 15 to 30 centimetres thick, laid on a prepared bed. Nowadays steel bars are laid within the concrete. This not only restrains shrinkage during setting, but also reduces expansion in warm weather. As a result, it is possible to lay long slabs without danger of cracking.
<br/><br/>
The demands of heavy traffic led to the concept of high-speed, long-distance roads, with access - or slip-lanes - spaced widely apart. The US Bronx River Parkway of 1925 was followed by several variants - Germany's autobahns and the Pan American Highway. Such roads - especially the intercity autobahns with their separate multi-lane carriageways for each direction - were the predecessors of today's motorways.
<br/><br/>
<b>Bridges</b><br/>
The development by the Romans of the arched bridge marked the beginning of scientific bridge-building; hitherto, bridges had generally been crossings in the form of felled trees or flat stone blocks. Absorbing the load by compression, arched bridges are very strong. Most were built of stone, but brick and timber were also used. A fine early example is at Alcantara in Spain, built of granite by the Romans in AD 105 to span the River Tagus. In modern times, metal and concrete arched bridges have been constructed. The first significant metal bridge, built of cast iron in 1779, still stands at Ironbridge in England.
<br/><br/>
Steel, with its superior strength-to-weight ratio, soon replaced iron in metal bridge-work. In the railway age, the truss (or girder) bridge became popular. Built of wood or metal, the truss beam consists of upper and lower horizontal booms joined by vertical or inclined members.
<br/><br/>
The suspension bridge has a deck supported by suspenders that drop from one or more overhead cables. It requires strong anchorage at each end to resist the inward tension of the cables, and the deck is strengthened to control distortion by moving loads or high winds. Such bridges are nevertheless light, and therefore the most suitable for very long spans. The Clifton Suspension Bridge in the UK, designed by Isambard Kingdom Brunei (1806—59) to span the Avon Gorge in England, is famous both for its beautiful setting and for its elegant design. The 1998 Akashi Kaikyo Bridge in Japan has a span of 1,991 metres, which is the longest to date.
<br/><br/>
Cantilever bridges, such as the 1889 Forth Rail Bridge in Scotland, exploit the potential of steel construction to produce a wide clearwater space. The spans have a central supporting pier and meet midstream. The downward thrust, where the spans meet, is countered by firm anchorage of the spans at their other ends. Although the suspension bridge can span a wider gap, the cantilever is relatively stable, and this was important for nineteenth-century railway builders. The world's longest cantilever span - 549 metres - is that of the Quebec rail bridge in Canada, constructed in 1918.`;

const textSegments = ref([
    // Part 1 header text segments (negative offsets to come before passage)
    { id: 'part1-title', text: 'Part 1', offset: -100 },
    { id: 'part1-desc', text: 'Read the text and answer questions 1–13.', offset: -93 },
    { id: 'part1-passage-title', text: 'The construction of roads and bridges', offset: -51 },
    // Single passage text segment (plain text length is ~4971 chars)
    { id: 'passage', text: passageText, offset: 0 },
    // Questions 1-3 text segments (offsets start after passage plain text length 4971)
    { id: 'q1-3-title', text: 'Questions 1-3', offset: 5000 },
    { id: 'q1-3-inst1', text: 'Label the diagram below.', offset: 5014 },
    { id: 'q1-3-inst2', text: 'Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the passage for each answer.', offset: 5039 },
    { id: 'q1-3-tarmac', text: 'Tarmacadam', offset: 5120 },
    { id: 'q1-3-chips', text: 'and stone chips.', offset: 5131 },
    { id: 'q1-3-middle', text: 'Middle layer', offset: 5148 },
    { id: 'q1-3-deep', text: 'deep.', offset: 5161 },
    { id: 'q1-3-crushed', text: 'Crushed stone dust and', offset: 5167 },
    // Questions 4-7 text segments
    { id: 'q4-7-title', text: 'Questions 4-7', offset: 5190 },
    { id: 'q4-7-inst-choose', text: 'Choose', offset: 5204 },
    { id: 'q4-7-inst-true', text: 'TRUE', offset: 5211 },
    { id: 'q4-7-inst-true-desc', text: 'if the statement agrees with the information given in the text, choose', offset: 5216 },
    { id: 'q4-7-inst-false', text: 'FALSE', offset: 5287 },
    { id: 'q4-7-inst-false-desc', text: 'if the statement contradicts the information, or choose', offset: 5293 },
    { id: 'q4-7-inst-notgiven', text: 'NOT GIVEN', offset: 5349 },
    { id: 'q4-7-inst-notgiven-desc', text: 'if there is no information on this.', offset: 5359 },
    { id: 'q4-num', text: '4.', offset: 5395 },
    { id: 'q4-text', text: 'Road construction improved continuously between the first and eighteenth centuries.', offset: 5398 },
    { id: 'q5-num', text: '5.', offset: 5482 },
    { id: 'q5-text', text: 'In Britain, during the nineteenth century, only the very rich could afford to use toll roads.', offset: 5485 },
    { id: 'q6-num', text: '6.', offset: 5579 },
    { id: 'q6-text', text: 'Nineteenth-century road surfaces were inadequate for heavy motor traffic.', offset: 5582 },
    { id: 'q7-num', text: '7.', offset: 5656 },
    { id: 'q7-text', text: 'Traffic speeds on long-distance highways were unregulated in the early part of the twentieth century.', offset: 5659 },
    // Questions 8-13 text segments
    { id: 'q8-13-title', text: 'Questions 8-13', offset: 5761 },
    { id: 'q8-13-inst1', text: 'Complete the table below.', offset: 5776 },
    { id: 'q8-13-inst2', text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 5802 },
    { id: 'table-type', text: 'Type of bridge', offset: 5857 },
    { id: 'table-features', text: 'Features', offset: 5872 },
    { id: 'table-examples', text: 'Example(s)', offset: 5881 },
    { id: 'arched', text: 'Arched bridge', offset: 5892 },
    { id: 'arched-intro', text: 'Introduced by the', offset: 5906 },
    { id: 'arched-strong', text: 'Very strong', offset: 5924 },
    { id: 'arched-made', text: 'Usually made of', offset: 5936 },
    { id: 'arched-examples', text: 'Alcantara, Spain Ironbridge, UK', offset: 5952 },
    { id: 'truss', text: 'Truss bridge', offset: 5984 },
    { id: 'truss-made', text: 'Made of wood or metal', offset: 5997 },
    { id: 'truss-popular', text: 'Popular for railways', offset: 6019 },
    { id: 'suspension', text: 'Suspension bridge', offset: 6040 },
    { id: 'suspension-deck', text: 'Has a suspended deck', offset: 6058 },
    { id: 'suspension-strong', text: 'Strong but', offset: 6079 },
    { id: 'suspension-clifton', text: 'Clifton, UK', offset: 6090 },
    { id: 'suspension-akashi', text: 'Akashi Kaikyo, Japan (currently the', offset: 6102 },
    { id: 'suspension-span', text: 'span)', offset: 6138 },
    { id: 'cantilever', text: 'Cantilever bridge', offset: 6144 },
    { id: 'cantilever-made', text: 'Made of', offset: 6162 },
    { id: 'cantilever-more', text: 'More', offset: 6170 },
    { id: 'cantilever-than', text: 'than the suspension bridge', offset: 6175 },
    { id: 'cantilever-quebec', text: 'Quebec, Canada', offset: 6202 },
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
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && contentTextRef.value) {
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
                        // For passage text, use the passageText segment directly (index 2)
                        segment = textSegments.value[3];
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
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegment('Part 1')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegment('Read the text and answer questions 1–13.')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl font-bold">
                            <span
                                class="text-gray-900 select-text"
                                v-html="getHighlightedSegment('The construction of roads and bridges')"
                            ></span>
                        </h2>
                    </div>

                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span
                                        class="text-lg text-gray-700"
                                        data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[3].text)"
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
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
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
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">
                            <!-- Questions 1-3 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" v-html="getHighlightedSegment('Questions 1-3')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegment('Label the diagram below.')"></span><br />
                                        <span
                                            class="text-gray-700"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the passage for each answer.',
                                                )
                                            "
                                        ></span>
                                    </p>
                                </div>

                                <div class="mb-4 border border-gray-300 p-2">
                                    <img src="/images/question.jpg" alt="Road construction diagram" class="mx-auto mb-2 max-w-full" />
                                </div>

                                <div class="space-y-4">
                                    <div
                                        id="question-1"
                                        class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 1"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="text-gray-700" v-html="getHighlightedSegment('Tarmacadam')"></span>
                                        <input
                                            v-model="answers.q1"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="1"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-700" v-html="getHighlightedSegment('and stone chips.')"></span>
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
                                    <div
                                        id="question-2"
                                        class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 2"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="text-gray-700" v-html="getHighlightedSegment('Middle layer')"></span>
                                        <input
                                            v-model="answers.q2"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="2"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-700" v-html="getHighlightedSegment('deep.')"></span>
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
                                    <div
                                        id="question-3"
                                        class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 3"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="text-gray-700" v-html="getHighlightedSegment('Crushed stone dust and')"></span>
                                        <input
                                            v-model="answers.q3"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="3"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
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
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 4-7 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q4-7-title" v-html="getHighlightedSegmentById('q4-7-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q4-7-inst-choose" v-html="getHighlightedSegmentById('q4-7-inst-choose')"></span>
                                        <span class="font-bold mx-0.5 text-gray-900" data-segment-id="q4-7-inst-true" v-html="getHighlightedSegmentById('q4-7-inst-true')"></span>
                                        <span class="text-gray-700" data-segment-id="q4-7-inst-true-desc" v-html="getHighlightedSegmentById('q4-7-inst-true-desc')"></span>
                                        <span class="font-bold mx-0.5 text-gray-900" data-segment-id="q4-7-inst-false" v-html="getHighlightedSegmentById('q4-7-inst-false')"></span>
                                        <span class="text-gray-700" data-segment-id="q4-7-inst-false-desc" v-html="getHighlightedSegmentById('q4-7-inst-false-desc')"></span>
                                        <span class="font-bold mx-0.5 text-gray-900" data-segment-id="q4-7-inst-notgiven" v-html="getHighlightedSegmentById('q4-7-inst-notgiven')"></span>
                                        <span class="text-gray-700" data-segment-id="q4-7-inst-notgiven-desc" v-html="getHighlightedSegmentById('q4-7-inst-notgiven-desc')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 4 -->
                                    <div
                                        id="question-4"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 4"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q4-num" v-html="getHighlightedSegmentById('q4-num')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q4-text" v-html="getHighlightedSegmentById('q4-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q4"
                                                    value="TRUE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q4"
                                                    value="FALSE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q4"
                                                    value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 5"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q5-num" v-html="getHighlightedSegmentById('q5-num')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q5-text" v-html="getHighlightedSegmentById('q5-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q5"
                                                    value="TRUE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q5"
                                                    value="FALSE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q5"
                                                    value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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

                                    <!-- Question 6 -->
                                    <div
                                        id="question-6"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 6"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q6-num" v-html="getHighlightedSegmentById('q6-num')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q6-text" v-html="getHighlightedSegmentById('q6-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q6"
                                                    value="TRUE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q6"
                                                    value="FALSE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q6"
                                                    value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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

                                    <!-- Question 7 -->
                                    <div
                                        id="question-7"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 7"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q7-num" v-html="getHighlightedSegmentById('q7-num')"></span>
                                            <span class="text-base text-gray-900" data-segment-id="q7-text" v-html="getHighlightedSegmentById('q7-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q7"
                                                    value="TRUE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q7"
                                                    value="FALSE"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="radio"
                                                    v-model="answers.q7"
                                                    value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black"
                                                />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                </div>
                            </div>

                            <!-- Questions 8-13 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" v-html="getHighlightedSegment('Questions 8-13')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegment('Complete the table below.')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                        ></span>
                                    </p>
                                </div>

                                <div class="overflow-x-auto bg-white">
                                    <table class="w-full border-collapse border border-black text-sm">
                                        <thead>
                                            <tr class="bg-white">
                                                <th class="border border-black p-2 text-left font-bold text-gray-800">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Type of bridge')"></span>
                                                </th>
                                                <th class="border border-black p-2 text-left font-bold text-gray-800">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Features')"></span>
                                                </th>
                                                <th class="border border-black p-2 text-left font-bold text-gray-800">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Example(s)')"></span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="transition-colors hover:bg-gray-100">
                                                <td class="border border-black p-2 font-medium text-gray-800">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Arched bridge')"></span>
                                                </td>
                                                <td class="border border-black p-2">
                                                    <div class="space-y-3">
                                                        <div
                                                            id="question-8"
                                                            class="relative flex items-center gap-2 p-1"
                                                            @mouseenter="hoveredQuestion = 8"
                                                            @mouseleave="hoveredQuestion = null"
                                                        >
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Introduced by the')"></span>
                                                            <input
                                                                v-model="answers.q8"
                                                                type="text"
                                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center focus:border-black focus:outline-none"
                                                                style="width: 100px"
                                                                placeholder="8"
                                                                spellcheck="false"
                                                                autocomplete="off"
                                                                autocorrect="off"
                                                                autocapitalize="off"
                                                            />
                                                            <button
                                                                v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                                                @click.stop="toggleFlag(8)"
                                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                                :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                                            >
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <div class="text-gray-700">
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Very strong')"></span>
                                                        </div>
                                                        <div
                                                            id="question-9"
                                                            class="relative flex items-center gap-2 p-1"
                                                            @mouseenter="hoveredQuestion = 9"
                                                            @mouseleave="hoveredQuestion = null"
                                                        >
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Usually made of')"></span>
                                                            <input
                                                                v-model="answers.q9"
                                                                type="text"
                                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center focus:border-black focus:outline-none"
                                                                style="width: 100px"
                                                                placeholder="9"
                                                                spellcheck="false"
                                                                autocomplete="off"
                                                                autocorrect="off"
                                                                autocapitalize="off"
                                                            />
                                                            <button
                                                                v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                                                @click.stop="toggleFlag(9)"
                                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                                :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                                            >
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border border-black p-2 text-gray-700">
                                                    <span
                                                        class="text-gray-700"
                                                        v-html="getHighlightedSegment('Alcantara, Spain Ironbridge, UK')"
                                                    ></span>
                                                </td>
                                            </tr>
                                            <tr class="transition-colors hover:bg-gray-100">
                                                <td class="border border-black p-2 font-medium text-gray-800">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Truss bridge')"></span>
                                                </td>
                                                <td class="border border-black p-2">
                                                    <div class="space-y-1 text-gray-700">
                                                        <div>
                                                            <span
                                                                class="text-gray-700"
                                                                v-html="getHighlightedSegment('Made of wood or metal')"
                                                            ></span>
                                                        </div>
                                                        <div>
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Popular for railways')"></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border border-black p-2 text-center text-gray-500">-</td>
                                            </tr>
                                            <tr class="transition-colors hover:bg-gray-100">
                                                <td class="border border-black p-2 font-medium text-gray-800">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Suspension bridge')"></span>
                                                </td>
                                                <td class="border border-black p-2">
                                                    <div class="space-y-3">
                                                        <div class="text-gray-700">
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Has a suspended deck')"></span>
                                                        </div>
                                                        <div
                                                            id="question-10"
                                                            class="relative flex items-center gap-2 p-1"
                                                            @mouseenter="hoveredQuestion = 10"
                                                            @mouseleave="hoveredQuestion = null"
                                                        >
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Strong but')"></span>
                                                            <input
                                                                v-model="answers.q10"
                                                                type="text"
                                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center focus:border-black focus:outline-none"
                                                                style="width: 100px"
                                                                placeholder="10"
                                                                spellcheck="false"
                                                                autocomplete="off"
                                                                autocorrect="off"
                                                                autocapitalize="off"
                                                            />
                                                            <button
                                                                v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                                                @click.stop="toggleFlag(10)"
                                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                                :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                                            >
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border border-black p-2">
                                                    <div class="space-y-2">
                                                        <div class="text-gray-700">
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Clifton, UK')"></span>
                                                        </div>
                                                        <div
                                                            id="question-11"
                                                            class="relative flex flex-wrap items-center gap-1 p-1"
                                                            @mouseenter="hoveredQuestion = 11"
                                                            @mouseleave="hoveredQuestion = null"
                                                        >
                                                            <span
                                                                class="text-gray-700"
                                                                v-html="getHighlightedSegment('Akashi Kaikyo, Japan (currently the')"
                                                            ></span>
                                                            <input
                                                                v-model="answers.q11"
                                                                type="text"
                                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center focus:border-black focus:outline-none"
                                                                style="width: 100px"
                                                                placeholder="11"
                                                                spellcheck="false"
                                                                autocomplete="off"
                                                                autocorrect="off"
                                                                autocapitalize="off"
                                                            />
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('span)')"></span>
                                                            <button
                                                                v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                                                @click.stop="toggleFlag(11)"
                                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                                :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                                            >
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="transition-colors hover:bg-gray-100">
                                                <td class="border border-black p-2 font-medium text-gray-800">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Cantilever bridge')"></span>
                                                </td>
                                                <td class="border border-black p-2">
                                                    <div class="space-y-3">
                                                        <div
                                                            id="question-12"
                                                            class="relative flex items-center gap-2 p-1"
                                                            @mouseenter="hoveredQuestion = 12"
                                                            @mouseleave="hoveredQuestion = null"
                                                        >
                                                            <span class="text-gray-700" v-html="getHighlightedSegment('Made of')"></span>
                                                            <input
                                                                v-model="answers.q12"
                                                                type="text"
                                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center focus:border-black focus:outline-none"
                                                                style="width: 100px"
                                                                placeholder="12"
                                                                spellcheck="false"
                                                                autocomplete="off"
                                                                autocorrect="off"
                                                                autocapitalize="off"
                                                            />
                                                            <button
                                                                v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                                                @click.stop="toggleFlag(12)"
                                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                                :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                                            >
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <div
                                                            id="question-13"
                                                            class="relative p-1"
                                                            @mouseenter="hoveredQuestion = 13"
                                                            @mouseleave="hoveredQuestion = null"
                                                        >
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <span class="text-gray-700" v-html="getHighlightedSegment('More')"></span>
                                                                <input
                                                                    v-model="answers.q13"
                                                                    type="text"
                                                                    class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center focus:border-black focus:outline-none"
                                                                    style="width: 100px"
                                                                    placeholder="13"
                                                                    spellcheck="false"
                                                                    autocomplete="off"
                                                                    autocorrect="off"
                                                                    autocapitalize="off"
                                                                />
                                                            </div>
                                                            <div class="mt-1 text-sm text-gray-700">
                                                                <span
                                                                    class="text-gray-700"
                                                                    v-html="getHighlightedSegment('than the suspension bridge')"
                                                                ></span>
                                                            </div>
                                                            <button
                                                                v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                                                @click.stop="toggleFlag(13)"
                                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                                :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                                :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                                            >
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border border-black p-2 text-gray-700">
                                                    <span class="text-gray-700" v-html="getHighlightedSegment('Quebec, Canada')"></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
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
</style>
