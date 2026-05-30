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

// The Pearl passage text
const passageText = `
<b>A</b><br/>
The pearl has always had a special status in the rich and powerful all through the history. For instance, women from ancient Rome went to bed with pearls on them, so that they could remind themselves how wealthy they were after waking up. Pearls used to have more commercial value than diamonds until jewellers learnt to cut gems. In the eastern countries like Persia, ground pearl powders could be used as a medicine to cure anything including heart diseases and epilepsy.<br/><br/>

<b>B</b><br/>
Pearls can generally be divided into three categories: natural, cultured and imitation. When an irritant (such as a grain of sand) gets inside a certain type of oyster, mussel, or clam, the mollusc will secrete a fluid as a means of defence to coat the irritant. Gradually, layers are accumulated around the irritant until a lustrous natural pearl is formed.<br/><br/>

<b>C</b><br/>
A cultured pearl undergoes the same process. There is only one difference between cultured pearls and natural ones: in cultured pearls, the irritant is a bead called 'mother of pearl' and is placed in the oyster through surgical implantation. This results in much larger cores in cultivated pearls than those in natural pearls. As long as there are enough layers of nacre (the secreted fluid covering the irritant) to create a gorgeous, gem-quality pearl; the size of the nucleus wouldn't make a difference to beauty or durability.<br/><br/>

<b>D</b><br/>
Pearls can come from both salt and freshwater sources. Typically, pearls from salt water usually have high quality, although several freshwater pearls are considered high in quality, too. In addition, freshwater pearls often have irregular shapes, with a puffed rice appearance. Nevertheless, it is the individual merits that determine the pearl's value more than the sources of pearls. Saltwater pearl oysters are usually cultivated in protected lagoons or volcanic atolls, while most freshwater cultured pearls sold today come from China. There are a number of options for producing cultured pearls: use fresh water or sea water shells, transplant the graft into the mantle or into the gonad, add a spherical bead or do it nonbeaded.<br/><br/>

<b>E</b><br/>
No matter which method is used to get pearls, the process usually takes several years. Mussels must reach a mature age, which may take up almost three years, and then be transplanted an irritant. When the irritant is put in place, it takes approximately another three years for a pearl to reach its full size. Sometimes, the irritant may be rejected. As a result, the pearl may be seriously deformed, or the oyster may directly die from such numerous complications as diseases. At the end of a 5- to 10-year circle, only half of the oysters may have made it through. Among the pearls that are actually produced in the end, only about 5% of them will be high-quality enough for the jewellery makers.<br/><br/>

<b>F</b><br/>
Imitation pearls are of another different story. The Island of Mallorca in Spain is renowned for its imitation pearl industry. In most cases, a bead is dipped into a solution made from fish scales. But this coating is quite thin and often wears off. One way to distinguish the imitation pearls is to have a bite on it. Fake pearls glide through your teeth, while the layers of nacre on the real pearls feel gritty.<br/><br/>

<b>G</b><br/>
Several factors are taken into account to evaluate a pearl: size, shape, colour, the quality of surface and luster. Generally, the three types of pearls come in such order (with the value decreasing): natural pearls, cultured pearls and imitation pearls (which basically are worthless). For jewellers, one way to tell whether a pearl is natural or cultured is to send it to a gem lab and perform an X-ray on it. High-quality natural pearls are extremely rare. Japan's Akoya pearls are one of the glossiest pearls out there, while the south sea water of Australia is a cradle to bigger pearls.<br/><br/>

<b>H</b><br/>
Historically, the pearls with the highest quality around the globe are found in the Persian Gulf, particularly around Bahrain. These pearls have to be hand-harvested by divers with no advanced equipment. Unfortunately, when the large reserve of oil was discovered in the early 1930s, Persian Gulf's natural pearl industry came to a sudden end because the contaminated water destroyed the once pristine pearls. In the present days, India probably has the largest stock of natural pearls. However, it is quite an irony that a large part of India's stock of natural pearls are originally from Bahrain.`;

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    // Part 1 header text segments
    { id: 'part1-title', text: 'Part 1', offset: 5000 },
    { id: 'part1-desc', text: 'Read the text and answer questions 1–13.', offset: 5007 },
    { id: 'part1-passage-title', text: 'The Pearl', offset: 5048 },
    // Single passage text segment
    { id: 'passage', text: passageText, offset: 0 },
    // Questions 1-4 text segments
    { id: 'q1-4-title', text: 'Questions 1-4', offset: 5086 },
    { id: 'q1-4-inst1', text: 'Reading Passage 1 has eight paragraphs, A-H.', offset: 5100 },
    { id: 'q1-4-inst2', text: 'Which paragraph contains the following information?', offset: 5146 },
    { id: 'q1-4-inst3', text: 'Write the correct letter, A-H, in boxes 1-4 on your answer sheet.', offset: 5198 },
    { id: 'q1-text', text: 'ancient stories around pearls and its customers', offset: 5265 },
    { id: 'q2-text', text: 'difficulties in cultivating process', offset: 5313 },
    { id: 'q3-text', text: 'factors which decide the value of natural pearls', offset: 5349 },
    { id: 'q4-text', text: 'a growth mechanism that distinguishes cultured pearls from natural ones', offset: 5398 },
    // Questions 5-10 text segments
    { id: 'q5-10-title', text: 'Questions 5-10', offset: 5470 },
    { id: 'q5-10-inst1', text: 'Complete the summary below.', offset: 5485 },
    { id: 'q5-10-inst2', text: 'Choose NO MORE THAN TWO WORDS from the passage for each answer.', offset: 5513 },
    { id: 'q5-10-inst3', text: 'Write your answers in boxes 5-10 on your answer sheet.', offset: 5577 },
    { id: 'q5-10-summary-a', text: 'In history, pearls have had great importance within the men of wealth and power, which were treated as gems for women in', offset: 5632 },
    { id: 'q5-10-summary-b', text: 'Also, pearls were even used as a medicine for people in', offset: 5753 },
    { id: 'q5-10-summary-c', text: 'There are essentially three types of pearls: natural, cultured and imitation. Most freshwater cultured pearls sold today come from China while', offset: 5809 },
    { id: 'q5-10-summary-d', text: 'Island is famous for its imitation pearl industry. Good-quality natural pearls are exceedingly unusual.', offset: 5948 },
    { id: 'q5-10-summary-e', text: 'often manufactures some of the glitteriest pearls while', offset: 6051 },
    { id: 'q5-10-summary-f', text: 'produces larger size ones due to the favourable environment along the coastline. In the past,', offset: 6107 },
    { id: 'q5-10-summary-g', text: 'in Persian Gulf produced the world\'s best pearls. Nowadays, the major remaining suppliers of natural pearls belong to India.', offset: 6200 },
    // Questions 11-13 text segments
    { id: 'q11-13-title', text: 'Questions 11-13', offset: 6320 },
    { id: 'q11-13-inst1', text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 6336 },
    { id: 'q11-13-inst2', text: 'In boxes 11-13 on your answer sheet, write', offset: 6419 },
    { id: 'q11-13-true', text: 'TRUE', offset: 6463 },
    { id: 'q11-13-true-desc', text: 'if the statement is true', offset: 6468 },
    { id: 'q11-13-false', text: 'FALSE', offset: 6493 },
    { id: 'q11-13-false-desc', text: 'if the statement is false', offset: 6499 },
    { id: 'q11-13-ng', text: 'NOT GIVEN', offset: 6525 },
    { id: 'q11-13-ng-desc', text: 'if the information is not given in the passage', offset: 6535 },
    { id: 'q11-text', text: 'Generally speaking, the centre of cultured pearl is significantly larger than that of a natural pearl.', offset: 6584 },
    { id: 'q12-text', text: 'Sometimes, fake pearls can be more expensive.', offset: 6686 },
    { id: 'q13-text', text: 'The size of the pearls produced in Japan is usually smaller than those in Australia.', offset: 6732 },
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

                        // Store plain text offsets
                        const startOffset = segment.offset + plainTextOffset;
                        const endOffset = startOffset + normalizedSelected.length;

                        selectedText.value = normalizedSelected;
                        selectionRange.value = { start: startOffset, end: endOffset };
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
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    // Remove overlapping highlights
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

// Handle click on content area
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

// Handle mouse enter on noted text
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

    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

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
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 1 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('part1-title')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegmentById('part1-desc')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" v-html="getHighlightedSegmentById('part1-passage-title')"></span>
                        </h2>
                    </div>

                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="text-lg text-gray-700" v-html="getHighlightedSegment(textSegments[3].text)"></span>
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

                            <!-- Questions 1-4: Paragraph Matching with Select Dropdowns -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-lg font-bold text-gray-900">
                <span class="text-gray-700" data-segment-id="q1-4-title" v-html="getHighlightedSegmentById('q1-4-title')"></span>
            </h3>
        </div>
        <p class="mb-2 text-sm leading-relaxed text-gray-700">
            <span class="text-gray-700" data-segment-id="q1-4-inst1" v-html="getHighlightedSegmentById('q1-4-inst1')"></span>
        </p>
        <p class="mb-2 text-sm leading-relaxed text-gray-700">
            <span class="text-gray-700" data-segment-id="q1-4-inst2" v-html="getHighlightedSegmentById('q1-4-inst2')"></span>
        </p>
        <p class="mb-4 text-sm leading-relaxed text-gray-700">
            <span class="text-gray-700" data-segment-id="q1-4-inst3" v-html="getHighlightedSegmentById('q1-4-inst3')"></span>
        </p>
    </div>

    <div class="space-y-4">
        <!-- Question 1 -->
        <div
            id="question-1"
            class="relative flex items-start gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 1"
            @mouseleave="hoveredQuestion = null"
        >
            <span class="font-bold text-gray-900 shrink-0">1</span>
            <span class="text-gray-700 flex-1" data-segment-id="q1-text" v-html="getHighlightedSegmentById('q1-text')"></span>
            <select
                v-model="answers.q1"
                class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                style="width: 80px"
            >
                <option value="" disabled selected>select</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
                <option value="F">F</option>
                <option value="G">G</option>
                <option value="H">H</option>
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
            class="relative flex items-start gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 2"
            @mouseleave="hoveredQuestion = null"
        >
            <span class="font-bold text-gray-900 shrink-0">2</span>
            <span class="text-gray-700 flex-1" data-segment-id="q2-text" v-html="getHighlightedSegmentById('q2-text')"></span>
            <select
                v-model="answers.q2"
                class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                style="width: 80px"
            >
                <option value="" disabled selected>select</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
                <option value="F">F</option>
                <option value="G">G</option>
                <option value="H">H</option>
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
            class="relative flex items-start gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 3"
            @mouseleave="hoveredQuestion = null"
        >
            <span class="font-bold text-gray-900 shrink-0">3</span>
            <span class="text-gray-700 flex-1" data-segment-id="q3-text" v-html="getHighlightedSegmentById('q3-text')"></span>
            <select
                v-model="answers.q3"
                class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                style="width: 80px"
            >
                <option value="" disabled selected>select</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
                <option value="F">F</option>
                <option value="G">G</option>
                <option value="H">H</option>
            </select>
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

        <!-- Question 4 -->
        <div
            id="question-4"
            class="relative flex items-start gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 4"
            @mouseleave="hoveredQuestion = null"
        >
            <span class="font-bold text-gray-900 shrink-0">4</span>
            <span class="text-gray-700 flex-1" data-segment-id="q4-text" v-html="getHighlightedSegmentById('q4-text')"></span>
            <select
                v-model="answers.q4"
                class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                style="width: 80px"
            >
                <option value="" disabled selected>select</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
                <option value="F">F</option>
                <option value="G">G</option>
                <option value="H">H</option>
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
    </div>
</div>

                            <!-- Questions 5-10: Summary Completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q5-10-title" v-html="getHighlightedSegmentById('q5-10-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q5-10-inst1" v-html="getHighlightedSegmentById('q5-10-inst1')"></span>
                                    </p>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q5-10-inst2" v-html="getHighlightedSegmentById('q5-10-inst2')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q5-10-inst3" v-html="getHighlightedSegmentById('q5-10-inst3')"></span>
                                    </p>
                                </div>

                                <!-- Summary paragraph with inline inputs -->
                                <div class="text-sm leading-relaxed text-gray-700 space-y-3 border border-gray-200 p-4 bg-gray-50">
                                    <!-- First sentence: Q5 -->
                                    <div
                                        id="question-5"
                                        class="relative inline"
                                        @mouseenter="hoveredQuestion = 5"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="text-gray-700" data-segment-id="q5-10-summary-a" v-html="getHighlightedSegmentById('q5-10-summary-a')"></span>
                                        <input
                                            v-model="answers.q5"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 130px"
                                            placeholder="5"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                            @click.stop="toggleFlag(5)"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all ml-1"
                                            :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Second sentence: Q6 -->
                                    <div
                                        id="question-6"
                                        class="relative inline"
                                        @mouseenter="hoveredQuestion = 6"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="text-gray-700" data-segment-id="q5-10-summary-b" v-html="getHighlightedSegmentById('q5-10-summary-b')"></span>
                                        <input
                                            v-model="answers.q6"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 130px"
                                            placeholder="6"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                            @click.stop="toggleFlag(6)"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all ml-1"
                                            :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Third sentence: Q7 -->
                                    <div
                                        id="question-7"
                                        class="relative inline"
                                        @mouseenter="hoveredQuestion = 7"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="text-gray-700" data-segment-id="q5-10-summary-c" v-html="getHighlightedSegmentById('q5-10-summary-c')"></span>
                                        <input
                                            v-model="answers.q7"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 130px"
                                            placeholder="7"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-700" data-segment-id="q5-10-summary-d" v-html="getHighlightedSegmentById('q5-10-summary-d')"></span>
                                        <button
                                            v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                            @click.stop="toggleFlag(7)"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all ml-1"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Fourth sentence: Q8 -->
                                    <div
                                        id="question-8"
                                        class="relative inline"
                                        @mouseenter="hoveredQuestion = 8"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q8"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 130px"
                                            placeholder="8"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-700" data-segment-id="q5-10-summary-e" v-html="getHighlightedSegmentById('q5-10-summary-e')"></span>
                                        <button
                                            v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                            @click.stop="toggleFlag(8)"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all ml-1"
                                            :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Fifth sentence: Q9 -->
                                    <div
                                        id="question-9"
                                        class="relative inline"
                                        @mouseenter="hoveredQuestion = 9"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q9"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 130px"
                                            placeholder="9"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-700" data-segment-id="q5-10-summary-f" v-html="getHighlightedSegmentById('q5-10-summary-f')"></span>
                                        <button
                                            v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="toggleFlag(9)"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all ml-1"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Sixth sentence: Q10 -->
                                    <div
                                        id="question-10"
                                        class="relative inline"
                                        @mouseenter="hoveredQuestion = 10"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q10"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 130px"
                                            placeholder="10"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-700" data-segment-id="q5-10-summary-g" v-html="getHighlightedSegmentById('q5-10-summary-g')"></span>
                                        <button
                                            v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all ml-1"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 11-13: TRUE / FALSE / NOT GIVEN -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q11-13-title" v-html="getHighlightedSegmentById('q11-13-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q11-13-inst1" v-html="getHighlightedSegmentById('q11-13-inst1')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q11-13-inst2" v-html="getHighlightedSegmentById('q11-13-inst2')"></span>
                                    </p>
                                    <div class="mb-4 text-sm text-gray-700 space-y-1">
                                        <div class="flex gap-2">
                                            <span class="font-bold text-gray-900" data-segment-id="q11-13-true" v-html="getHighlightedSegmentById('q11-13-true')"></span>
                                            <span class="text-gray-700" data-segment-id="q11-13-true-desc" v-html="getHighlightedSegmentById('q11-13-true-desc')"></span>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="font-bold text-gray-900" data-segment-id="q11-13-false" v-html="getHighlightedSegmentById('q11-13-false')"></span>
                                            <span class="text-gray-700" data-segment-id="q11-13-false-desc" v-html="getHighlightedSegmentById('q11-13-false-desc')"></span>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="font-bold text-gray-900" data-segment-id="q11-13-ng" v-html="getHighlightedSegmentById('q11-13-ng')"></span>
                                            <span class="text-gray-700" data-segment-id="q11-13-ng-desc" v-html="getHighlightedSegmentById('q11-13-ng-desc')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 11 -->
                                    <div
                                        id="question-11"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 11"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0">11</span>
                                            <span class="text-base text-gray-900" data-segment-id="q11-text" v-html="getHighlightedSegmentById('q11-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
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
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0">12</span>
                                            <span class="text-base text-gray-900" data-segment-id="q12-text" v-html="getHighlightedSegmentById('q12-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
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
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0">13</span>
                                            <span class="text-base text-gray-900" data-segment-id="q13-text" v-html="getHighlightedSegmentById('q13-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q13" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q13" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q13" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
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
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            ></path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
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