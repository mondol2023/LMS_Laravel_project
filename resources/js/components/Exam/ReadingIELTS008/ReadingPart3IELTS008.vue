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
const PANEL_WIDTH_KEY = 'reading-ielts008-part1-panel-width';
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

const summaryWords = [
    { letter: 'A', word: 'inventive' },
    { letter: 'B', word: 'similar' },
    { letter: 'C', word: 'beneficial' },
    { letter: 'D', word: 'next' },
    { letter: 'E', word: 'mixed' },
    { letter: 'F', word: 'justified' },
    { letter: 'G', word: 'inferior' },
];

const peopleOptions = [
    { letter: 'A', name: 'Jean-Auguste-Dominique Ingres' },
    { letter: 'B', name: 'Francis Wey' },
    { letter: 'C', name: 'Charles Baudelaire' },
    { letter: 'D', name: 'Eugene Delacroix' },
    { letter: 'E', name: 'Philip Gilbert Hamerton' },
];

// answers.q31–q34 hold A–G letters; answers.q35–q40 hold A–E letters
// (already declared in the existing answers ref — just ensure these keys exist)

// ── drag state ────────────────────────────────────────────────────────────────
const draggedLetter  = ref<string>('');
const dragSource     = ref<string>('');   // 'summary-palette' | 'people-palette' | 'slot-q31' etc.

// ── computed available letters ────────────────────────────────────────────────
const usedSummaryLetters = computed(() =>
    ['q31','q32','q33','q34'].map(k => answers.value[k as keyof typeof answers.value]).filter(Boolean)
);
const availableSummaryLetters = computed(() =>
    summaryWords.filter(w => !usedSummaryLetters.value.includes(w.letter))
);

const usedPeopleLetters = computed(() =>
    ['q35','q36','q37','q38','q39','q40'].map(k => answers.value[k as keyof typeof answers.value]).filter(Boolean)
);
const availablePeopleLetters = computed(() =>
    peopleOptions.filter(p => !usedPeopleLetters.value.includes(p.letter))
);

// ── drag handlers ─────────────────────────────────────────────────────────────
const onDragStartFromPalette = (palette: 'summary' | 'people', letter: string) => {
    draggedLetter.value = letter;
    dragSource.value = `${palette}-palette`;
};

const onDragStartFromSlot = (slotKey: string, letter: string) => {
    draggedLetter.value = letter;
    dragSource.value = `slot-${slotKey}`;
};

const onDropToSlot = (slotKey: string) => {
    if (!draggedLetter.value) return;
    // If came from another slot, clear that slot
    if (dragSource.value.startsWith('slot-')) {
        const srcKey = dragSource.value.replace('slot-', '');
        if (srcKey !== slotKey) {
            (answers.value as any)[srcKey] = '';
        }
    }
    (answers.value as any)[slotKey] = draggedLetter.value;
    draggedLetter.value = '';
    dragSource.value = '';
};

const onDropToPalette = (palette: 'summary' | 'people') => {
    // Dragging from a slot back to palette clears that slot
    if (dragSource.value.startsWith('slot-')) {
        const srcKey = dragSource.value.replace('slot-', '');
        (answers.value as any)[srcKey] = '';
    }
    draggedLetter.value = '';
    dragSource.value = '';
};

const onDragOver = (e: DragEvent) => e.preventDefault();

// Text segments with their cumulative offsets in the full text
const passageText = `This may seem a pointless question today. Surrounded as we are by thousands of photographs, most of us take for granted that, in addition to supplying information and seducing customers, camera images also serve as decoration, afford spiritual enrichment, and provide significant insights into the passing scene. But in the decades following the discovery of photography, this question reflected the search for ways to fit the mechanical medium into the traditional schemes of artistic expression.

The much-publicized pronouncement by painter Paul Delaroche that the daguerreotype* signalled the end of painting is perplexing because this clever artist also forecast the usefulness of the medium for graphic artists in a letter written in 1839. Nevertheless, it is symptomatic of the swing between the outright rejection and qualified acceptance of the medium that was fairly typical of the artistic establishment. Discussion of the role of photography in art was especially spirited in France, where the internal policies of the time had created a large pool of artists, but it was also taken up by important voices in England. In both countries, public interest in this topic was a reflection of the belief that national stature and achievement in the arts were related.

From the maze of conflicting statements and heated articles on the subject, three main positions about the potential of camera art emerged. The simplest, entertained by many painters and a section of the public, was that photographs should not be considered ‘art’ because they were made with a mechanical device and by physical and chemical phenomena instead of by human hand and spirit; to some, camera images seemed to have more in common with fabric produced by machinery in a mill than with handmade creations fired by inspiration. The second widely held view, shared by painters, some photographers, and some critics, was that photographs would be useful to art but should not be considered equal in creativeness to drawing and painting. Lastly, by assuming that the process was comparable to other techniques such as etching and lithography, a fair number of individuals realized that camera images were or could be as significant as handmade works of art and that they might have a positive influence on the arts and on culture in general.

Artists reacted to photography in various ways. Many portrait painters - miniaturists in particular - who realized that photography represented the ‘handwriting on the wall’ became involved with daguerreotyping or paper photography in an effort to save their careers; some incorporated it with painting, while others renounced painting altogether. Still other painters, the most prominent among them the French painter, Jean- Auguste-Dominique Ingres, began almost immediately to use photography to make a record of their own output and also to provide themselves with source
material for poses and backgrounds, vigorously denying at the same time its influence on their vision or its claims as art.

The view that photographs might be worthwhile to artists was enunciated in considerable detail by Lacan and Francis Wey. The latter, an art and literary critic, who eventually recognised that camera images could be inspired as well as informative, suggested that they would lead to greater naturalness in the graphic depiction of anatomy, clothing, likeness, expression, and landscape. By studying photographs, true artists, he claimed, would be relieved of menial tasks and become free to devote themselves to the more important spiritual aspects of their work.

Wey left unstated what the incompetent artist might do as an alternative, but according to the influential French critic and poet Charles Baudelaire, writing in response to an exhibition of photography in 1859, lazy and untalented painters would become photographers. Fired by a belief in art as an imaginative embodiment of cultivated ideas and dreams, Baudelaire regarded photography as ‘a very humble servant of art and science’; a medium largely unable to transcend ‘external reality’. For this critic, photography was linked with ‘the great industrial madness’ of the time, which in his eyes exercised disastrous consequences on the spiritual qualities of life and art.

Eugene Delacroix was the most prominent of the French artists who welcomed photography as help-mate but recognized its limitations. Regretting that ‘such a wonderful invention’ had arrived so late in his lifetime, he still took lessons in daguerreotyping, and both commissioned and collected photographs. Delacroix’s enthusiasm for the medium can be sensed in a journal entry noting that if photographs were used as they should be, an artist might ‘raise himself to heights that we do not yet know’.


The question of whether the photograph was document or art aroused interest in England also. The most important statement on this matter was an unsigned article that concluded that while photography had a role to play, it should not be ‘constrained’ into ‘competition’ with art; a more stringent viewpoint led critic Philip Gilbert Hamerton to dismiss camera images as ‘narrow in range, emphatic in assertion, telling one truth for ten falsehoods’.

These writers reflected the opposition of a section of the cultural elite in England and France to the ‘cheapening of art’ which the growing acceptance and purchase of camera pictures by the middle class represented. Technology made photographic images a common sight in the shop windows of Regent Street and Piccadilly in London and the commercial boulevards of Paris. In London, for example, there were at the time some 130 commercial establishments where portraits, landscapes, and photographic reproductions of works of art could be bought. This appeal to the middle class convinced the elite that photographs would foster a desire for realism instead of idealism, even though some critics recognized that the work of individual photographers might display an uplifting style and substance that was consistent with the defining characteristics of art.`;

const textSegments = ref([
    { id: 0 , text: passageText, offset: 0 },
    { id: 1 ,text: 'READING PASSAGE 3', offset: 4851 },
    { id: 2 ,text: 'IS PHOTOGRAPHY ART?', offset: 4870 },
    { id: 3 ,text: 'Questions 27–30', offset: 4890 },
    { id: 4 ,text: 'Choose the correct letter, A, B, C or D', offset: 4907 },
    { id: 5 ,text: 'What is the writer’s main point in the first paragraph?', offset: 4949 },
    { id: 6 ,text: 'A. photography is used for many different purposes.', offset: 5005 },
    { id: 7 ,text: 'B. photographers and artists have the same principal aims.', offset: 5057 },
    { id: 8 ,text: 'C. photography has not always been a readily accepted art form.', offset: 5119 },
    { id: 9 ,text: 'D. photographers today are more creative than those of the past.', offset: 5188 },
    { id: 10 ,text: 'What positive view about artists was shared by the French and the English?', offset: 5256 },
    { id: 11 ,text: 'A. that only artists could reflect a culture’s true values', offset: 5334 },
    { id: 12 ,text: 'B. that only artists were qualified to judge photography', offset: 5393 },
    { id: 13 ,text: 'C. that artists could lose work as a result of photography', offset: 5452 },
    { id: 14 ,text: 'D. that artist success raised a country’s international profile', offset: 5514 },
    { id: 15 ,text: 'What does the writer mean by “the handwriting on the wall”?', offset: 5585 },
    { id: 16 ,text: 'A. an example of poor talent', offset: 5645 },
    { id: 17 ,text: 'B. a message that cannot be trusted', offset: 5675 },
    { id: 18 ,text: 'C. an advertisement for something new', offset: 5714 },
    { id: 19 ,text: 'D. a signal that something bad will happen', offset: 5754 },
    { id: 20 ,text: 'What was the result of the widespread availability of photographs to the middle classes?', offset: 5802 },
    { id: 21 ,text: 'A. The most educated worried about its impact on public taste.', offset: 5890 },
    { id: 22 ,text: 'B. It helped artists appreciate the merits of photography.', offset: 5957 },
    { id: 23 ,text: 'C. Improvements were made in photographic methods.', offset: 6016 },
    { id: 24 ,text: 'D. It led to a reduction in the price of photographs.', offset: 6069 },
    { id: 25 ,text: 'Questions 31–34', offset: 6121 },
    { id: 26 ,text: 'Complete the summary of Paragraph 3 using the list of words, A–G, below.', offset: 6138 },
    { id: 27 ,text: 'Write your answers in boxes 31–34 on your answer sheet.', offset: 6217 },
    { id: 28 ,text: 'Camera art', offset: 6276 },
    { id: 29 ,text: 'In the early days of photography, opinions on its future were', offset: 6288 },
    { id: 30 ,text: ', but three clear views emerged.', offset: 6351 },
    { id: 31 ,text: 'A large number of artists and ordinary people saw photographs as', offset: 6382 },
    { id: 32 ,text: 'to paintings because of the way they were produced.', offset: 6451 },
    { id: 33 ,text: 'Another popular view was that photographs could have a role to play in the art world, despite photography being less', offset: 6505 },
    { id: 34 ,text: '.', offset: 6608 },
    { id: 35 ,text: 'Finally, a smaller number of people suspected that the impact of photography on art and society could be', offset: 6611 },
    { id: 36 ,text: 'Questions 35–40', offset: 6718 },
    { id: 37 ,text: 'Look at the following statements and the list of people, A–E, below.', offset: 6735 },
    { id: 38 ,text: 'Match each statement with the correct person.', offset: 6810 },
    { id: 39 ,text: 'Write the correct letter, A–E, in boxes 35–40 on your answer sheet.', offset: 6856 },
    { id: 40 ,text: 'He claimed that photography would make paintings more realistic.', offset: 6927 },
    { id: 41 ,text: 'He highlighted the limitations and deceptions of the camera.', offset: 6994 },
    { id: 42 ,text: 'He documented his production of artwork by photographing his works.', offset: 7058 },
    { id: 43 ,text: 'He noted the potential for photography to enrich artistic talent.', offset: 7132 },
    { id: 44 ,text: 'He based some of the scenes in his paintings on photographs.', offset: 7198 },
    { id: 45 ,text: 'He felt photography was part of the trend towards greater mechanisation.', offset: 7261 },
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

    <div class="mx-auto px-3 sm:px-4 lg:px-6 py-0.5">
        <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
            :class="{ 'select-none': isResizing }">

            <!-- ════════════════════════════════════════════════
                 READING PASSAGE PANEL
            ════════════════════════════════════════════════ -->
            <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                :style="{ '--panel-width': `${leftPanelWidth}%` }">
                <div class="pt-6 px-4">
                    <h2 class="text-base font-bold text-gray-900">
                        <span :data-segment-id="1" v-html="getHighlightedSegment('READING PASSAGE 3')"></span><br />
                        <span :data-segment-id="2" v-html="getHighlightedSegment('IS PHOTOGRAPHY ART?')"></span>
                    </h2>
                </div>
                <div class="flex-1 space-y-6 overflow-y-auto px-4" :style="contentZoom">
                    <div class="p-4">
                        <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700 select-text">
                            <span class="text-base text-gray-700 select-text"
                                :data-segment-id="0"
                                v-html="getHighlightedSegment(passageText)">
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resize Handle -->
            <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center
                        border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                @mousedown="startResize" title="Drag to resize panels">
                <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                    <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                    </svg>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════
                 QUESTIONS PANEL
            ════════════════════════════════════════════════ -->
            <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                :style="{ '--panel-width': `${leftPanelWidth}%` }">
                <div class="flex-1 overflow-y-auto pb-32">
                    <div class="space-y-8 p-4" :style="contentZoom">

                        <!-- ══════════════════════════════════
                             QUESTIONS 27–30  (radio buttons)
                        ══════════════════════════════════ -->
                        <div class="p-6">
                            <div class="mb-6">
                                <h3 class="mb-4 text-base font-bold text-gray-900">
                                    <span :data-segment-id="3"
                                        v-html="getHighlightedSegment('Questions 27\u201330')"></span>
                                </h3>
                                <p class="mb-4 text-base leading-relaxed text-gray-700">
                                    <span :data-segment-id="4"
                                        v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D')"></span>
                                </p>
                            </div>

                            <!-- Q27 -->
                            <div id="question-27" class="relative mb-4 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                <div class="mb-2 flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">27</span>
                                    <p class="text-base font-semibold text-gray-800"
                                        :data-segment-id="5"
                                        v-html="getHighlightedSegment('What is the writer\u2019s main point in the first paragraph?')"></p>
                                </div>
                                <div class="space-y-2 pl-10 text-base text-gray-800">
                                    <label v-for="(seg, val) in {A: {id:6, text:'A. photography is used for many different purposes.'}, B: {id:7, text:'B. photographers and artists have the same principal aims.'}, C: {id:8, text:'C. photography has not always been a readily accepted art form.'}, D: {id:9, text:'D. photographers today are more creative than those of the past.'}}"
                                        :key="val" class="flex items-start gap-3 cursor-pointer">
                                        <input v-model="answers.q27" type="radio" :value="val"
                                            class="mt-1 h-4 w-4 border-gray-300 focus:ring-gray-500 select-text" />
                                        <span :data-segment-id="seg.id" v-html="getHighlightedSegment(seg.text)"></span>
                                    </label>
                                </div>
                                <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                    @click.stop="toggleFlag(27)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(27) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>

                            <!-- Q28 -->
                            <div id="question-28" class="relative mb-4 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                <div class="mb-2 flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">28</span>
                                    <p class="text-base font-semibold text-gray-800"
                                        :data-segment-id="10"
                                        v-html="getHighlightedSegment('What positive view about artists was shared by the French and the English?')"></p>
                                </div>
                                <div class="space-y-2 pl-10 text-base text-gray-800">
                                    <label v-for="(seg, val) in {A: {id:11, text:'A. that only artists could reflect a culture\u2019s true values'}, B: {id:12, text:'B. that only artists were qualified to judge photography'}, C: {id:13, text:'C. that artists could lose work as a result of photography'}, D: {id:14, text:'D. that artist success raised a country\u2019s international profile'}}"
                                        :key="val" class="flex items-start gap-3 cursor-pointer">
                                        <input v-model="answers.q28" type="radio" :value="val"
                                            class="mt-1 h-4 w-4 border-gray-300 focus:ring-gray-500" />
                                        <span :data-segment-id="seg.id" v-html="getHighlightedSegment(seg.text)"></span>
                                    </label>
                                </div>
                                <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                    @click.stop="toggleFlag(28)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(28) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>

                            <!-- Q29 -->
                            <div id="question-29" class="relative mb-4 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                <div class="mb-2 flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">29</span>
                                    <p class="text-base font-semibold text-gray-800"
                                        :data-segment-id="15"
                                        v-html="getHighlightedSegment('What does the writer mean by \u201cthe handwriting on the wall\u201d?')"></p>
                                </div>
                                <div class="space-y-2 pl-10 text-base text-gray-800">
                                    <label v-for="(seg, val) in {A: {id:16, text:'A. an example of poor talent'}, B: {id:17, text:'B. a message that cannot be trusted'}, C: {id:18, text:'C. an advertisement for something new'}, D: {id:19, text:'D. a signal that something bad will happen'}}"
                                        :key="val" class="flex items-start gap-3 cursor-pointer">
                                        <input v-model="answers.q29" type="radio" :value="val"
                                            class="mt-1 h-4 w-4 border-gray-300 focus:ring-gray-500" />
                                        <span :data-segment-id="seg.id" v-html="getHighlightedSegment(seg.text)"></span>
                                    </label>
                                </div>
                                <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                    @click.stop="toggleFlag(29)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(29) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>

                            <!-- Q30 -->
                            <div id="question-30" class="relative mb-4 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                <div class="mb-2 flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">30</span>
                                    <p class="text-base font-semibold text-gray-800"
                                        :data-segment-id="20"
                                        v-html="getHighlightedSegment('What was the result of the widespread availability of photographs to the middle classes?')"></p>
                                </div>
                                <div class="space-y-2 pl-10 text-base text-gray-800">
                                    <label v-for="(seg, val) in {A: {id:21, text:'A. The most educated worried about its impact on public taste.'}, B: {id:22, text:'B. It helped artists appreciate the merits of photography.'}, C: {id:23, text:'C. Improvements were made in photographic methods.'}, D: {id:24, text:'D. It led to a reduction in the price of photographs.'}}"
                                        :key="val" class="flex items-start gap-3 cursor-pointer">
                                        <input v-model="answers.q30" type="radio" :value="val"
                                            class="mt-1 h-4 w-4 border-gray-300 focus:ring-gray-500" />
                                        <span :data-segment-id="seg.id" v-html="getHighlightedSegment(seg.text)"></span>
                                    </label>
                                </div>
                                <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                    @click.stop="toggleFlag(30)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(30) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- ══════════════════════════════════
                             QUESTIONS 31–34  (drag-and-drop summary)
                        ══════════════════════════════════ -->
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="mb-2 text-base font-bold text-gray-900">
                                    <span :data-segment-id="25"
                                        v-html="getHighlightedSegment('Questions 31\u201334')"></span>
                                </h3>
                                <p class="text-base leading-relaxed text-gray-700">
                                    <span :data-segment-id="26"
                                        v-html="getHighlightedSegment('Complete the summary of Paragraph 3 using the list of words, A\u2013G, below.')"></span><br />
                                    <span :data-segment-id="27"
                                        v-html="getHighlightedSegment('Write your answers in boxes 31\u201334 on your answer sheet.')"></span>
                                </p>
                            </div>

                            <!-- Word bank palette A–G -->
                            <div class="mb-5 flex flex-wrap gap-2 rounded border border-gray-300 bg-gray-50 px-4 py-3"
                                @dragover="onDragOver" @drop="onDropToPalette('summary')">
                                <template v-for="w in summaryWords" :key="w.letter">
                                    <div v-if="availableSummaryLetters.find(x => x.letter === w.letter)"
                                        draggable="true"
                                        @dragstart="onDragStartFromPalette('summary', w.letter)"
                                        class="flex cursor-grab items-center gap-1 rounded border border-gray-400 bg-white px-3 py-1 text-sm select-none active:cursor-grabbing">
                                        <span class="font-semibold">{{ w.letter }}</span>
                                        <span class="text-gray-700">{{ w.word }}</span>
                                    </div>
                                    <div v-else
                                        class="flex items-center gap-1 rounded border border-dashed border-gray-300 px-3 py-1 text-sm text-gray-300 select-none">
                                        <span class="font-semibold">{{ w.letter }}</span>
                                        <span>{{ w.word }}</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Summary title -->
                            <p class="mb-4 text-center text-base font-semibold text-gray-900 underline"
                                :data-segment-id="28"
                                v-html="getHighlightedSegment('Camera art')"></p>

                            <!-- Gapped summary sentences -->
                            <div class="space-y-4 text-base leading-relaxed text-gray-800">

                                <!-- Q31 -->
                                <p id="question-31" class="relative pr-10"
                                    @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
                                    <span :data-segment-id="29"
                                        v-html="getHighlightedSegment('In the early days of photography, opinions on its future were')"></span>
                                    <span class="mx-1 inline-flex items-center gap-1"
                                        @dragover="onDragOver" @drop="onDropToSlot('q31')">
                                        <span :class="['inline-flex min-w-[6rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                            answers.q31 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                            :draggable="!!answers.q31"
                                            @dragstart="answers.q31 ? onDragStartFromSlot('q31', answers.q31) : undefined">
                                            {{ answers.q31 || '31' }}
                                        </span>
                                    </span>
                                    <span :data-segment-id="30"
                                        v-html="getHighlightedSegment(', but three clear views emerged.')"></span>
                                    <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                        @click.stop="toggleFlag(31)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </p>

                                <!-- Q32 -->
                                <p id="question-32" class="relative pr-10"
                                    @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
                                    <span :data-segment-id="31"
                                        v-html="getHighlightedSegment('A large number of artists and ordinary people saw photographs as')"></span>
                                    <span class="mx-1 inline-flex items-center gap-1"
                                        @dragover="onDragOver" @drop="onDropToSlot('q32')">
                                        <span :class="['inline-flex min-w-[6rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                            answers.q32 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                            :draggable="!!answers.q32"
                                            @dragstart="answers.q32 ? onDragStartFromSlot('q32', answers.q32) : undefined">
                                            {{ answers.q32 || '32' }}
                                        </span>
                                    </span>
                                    <span :data-segment-id="32"
                                        v-html="getHighlightedSegment('to paintings because of the way they were produced.')"></span>
                                    <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                        @click.stop="toggleFlag(32)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(32) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </p>

                                <!-- Q33 -->
                                <p id="question-33" class="relative pr-10"
                                    @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null">
                                    <span :data-segment-id="33"
                                        v-html="getHighlightedSegment('Another popular view was that photographs could have a role to play in the art world, despite photography being less')"></span>
                                    <span class="mx-1 inline-flex items-center gap-1"
                                        @dragover="onDragOver" @drop="onDropToSlot('q33')">
                                        <span :class="['inline-flex min-w-[6rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                            answers.q33 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                            :draggable="!!answers.q33"
                                            @dragstart="answers.q33 ? onDragStartFromSlot('q33', answers.q33) : undefined">
                                            {{ answers.q33 || '33' }}
                                        </span>
                                    </span>
                                    <span :data-segment-id="34" v-html="getHighlightedSegment('.')"></span>
                                    <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                        @click.stop="toggleFlag(33)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </p>

                                <!-- Q34 -->
                                <p id="question-34" class="relative pr-10"
                                    @mouseenter="hoveredQuestion = 34" @mouseleave="hoveredQuestion = null">
                                    <span :data-segment-id="35"
                                        v-html="getHighlightedSegment('Finally, a smaller number of people suspected that the impact of photography on art and society could be')"></span>
                                    <span class="mx-1 inline-flex items-center gap-1"
                                        @dragover="onDragOver" @drop="onDropToSlot('q34')">
                                        <span :class="['inline-flex min-w-[6rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                            answers.q34 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                            :draggable="!!answers.q34"
                                            @dragstart="answers.q34 ? onDragStartFromSlot('q34', answers.q34) : undefined">
                                            {{ answers.q34 || '34' }}
                                        </span>
                                    </span>
                                    <span>.</span>
                                    <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                        @click.stop="toggleFlag(34)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(34) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </p>
                            </div>
                        </div>

                        <!-- ══════════════════════════════════
                             QUESTIONS 35–40  (drag-and-drop matching)
                        ══════════════════════════════════ -->
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="mb-2 text-base font-bold text-gray-900">
                                    <span :data-segment-id="36"
                                        v-html="getHighlightedSegment('Questions 35\u201340')"></span>
                                </h3>
                                <div class="mb-2 text-base leading-relaxed text-gray-700">
                                    <p :data-segment-id="37"
                                        v-html="getHighlightedSegment('Look at the following statements and the list of people, A\u2013E, below.')"></p>
                                    <p :data-segment-id="38"
                                        v-html="getHighlightedSegment('Match each statement with the correct person.')"></p>
                                    <p :data-segment-id="39"
                                        v-html="getHighlightedSegment('Write the correct letter, A\u2013E, in boxes 35\u201340 on your answer sheet.')"></p>
                                </div>
                            </div>

                            <!-- People palette A–E -->
                            <div class="mb-5 flex flex-wrap gap-2 rounded border border-gray-300 bg-gray-50 px-4 py-3"
                                @dragover="onDragOver" @drop="onDropToPalette('people')">
                                <template v-for="p in peopleOptions" :key="p.letter">
                                    <div v-if="availablePeopleLetters.find(x => x.letter === p.letter)"
                                        draggable="true"
                                        @dragstart="onDragStartFromPalette('people', p.letter)"
                                        class="flex cursor-grab items-center gap-1 rounded border border-gray-400 bg-white px-3 py-1 text-sm select-none active:cursor-grabbing">
                                        <span class="font-semibold">{{ p.letter }}</span>
                                        <span class="text-gray-700">{{ p.name }}</span>
                                    </div>
                                    <div v-else
                                        class="flex items-center gap-1 rounded border border-dashed border-gray-300 px-3 py-1 text-sm text-gray-300 select-none">
                                        <span class="font-semibold">{{ p.letter }}</span>
                                        <span>{{ p.name }}</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Statement rows 35–40 -->
                            <div class="space-y-3 text-base text-gray-800">

                                <!-- Q35 -->
                                <div id="question-35" class="relative flex items-center gap-3 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                    @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null"
                                    @dragover="onDragOver" @drop="onDropToSlot('q35')">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">35</span>
                                    <p class="flex-1" :data-segment-id="40"
                                        v-html="getHighlightedSegment('He claimed that photography would make paintings more realistic.')"></p>
                                    <span :class="['inline-flex min-w-[4rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                        answers.q35 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                        :draggable="!!answers.q35"
                                        @dragstart="answers.q35 ? onDragStartFromSlot('q35', answers.q35) : undefined">
                                        {{ answers.q35 || '35' }}
                                    </span>
                                    <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                        @click.stop="toggleFlag(35)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>

                                <!-- Q36 -->
                                <div id="question-36" class="relative flex items-center gap-3 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                    @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null"
                                    @dragover="onDragOver" @drop="onDropToSlot('q36')">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">36</span>
                                    <p class="flex-1" :data-segment-id="41"
                                        v-html="getHighlightedSegment('He highlighted the limitations and deceptions of the camera.')"></p>
                                    <span :class="['inline-flex min-w-[4rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                        answers.q36 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                        :draggable="!!answers.q36"
                                        @dragstart="answers.q36 ? onDragStartFromSlot('q36', answers.q36) : undefined">
                                        {{ answers.q36 || '36' }}
                                    </span>
                                    <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                        @click.stop="toggleFlag(36)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>

                                <!-- Q37 -->
                                <div id="question-37" class="relative flex items-center gap-3 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                    @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null"
                                    @dragover="onDragOver" @drop="onDropToSlot('q37')">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">37</span>
                                    <p class="flex-1" :data-segment-id="42"
                                        v-html="getHighlightedSegment('He documented his production of artwork by photographing his works.')"></p>
                                    <span :class="['inline-flex min-w-[4rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                        answers.q37 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                        :draggable="!!answers.q37"
                                        @dragstart="answers.q37 ? onDragStartFromSlot('q37', answers.q37) : undefined">
                                        {{ answers.q37 || '37' }}
                                    </span>
                                    <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                        @click.stop="toggleFlag(37)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>

                                <!-- Q38 -->
                                <div id="question-38" class="relative flex items-center gap-3 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                    @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null"
                                    @dragover="onDragOver" @drop="onDropToSlot('q38')">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">38</span>
                                    <p class="flex-1" :data-segment-id="43"
                                        v-html="getHighlightedSegment('He noted the potential for photography to enrich artistic talent.')"></p>
                                    <span :class="['inline-flex min-w-[4rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                        answers.q38 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                        :draggable="!!answers.q38"
                                        @dragstart="answers.q38 ? onDragStartFromSlot('q38', answers.q38) : undefined">
                                        {{ answers.q38 || '38' }}
                                    </span>
                                    <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                        @click.stop="toggleFlag(38)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>

                                <!-- Q39 -->
                                <div id="question-39" class="relative flex items-center gap-3 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                    @mouseenter="hoveredQuestion = 39" @mouseleave="hoveredQuestion = null"
                                    @dragover="onDragOver" @drop="onDropToSlot('q39')">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">39</span>
                                    <p class="flex-1" :data-segment-id="44"
                                        v-html="getHighlightedSegment('He based some of the scenes in his paintings on photographs.')"></p>
                                    <span :class="['inline-flex min-w-[4rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                        answers.q39 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                        :draggable="!!answers.q39"
                                        @dragstart="answers.q39 ? onDragStartFromSlot('q39', answers.q39) : undefined">
                                        {{ answers.q39 || '39' }}
                                    </span>
                                    <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                        @click.stop="toggleFlag(39)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>

                                <!-- Q40 -->
                                <div id="question-40" class="relative flex items-center gap-3 rounded border-l-4 border-gray-400 bg-gray-50 p-4 pr-10"
                                    @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null"
                                    @dragover="onDragOver" @drop="onDropToSlot('q40')">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-700">40</span>
                                    <p class="flex-1" :data-segment-id="45"
                                        v-html="getHighlightedSegment('He felt photography was part of the trend towards greater mechanisation.')"></p>
                                    <span :class="['inline-flex min-w-[4rem] cursor-pointer items-center justify-center rounded border px-3 py-1 text-sm font-semibold',
                                        answers.q40 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-400 bg-gray-50 text-gray-400']"
                                        :draggable="!!answers.q40"
                                        @dragstart="answers.q40 ? onDragStartFromSlot('q40', answers.q40) : undefined">
                                        {{ answers.q40 || '40' }}
                                    </span>
                                    <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                        @click.stop="toggleFlag(40)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Teleport overlays (identical to example template) ─────────────── -->
    <Teleport to="body">
        <!-- Context menu (highlight / note) -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"/>
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete highlight tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                        <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                        <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note hover tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note input modal -->
        <div v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border border-gray-900 bg-white px-3 py-0.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 py-0.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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
