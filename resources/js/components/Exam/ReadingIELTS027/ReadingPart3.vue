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

// Drag and drop functionality for questions 35-40
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const summaryOptions = [
    { value: 'A', label: 'action' },
    { value: 'B', label: 'controls' },
    { value: 'C', label: 'failure' },
    { value: 'D', label: 'fish catches' },
    { value: 'E', label: 'fish processing' },
    { value: 'F', label: 'fishing techniques' },
    { value: 'G', label: 'large boats' },
    { value: 'H', label: 'marine reserves' },
    { value: 'I', label: 'the land' },
    { value: 'J', label: 'the past' },
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
const getOptionLabel = (value: string): string => {
    const option = summaryOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Computed property to filter out used options
const availableOptions = computed(() => {
    const usedOptions = [
        answers.value.q35,
        answers.value.q36,
        answers.value.q37,
        answers.value.q38,
        answers.value.q39,
        answers.value.q40,
    ].filter(Boolean);
    return summaryOptions.filter((opt) => !usedOptions.includes(opt.value));
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

// Highlight handlers
const passageText =
    ref(`<strong>A</strong>
Fun is becoming a tricky issue for ride designers. In order to increase excitement, they have been ramping up the accelerations to create the most dizzying forces possible. But getting it right is far from easy. Err on the side of caution and people won't bother with a second ride. Go too far, however, and they may not be able to come back for more. The problem is that true innovation has been lacking for a while, and fairground rides have become more about survival than actual enjoyment. So if our thrill-seeking bodies can really take no more, what’s going to keep dragging us back to amusement parks? Creating something new and exciting, yet safe, is going to take some careful thought.

<strong>B</strong>
When the Disney Corporation asked German designer Walter Stengel to design a giant loop ride for them in the 1970s, he went to NASA, the aeronautics and space foundation, to discover the effects of sustained acceleration on the pilots. NASA’s research suggests that the maximum level we can endure is 9 g, g being the standard unit of acceleration due to gravity. Go much beyond that and pilots pass out. Go further still and they suffer serious internal damage. So, Stengel decided that the maximum vertical acceleration for the public should be 6, and then only for a second or so. What’s more, he put firm restrictions on the rate at which acceleration can increase – you’ll never go down a 45-degree ramp into a tight circular loop, for instance.

<strong>C</strong>
But stricter safety limits only intensify the need to search for novel ways to thrill customers. Part of the problem is that no matter how exciting an attraction is, after a few rides, the passengers will have some idea of what to expect. The next stage in designing rides, however, could throw predictability out of the window. This step has already been taken in the most recent waltzers, or tea-cuprides. Ride a waltzer and you sit in a car that spins on its own axis. The car is on a huge platform that also rotates. In the past, you could take comfort from the fact that the spin was tightly controlled by gears that turned your car at a rate determined by the rotation speed of the whole ride. But the latest generation of waltzer cars spin freely, at a rate determined by the weight and position of the people in them. So you never have the same experience twice. "People seem to like these 'chaotic rides'," says Stengel..

<strong>D</strong>
Although seemingly a passport to endless thrills, chaos does have one rather obvious drawback: it’s unpredictable. Despite complex calculations, designers can never be completely sure that something odd won’t happen, especially since freely turning systems occasionally hit a resonance frequency. For example, if pushed at a particular frequency, a child on a swing would go over the top of the swing's frame. Similarly, if you drive a revolving waltzer car at its resonance frequency, it could speed up uncontrollably. This could be very hazardous, according to Stengel. If a ride is subjected to unforeseen stresses, no one can guarantee that it will be able to cope.
<br/>
<strong>E</strong>
No one even knows what the safe limits of rotational force are, let alone its effect on the human body. Stengel has worked with the German Air Force, rotating volunteers head over heels while also making them cartwheel or pirouette like a ballet dancer. It emerged that if the pilots were turned on all three axes simultaneously, they became so nauseous they almost blacked out, and when they got off, they couldn't walk. But what Stengel found particularly puzzling was that they also developed headaches and other problems about two days later. Since these effects aren't understood, he tries to limit how people on his rides are rotated. We want to provide fun, not pain.

<strong>F</strong>
With that goal in mind, Stengel feels that finding people around in ever more chaotic machines is no longer the way forward. He believes that the sequence of accelerations, not their size, is what counts, and that the way to make rides more fun is to put people through a carefully designed succession of relatively small accelerations. Other experts in this field agree, and it seems likely that designers could formulate profiles even for existing attractions that would lead to higher amusement value. Recent experiments testingthetolerancesofDutchmilitarypilotstoarangeofaccelerationshaveshownthattumblingaround in machines doesn't have to be unpleasant. When the force is kept low, the subjects actually enjoy the experience.
<br/>
<strong>G</strong>
The fun seems to come from the unforeseen, particularly when an effect called the Coriolis illusion comes into play. This is an agreeable tumbling feeling which occurs, for example, when the head is suddenly tilted while the subject is spinning with eyes closed. It appears that a roll which includes, for instance, an unexpected change of acceleration from a small negative g—a feeling of weightlessness—to a small positive g, a slight crushing sensation, has an extraordinary effect on people. If the theories of Stengel and other experts really do work, fairground fun might one day be measured in smiles, not screams.
`);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
  // ===== Header (kept intact structure) =====
  { id: 'part3-title', text: 'Part 3', offset: 9000 },
  { id: 'part3-desc', text: 'Read the text and answer questions 27–40.', offset: 9007 },
  { id: 'part3-passage-title', text: 'Reading Passage 3', offset: 9048 },

  // ===== Actual passage text =====
  { id: 'passage', text: passageText, offset: 0 },

  // ===== Questions 27–32 =====
  { id: 'q27-32-title', text: 'Questions 27-32', offset: 9080 },

  { id: 'q27-32-inst1', text: 'You should spend about 20 minutes on Questions 27-40, which are based on Reading Passage 3 on pages 11 and 12.', offset: 9096 },

  { id: 'q27-32-inst2', text: 'Reading Passage 3 has seven paragraphs, A-G.', offset: 9195 },

  { id: 'q27-32-inst3', text: 'Choose the correct heading for paragraphs B-G from the list of headings below.', offset: 9240 },

  { id: 'q27-32-inst4', text: 'Write the correct number i-vii in boxes 27-32 on your answer sheet.', offset: 9315 },

  // Paragraph choices
  { id: 'pB', text: 'Paragraph B', offset: 9390 },
  { id: 'pC', text: 'Paragraph C', offset: 9405 },
  { id: 'pD', text: 'Paragraph D', offset: 9420 },
  { id: 'pE', text: 'Paragraph E', offset: 9435 },
  { id: 'pF', text: 'Paragraph F', offset: 9450 },
  { id: 'pG', text: 'Paragraph G', offset: 9465 },

  // ===== Questions 33–37 =====
  { id: 'q33-37-title', text: 'Questions 33-37', offset: 9500 },

  { id: 'q33-37-inst1', text: 'Complete the sentences below.', offset: 9517 },

  { id: 'q33-37-inst2', text: 'Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the passage for each answer.', offset: 9545 },

  { id: 'q33-37-inst3', text: 'Write your answers in boxes 33-37 on your answer sheet.', offset: 9625 },

  // Questions
  { id: 'q33-num', text: '33.', offset: 9680 },
  { id: 'q33-text', text: 'Some attractions, such as the new type of waltzers, depend on both the', offset: 9685 },

  { id: 'q34-num', text: '34.', offset: 9755 },
  { id: 'q34-text', text: 'Designers need to be aware that a "chaotic" ride could accelerate at a violent rate if it reaches its', offset: 9760 },

  { id: 'q35-num', text: '35.', offset: 9860 },
  { id: 'q35-text', text: 'Research has shown that people will begin to feel ill if they are subjected to movement on all', offset: 9865 },

  { id: 'q36-num', text: '36.', offset: 9960 },
  { id: 'q36-text', text: "Volunteers in Stengel's rotation tests suffered delayed reactions such as", offset: 9965 },

  { id: 'q37-num', text: '37.', offset: 10040 },
  { id: 'q37-text', text: 'A phenomenon known as the', offset: 10045 },

  // ===== Questions 38–40 =====
  { id: 'q38-40-title', text: 'Questions 38-40', offset: 10080 },

  { id: 'q38-40-inst1', text: 'Do the following statements agree with the claims of the writer of Reading Passage 3?', offset: 10100 },

  { id: 'q38-40-inst2', text: 'In boxes 38-40 on your answer sheet, write:', offset: 10185 },

  { id: 'yes-label', text: 'YES', offset: 10235 },
  { id: 'yes-desc', text: 'if the statement agrees with the claims of the writer.', offset: 10240 },

  { id: 'no-label', text: 'NO', offset: 10295 },
  { id: 'no-desc', text: 'if the statement contradicts the claims of the writer.', offset: 10300 },

  { id: 'ng-label', text: 'NOT GIVEN', offset: 10360 },
  { id: 'ng-desc', text: 'if it is impossible to say what the writer thinks about this.', offset: 10370 },

  // Statements
  { id: 'q38-num', text: '38.', offset: 10440 },
  { id: 'q38-text', text: 'There is still a lot to be learnt about the rates of acceleration which people can withstand.', offset: 10445 },

  { id: 'q39-num', text: '39.', offset: 10540 },
  { id: 'q39-text', text: 'Children enjoy funfairs more than adults.', offset: 10545 },

  { id: 'q40-num', text: '40.', offset: 10595 },
  { id: 'q40-text', text: 'Current rides could probably be adapted to become more enjoyable.', offset: 10600 },
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
                        // For passage text, use the passageText segment directly (index 3)
                        segment = textSegments.value[4];
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
        const input = document.querySelector<HTMLTextAreaElement>('.note-input-field');
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

// Watch notes and emit changes
watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

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
    <div class="pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2" @mouseup="handlePassageTextSelect" @click="handleContentClick">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('part3-title')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegmentById('part3-desc')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="part3-passage-title" v-html="getHighlightedSegmentById('part3-passage-title')"></span>
                        </h2>
                    </div>

                    <div class="space-y-2" :style="contentZoom">
                        <div ref="passageTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span
                                        class="text-lg select-text"
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
                    <div ref="questionsTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 27-32: Heading matching with List of Headings -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 mb-3">
                                        <span class="select-text" data-segment-id="q27-32-title" v-html="getHighlightedSegmentById('q27-32-title')"></span>
                                    </h3>
                                    <p class="mb-2 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q27-32-inst1" v-html="getHighlightedSegmentById('q27-32-inst1')"></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q27-32-inst2" v-html="getHighlightedSegmentById('q27-32-inst2')"></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q27-32-inst3" v-html="getHighlightedSegmentById('q27-32-inst3')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q27-32-inst4" v-html="getHighlightedSegmentById('q27-32-inst4')"></span>
                                    </p>
                                </div>

                                <!-- List of Headings box -->
                                <div class="mb-6 border border-gray-900 bg-white p-4">
                                    <h4 class="mb-3 text-center font-bold text-gray-900 text-sm">List of Headings</h4>
                                    <div class="space-y-1 text-sm text-gray-800">
                                        <p><span class="font-medium">i</span>&nbsp;&nbsp;Less is more</p>
                                        <p><span class="font-medium">ii</span>&nbsp;&nbsp;Research can't guarantee safety</p>
                                        <p><span class="font-medium">iii</span>&nbsp;&nbsp;Unexplained symptoms</p>
                                        <p><span class="font-medium">iv</span>&nbsp;&nbsp;Setting the limits of acceleration</p>
                                        <p><span class="font-medium">v</span>&nbsp;&nbsp;The irresistible appeal of speed</p>
                                        <p><span class="font-medium">vi</span>&nbsp;&nbsp;Gentle surprises</p>
                                        <p><span class="font-medium">vii</span>&nbsp;&nbsp;A difficult task</p>
                                        <p><span class="font-medium">viii</span>&nbsp;&nbsp;A different ride every time</p>
                                    </div>
                                </div>

                                <!-- Q27–32 dropdown rows -->
                                <div class="space-y-4">
                                    <!-- Question 27 -->
                                    <div
                                        id="question-27"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 27"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0">27.</span>
                                            <span class="text-sm text-gray-700 flex-shrink-0 select-text" data-segment-id="pB" v-html="getHighlightedSegmentById('pB')"></span>
                                            <select
                                                v-model="answers.q27"
                                                class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-sm transition-colors focus:border-black focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="i">i</option>
                                                <option value="ii">ii</option>
                                                <option value="iii">iii</option>
                                                <option value="iv">iv</option>
                                                <option value="v">v</option>
                                                <option value="vi">vi</option>
                                                <option value="vii">vii</option>
                                                <option value="viii">viii</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                            @click.stop="toggleFlag(27)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 28 -->
                                    <div
                                        id="question-28"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 28"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0">28.</span>
                                            <span class="text-sm text-gray-700 flex-shrink-0 select-text" data-segment-id="pC" v-html="getHighlightedSegmentById('pC')"></span>
                                            <select
                                                v-model="answers.q28"
                                                class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-sm transition-colors focus:border-black focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="i">i</option>
                                                <option value="ii">ii</option>
                                                <option value="iii">iii</option>
                                                <option value="iv">iv</option>
                                                <option value="v">v</option>
                                                <option value="vi">vi</option>
                                                <option value="vii">vii</option>
                                                <option value="viii">viii</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                            @click.stop="toggleFlag(28)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 29 -->
                                    <div
                                        id="question-29"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 29"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0">29.</span>
                                            <span class="text-sm text-gray-700 flex-shrink-0 select-text" data-segment-id="pD" v-html="getHighlightedSegmentById('pD')"></span>
                                            <select
                                                v-model="answers.q29"
                                                class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-sm transition-colors focus:border-black focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="i">i</option>
                                                <option value="ii">ii</option>
                                                <option value="iii">iii</option>
                                                <option value="iv">iv</option>
                                                <option value="v">v</option>
                                                <option value="vi">vi</option>
                                                <option value="vii">vii</option>
                                                <option value="viii">viii</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                            @click.stop="toggleFlag(29)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 30 -->
                                    <div
                                        id="question-30"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 30"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0">30.</span>
                                            <span class="text-sm text-gray-700 flex-shrink-0 select-text" data-segment-id="pE" v-html="getHighlightedSegmentById('pE')"></span>
                                            <select
                                                v-model="answers.q30"
                                                class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-sm transition-colors focus:border-black focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="i">i</option>
                                                <option value="ii">ii</option>
                                                <option value="iii">iii</option>
                                                <option value="iv">iv</option>
                                                <option value="v">v</option>
                                                <option value="vi">vi</option>
                                                <option value="vii">vii</option>
                                                <option value="viii">viii</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                            @click.stop="toggleFlag(30)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 31 -->
                                    <div
                                        id="question-31"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 31"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0">31.</span>
                                            <span class="text-sm text-gray-700 flex-shrink-0 select-text" data-segment-id="pF" v-html="getHighlightedSegmentById('pF')"></span>
                                            <select
                                                v-model="answers.q31"
                                                class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-sm transition-colors focus:border-black focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="i">i</option>
                                                <option value="ii">ii</option>
                                                <option value="iii">iii</option>
                                                <option value="iv">iv</option>
                                                <option value="v">v</option>
                                                <option value="vi">vi</option>
                                                <option value="vii">vii</option>
                                                <option value="viii">viii</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                            @click.stop="toggleFlag(31)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 32 -->
                                    <div
                                        id="question-32"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0">32.</span>
                                            <span class="text-sm text-gray-700 flex-shrink-0 select-text" data-segment-id="pG" v-html="getHighlightedSegmentById('pG')"></span>
                                            <select
                                                v-model="answers.q32"
                                                class="w-28 border-2 border-gray-900 bg-white px-2 py-1 text-sm transition-colors focus:border-black focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="i">i</option>
                                                <option value="ii">ii</option>
                                                <option value="iii">iii</option>
                                                <option value="iv">iv</option>
                                                <option value="v">v</option>
                                                <option value="vi">vi</option>
                                                <option value="vii">vii</option>
                                                <option value="viii">viii</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                            @click.stop="toggleFlag(32)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 33-37: Complete the sentences (fill-in-the-blank inline) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-3">
                                        <span class="select-text" data-segment-id="q33-37-title" v-html="getHighlightedSegmentById('q33-37-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q33-37-inst1" v-html="getHighlightedSegmentById('q33-37-inst1')"></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q33-37-inst2" v-html="getHighlightedSegmentById('q33-37-inst2')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q33-37-inst3" v-html="getHighlightedSegmentById('q33-37-inst3')"></span>
                                    </p>
                                </div>

                                <div class="space-y-5 text-sm text-gray-800">
                                    <!-- Question 33 -->
                                    <div
                                        id="question-33"
                                        class="relative flex flex-wrap items-baseline gap-x-1 gap-y-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 33"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q33-num" v-html="getHighlightedSegmentById('q33-num')"></span>
                                        <span class="text-gray-800 select-text" data-segment-id="q33-text" v-html="getHighlightedSegmentById('q33-text')"></span>
                                        <input
                                            v-model="answers.q33"
                                            type="text"
                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 170px"
                                            placeholder="33"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-800 select-text">of their passengers in order to create a variety of ride experiences.</span>
                                        <button
                                            v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                            @click.stop="toggleFlag(33)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 34 -->
                                    <div
                                        id="question-34"
                                        class="relative flex flex-wrap items-baseline gap-x-1 gap-y-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 34"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q34-num" v-html="getHighlightedSegmentById('q34-num')"></span>
                                        <span class="text-gray-800 select-text" data-segment-id="q34-text" v-html="getHighlightedSegmentById('q34-text')"></span>
                                        <input
                                            v-model="answers.q34"
                                            type="text"
                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 170px"
                                            placeholder="34"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-800 select-text">.</span>
                                        <button
                                            v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                            @click.stop="toggleFlag(34)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 35 -->
                                    <div
                                        id="question-35"
                                        class="relative flex flex-wrap items-baseline gap-x-1 gap-y-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 35"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q35-num" v-html="getHighlightedSegmentById('q35-num')"></span>
                                        <span class="text-gray-800 select-text" data-segment-id="q35-text" v-html="getHighlightedSegmentById('q35-text')"></span>
                                        <input
                                            v-model="answers.q35"
                                            type="text"
                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 170px"
                                            placeholder="35"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-800 select-text">at the same time.</span>
                                        <button
                                            v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                            @click.stop="toggleFlag(35)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 36 -->
                                    <div
                                        id="question-36"
                                        class="relative flex flex-wrap items-baseline gap-x-1 gap-y-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 36"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q36-num" v-html="getHighlightedSegmentById('q36-num')"></span>
                                        <span class="text-gray-800 select-text" data-segment-id="q36-text" v-html="getHighlightedSegmentById('q36-text')"></span>
                                        <input
                                            v-model="answers.q36"
                                            type="text"
                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 170px"
                                            placeholder="36"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-800 select-text">.</span>
                                        <button
                                            v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                            @click.stop="toggleFlag(36)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 37 -->
                                    <div
                                        id="question-37"
                                        class="relative flex flex-wrap items-baseline gap-x-1 gap-y-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 37"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <span class="font-bold text-gray-900 flex-shrink-0" data-segment-id="q37-num" v-html="getHighlightedSegmentById('q37-num')"></span>
                                        <span class="text-gray-800 select-text" data-segment-id="q37-text" v-html="getHighlightedSegmentById('q37-text')"></span>
                                        <input
                                            v-model="answers.q37"
                                            type="text"
                                            class="question-input border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 170px"
                                            placeholder="37"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <span class="text-gray-800 select-text">produced a pleasurable sensation in test subjects.</span>
                                        <button
                                            v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                            @click.stop="toggleFlag(37)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 38-40: YES / NO / NOT GIVEN -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-3">
                                        <span class="select-text" data-segment-id="q38-40-title" v-html="getHighlightedSegmentById('q38-40-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q38-40-inst1" v-html="getHighlightedSegmentById('q38-40-inst1')"></span>
                                    </p>
                                    <p class="mb-3 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q38-40-inst2" v-html="getHighlightedSegmentById('q38-40-inst2')"></span>
                                    </p>
                                    <div class="mb-4 space-y-1 text-sm text-gray-700">
                                        <p>
                                            <span class="font-bold text-gray-900" data-segment-id="yes-label" v-html="getHighlightedSegmentById('yes-label')"></span>
                                            <span class="ml-2" data-segment-id="yes-desc" v-html="getHighlightedSegmentById('yes-desc')"></span>
                                        </p>
                                        <p>
                                            <span class="font-bold text-gray-900" data-segment-id="no-label" v-html="getHighlightedSegmentById('no-label')"></span>
                                            <span class="ml-2" data-segment-id="no-desc" v-html="getHighlightedSegmentById('no-desc')"></span>
                                        </p>
                                        <p>
                                            <span class="font-bold text-gray-900" data-segment-id="ng-label" v-html="getHighlightedSegmentById('ng-label')"></span>
                                            <span class="ml-2" data-segment-id="ng-desc" v-html="getHighlightedSegmentById('ng-desc')"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 38 -->
                                    <div
                                        id="question-38"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 flex-shrink-0" data-segment-id="q38-num" v-html="getHighlightedSegmentById('q38-num')"></span>
                                            <span class="text-base text-gray-900 select-text" data-segment-id="q38-text" v-html="getHighlightedSegmentById('q38-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                            @click.stop="toggleFlag(38)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 39 -->
                                    <div
                                        id="question-39"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 flex-shrink-0" data-segment-id="q39-num" v-html="getHighlightedSegmentById('q39-num')"></span>
                                            <span class="text-base text-gray-900 select-text" data-segment-id="q39-text" v-html="getHighlightedSegmentById('q39-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                            @click.stop="toggleFlag(39)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 40 -->
                                    <div
                                        id="question-40"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 flex-shrink-0" data-segment-id="q40-num" v-html="getHighlightedSegmentById('q40-num')"></span>
                                            <span class="text-base text-gray-900 select-text" data-segment-id="q40-text" v-html="getHighlightedSegmentById('q40-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                            @click.stop="toggleFlag(40)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
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
