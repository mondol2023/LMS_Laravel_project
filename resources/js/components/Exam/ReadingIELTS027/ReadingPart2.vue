<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted,reactive, ref, watch } from 'vue';

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

// Reading Part 2 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part2-panel-width';
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

const answers = reactive({
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q2021: [],
    q2223: [],
    q24: '',
    q25: '',
    q26: '',
});

// Text segments with their cumulative offsets in the full text
const passageText = `<strong>A</strong> <br/>
Invasive species are among the leading threats to the native wildlife of most countries, with approximately 42 percent of endangered species at risk from them. Invasive species can be any kind of organism—for example, a mammal, amphibian, fish, insect, or plant—that is not native to an ecosystem. Often, they reproduce and spread with great speed. Contrary to popular belief, a plant or animal classified as an invasive species does not necessarily originate in another country. For example, lake trout are native to the Great Lakes of North America but are considered an invasive species in Yellowstone Lake in Wyoming because they compete with 
native cutthroat trout for habitat. 
<br/>
<strong>B</strong><br/>
When a new species is introduced into an ecosystem, native wildlife may struggle to compete with it for food and other resources. Invasive species can change the food web in an ecosystem by destroying or replacing native food sources while providing little or no food for local wildlife. In extreme cases, the invader may prey on the native species. Above all, invasive species threaten biodiversity in many habitats. For instance, the climbing plant species kudzu (which is native to East Asia) can easily replace a habitat that had a wide range of plants with a monoculture consisting solely of kudzu, as has started to happen in the southeast of the USA.
<br/>
<strong>C</strong><br/>
The phenomenon is not an exclusively modern one. Humans have always transferred a variety of species from one region to another, but the development of rapid means of transportation has increased the frequency of such introductions. Aquatic organisms can be shipped across the oceans, while insects can easily get into the wooden crates that are transported around the world in this way. In addition, climate change has enabled some invasive plant species to colonize new areas. Other invasive species include pets such as snakes or turtles, which are intentionally set free into the wild.
<br/><strong>D</strong><br/>

Invasive species do not all present the same level of threat to native ecosystems and can be classified into three types. The first of these may be introduced species that can maintain themselves in a limited range of habitats without upsetting the ecological equilibrium of the area. Some scientists have even argued that, in these cases, the introduction enhances the diversity of animal and plant life in that specific area. A second group of invaders presents a greater threat because their spread is at the expense of one native species. The North American grey squirrel, for example, was introduced to the UK in 1876 largely because wealthy landowners thought it would be a fashionable and attractive addition to the local wildlife on their estates. It spread widely, leading directly to the dramatic decline in the population of the native red squirrel. However, it would appear that this is the only definite negative impact of the grey squirrel.
<br/>
<strong>E</strong><br/>
There is a third level of threat in which the dominance of the introduced species has an extremely destructive effect on the entire ecosystem. One of the most damaging examples involved a species of comb jellyfish. Native to estuaries along the western Atlantic coast from the northern United States to the Valdés Peninsula in Argentina, this species was released from a ship into the Black Sea in Eastern Europe in 1982, almost certainly by accident. The Black Sea has levels of industrial waste that are, by international standards, exceptionally high. Despite this, fishing boats were still able to catch healthy numbers of fish. But when the invading jellyfish underwent a population explosion in the space of just six years, the entire marine ecosystem was transformed, and fish numbers declined dramatically because they were in competition with the jellyfish, preying on exactly the same microscopic creatures. The jellyfish had a more serious impact on the ecosystem than the heavily polluted water.
<br/><strong>F</strong><br/>

More than a century after its introduction outside its native range on the Amazon River in South America, a plant known as a water hyacinth can be found in tropical lakes, streams, and rivers around the world. Its beauty attracted botanists seeking exotic plants for botanical gardens, and they imported it to a horticultural exhibition in New Orleans in 1884. Visitors were so impressed that they planted it in many locations during the 1880s and 1890s, resulting in the aquatic ecosystems of the southeastern United States being progressively colonized by vast, floating, dense carpets of water hyacinth. Today, it is present around the globe, damaging boat engines and even blocking cooling pipes for power plants, occasionally leading to massive blackouts. The impact of the water hyacinth on native water plants is largely unstudied, as is unfortunately the case for most ecosystems invaded by new plant species.
<br/>
<strong>G</strong><br/>
In the United States, more than 7,000 introduced species have established themselves, of which at least 15 percent cause ecological damage. As the number of invasive species expands, legislation to deal with this problem is rare or non-existent in the majority of countries. Unfortunately, ordinary people outside the scientific community have a very limited understanding of the threat posed by invasive species, which means that other environmental threats receive considerably more media attention. The introduction of new species can initially seem highly desirable, but the full extent of their impact is consistently underestimated.
<br/>
<strong>H</strong><br/>
Although ultimately, measures need to be taken at an international level, limited action is possible by individuals. One way is for people to plant native plants in their gardens rather than species from abroad. It is also useful to learn to identify invasive species and report any sightings to wildlife organizations. Regularly cleaning clothing, boots, boats, tires, and any other equipment regularly used outdoors can remove insects and plant parts that may introduce invasive species into new locations.
`;

const textSegments = ref([
  // Header
  { id: 'part2-title', text: 'Part 2', offset: 7000 },
  { id: 'part2-desc', text: 'Read the text and answer questions 14–26.', offset: 7007 },
  { id: 'part2-passage-title', text: 'Invasive species', offset: 7048 },

  { id: 'passage', text: passageText, offset: 0 },

  // Q14–19
  { id: 'q14-19-title', text: 'Questions 14-19', offset: 7080 },
  { id: 'q14-19-inst1', text: 'Reading Passage 2 has eight paragraphs, A-H.', offset: 7096 },
  { id: 'q14-19-inst2', text: 'Which paragraph contains the following information?', offset: 7140 },

  { id: 'q14-num', text: '14.', offset: 7195 },
  { id: 'q14-text', text: 'A suggestion that people have moved numerous species across the globe throughout history', offset: 7199 },

  { id: 'q15-num', text: '15.', offset: 7285 },
  { id: 'q15-text', text: 'An example of how an ecosystem can be damaged very rapidly', offset: 7289 },

  { id: 'q16-num', text: '16.', offset: 7355 },
  { id: 'q16-text', text: 'A description of what can be done to restrict the spread of invasive species', offset: 7359 },

  { id: 'q17-num', text: '17.', offset: 7435 },
  { id: 'q17-text', text: 'A reference to the lack of research on the effects of some invasive species', offset: 7439 },

  { id: 'q18-num', text: '18.', offset: 7515 },
  { id: 'q18-text', text: 'A mention of a current lack of public awareness of the problem of invasive species', offset: 7519 },

  { id: 'q19-num', text: '19.', offset: 7605 },
  { id: 'q19-text', text: 'That an introduced species may benefit a specific ecosystem', offset: 7609 },

  // Q20–21
  { id: 'q20-21-title', text: 'Questions 20-21', offset: 7685 },
  { id: 'q20-21-inst', text: 'Choose TWO letters, A-E.', offset: 7702 },

  { id: 'q20-21-q', text: 'Which TWO reasons for the spread of invasive species are mentioned in the text?', offset: 7730 },

  { id: 'opt-a', text: 'The wish to eliminate undesirable native species', offset: 7810 },
  { id: 'opt-b', text: 'The recent expansion of international trade in agricultural products', offset: 7860 },
  { id: 'opt-c', text: 'A lack of checks on some of the cargo on board ships', offset: 7935 },
  { id: 'opt-d', text: 'The deliberate release of non-native animals', offset: 7995 },
  { id: 'opt-e', text: 'An extension of their geographical range as a result of global warming', offset: 8045 },

  // Q22–23
  { id: 'q22-23-title', text: 'Questions 22-23', offset: 8125 },
  { id: 'q22-23-inst', text: 'Choose TWO letters, A-E.', offset: 8143 },

  { id: 'q22-23-q', text: 'Which TWO statements does the writer make about the water hyacinth?', offset: 8170 },

  { id: 'opt22-a', text: 'It is native to almost every region of the world.', offset: 8245 },
  { id: 'opt22-b', text: 'It was brought to North America in the late nineteenth century.', offset: 8295 },
  { id: 'opt22-c', text: 'Its beauty has led people to ignore the negative effects it has.', offset: 8360 },
  { id: 'opt22-d', text: 'Its spread has caused some practical problems in recent years.', offset: 8425 },
  { id: 'opt22-e', text: 'Scientists recommend its introduction to the USA.', offset: 8490 },

  // Q24–26
  { id: 'q24-26-title', text: 'Questions 24-26', offset: 8555 },
  { id: 'q24-26-inst', text: 'Complete the sentences below.', offset: 8573 },

  { id: 'q24-num', text: '24.', offset: 8605 },
  { id: 'q24-text', text: 'Kudzu has reduced the of certain areas in the south east of the USA.', offset: 8609 },

  { id: 'q25-num', text: '25.', offset: 8675 },
  { id: 'q25-text', text: 'Some introduced species present a low level of threat if they remain within a small area and do not disturb the of the surrounding ecosystem.', offset: 8679 },

  { id: 'q26-num', text: '26.', offset: 8800 },
  { id: 'q26-text', text: 'The effect of invasive jellyfish in the Black Sea was greater than that from factories.', offset: 8804 },
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

function handleTwoChoiceCheckbox(field, value, event) {
    if (!answers[field]) answers[field] = [];
    
    if (event.target.checked) {
        // If already 2 selected, drop the earliest pick before adding new one
        if (answers[field].length >= 2) {
            answers[field].shift();
        }
        answers[field] = [...answers[field], value];
    } else {
        answers[field] = answers[field].filter(v => v !== value);
    }
}

// Expose methods for parent component
const getAnswers = () => {
    return answers;
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

const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = textSegments.value.find(s => s.id === segmentId);
    if (!segment) return '';

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segment.text);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(h =>
        h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(n =>
        n.start < segmentEnd && n.end > segmentOffset
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segment.text;
    }

    const annotations = [
        ...overlappingHighlights.map(h => ({
            start: h.start_offset, end: h.end_offset,
            type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map(n => ({
            start: n.start, end: n.end,
            type: 'note' as const, color: 'blue', id: n.id,
        })),
    ].sort((a, b) => b.start - a.start);

    let result = segment.text;

    for (const annotation of annotations) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd   = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);
        if (plainStart >= plainEnd) continue;

        const htmlStart = plainToHtmlOffset(result, plainStart);
        const htmlEnd   = plainToHtmlOffset(result, plainEnd);
        const before    = result.substring(0, htmlStart);
        const annotated = result.substring(htmlStart, htmlEnd);
        const after     = result.substring(htmlEnd);

        if (annotation.type === 'note') {
            result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
        } else {
            result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
        }
    }

    return result;
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
        <!-- Part 2 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('part2-title')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegmentById('part2-desc')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" v-html="getHighlightedSegmentById('part2-passage-title')"></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="">
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
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 14-19: Paragraph matching dropdowns (A-H) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q14-19-title" v-html="getHighlightedSegmentById('q14-19-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q14-19-inst1" v-html="getHighlightedSegmentById('q14-19-inst1')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q14-19-inst2" v-html="getHighlightedSegmentById('q14-19-inst2')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Question 14 -->
                                    <div
                                        id="question-14"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q14-num" v-html="getHighlightedSegmentById('q14-num')"></span>
                                            <select
                                                v-model="answers.q14"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="q14-text" v-html="getHighlightedSegmentById('q14-text')"></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 15 -->
                                    <div
                                        id="question-15"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 15"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q15-num" v-html="getHighlightedSegmentById('q15-num')"></span>
                                            <select
                                                v-model="answers.q15"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="q15-text" v-html="getHighlightedSegmentById('q15-text')"></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 16 -->
                                    <div
                                        id="question-16"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 16"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q16-num" v-html="getHighlightedSegmentById('q16-num')"></span>
                                            <select
                                                v-model="answers.q16"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="q16-text" v-html="getHighlightedSegmentById('q16-text')"></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 17 -->
                                    <div
                                        id="question-17"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 17"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q17-num" v-html="getHighlightedSegmentById('q17-num')"></span>
                                            <select
                                                v-model="answers.q17"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="q17-text" v-html="getHighlightedSegmentById('q17-text')"></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 18 -->
                                    <div
                                        id="question-18"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 18"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q18-num" v-html="getHighlightedSegmentById('q18-num')"></span>
                                            <select
                                                v-model="answers.q18"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="q18-text" v-html="getHighlightedSegmentById('q18-text')"></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="toggleFlag(18)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 19 -->
                                    <div
                                        id="question-19"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 19"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q19-num" v-html="getHighlightedSegmentById('q19-num')"></span>
                                            <select
                                                v-model="answers.q19"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="q19-text" v-html="getHighlightedSegmentById('q19-text')"></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                            @click.stop="toggleFlag(19)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 20-21: Choose TWO checkboxes -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q20-21-title" v-html="getHighlightedSegmentById('q20-21-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q20-21-inst" v-html="getHighlightedSegmentById('q20-21-inst')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q20-21-q" v-html="getHighlightedSegmentById('q20-21-q')"></span>
                                    </p>
                                    <p class="mb-3 text-xs text-gray-500 italic">Select exactly 2 options. Selecting a third will deselect the earliest choice.</p>
                                </div>

                                <div
                                    id="question-20-21"
                                    class="relative space-y-2 bg-white p-2"
                                    @mouseenter="hoveredQuestion = 20"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <label
                                        v-for="opt in ['A','B','C','D','E']"
                                        :key="'q2021-' + opt"
                                        class="flex cursor-pointer items-start gap-3 rounded border p-2 transition-colors select-none"
                                        :class="answers.q2021 && answers.q2021.includes(opt) ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:border-gray-400 hover:bg-gray-50'"
                                    >
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black"
                                            :checked="answers.q2021 && answers.q2021.includes(opt)"
                                            @change="handleTwoChoiceCheckbox('q2021', opt, $event)"
                                        />
                                        <span class="text-sm text-gray-900">
                                            <span class="font-bold mr-1">{{ opt }}</span>
                                            <span v-html="getHighlightedSegmentById('opt-' + opt.toLowerCase())"></span>
                                        </span>
                                    </label>
                                    <button
                                        v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                        @click.stop="toggleFlag(20)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Questions 22-23: Choose TWO checkboxes -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q22-23-title" v-html="getHighlightedSegmentById('q22-23-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q22-23-inst" v-html="getHighlightedSegmentById('q22-23-inst')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q22-23-q" v-html="getHighlightedSegmentById('q22-23-q')"></span>
                                    </p>
                                    <p class="mb-3 text-xs text-gray-500 italic">Select exactly 2 options. Selecting a third will deselect the earliest choice.</p>
                                </div>

                                <div
                                    id="question-22-23"
                                    class="relative space-y-2 bg-white p-2"
                                    @mouseenter="hoveredQuestion = 22"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <label
                                        v-for="opt in ['A','B','C','D','E']"
                                        :key="'q2223-' + opt"
                                        class="flex cursor-pointer items-start gap-3 rounded border p-2 transition-colors select-none"
                                        :class="answers.q2223 && answers.q2223.includes(opt) ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:border-gray-400 hover:bg-gray-50'"
                                    >
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black"
                                            :checked="answers.q2223 && answers.q2223.includes(opt)"
                                            @change="handleTwoChoiceCheckbox('q2223', opt, $event)"
                                        />
                                        <span class="text-sm text-gray-900">
                                            <span class="font-bold mr-1">{{ opt }}</span>
                                            <span v-html="getHighlightedSegmentById('opt22-' + opt.toLowerCase())"></span>
                                        </span>
                                    </label>
                                    <button
                                        v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                        @click.stop="toggleFlag(22)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Questions 24-26: Complete the sentences (fill-in-the-blank) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q24-26-title" v-html="getHighlightedSegmentById('q24-26-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q24-26-inst" v-html="getHighlightedSegmentById('q24-26-inst')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Question 24 -->
                                    <div
                                        id="question-24"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 24"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q24-num" v-html="getHighlightedSegmentById('q24-num')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span data-segment-id="q24-text" v-html="getHighlightedSegmentById('q24-text').replace('___', '')"></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 mt-1">
                                            <input
                                                v-model="answers.q24"
                                                type="text"
                                                class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px"
                                                placeholder="24"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                            @click.stop="toggleFlag(24)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 25 -->
                                    <div
                                        id="question-25"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 25"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q25-num" v-html="getHighlightedSegmentById('q25-num')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span data-segment-id="q25-text" v-html="getHighlightedSegmentById('q25-text')"></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 mt-1">
                                            <input
                                                v-model="answers.q25"
                                                type="text"
                                                class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px"
                                                placeholder="25"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                            @click.stop="toggleFlag(25)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 26 -->
                                    <div
                                        id="question-26"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 26"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q26-num" v-html="getHighlightedSegmentById('q26-num')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span data-segment-id="q26-text" v-html="getHighlightedSegmentById('q26-text')"></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 mt-1">
                                            <input
                                                v-model="answers.q26"
                                                type="text"
                                                class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px"
                                                placeholder="26"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                            @click.stop="toggleFlag(26)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
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

        <!-- Context Menu for Highlighting -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
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

            <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
                <div
                    class="delete-highlight-tooltip fixed z-9999"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                >
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
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

            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
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

            <div
                v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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
                    <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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
</style>
