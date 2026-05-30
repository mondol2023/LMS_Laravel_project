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

// Reading Part 1 component - The Connection Between Culture and Thought

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

// Answers for questions 1-13
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

// Drag and drop functionality for questions 6-9
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Researcher options for questions 6-9
const researcherOptions = [
    { value: 'A', label: 'Bessett & Masuku' },
    { value: 'B', label: 'Bessett & Choi' },
    { value: 'C', label: 'Bessett & Ara' },
];

// Text segments with their cumulative offsets in the full text
const passageText = ref(`
<b>The Connection Between Culture and Thought</b><br/><br/>
<strong>A. </strong>The world's population has surpassed 7 billion and continues to grow. Across the globe, humans have many differences. These differences can be influenced by factors such as geography, climate, politics, nationality, and many more. Culture is one such aspect that can change the way people behave.<br/><br/>

<strong>B. </strong>Your culture may influence your clothing, your language, and many aspects of your life. But is culture influential enough to change the way an individual thinks? It has long been believed that people from different cultures would think differently. For example, a young boy from a farm would talk about cows while a boy from New York will talk about cars. If two young children from different countries are asked about their thoughts about a painting, they would answer differently because of their cultural backgrounds.<br/><br/>

<strong>C. </strong>In recent years, there has been new research that changed this long-held belief; However, this new research is not the first to explore the idea that culture can change the way we think. Earlier research has provided valuable insight to the question. One of the earliest research projects was carried out in the Soviet Union. This project was designed to find out whether culture would affect people's way of thought processing. The researchers focused on how living environment and nationality might influence how people think. The experiment led by Bessett aimed to question such awareness of cognitive psychology. Bessett conducted several versions of the experiment to test different cognitive processes.<br/><br/>

<strong>D. </strong>One experiment led by Bessett and Masuku showed an animated video picturing a big fish swimming among smaller fish and other sea creatures. Subjects were asked to describe the scene. The Japanese participants tended to focus on the aquatic background, such as the plants and color of the water, as well as the relationship between the big and small fish. American participants tended to focus on individual fishes, mainly the larger, more unique looking fish. The experiment suggested that members of Eastern cultures focus more on the overall picture, while members of Western culture focus more on the individuals.<br/><br/>

<strong>E. </strong>In another experiment performed by Bessett and Choi, the subjects were presented with some very convincing evidence for a position. Both the Korean and the American showed strong support. And after they were given some evidence opposing the position, the Korean started to modify or decreased their support. However, the American began to give more support to the former argument. This project suggested that in Korean culture, support for arguments is based on context. Ideas and conclusions are changeable and flexible, so an individual may be more willing to change his or her mind. For Americans, they were less willing to change their original conclusion.<br/><br/>

<strong>F. </strong>Bessett and Ara devised an experiment to test the thought processing of both oriental and occidental worlds. Test subject was given an argument "All animals with furs hibernate. Rabbit has fur. Therefore, rabbit hibernate". People from the eastern world questioned the argument as not being logical, because in their knowledge some furry animals just don't hibernate. But the American think the statement is right. They assume the logic deduction is based on a correct argument, thus the conclusion is right since the logic is right.<br/><br/>

<strong>G. </strong>From these early experiments in the Soviet Union, one might conclude that our original premise— that culture can impact the way we think—was still correct. However, recent research criticizes this view, as well as Bessett's early experiments. Though these experiments changed the original belief on thought processing, how much does it result from all factors needs further discussion. Fischer thinks Bessett's experiments provide valuable information because his research only provides qualitative descriptions, not results from controlled environment. Chang partly agrees with him, because there are some social factors that might influence the results.<br/><br/>

<strong>H. </strong>Another criticism of Bessett's experiments is that culture was studied as a sub-factor of nationality. The experiments assumed that culture would be the same among all members of a nationality. For example, every American that participated in the experiments could be assumed to have the same culture. In reality, culture is much more complicated than nationality. These early experiments did not control for other factors, such as socioeconomic status, education, ethnicity, and regional differences in culture. All of these factors could have a big effect on the individual's response.<br/><br/>

<strong>I. </strong>A third criticism of Bessett's experiment is that the content itself should have been more abstract, such as a puzzle or an IQ test. With objective content, such as nature and animals, people from different countries of the world might have different pre-conceived ideas about these animals. Prior knowledge based on geographic location would further complicate the results. A test that is more abstract, or more quantitative, would provide a more controlled study of how cognitive processing works for different groups of people.<br/><br/>

<strong>J. </strong>The research on culture's effect on cognitive processing still goes on today, and while some criticisms exist of Bessett's early studies, the projects still provide valuable insight. It is important for future research projects to control carefully for the variables, such as culture. Something like culture is complex and difficult to define. It can also be influenced by many other variables, such as geography or education styles. When studying a variable like culture, it is critical that the researcher create a clear definition for what is—and what is not—considered culture.<br/><br/>

<strong>K. </strong>Another important aspect of modern research is the ethical impact of the research. A researcher must consider carefully whether the results of the research will negatively impact any of the groups involved. In an increasingly globalised job economy, generalisations made about nationalities can be harmful to prospective employees. This information could also impact the way tests and university admissions standards are designed, which would potentially favor one group or create a disadvantage for another. When conducting any research about culture and nationality, researchers should consider all possible effects, positive or negative, that their conclusions may have when published for the world to see.<br/><br/>
`);

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts = [
    // Passage Panel (segment_0 to segment_2)
    'Part 1', // segment_0
    'Read the text and answer questions 1–13.', // segment_1
    passageText.value, // segment_2

    // Questions 1-5 Section (segment_3 to segment_14)
    'Questions 1-5', // segment_3
    'Reading Passage 1 has eleven paragraphs, A-K. Which paragraph contains the following information? Write the correct letter, A-K, in boxes 1-5 on your answer sheet. NB You may use any letter more than once.', // segment_4
    '1', // segment_5
    'All people have the same reaction to a certain point of view.', // segment_6
    '2', // segment_7
    'Qualitative descriptions are valuable in exploring thought processing.', // segment_8
    '3', // segment_9
    'Different cultures will affect the description of the same scene.', // segment_10
    '4', // segment_11
    'We thought of young people as widely different at different geographical locations.', // segment_12
    '5', // segment_13
    'Eastern people are less likely to stick to their argument.', // segment_14

    // Questions 6-9 Section (segment_15 to segment_28)
    'Questions 6-9', // segment_15
    'Look at the following statements (Questions 6-9) and the list of researchers below. Match each statement with the correct researcher, A-C. Write the correct letter, A-C, in boxes 6-9 on your answer sheet. NB You may use any letter more than once.', // segment_16
    'List of Researchers', // segment_17
    'A Bessett & Masuku', // segment_18
    'B Bessett & Choi', // segment_19
    'C Bessett & Ara', // segment_20
    '6', // segment_21
    'Geographical location affects people\'s position on certain arguments.', // segment_22
    '7', // segment_23
    'Animated images reveal different process strategies.', // segment_24
    '8', // segment_25
    'Eastern people challenge a deduction because they knew it is not true.', // segment_26
    '9', // segment_27
    'Eastern people find more difficulty when asked to identify the same object.', // segment_28

    // Questions 10-13 Section (segment_29 to segment_40)
    'Questions 10-13', // segment_29
    'Complete the sentences below. Choose NO MORE THAN TWO WORDS from the passage for each answer. Write your answers in boxes 10-13 on your answer sheet.', // segment_30
    'Researchers in the Soviet Union wanted to find out how', // segment_31
    'and nationality will control the way people think.', // segment_32
    'Bessett and Ara\'s experiment shows, for Americans, so long as the logic deduction is based on a correct argument, the', // segment_33
    'should be right.', // segment_34
    'Fischer thinks Bessett\'s research is quite valuable because it is conducted in a', // segment_35
    'way rather than in controlled environment.', // segment_36
    'Future researchers on culture\'s effect on cognitive processing should start with a', // segment_37
    'of culture as a variable.', // segment_38
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
            const segment = textSegments.value.find((s) => String(s.id) === segmentIdAttr);
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

// Drag and drop handlers for questions 6-9
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

const clearAnswer69 = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// Get researcher label from option value for display
const getResearcherLabel = (value: string): string => {
    const option = researcherOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
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
        <!-- Part 1 Header -->
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
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
                            <!-- Questions 1-5: Which paragraph contains the information -->
                            <div class="p-4">
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900">
                                        <span class="text-gray-700" data-segment-id="segment_3"
                                            v-html="getHighlightedSegment(allStaticTexts[3])"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_4"
                                            v-html="getHighlightedSegment(allStaticTexts[4])"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Question 1 -->
                                    <div id="question-1" class="relative bg-white p-3" @mouseenter="hoveredQuestion = 1"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="segment_5"
                                                v-html="getHighlightedSegment(allStaticTexts[5])"></span>
                                            <span class="text-base text-gray-900" data-segment-id="segment_6"
                                                v-html="getHighlightedSegment(allStaticTexts[6])"></span>
                                        </div>
                                        <div class="ml-6">
                                            <select v-model="answers.q1"
                                                class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                                <option value="" disabled selected>Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                                <option value="H">H</option>
                                                <option value="I">I</option>
                                                <option value="J">J</option>
                                                <option value="K">K</option>
                                            </select>
                                        </div>
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

                                    <!-- Question 2 -->
                                    <div id="question-2" class="relative bg-white p-3" @mouseenter="hoveredQuestion = 2"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="segment_7"
                                                v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                            <span class="text-base text-gray-900" data-segment-id="segment_8"
                                                v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                                        </div>
                                        <div class="ml-6">
                                            <select v-model="answers.q2"
                                                class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                                <option value="" disabled selected>Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                                <option value="H">H</option>
                                                <option value="I">I</option>
                                                <option value="J">J</option>
                                                <option value="K">K</option>
                                            </select>
                                        </div>
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

                                    <!-- Question 3 -->
                                    <div id="question-3" class="relative bg-white p-3" @mouseenter="hoveredQuestion = 3"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="segment_9"
                                                v-html="getHighlightedSegment(allStaticTexts[9])"></span>
                                            <span class="text-base text-gray-900" data-segment-id="segment_10"
                                                v-html="getHighlightedSegment(allStaticTexts[10])"></span>
                                        </div>
                                        <div class="ml-6">
                                            <select v-model="answers.q3"
                                                class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                                <option value="" disabled selected>Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                                <option value="H">H</option>
                                                <option value="I">I</option>
                                                <option value="J">J</option>
                                                <option value="K">K</option>
                                            </select>
                                        </div>
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

                                    <!-- Question 4 -->
                                    <div id="question-4" class="relative bg-white p-3" @mouseenter="hoveredQuestion = 4"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="segment_11"
                                                v-html="getHighlightedSegment(allStaticTexts[11])"></span>
                                            <span class="text-base text-gray-900" data-segment-id="segment_12"
                                                v-html="getHighlightedSegment(allStaticTexts[12])"></span>
                                        </div>
                                        <div class="ml-6">
                                            <select v-model="answers.q4"
                                                class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                                <option value="" disabled selected>Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                                <option value="H">H</option>
                                                <option value="I">I</option>
                                                <option value="J">J</option>
                                                <option value="K">K</option>
                                            </select>
                                        </div>
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

                                    <!-- Question 5 -->
                                    <div id="question-5" class="relative bg-white p-3" @mouseenter="hoveredQuestion = 5"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="segment_13"
                                                v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                            <span class="text-base text-gray-900" data-segment-id="segment_14"
                                                v-html="getHighlightedSegment(allStaticTexts[14])"></span>
                                        </div>
                                        <div class="ml-6">
                                            <select v-model="answers.q5"
                                                class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                                <option value="" disabled selected>Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                                <option value="H">H</option>
                                                <option value="I">I</option>
                                                <option value="J">J</option>
                                                <option value="K">K</option>
                                            </select>
                                        </div>
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
                                </div>
                            </div>

                            <!-- Questions 6-9: Match researchers with drag and drop -->
                            <div class="border-t pt-4">
                                <div class="p-4">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_15"
                                                v-html="getHighlightedSegment(allStaticTexts[15])"></span>
                                        </h3>
                                        <p class="text-sm leading-relaxed text-gray-700">
                                            <span class="text-gray-700" data-segment-id="segment_16"
                                                v-html="getHighlightedSegment(allStaticTexts[16])"></span>
                                        </p>
                                    </div>

                                    <!-- Side by side layout: Questions (left) + Draggable Options (right) -->
                                    <div class="flex gap-4">
                                        <!-- Left side: Questions with drop zones -->
                                        <div class="flex-1 space-y-3">
                                            <!-- Question 6 -->
                                            <div id="question-6" class="relative rounded border border-gray-200 bg-white p-3"
                                                @mouseenter="hoveredQuestion = 6" @mouseleave="hoveredQuestion = null">
                                                <div class="flex items-start gap-3">
                                                    <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="segment_21"
                                                        v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                                                    <span class="text-base text-gray-900 flex-1" data-segment-id="segment_22"
                                                        v-html="getHighlightedSegment(allStaticTexts[22])"></span>
                                                    <span
                                                        class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                        :class="dragOverQuestion === 6 ? 'border-blue-500 bg-blue-50' : answers.q6 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 6)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 6)"
                                                        @click="clearAnswer69(6)"
                                                        :title="answers.q6 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q6 ? `${answers.q6}` : '' }}
                                                    </span>
                                                </div>
                                                <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                                    @click.stop="toggleFlag(6)"
                                                    class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(6) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 7 -->
                                            <div id="question-7" class="relative rounded border border-gray-200 bg-white p-3"
                                                @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                                <div class="flex items-start gap-3">
                                                    <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="segment_23"
                                                        v-html="getHighlightedSegment(allStaticTexts[23])"></span>
                                                    <span class="text-base text-gray-900 flex-1" data-segment-id="segment_24"
                                                        v-html="getHighlightedSegment(allStaticTexts[24])"></span>
                                                    <span
                                                        class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                        :class="dragOverQuestion === 7 ? 'border-blue-500 bg-blue-50' : answers.q7 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 7)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 7)"
                                                        @click="clearAnswer69(7)"
                                                        :title="answers.q7 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q7 ? `${answers.q7}` : '' }}
                                                    </span>
                                                </div>
                                                <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                                    @click.stop="toggleFlag(7)"
                                                    class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(7) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 8 -->
                                            <div id="question-8" class="relative rounded border border-gray-200 bg-white p-3"
                                                @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                                <div class="flex items-start gap-3">
                                                    <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="segment_25"
                                                        v-html="getHighlightedSegment(allStaticTexts[25])"></span>
                                                    <span class="text-base text-gray-900 flex-1" data-segment-id="segment_26"
                                                        v-html="getHighlightedSegment(allStaticTexts[26])"></span>
                                                    <span
                                                        class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                        :class="dragOverQuestion === 8 ? 'border-blue-500 bg-blue-50' : answers.q8 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 8)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 8)"
                                                        @click="clearAnswer69(8)"
                                                        :title="answers.q8 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q8 ? `${answers.q8}` : '' }}
                                                    </span>
                                                </div>
                                                <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                                    @click.stop="toggleFlag(8)"
                                                    class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(8) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Question 9 -->
                                            <div id="question-9" class="relative rounded border border-gray-200 bg-white p-3"
                                                @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                                <div class="flex items-start gap-3">
                                                    <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="segment_27"
                                                        v-html="getHighlightedSegment(allStaticTexts[27])"></span>
                                                    <span class="text-base text-gray-900 flex-1" data-segment-id="segment_28"
                                                        v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                                    <span
                                                        class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all shrink-0"
                                                        :class="dragOverQuestion === 9 ? 'border-blue-500 bg-blue-50' : answers.q9 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 9)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 9)"
                                                        @click="clearAnswer69(9)"
                                                        :title="answers.q9 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q9 ? `${answers.q9}` : '' }}
                                                    </span>
                                                </div>
                                                <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                                    @click.stop="toggleFlag(9)"
                                                    class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(9) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                    :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Right side: Draggable researcher options -->
                                        <div class="w-44 shrink-0 self-start sticky top-4">
                                            <div class="rounded border border-gray-300 bg-gray-50 p-3">
                                                <p class="mb-2 text-sm font-bold text-gray-900" data-segment-id="segment_17"
                                                    v-html="getHighlightedSegment(allStaticTexts[17])"></p>
                                                <p class="text-xs text-gray-600 mb-3">Drag to match questions</p>
                                                <div class="space-y-2">
                                                    <div v-for="option in researcherOptions" :key="option.value"
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
                            </div>

                            <!-- Questions 10-13: Sentence completion -->
                            <div class="border-t pt-4">
                                <div class="p-4">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_29"
                                                v-html="getHighlightedSegment(allStaticTexts[29])"></span>
                                        </h3>
                                        <p class="text-sm text-gray-700">
                                            <span class="text-gray-700" data-segment-id="segment_30"
                                                v-html="getHighlightedSegment(allStaticTexts[30])"></span>
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        <!-- Question 10 -->
                                        <div id="question-10" class="relative flex items-start gap-2 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <span class="text-base text-gray-900" data-segment-id="segment_31"
                                                        v-html="getHighlightedSegment(allStaticTexts[31])"></span>
                                                    <input v-model="answers.q10" type="text"
                                                        class="question-input mx-1 border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 120px" placeholder="10" maxlength="20"
                                                        spellcheck="false" autocomplete="off" />
                                                    <span class="text-base text-gray-900" data-segment-id="segment_32"
                                                        v-html="getHighlightedSegment(allStaticTexts[32])"></span>
                                                </div>
                                            </div>
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

                                        <!-- Question 11 -->
                                        <div id="question-11" class="relative flex items-start gap-2 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <span class="text-base text-gray-900" data-segment-id="segment_33"
                                                        v-html="getHighlightedSegment(allStaticTexts[33])"></span>
                                                    <input v-model="answers.q11" type="text"
                                                        class="question-input mx-1 border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 120px" placeholder="11" maxlength="20"
                                                        spellcheck="false" autocomplete="off" />
                                                    <span class="text-base text-gray-900" data-segment-id="segment_34"
                                                        v-html="getHighlightedSegment(allStaticTexts[34])"></span>
                                                </div>
                                            </div>
                                            <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                                @click.stop="toggleFlag(11)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Question 12 -->
                                        <div id="question-12" class="relative flex items-start gap-2 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <span class="text-base text-gray-900" data-segment-id="segment_35"
                                                        v-html="getHighlightedSegment(allStaticTexts[35])"></span>
                                                    <input v-model="answers.q12" type="text"
                                                        class="question-input mx-1 border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 120px" placeholder="12" maxlength="20"
                                                        spellcheck="false" autocomplete="off" />
                                                    <span class="text-base text-gray-900" data-segment-id="segment_36"
                                                        v-html="getHighlightedSegment(allStaticTexts[36])"></span>
                                                </div>
                                            </div>
                                            <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                                @click.stop="toggleFlag(12)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Question 13 -->
                                        <div id="question-13" class="relative flex items-start gap-2 bg-white p-3"
                                            @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">•</span>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <span class="text-base text-gray-900" data-segment-id="segment_37"
                                                        v-html="getHighlightedSegment(allStaticTexts[37])"></span>
                                                    <input v-model="answers.q13" type="text"
                                                        class="question-input mx-1 border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none"
                                                        style="width: 120px" placeholder="13" maxlength="20"
                                                        spellcheck="false" autocomplete="off" />
                                                    <span class="text-base text-gray-900" data-segment-id="segment_38"
                                                        v-html="getHighlightedSegment(allStaticTexts[38])"></span>
                                                </div>
                                            </div>
                                            <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                                @click.stop="toggleFlag(13)"
                                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
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
    </div>

    <!-- Context Menu for Highlighting -->
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
                        <!-- Quote marks icon -->
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

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
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
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText
                        }}</span>
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
