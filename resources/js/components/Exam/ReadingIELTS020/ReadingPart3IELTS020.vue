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

// Passage text
const passageText =
    ref(`600 years ago, roller coaster pioneers never would have imagined the advancements that have been made to create the roller coasters of today. The tallest and fastest roller coaster in the world is the Kingda Ka, a coaster in New Jersey that launches its passengers from zero to 128 miles per hour in 3.5 seconds. It then heaves its riders skyward at a 90-degree angle (straight up) until it reaches a height of 456 feet, over one and a half football fields, above the ground, before dropping another 418 feet. With that said, roller coasters are about more than just speed and height, they are about the creativity of the designers that build them, each coaster having its own unique way of producing intense thrills at a lesser risk than the average car ride. Roller coasters have evolved drastically over the years, from their primitive beginnings as Russian ice slides, to the metal monsters of today. Their combination of creativity and structural elements make them one of the purest forms of architecture.

At first glance, a roller coaster is something like a passenger train. It consists of a series of connected cars that move on tracks. But unlike a passenger train, a roller coaster has no engine or power source of its own. For most of the ride, the train is moved by gravity and momentum. To build up this momentum, you need to get the train to the top of the first hill or give it a powerful launch. The traditional lifting mechanism is a long length of chain running up the hill under the track. The chain is fastened in a loop, which is wound around a gear at the top of the hill and another one at the bottom of the hill. The gear at the bottom of the hill is turned by a simple motor. This turns the chain loop so that it continually moves up the hill like a long conveyer belt. The coaster cars grip onto the chain with several chain dogs, sturdy hinged hooks. When the train rolls to the bottom of the hill, the dogs catches onto the chain links. Once the chain dog is hooked, the chain simply pulls the train to the top of the hill. At the summit, the chain dog is released and the train starts its descent down the hill.

Roller coasters have a long, fascinating history. The direct ancestors of roller coasters were monumental ice slides - long, steep wooden slides covered in ice, some as high as 70 feet - that were popular in Russia in the 16th and 17th centuries. Riders shot down the slope in sleds made out of wood or blocks of ice, crash-landing in a sand pile. Coaster historians diverge on the exact evolution of these ice slides into actual rolling carts. The most widespread account is that a few entrepreneurial Frenchmen imported the ice slide idea to France. The warmer climate of France tended to melt the ice, so the French started building waxed slides instead, eventually adding wheels to the sleds. In 1817, the Russes a Belleville (Russian Mountains of Belleville) became the first roller coaster where the train was attached to the track (in this case, the train axle fit into a carved groove). The French continued to expand on this idea, coming up with more complex track layouts, with multiple cars and all sorts of twists and turns.

In comparison to the world's first roller coaster, there is perhaps an even greater debate over what was America's first true coaster. Many will say that it is Pennsylvania's own Maunch Chunk-Summit Hill and Switch Back Railroad. The Maunch Chunk Summit Hill and Switch Back Railroad was originally America's second railroad, and considered by many to be the greatest coaster of all time. Located in the Lehigh valley, it was originally used to transport coal from the top of Mount Pisgah to the bottom of Mount Jefferson, until Josiah White, a mining entrepreneur, had the idea of turning it into a part-time thrill ride. Because of its immediate popularity, it soon became strictly a passenger train. A steam engine would haul passengers to the top of the mountain, before letting them coast back down, with speeds rumored to reach 100 miles per hour! The reason that it was called a switch back railroad, a switch back track was located at the top - where the steam engine would let the riders coast back down. This type of track featured a dead end where the steam engine would detach its cars, allowing riders to coast down backwards. The railway went through a couple of minor track changes and name changes over the years, but managed to last from 1829 to 1937, over 100 years.

The coaster craze in America was just starting to build. The creation of the Switch Back Railway, by La Marcus Thompson, gave roller coasters national attention. Originally built at New York's Coney Island in 1884, Switch Back Railways began popping up all over the country. The popularity of these rides may puzzle the modern-day thrill seeker, due to the mild ride they gave in comparison to the modern-day roller coaster. Guests would pay a nickel to wait in line up to five hours just to go down a pair of side-by-side tracks with gradual hills that vehicles coasted down at a top speed around six miles per hour. Regardless, Switchback Railways were very popular, and sparked many people, including Thompson, to design coasters that were bigger and better.

The 1910s and 1920s were probably the best decade that the roller coaster has ever seen. The new wave of technology, such as the "unstop wheels", an arrangement that kept a coaster's wheels to its tracks by resisted high gravitational forces, showed coasters a realm of possibilities that has never been seen before. In 1919, North America alone had about 1,500 roller coasters, a number that was rising rampantly. Then, the Great Depression gave a crushing blow to amusement parks all over America. As bad as it was, amusement parks had an optimistic look on the future in the late 1930s. But, in 1942 roller coasters could already feel the effects of World War Two, as they were forced into a shadow of neglect. Most, nearly all of America's roller coasters were shut down. To this very day, the number of roller coaster in America is just a very tiny fraction of the amount of roller coasters in the 1920s`);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    { id: 'part-header', text: 'Part 3', offset: -100 },
{ id: 'part-instructions', text: 'Read the text and answer questions 27–40.', offset: -93 },
{ id: 'header-title', text: 'Roller Coaster', offset: -51 },

{
    id: 'passage',
    text: passageText.value,
    offset: 0,
},

// Questions 27-30 (offsets start at 10000 to ensure no overlap with passage)
{ id: 'q27-30-title', text: 'Questions 27-30', offset: 10000 },
{
    id: 'q27-30-instructions',
    text: 'Answer the questions below.',
    offset: 10030,
},
{
    id: 'q27-30-inst2',
    text: 'A diagram that explains the mechanism and working principles of roller coaster. Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the passage for each answer.',
    offset: 10080,
},
{ id: 'q27-30-subheading', text: 'Traditional lifting mechanism', offset: 10280 },

// Q27
{ id: 'q27-pre1', text: 'Traditional roller coaster\'s lifting force depends on a long time of', offset: 10330 },
{ id: 'q27-num', text: '27', offset: 10410 },
{ id: 'q27-post1', text: 'for climbing up', offset: 10415 },

// Q28
{ id: 'q28-pre1', text: 'which is connected firmly to a', offset: 10445 },
{ id: 'q28-num', text: '28', offset: 10485 },
{ id: 'q28-post1', text: 'shape track', offset: 10490 },

// Q29
{ id: 'q29-pre1', text: 'There are both', offset: 10520 },
{ id: 'q29-num', text: '29', offset: 10545 },
{ id: 'q29-post1', text: 'on the top and underneath the hill', offset: 10550 },

// Q30
{ id: 'q30-pre1', text: 'and it is powered by a', offset: 10600 },
{ id: 'q30-num', text: '30', offset: 10630 },
{ id: 'q30-post1', text: 'when it takes a turn', offset: 10635 },

// Questions 31-36 (offsets start at 11000 to ensure no overlap)
{ id: 'q31-36-title', text: 'Questions 31-36', offset: 11000 },
{ id: 'q31-36-inst1', text: 'Summary', offset: 11030 },
{
    id: 'q31-36-inst2',
    text: 'Complete the following summary of the paragraphs of Reading Passage, using NO MORE THAN TWO WORDS from the Reading Passage for each answer.',
    offset: 11060,
},
{
    id: 'q31-36-inst3',
    text: 'Write your answers in boxes 31-36 on your answer sheet.',
    offset: 11210,
},

// Q31
{ id: 'sum-p1-1', text: 'The first roller coaster was perhaps originated from Russia which is wrapped up by', offset: 11280 },
{ id: 'q31-num', text: '31', offset: 11380 },

// Q32
{ id: 'sum-p1-2', text: 'which was introduced into France, and it was modified to', offset: 11390 },
{ id: 'q32-num', text: '32', offset: 11470 },

// Q33
{ id: 'sum-p1-3', text: 'because temperature there would', offset: 11480 },
{ id: 'q33-num', text: '33', offset: 11530 },
{ id: 'sum-p1-4', text: 'the ice', offset: 11535 },

// Q34
{ id: 'sum-p1-5', text: 'This time', offset: 11555 },
{ id: 'q34-num', text: '34', offset: 11580 },
{ id: 'sum-p1-6', text: 'were installed on the board', offset: 11585 },

// Q35
{ id: 'sum-p2-1', text: 'In America, the first roller coaster was said to appear in Pennsylvania, it was actually a railroad which was designed to send', offset: 11630 },
{ id: 'q35-num', text: '35', offset: 11780 },

// Q36
{ id: 'sum-p2-2', text: 'Josiah White turned it into a thrill ride, it was also called switch back track and a', offset: 11790 },
{ id: 'q36-num', text: '36', offset: 11880 },
{ id: 'sum-p2-3', text: 'there allowed riders to slide downward back again', offset: 11885 },

// Questions 37-40 (offsets start at 12000 to ensure no overlap)
{ id: 'q37-40-title', text: 'Questions 37-40', offset: 12000 },
{
    id: 'q37-40-inst',
    text: 'Do the following statements agree with the information given in Reading Passage 3?',
    offset: 12030,
},
{ id: 'q37-40-inst-yes', text: 'YES', offset: 12120 },
{ id: 'q37-40-inst-yes-desc', text: 'if the statement is true', offset: 12130 },
{ id: 'q37-40-inst-no', text: 'NO', offset: 12170 },
{ id: 'q37-40-inst-no-desc', text: 'if the statement is false', offset: 12180 },
{ id: 'q37-40-inst-ng', text: 'NOT GIVEN', offset: 12230 },
{ id: 'q37-40-inst-ng-desc', text: 'if the information is not given in the passage.', offset: 12250 },

// Q37
{ id: 'q37-num', text: '37.', offset: 12320 },
{ id: 'q37', text: 'The most exciting roller coaster in the world is in New Jersey.', offset: 12335 },

// Q38
{ id: 'q38-num', text: '38.', offset: 12420 },
{ id: 'q38', text: 'French added more innovation on Russian ice slide including both cars and tracks.', offset: 12435 },

// Q39
{ id: 'q39-num', text: '39.', offset: 12530 },
{ id: 'q39', text: 'Switch Back Railways began to gain popularity since its first construction in New York.', offset: 12545 },

// Q40
{ id: 'q40-num', text: '40.', offset: 12650 },
{ id: 'q40', text: 'The Great Depression affected amusement parks yet did not shake the significant role of US roller coasters in the world.', offset: 12665 },
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

// Helper to get a highlighted version of a specific text segment by segment id
const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';
    return getHighlightedSegment(segment.text);
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
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50;
            const menuY = rect.top - menuHeight - 8;

            const viewportWidth = window.innerWidth;
            const menuWidth = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection && range) {
                let targetSpan: Node | null = range.startContainer;

                // Walk up to find element node
                while (targetSpan && targetSpan.nodeType !== Node.ELEMENT_NODE) {
                    targetSpan = targetSpan.parentNode;
                }

                // Find element with data-segment-id or select-text class
                let segmentEl: Element | null = null;
                let currentNode: Node | null = targetSpan;

                while (currentNode && currentNode !== document.body) {
                    if (currentNode.nodeType === Node.ELEMENT_NODE) {
                        const el = currentNode as Element;
                        if (el.hasAttribute('data-segment-id')) {
                            segmentEl = el;
                            break;
                        }
                    }
                    currentNode = currentNode.parentNode;
                }

                if (segmentEl) {
                    const segmentId = segmentEl.getAttribute('data-segment-id');
                    const segment = textSegments.value.find((s) => s.id === segmentId);

                    if (segment) {
                        const normalizedSelected = selected.trim();

                        const preSelectionRange = document.createRange();
                        preSelectionRange.selectNodeContents(segmentEl);
                        preSelectionRange.setEnd(range.startContainer, range.startOffset);

                        const plainTextOffset = preSelectionRange.toString().length;

                        const startOffset = segment.offset + plainTextOffset;
                        const endOffset = startOffset + normalizedSelected.length;

                        selectedText.value = normalizedSelected;
                        selectionRange.value = { start: startOffset, end: endOffset };
                    }
                } else {
                    // Fallback: try to find segment by text content match
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
                        const spanText = (targetSpan as Element).textContent || '';
                        const isPassageText = (targetSpan as Element).classList?.contains('text-lg') || spanText.length > 500;

                        let segment = null;

                        if (isPassageText) {
                            segment = textSegments.value.find((s) => s.id === 'passage');
                        } else {
                            segment = textSegments.value.find((s) => s.text === spanText.trim());

                            if (!segment) {
                                segment = textSegments.value.find((s) => {
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

                            const preSelectionRange = document.createRange();
                            preSelectionRange.selectNodeContents(targetSpan as Element);
                            preSelectionRange.setEnd(range.startContainer, range.startOffset);

                            const plainTextOffset = preSelectionRange.toString().length;

                            const startOffset = segment.offset + plainTextOffset;
                            const endOffset = startOffset + normalizedSelected.length;

                            selectedText.value = normalizedSelected;
                            selectionRange.value = { start: startOffset, end: endOffset };
                        }
                    }
                }
            }
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
        const input = document.querySelector<HTMLTextAreaElement>('.note-input-field');
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

onMounted(() => {
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
    <div class="pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 part-header-box border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2" @mouseup="handlePassageTextSelect"
            @click="handleContentClick">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header"
                v-html="getHighlightedSegment('Part 3')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions"
                v-html="getHighlightedSegment('Read the text and answer questions 27–40.')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="header-title"
                                v-html="getHighlightedSegment('Roller Coaster')"></span>
                        </h2>
                    </div>

                    <div class="space-y-2" :style="contentZoom">
                        <div ref="passageTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick"
                            class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span class="text-lg select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[3].text)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div ref="questionsTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick"
                        class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 27-30: Diagram Completion -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-lg font-bold text-gray-900">
                <span class="select-text" data-segment-id="q27-30-title" v-html="getHighlightedSegmentById('q27-30-title')"></span>
            </h3>
        </div>
        <p class="text-sm font-semibold text-gray-800 mb-1">
            <span class="select-text" data-segment-id="q27-30-instructions" v-html="getHighlightedSegmentById('q27-30-instructions')"></span>
        </p>
        <p class="text-sm leading-relaxed text-gray-700 mb-4">
            <span class="select-text" data-segment-id="q27-30-inst2" v-html="getHighlightedSegmentById('q27-30-inst2')"></span>
        </p>
        <p class="text-sm font-bold text-gray-900 mb-4">
            <span class="select-text" data-segment-id="q27-30-subheading" v-html="getHighlightedSegmentById('q27-30-subheading')"></span>
        </p>
    </div>

    <!-- Diagram Image -->
    <div class="mb-6 flex justify-center">
        <img
            src="/images/reading/IELTS020.png"
            alt="Roller Coaster Lifting Mechanism Diagram"
            class="w-full h-auto max-w-xl rounded border border-gray-200"
        />
    </div>

    <!-- Q27 -->
    <div id="question-27" class="mb-2" @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
        <p class="text-sm leading-relaxed text-gray-800">
            <span class="font-semibold text-gray-900">(1) </span>
            <span class="select-text" data-segment-id="q27-pre1" v-html="getHighlightedSegmentById('q27-pre1')"></span>
            <span class="inline-flex items-center align-middle">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q27 }">
                    <span class="select-text" data-segment-id="q27-num" v-html="getHighlightedSegmentById('q27-num')"></span>
                    <input v-model="answers.q27" type="text" placeholder=""
                        class="w-16 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button with fixed width container -->
                <span class="inline-block w-7 ml-1">
                    <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)" @click.stop="toggleFlag(27)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(27) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
            <span class="select-text" data-segment-id="q27-post1" v-html="getHighlightedSegmentById('q27-post1')"></span>
        </p>
    </div>

    <!-- Q28 -->
    <div id="question-28" class="mb-2" @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
        <p class="text-sm leading-relaxed text-gray-800">
            <span class="select-text" data-segment-id="q28-pre1" v-html="getHighlightedSegmentById('q28-pre1')"></span>
            <span class="inline-flex items-center align-middle">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q28 }">
                    <span class="select-text" data-segment-id="q28-num" v-html="getHighlightedSegmentById('q28-num')"></span>
                    <input v-model="answers.q28" type="text" placeholder=""
                        class="w-16 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button with fixed width container -->
                <span class="inline-block w-7 ml-1">
                    <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)" @click.stop="toggleFlag(28)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(28) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
            <span class="select-text" data-segment-id="q28-post1" v-html="getHighlightedSegmentById('q28-post1')"></span>
        </p>
    </div>

    <!-- Q29 -->
    <div id="question-29" class="mb-2" @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
        <p class="text-sm leading-relaxed text-gray-800">
            <span class="font-semibold text-gray-900">(2) </span>
            <span class="select-text" data-segment-id="q29-pre1" v-html="getHighlightedSegmentById('q29-pre1')"></span>
            <span class="inline-flex items-center align-middle">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q29 }">
                    <span class="select-text" data-segment-id="q29-num" v-html="getHighlightedSegmentById('q29-num')"></span>
                    <input v-model="answers.q29" type="text" placeholder=""
                        class="w-16 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button with fixed width container -->
                <span class="inline-block w-7 ml-1">
                    <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)" @click.stop="toggleFlag(29)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(29) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
            <span class="select-text" data-segment-id="q29-post1" v-html="getHighlightedSegmentById('q29-post1')"></span>
        </p>
    </div>

    <!-- Q30 -->
    <div id="question-30" class="mb-2" @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
        <p class="text-sm leading-relaxed text-gray-800">
            <span class="select-text" data-segment-id="q30-pre1" v-html="getHighlightedSegmentById('q30-pre1')"></span>
            <span class="inline-flex items-center align-middle">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q30 }">
                    <span class="select-text" data-segment-id="q30-num" v-html="getHighlightedSegmentById('q30-num')"></span>
                    <input v-model="answers.q30" type="text" placeholder=""
                        class="w-16 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button with fixed width container -->
                <span class="inline-block w-7 ml-1">
                    <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)" @click.stop="toggleFlag(30)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(30) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
            <span class="select-text" data-segment-id="q30-post1" v-html="getHighlightedSegmentById('q30-post1')"></span>
        </p>
    </div>
</div>

<!-- Questions 31-36: Summary Completion -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-lg font-bold text-gray-900">
                <span class="select-text" data-segment-id="q31-36-title"
                    v-html="getHighlightedSegmentById('q31-36-title')"></span>
            </h3>
        </div>
        <p class="text-base font-semibold text-gray-900 mb-1">
            <span class="select-text" data-segment-id="q31-36-inst1" v-html="getHighlightedSegmentById('q31-36-inst1')"></span>
        </p>
        <p class="text-sm leading-relaxed text-gray-700 mb-1">
            <span class="select-text" data-segment-id="q31-36-inst2" v-html="getHighlightedSegmentById('q31-36-inst2')"></span>
        </p>
        <p class="text-sm leading-relaxed text-gray-700 mb-4">
            <span class="select-text" data-segment-id="q31-36-inst3" v-html="getHighlightedSegmentById('q31-36-inst3')"></span>
        </p>
    </div>

    <!-- Single continuous summary paragraph -->
    <div class="bg-white p-4 rounded border border-gray-200">
        <!-- Q31 -->
        <div id="question-31" class="inline" @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
            <span class="select-text" data-segment-id="sum-p1-1" v-html="getHighlightedSegmentById('sum-p1-1')"></span>
            <span class="inline-flex items-center align-middle relative">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q31 }">
                    <span class="select-text" data-segment-id="q31-num" v-html="getHighlightedSegmentById('q31-num')"></span>
                    <input v-model="answers.q31" type="text" placeholder=""
                        class="w-20 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button - fixed width container to prevent text shift -->
                <span class="inline-block w-6 ml-1">
                    <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)" @click.stop="toggleFlag(31)"
                        class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
        </div>

        <!-- Q32 -->
        <div id="question-32" class="inline" @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
            <span class="select-text" data-segment-id="sum-p1-2" v-html="getHighlightedSegmentById('sum-p1-2')"></span>
            <span class="inline-flex items-center align-middle relative">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q32 }">
                    <span class="select-text" data-segment-id="q32-num" v-html="getHighlightedSegmentById('q32-num')"></span>
                    <input v-model="answers.q32" type="text" placeholder=""
                        class="w-20 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button - fixed width container to prevent text shift -->
                <span class="inline-block w-6 ml-1">
                    <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)" @click.stop="toggleFlag(32)"
                        class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(32) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
        </div>

        <!-- Q33 -->
        <div id="question-33" class="inline" @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null">
            <span class="select-text" data-segment-id="sum-p1-3" v-html="getHighlightedSegmentById('sum-p1-3')"></span>
            <span class="inline-flex items-center align-middle relative">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q33 }">
                    <span class="select-text" data-segment-id="q33-num" v-html="getHighlightedSegmentById('q33-num')"></span>
                    <input v-model="answers.q33" type="text" placeholder=""
                        class="w-20 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button - fixed width container to prevent text shift -->
                <span class="inline-block w-6 ml-1">
                    <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)" @click.stop="toggleFlag(33)"
                        class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
            <span class="select-text" data-segment-id="sum-p1-4" v-html="getHighlightedSegmentById('sum-p1-4')"></span>
        </div>

        <!-- Q34 -->
        <div id="question-34" class="inline" @mouseenter="hoveredQuestion = 34" @mouseleave="hoveredQuestion = null">
            <span class="select-text" data-segment-id="sum-p1-5" v-html="getHighlightedSegmentById('sum-p1-5')"></span>
            <span class="inline-flex items-center align-middle relative">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q34 }">
                    <span class="select-text" data-segment-id="q34-num" v-html="getHighlightedSegmentById('q34-num')"></span>
                    <input v-model="answers.q34" type="text" placeholder=""
                        class="w-20 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button - fixed width container to prevent text shift -->
                <span class="inline-block w-6 ml-1">
                    <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)" @click.stop="toggleFlag(34)"
                        class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(34) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
            <span class="select-text" data-segment-id="sum-p1-6" v-html="getHighlightedSegmentById('sum-p1-6')"></span>
        </div>

        <!-- Q35 -->
        <div id="question-35" class="inline" @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null">
            <span class="select-text" data-segment-id="sum-p2-1" v-html="getHighlightedSegmentById('sum-p2-1')"></span>
            <span class="inline-flex items-center align-middle relative">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q35 }">
                    <span class="select-text" data-segment-id="q35-num" v-html="getHighlightedSegmentById('q35-num')"></span>
                    <input v-model="answers.q35" type="text" placeholder=""
                        class="w-20 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button - fixed width container to prevent text shift -->
                <span class="inline-block w-6 ml-1">
                    <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)" @click.stop="toggleFlag(35)"
                        class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
        </div>

        <!-- Q36 -->
        <div id="question-36" class="inline" @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null">
            <span class="select-text" data-segment-id="sum-p2-2" v-html="getHighlightedSegmentById('sum-p2-2')"></span>
            <span class="inline-flex items-center align-middle relative">
                <span class="inline-flex min-w-24 items-center justify-center border-2 border-gray-400 px-1 py-0.5"
                    :class="{ 'border-gray-900': answers.q36 }">
                    <span class="select-text" data-segment-id="q36-num" v-html="getHighlightedSegmentById('q36-num')"></span>
                    <input v-model="answers.q36" type="text" placeholder=""
                        class="w-20 bg-transparent text-center text-sm focus:outline-none "
                        maxlength="15" />
                </span>
                <!-- Flag button - fixed width container to prevent text shift -->
                <span class="inline-block w-6 ml-1">
                    <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)" @click.stop="toggleFlag(36)"
                        class="flex h-6 w-6 items-center justify-center rounded border transition-all bg-white"
                        :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
            </span>
            <span class="select-text" data-segment-id="sum-p2-3" v-html="getHighlightedSegmentById('sum-p2-3')"></span>
        </div>
    </div>
</div>

                            <!-- Questions 37-40: YES/NO/NOT GIVEN -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="q37-40-title"
                                                v-html="getHighlightedSegment('Questions 37-40')"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-2">
                                        <span class="select-text" data-segment-id="q37-40-inst"
                                            v-html="getHighlightedSegment('Do the following statements agree with the information given in Reading Passage 3?')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text font-bold mx-0.5 text-gray-900"
                                            data-segment-id="q37-40-inst-yes"
                                            v-html="getHighlightedSegment('YES')"></span>
                                        <span class="select-text" data-segment-id="q37-40-inst-yes-desc"
                                            v-html="getHighlightedSegment('if the statement is true')"></span>
                                        <br />
                                        <span class="select-text font-bold mx-0.5 text-gray-900"
                                            data-segment-id="q37-40-inst-no"
                                            v-html="getHighlightedSegment('NO')"></span>
                                        <span class="select-text" data-segment-id="q37-40-inst-no-desc"
                                            v-html="getHighlightedSegment('if the statement is false')"></span>
                                        <br />
                                        <span class="select-text font-bold mx-0.5 text-gray-900"
                                            data-segment-id="q37-40-inst-ng"
                                            v-html="getHighlightedSegment('NOT GIVEN')"></span>
                                        <span class="select-text" data-segment-id="q37-40-inst-ng-desc"
                                            v-html="getHighlightedSegment('if the information is not given in the passage.')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 37 -->
                                    <div id="question-37" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                data-segment-id="q37-num" v-html="getHighlightedSegment('37.')"></span>
                                            <span class="text-base text-gray-900 select-text" data-segment-id="q37"
                                                v-html="getHighlightedSegment('The most exciting roller coaster in the world is in New Jersey.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q37" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q37" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q37" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                            @click.stop="toggleFlag(37)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 38 -->
                                    <div id="question-38" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                data-segment-id="q38-num" v-html="getHighlightedSegment('38.')"></span>
                                            <span class="text-base text-gray-900 select-text" data-segment-id="q38"
                                                v-html="getHighlightedSegment('French added more innovation on Russian ice slide including both cars and tracks.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                            @click.stop="toggleFlag(38)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 39 -->
                                    <div id="question-39" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 39" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                data-segment-id="q39-num" v-html="getHighlightedSegment('39.')"></span>
                                            <span class="text-base text-gray-900 select-text" data-segment-id="q39"
                                                v-html="getHighlightedSegment('Switch Back Railways began to gain popularity since its first construction in New York.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                            @click.stop="toggleFlag(39)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 40 -->
                                    <div id="question-40" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                data-segment-id="q40-num" v-html="getHighlightedSegment('40.')"></span>
                                            <span class="text-base text-gray-900 select-text" data-segment-id="q40"
                                                v-html="getHighlightedSegment('The Great Depression affected amusement parks yet did not shake the significant role of US roller coasters in the world.')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                            @click.stop="toggleFlag(40)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
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
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText
                        }}</span>
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
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl" :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @click.stop>
            <div class="mb-3">
                <p
                    class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"</p>
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

/* Prevent selection from bleeding between panels */
.passage-panel * {
    user-select: none;
    -webkit-user-select: none;
}

.passage-panel .select-text,
.passage-panel .select-text * {
    user-select: text;
    -webkit-user-select: text;
}

.answer-panel * {
    user-select: none;
    -webkit-user-select: none;
}

.answer-panel .select-text,
.answer-panel .select-text *,
.answer-panel input {
    user-select: text;
    -webkit-user-select: text;
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
