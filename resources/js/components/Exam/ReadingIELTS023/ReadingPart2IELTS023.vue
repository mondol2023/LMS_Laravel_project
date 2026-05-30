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

// Reading Part 2 component - How Well Do We Concentrate?

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

// Drag and drop functionality for questions 19-23
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Scientist options for questions 19-23
const scientistOptions = [
    { value: 'A', label: 'Thomas Lehman' },
    { value: 'B', label: 'Earl Miller' },
    { value: 'C', label: 'David Meyer' },
    { value: 'D', label: 'Gloria Mark' },
    { value: 'E', label: 'Edward Hallowell' },
];

// Text segments with their cumulative offsets in the full text
const passageText = ref(`
<strong>How Well Do We Concentrate?</strong><br/><br/>

<strong>A. </strong>Do you read while listening to music? Do you like to watch TV while finishing your homework? People who have these kinds of habits are called multi-taskers. Multi-taskers are able to complete two tasks at the same time by dividing their focus. However, Thomas Lehman, a researcher in Psychology, believes people never really do multiple things simultaneously. Maybe a person is reading while listening to music, but in reality, the brain can only focus on one task. Reading the words in a book will cause you to ignore some of the words of the music. When people think they are accomplishing two different tasks efficiently, what they are really doing is dividing their focus. While listening to music, people become less able to focus on their surroundings. For example, we all have experience of times when we talk with friends and they are not responding properly. Maybe they are listening to someone else talk, or maybe they are reading a text on their smart phone and don't hear what you are saying. Lehman called this phenomenon "email voice".<br/><br/>

<strong>B. </strong>The world has been changed by computers and its spin offs like smart-phones or cell phones. Now that most individuals have a personal device, like a smart-phone or a laptop, they are frequently reading, watching or listening to virtual information. This raises the occurrence of multitasking in our day to day life. Now when you work, you work with your typewriter, your cell phone, and some colleagues who may drop by at any time to speak with you. In professional meetings, when one normally focuses and listens to one another, people are more likely to have a cell phone in their lap, reading or communicating silently with more people than ever. Even inventions such as the cordless phone has increased multitasking. In the old days, a traditional wall phone would ring, and then the house wife would have to stop her activities to answer it. When it rang, the house wife will sit down with her legs up, and chat, with no laundry or sweeping or answering the door. In the modern era, our technology is convenient enough to not interrupt our daily tasks.<br/><br/>

<strong>C. </strong>Earl Miller, an expert at the Massachusetts Institute of Technology, studied the prefrontal cortex, which controls the brain while a person is multitasking. According to his studies, the size of this cortex varies between species. He found that for humans, the size of this part constitutes one third of the brain, while it is only 4 to 5 percent in dogs, and about 15% in monkeys. Given that this cortex is larger in a human, it allows a human to be more flexible and accurate in his or her multitasking. However, Miller wanted to look further into whether the cortex was truly processing information about two different tasks simultaneously. He designed an experiment where he presents visual stimulants to his subjects in a way that mimics multitasking. Miller then attached sensors to the patients' heads to pick up the electric patterns of the brain. This sensor would show if the brain particles, called neurons, were truly processing two different tasks. What he found is that the brain neurons only lit up in singular areas one at a time, and never simultaneously.<br/><br/>

<strong>D. </strong>David Meyer, a professor of University of Michigan, studied the young adults in a similar experiment. He instructed them to simultaneously do math problems and classify simple words into different categories. For this experiment, Meyer found that when you think you are doing several jobs at the same time, you are actually switching between jobs. Even though the people tried to do the tasks at the same time, and both tasks were eventually accomplished, overall, the task took more time than if the person focused on a single task one at a time.<br/><br/>

<strong>E. </strong>People sacrifice efficiency when multitasking. Gloria Mark set office workers as his subjects. He found that they were constantly multitasking. He observed that nearly every 11 minutes people at work were disrupted. He found that doing different jobs at the same time may actually save time. However, despite the fact that they are faster, it does not mean they are more efficient. And we are equally likely to self-interrupt as be interrupted by outside sources. He found that in office nearly every 12 minutes an employee would stop and with no reason at all, check a website on their computer, call someone or write an email. If they concentrated for more than 20 minutes, they would feel distressed. He suggested that the average person may suffer from a short concentration span. This short attention span might be natural, but others suggest that new technology may be the problem. With cell phones and computers at our sides at all times, people will never run out of distractions. The format of media, such as advertisements, music, news articles and TV shows are also shortening, so people are used to paying attention to information for a very short time.<br/><br/>

<strong>F. </strong>So even though focusing on one single task is the most efficient way for our brains to work, it is not practical to use this method in real life. According to human nature, people feel more comfortable and efficient in environments with a variety of tasks. Edward Hallowell said that people are losing a lot of efficiency in the workplace due to multitasking, outside distractions and self-distractions. As a matter of fact, the changes made to the work place do not have to be dramatic. No one is suggesting we ban e-mail or make employees focus on only one task. However, certain common workplace tasks, such as group meetings, would be more efficient if we banned cell-phones, a common distraction. A person can also apply these tips to prevent self-distraction. Instead of arriving to your office and checking all of your e-mails for new tasks, a common workplace ritual, a person could dedicate an hour to a single task first thing in the morning. Self-timing is a great way to reduce distraction and efficiently finish tasks one by one, instead of slowing ourselves down with multi-tasking.<br/><br/>
`);

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts = [
    // Passage Panel (segment_0 to segment_2)
    'Part 2', // segment_0
    'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', // segment_1
    passageText.value, // segment_2

    // Questions 14-18 Section (segment_3 to segment_8)
    'Questions 14-18', // segment_3
    'Reading Passage 2 has six paragraphs, A-F. Which paragraph contains the following information? Write the correct letter, A-F, in boxes 14-18 on your answer sheet.', // segment_4
    '14 a reference to a domestic situation that does not require multitasking', // segment_5
    '15 a possible explanation of why we always do multitask together', // segment_6
    '16 a practical solution to multitask in work environment', // segment_7
    '17 relating multitasking to the size of prefrontal cortex', // segment_8
    '18 longer time spent doing two tasks at the same time than one at a time', // segment_9

    // Questions 19-23 Section (segment_10 to segment_24)
    'Questions 19-23', // segment_10
    'Look at the following statements (Questions 19-23) and the list of scientists below. Match each statement with the correct scientist, A-E.', // segment_11
    'Write the correct letter, A-E, in boxes 19-23 on your answer sheet.', // segment_12
    'NB You may use any letter more than once.', // segment_13
    'List of Scientists', // segment_14
    'A Thomas Lehman', // segment_15
    'B Earl Miller', // segment_16
    'C David Meyer', // segment_17
    'D Gloria Mark', // segment_18
    'E Edward Hallowell', // segment_19
    '19 When faced multiple visual stimulants, one can only concentrate on one of them.', // segment_20
    '20 Doing two things together may be faster but not better.', // segment_21
    '21 People never really do two things together even if you think you do.', // segment_22
    '22 The causes of multitask lie in the environment.', // segment_23
    '23 Even minor changes in the work place will improve work efficiency.', // segment_24

    // Questions 24-26 Section (segment_25 to segment_31)
    'Questions 24-26', // segment_25
    'Complete the sentences below. Choose NO MORE THAN TWO WORDS from the passage for each answer. Write your answers in boxes 24-26 on your answer sheet.', // segment_26
    'A term used to refer to a situation when you are reading a text and cannot focus on your surroundings is', // segment_27
    'The', // segment_28
    'part of the brain controls multitasking.', // segment_29
    'The practical solution of multitask in work is not to allow use of cell phone in', // segment_30
];

// Build textSegments with cumulative offsets
let currentOffset = 0;
const newTextSegments = allStaticTexts.map((text, index) => {
    const segment = {
        id: `segment_${index}`,
        text: text,
        offset: currentOffset,
    };
    currentOffset += text.replace(/<[^>]*>/g, '').length; // Use plain text length for offset calculation
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

                    // Check if this is the main passage text (contains text-lg class or is very long)
                    const isPassageText = (targetSpan as Element).classList?.contains('text-lg') || spanText.length > 500; // Passage is long

                    let segment = null;

                    if (isPassageText) {
                        // For passage text, use the passageText segment directly (index 2)
                        segment = textSegments.value[2];
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

// Drag and drop handlers for questions 19-23
const handleDragStart = (event: DragEvent, option: string) => {
    draggedOption.value = option;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (event: DragEvent, questionNum: number) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleDrop = (event: DragEvent, questionNum: number) => {
    event.preventDefault();
    const option = event.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer1923 = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 2 Header -->
        <div class=" part-header-box mx-5 mt-4 border-gray-400 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="segment_0"
                v-html="getHighlightedSegment(allStaticTexts[0])"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="segment_1"
                v-html="getHighlightedSegment(allStaticTexts[1])"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="text-lg text-gray-700" data-segment-id="segment_2"
                                        v-html="getHighlightedSegment(allStaticTexts[2])"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
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
                            <!-- Questions 14-18: Paragraph matching with select dropdowns -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_3"
                                                v-html="getHighlightedSegment(allStaticTexts[3])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_4"
                                            v-html="getHighlightedSegment(allStaticTexts[4])"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Question 14 -->
                                    <div id="question-14" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900 flex-1" data-segment-id="segment_5"
                                            v-html="getHighlightedSegment(allStaticTexts[5])"></span>
                                        <select v-model="answers.q14"
                                            class="question-select w-32 border border-gray-900 bg-white px-3 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
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
                                    <div id="question-15" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900 flex-1" data-segment-id="segment_6"
                                            v-html="getHighlightedSegment(allStaticTexts[6])"></span>
                                        <select v-model="answers.q15"
                                            class="question-select w-32 border border-gray-900 bg-white px-3 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
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
                                    <div id="question-16" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900 flex-1" data-segment-id="segment_7"
                                            v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                        <select v-model="answers.q16"
                                            class="question-select w-32 border border-gray-900 bg-white px-3 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
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
                                    <div id="question-17" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900 flex-1" data-segment-id="segment_8"
                                            v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                                        <select v-model="answers.q17"
                                            class="question-select w-32 border border-gray-900 bg-white px-3 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
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

                                    <!-- Question 18 -->
                                    <div id="question-18" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900 flex-1" data-segment-id="segment_9"
                                            v-html="getHighlightedSegment(allStaticTexts[9])"></span>
                                        <select v-model="answers.q18"
                                            class="question-select w-32 border border-gray-900 bg-white px-3 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
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
                                </div>
                            </div>

                            <!-- Questions 19-23: Scientist matching with drag and drop -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_10"
                                                v-html="getHighlightedSegment(allStaticTexts[10])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_11"
                                            v-html="getHighlightedSegment(allStaticTexts[11])"></span>
                                    </p>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_12"
                                            v-html="getHighlightedSegment(allStaticTexts[12])"></span>
                                    </p>
                                    <p class="mb-4 text-sm italic text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_13"
                                            v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                    </p>
                                </div>

                                <!-- Side by side layout: Questions (left) + Draggable Options (right) -->
                                <div class="flex gap-4">
                                    <!-- Left side: Questions with drop zones -->
                                    <div class="flex-1 space-y-3">
                                        <!-- Question 19 -->
                                        <div id="question-19" class="relative rounded border border-gray-200 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
                                            <div class="flex items-start gap-3">
                                                <span class="text-base text-gray-900 flex-1" data-segment-id="segment_20"
                                                    v-html="getHighlightedSegment(allStaticTexts[20])"></span>
                                                <span
                                                    class="inline-flex min-h-8 min-w-20 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                    :class="dragOverQuestion === 19 ? 'border-blue-500 bg-blue-50' : answers.q19 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 19)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 19)"
                                                    @click="clearAnswer1923(19)"
                                                    :title="answers.q19 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q19 || '' }}
                                                </span>
                                            </div>
                                            <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                                @click.stop="toggleFlag(19)"
                                                class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(19) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Question 20 -->
                                        <div id="question-20" class="relative rounded border border-gray-200 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                            <div class="flex items-start gap-3">
                                                <span class="text-base text-gray-900 flex-1" data-segment-id="segment_21"
                                                    v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                                                <span
                                                    class="inline-flex min-h-8 min-w-20 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                    :class="dragOverQuestion === 20 ? 'border-blue-500 bg-blue-50' : answers.q20 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 20)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 20)"
                                                    @click="clearAnswer1923(20)"
                                                    :title="answers.q20 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q20 || '' }}
                                                </span>
                                            </div>
                                            <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                                @click.stop="toggleFlag(20)"
                                                class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(20) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Question 21 -->
                                        <div id="question-21" class="relative rounded border border-gray-200 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                            <div class="flex items-start gap-3">
                                                <span class="text-base text-gray-900 flex-1" data-segment-id="segment_22"
                                                    v-html="getHighlightedSegment(allStaticTexts[22])"></span>
                                                <span
                                                    class="inline-flex min-h-8 min-w-20 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                    :class="dragOverQuestion === 21 ? 'border-blue-500 bg-blue-50' : answers.q21 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 21)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 21)"
                                                    @click="clearAnswer1923(21)"
                                                    :title="answers.q21 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q21 || '' }}
                                                </span>
                                            </div>
                                            <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                                @click.stop="toggleFlag(21)"
                                                class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(21) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Question 22 -->
                                        <div id="question-22" class="relative rounded border border-gray-200 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                            <div class="flex items-start gap-3">
                                                <span class="text-base text-gray-900 flex-1" data-segment-id="segment_23"
                                                    v-html="getHighlightedSegment(allStaticTexts[23])"></span>
                                                <span
                                                    class="inline-flex min-h-8 min-w-20 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                    :class="dragOverQuestion === 22 ? 'border-blue-500 bg-blue-50' : answers.q22 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 22)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 22)"
                                                    @click="clearAnswer1923(22)"
                                                    :title="answers.q22 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q22 || '' }}
                                                </span>
                                            </div>
                                            <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                                @click.stop="toggleFlag(22)"
                                                class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(22) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Question 23 -->
                                        <div id="question-23" class="relative rounded border border-gray-200 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                            <div class="flex items-start gap-3">
                                                <span class="text-base text-gray-900 flex-1" data-segment-id="segment_24"
                                                    v-html="getHighlightedSegment(allStaticTexts[24])"></span>
                                                <span
                                                    class="inline-flex min-h-8 min-w-20 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                    :class="dragOverQuestion === 23 ? 'border-blue-500 bg-blue-50' : answers.q23 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, 23)"
                                                    @dragleave="handleDragLeave"
                                                    @drop="handleDrop($event, 23)"
                                                    @click="clearAnswer1923(23)"
                                                    :title="answers.q23 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q23 || '' }}
                                                </span>
                                            </div>
                                            <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                                @click.stop="toggleFlag(23)"
                                                class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(23) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Right side: Draggable scientist options -->
                                    <div class="w-48 shrink-0 self-start sticky top-4">
                                        <div class="rounded border border-gray-300 bg-gray-50 p-3">
                                            <p class="mb-2 text-sm font-bold text-gray-900" data-segment-id="segment_14"
                                                v-html="getHighlightedSegment(allStaticTexts[14])"></p>
                                            <p class="text-xs text-gray-600 mb-3">Drag to match questions</p>
                                            <div class="space-y-2">
                                                <div v-for="option in scientistOptions" :key="option.value"
                                                    class="flex cursor-grab items-center gap-2 rounded border border-gray-300 bg-white px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd">
                                                    <span class="font-bold text-gray-900">{{ option.value }}</span>
                                                    <span class="text-sm text-gray-700">{{ option.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 24-26: Sentence completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_25"
                                                v-html="getHighlightedSegment(allStaticTexts[25])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_26"
                                            v-html="getHighlightedSegment(allStaticTexts[26])"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 24 -->
                                    <div id="question-24" class="relative flex items-start gap-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900">•</span>
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-1">
                                                <span class="text-base text-gray-900" data-segment-id="segment_27"
                                                    v-html="getHighlightedSegment(allStaticTexts[27])"></span>
                                                <input v-model="answers.q24" type="text" maxlength="20"
                                                    class="question-input mx-1 border border-gray-900 px-3 py-1 text-sm focus:border-black focus:outline-none"
                                                    style="width: 180px" placeholder="24" spellcheck="false"
                                                    autocomplete="off" autocorrect="off" autocapitalize="off" />
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                            @click.stop="toggleFlag(24)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 25 -->
                                    <div id="question-25" class="relative flex items-start gap-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900">•</span>
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-1">
                                                <span class="text-base text-gray-900" data-segment-id="segment_28"
                                                    v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                                <input v-model="answers.q25" type="text" maxlength="20"
                                                    class="question-input mx-1 border border-gray-900 px-3 py-1 text-sm focus:border-black focus:outline-none"
                                                    style="width: 180px" placeholder="25" spellcheck="false"
                                                    autocomplete="off" autocorrect="off" autocapitalize="off" />
                                                <span class="text-base text-gray-900" data-segment-id="segment_29"
                                                    v-html="getHighlightedSegment(allStaticTexts[29])"></span>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                            @click.stop="toggleFlag(25)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 26 -->
                                    <div id="question-26" class="relative flex items-start gap-2 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-900">•</span>
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-1">
                                                <span class="text-base text-gray-900" data-segment-id="segment_30"
                                                    v-html="getHighlightedSegment(allStaticTexts[30])"></span>
                                                <input v-model="answers.q26" type="text" maxlength="20"
                                                    class="question-input mx-1 border border-gray-900 px-3 py-1 text-sm focus:border-black focus:outline-none"
                                                    style="width: 180px" placeholder="26" spellcheck="false"
                                                    autocomplete="off" autocorrect="off" autocapitalize="off" />
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                            @click.stop="toggleFlag(26)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
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
                <!-- Tooltip Content -->
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <!-- Note Button -->
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note">
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
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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
