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

// Reading Part 3 component - Robert Louis Stevenson

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part3-panel-width';
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

// Answers for questions 27-40
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

// Drag and drop functionality for questions 36-40
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Options for questions 36-40 (from the passage)
const summaryOptions = [
    { value: 'A', label: 'natural ability' },
    { value: 'B', label: 'romance' },
    { value: 'C', label: 'colorful language' },
    { value: 'D', label: 'critical acclaim' },
    { value: 'E', label: 'humor' },
    { value: 'F', label: 'technical control' },
    { value: 'G', label: 'storytelling' },
    { value: 'H', label: 'depth' },
    { value: 'I', label: 'human nature' },
];

// Robert Louis Stevenson passage text
const passageText = ref(`<b>Robert Louis Stevenson</b>
A Scottish novelist, poet, essayist, and travel writer, Robert Louis Stevenson was born at 8 Howard Place, Edinburgh, Scotland, on 13 November 1850. It has been more than 100 years since his death. Stevenson was a writer who caused conflicting opinions about his works. On one hand, he was often highly praised for his expert prose and style by many English-language critics. On the other hand, others criticised the religious themes in his works, often misunderstanding Stevenson's own religious beliefs. Since his death a century before, critics and biographers have disagreed on the legacy of Stevenson's writing. Two biographers, KF and CP , wrote a biography about Stevenson with a clear focus. They chose not to criticise aspects of Stevenson's personal life. Instead, they focused on his writing, and gave high praise to his writing style and skill.

The literary pendulum has swung these days. Different critics have different opinions towards Robert Louis Stevenson's works. Though today, Stevenson is one of the most translated authors in the world, his works have sustained a wide variety of negative criticism throughout his life. It was like a complete reversal of polarity—from highly positive to slightly less positive to clearly negative; after being highly praised as a great writer, he became an example of an author with corrupt ethics and lack of moral. Many literary critics passed his works off as children's stories or horror stories, and thought to have little social value in an educational setting. Stevenson's works were often excluded from literature curriculum because of its controversial nature. These debates remain, and many critics still assert that despite his skill, his literary works still lack moral value.

One of the main reasons why Stevenson's literary works attracted so much criticism was due to the genre of his writing. Stevenson mainly wrote adventure stories, which was part of a popular and entertaining writing fad at the time. Many of us believe adventure stories are exciting, offer engaging characters, action, and mystery but ultimately can't teach moral principles. The plot points are one-dimensional and rarely offer a deeper moral meaning, instead focusing on exciting and shocking plot twists and thrilling events. His works were even criticised by fellow authors. Though Stevenson's works have deeply influenced Oscar Wilde, Wilde often joked that Stevenson would have written better works if he wasn't born in Scotland. Other authors came to Stevenson's defence, including Galsworthy who claimed that Stevenson is a greater writer than Thomas Hardy.

Despite Wilde's criticism, Stevenson's Scottish identity was an integral part of his written works. Although Stevenson's works were not popular in Scotland when he was alive, many modern Scottish literary critics claim that Sir Walter Scott and Robert Louis Stevenson are the most influential writers in the history of Scotland. While many critics exalt Sir Walter Scott as a literary genius because of his technical ability, others argue that Stevenson deserves the same recognition for his natural ability to capture stories and characters in words. Many of Scott's works were taken more seriously as literature for their depth due to their tragic themes, but fans of Stevenson praise his unique style of story-telling and capture of human nature. Stevenson's works, unlike other British authors, captured the unique day to day life of average Scottish people. Many literary critics point to this as a flaw of his works. According to the critics, truly important literature should transcend local culture and stories. However, many critics praise the local taste of his literature. To this day, Stevenson's works provide valuable insight to life in Scotland during the 19th century.

Despite much debate of Stevenson's writing topics, his writing was not the only source of attention for critics. Stevenson's personal life often attracted a lot of attention from his fans and critics alike. Some even argue that his personal life eventually outshone his writing. Stevenson had been plagued with health problems his whole life, and often had to live in much warmer climates than the cold, dreary weather of Scotland in order to recover. So he took his family to a south pacific island Samoa, which was a controversial decision at that time. However, Stevenson did not regret the decision. The sea air and thrill of adventure complimented the themes of his writing, and for a time restored his health. From there, Stevenson gained a love of travelling, and for nearly three years he wandered the eastern and central Pacific. Much of his works reflected this love of travel and adventure that Stevenson experienced in the Pacific islands. It was as a result of this biographical attention that the feeling grew that interest in Stevenson's life had taken the place of interest in his works. Whether critics focus on his writing subjects, his religious beliefs, or his eccentric lifestyle of travel and adventure, people from the past and present have different opinions about Stevenson as an author. Today, he remains a controversial yet widely popular figure in Western literature.`);

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts = [
    // Header (segment_0 to segment_1)
    'Part 3', // segment_0
    'You should spend about 20 minutes on Questions 27-40 which are based on Reading Passage 3 below.', // segment_1
    passageText.value, // segment_2

    // Questions 27-31: Multiple Choice (segment_3 to segment_27)
    'Questions 27-31', // segment_3
    'Choose the correct letter, A, B, C or D. Write the correct letter in boxes 27-31 on your answer sheet.', // segment_4
    '27 Stevenson\'s biographers KF and CP', // segment_5
    'underestimated the role of family played in Stevenson\'s life.', // segment_6
    'overestimated the writer\'s works in the literature history.', // segment_7
    'exaggerated Stevenson\'s religious belief in his works.', // segment_8
    'elevated Stevenson\'s role as a writer.', // segment_9
    '28 The main point of the second paragraph is', // segment_10
    'the public give a more fair criticism to Stevenson\'s works.', // segment_11
    'recent criticism has been justified.', // segment_12
    'the style of Stevenson\'s works overweigh his faults in his life.', // segment_13
    'Stevenson\'s works\' drawback is lack of ethical nature.', // segment_14
    '29 According to the author, adventure stories', // segment_15
    'do not provide plot twists well.', // segment_16
    'cannot be used by writers to show moral values.', // segment_17
    'are more fashionable art form.', // segment_18
    'can be found in other\'s works but not in Stevenson\'s.', // segment_19
    '30 What does the author say about Stevenson\'s works?', // segment_20
    'They describe the life of people in Scotland.', // segment_21
    'They are commonly regarded as real literature.', // segment_22
    'They were popular during Stevenson\'s life.', // segment_23
    'They transcend the local culture and stories.', // segment_24
    '31 The lifestyle of Stevenson', // segment_25
    'made his family envy him so much.', // segment_26
    'should be responsible for his death.', // segment_27
    'gained more attention from the public than his works.', // segment_28
    'didn\'t well prepare his life in Samoa.', // segment_29

    // Questions 32-35: True/False/Not Given (segment_30 to segment_38)
    'Questions 32-35', // segment_30
    'Do the following statements agree with the information given in Reading Passage 3? In boxes 32-35 on you answer sheet, write', // segment_31
    'TRUE if the statement is true', // segment_32
    'FALSE if the statement is false', // segment_33
    'NOT GIVEN if the information is not given in the passage', // segment_34
    '32 Although Oscar Wilde admired Robert Louis Stevenson very much, he believed Stevenson could have written greater works.', // segment_35
    '33 Robert Louis Stevenson encouraged Oscar Wilde to start writing at first.', // segment_36
    '34 Galsworthy thought Hardy is greater writer than Stevenson is.', // segment_37
    '35 Critics only paid attention to Robert Louis Stevenson\'s writing topics.', // segment_38

    // Questions 36-40: Summary completion (segment_39 to segment_56)
    'Questions 36-40', // segment_39
    'Complete the notes using the list of words, A-I, below. Write the correct letter ,A-I, in boxes 36-40 on your answer sheet.', // segment_40
    'Sir Walter Scott and Robert Louis Stevenson', // segment_41
    'A lot of people believe that Sir Walter Scott and Robert Louis Stevenson are the most influential writer in the history of Scotland, but Sir Walter Scott is more proficient in', // segment_42
    'while Stevenson has better', // segment_43
    'Scott\'s books illustrate', // segment_44
    'especially in terms of tragedy, but a lot of readers prefer Stevenson\'s', // segment_45
    'What\'s more, Stevenson\'s understanding of', // segment_46
    'made his works have the most unique expression of Scottish people.', // segment_47
    // Options A-I
    'A natural ability', // segment_48
    'B romance', // segment_49
    'C colorful language', // segment_50
    'D critical acclaim', // segment_51
    'E humor', // segment_52
    'F technical control', // segment_53
    'G storytelling', // segment_54
    'H depth', // segment_55
    'I human nature', // segment_56
];

// Build textSegments with cumulative offsets
let currentOffset = 0;
const newTextSegments = allStaticTexts.map((text, index) => {
    const segment = {
        id: `segment_${index}`,
        text: text,
        offset: currentOffset,
    };
    currentOffset += text.replace(/<[^>]*>/g, '').length;
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

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(plainText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
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

// Helper to get a highlighted version of a specific text
const getHighlightedText = (text: string) => {
    return getHighlightedSegmentById(text);
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
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && contentTextRef.value) {
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
                let targetSpan = range.startContainer;

                while (targetSpan && targetSpan.nodeType !== Node.ELEMENT_NODE) {
                    targetSpan = targetSpan.parentNode;
                }

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
                        segment = textSegments.value[2];
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

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
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

// Drag and drop handlers
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
        answers.value.q36,
        answers.value.q37,
        answers.value.q38,
        answers.value.q39,
        answers.value.q40,
    ].filter(Boolean);
    return summaryOptions.filter((opt) => !usedOptions.includes(opt.value));
});

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
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 part-header-box  border-gray-400  px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="segment_0"
                v-html="getHighlightedSegment(allStaticTexts[0])"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="segment_1"
                v-html="getHighlightedSegment(allStaticTexts[1])"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="space-y-2 p-6" :style="contentZoom">
                        <div class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span class="text-lg select-text" data-segment-id="segment_2"
                                        v-html="getHighlightedSegment(allStaticTexts[2])"></span>
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
                    <!-- Scrollable Questions Content -->
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">
                            <!-- Questions 27-31: Multiple Choice -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_3"
                                                v-html="getHighlightedSegment(allStaticTexts[3])"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_4"
                                            v-html="getHighlightedSegment(allStaticTexts[4])"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 27 -->
                                    <div id="question-27" class="relative p-2" @mouseenter="hoveredQuestion = 27"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                            @click.stop="toggleFlag(27)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700" data-segment-id="segment_5"
                                                v-html="getHighlightedSegment(allStaticTexts[5])"></p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q27" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_6"
                                                        v-html="getHighlightedSegment(allStaticTexts[6])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q27" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_7"
                                                        v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q27" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_8"
                                                        v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q27" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_9"
                                                        v-html="getHighlightedSegment(allStaticTexts[9])"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 28 -->
                                    <div id="question-28" class="relative p-2" @mouseenter="hoveredQuestion = 28"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                            @click.stop="toggleFlag(28)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700" data-segment-id="segment_10"
                                                v-html="getHighlightedSegment(allStaticTexts[10])"></p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q28" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_11"
                                                        v-html="getHighlightedSegment(allStaticTexts[11])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q28" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_12"
                                                        v-html="getHighlightedSegment(allStaticTexts[12])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q28" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_13"
                                                        v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q28" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_14"
                                                        v-html="getHighlightedSegment(allStaticTexts[14])"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 29 -->
                                    <div id="question-29" class="relative p-2" @mouseenter="hoveredQuestion = 29"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                            @click.stop="toggleFlag(29)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700" data-segment-id="segment_15"
                                                v-html="getHighlightedSegment(allStaticTexts[15])"></p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q29" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_16"
                                                        v-html="getHighlightedSegment(allStaticTexts[16])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q29" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_17"
                                                        v-html="getHighlightedSegment(allStaticTexts[17])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q29" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_18"
                                                        v-html="getHighlightedSegment(allStaticTexts[18])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q29" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_19"
                                                        v-html="getHighlightedSegment(allStaticTexts[19])"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 30 -->
                                    <div id="question-30" class="relative p-2" @mouseenter="hoveredQuestion = 30"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                            @click.stop="toggleFlag(30)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700" data-segment-id="segment_20"
                                                v-html="getHighlightedSegment(allStaticTexts[20])"></p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q30" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_21"
                                                        v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q30" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_22"
                                                        v-html="getHighlightedSegment(allStaticTexts[22])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q30" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_23"
                                                        v-html="getHighlightedSegment(allStaticTexts[23])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q30" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_24"
                                                        v-html="getHighlightedSegment(allStaticTexts[24])"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 31 -->
                                    <div id="question-31" class="relative p-2" @mouseenter="hoveredQuestion = 31"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                            @click.stop="toggleFlag(31)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700" data-segment-id="segment_25"
                                                v-html="getHighlightedSegment(allStaticTexts[25])"></p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q31" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_26"
                                                        v-html="getHighlightedSegment(allStaticTexts[26])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q31" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_27"
                                                        v-html="getHighlightedSegment(allStaticTexts[27])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q31" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_28"
                                                        v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q31" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="segment_29"
                                                        v-html="getHighlightedSegment(allStaticTexts[29])"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 32-35: True/False/Not Given -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_30"
                                                v-html="getHighlightedSegment(allStaticTexts[30])"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="segment_31"
                                            v-html="getHighlightedSegment(allStaticTexts[31])"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mt-2">
                                        <span class="font-bold text-gray-900" data-segment-id="segment_32"
                                            v-html="getHighlightedSegment(allStaticTexts[32])"></span><br />
                                        <span class="font-bold text-gray-900" data-segment-id="segment_33"
                                            v-html="getHighlightedSegment(allStaticTexts[33])"></span><br />
                                        <span class="font-bold text-gray-900" data-segment-id="segment_34"
                                            v-html="getHighlightedSegment(allStaticTexts[34])"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 32 -->
                                    <div id="question-32" class="relative p-2" @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                            @click.stop="toggleFlag(32)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p class="mb-3 text-gray-700" data-segment-id="segment_35"
                                            v-html="getHighlightedSegment(allStaticTexts[35])"></p>
                                        <div class="space-x-4 ml-6">
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q32" type="radio" value="TRUE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">TRUE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q32" type="radio" value="FALSE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">FALSE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q32" type="radio" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Question 33 -->
                                    <div id="question-33" class="relative p-2" @mouseenter="hoveredQuestion = 33"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                            @click.stop="toggleFlag(33)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p class="mb-3 text-gray-700" data-segment-id="segment_36"
                                            v-html="getHighlightedSegment(allStaticTexts[36])"></p>
                                        <div class="space-x-4 ml-6">
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q33" type="radio" value="TRUE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">TRUE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q33" type="radio" value="FALSE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">FALSE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q33" type="radio" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Question 34 -->
                                    <div id="question-34" class="relative p-2" @mouseenter="hoveredQuestion = 34"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                            @click.stop="toggleFlag(34)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p class="mb-3 text-gray-700" data-segment-id="segment_37"
                                            v-html="getHighlightedSegment(allStaticTexts[37])"></p>
                                        <div class="space-x-4 ml-6">
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q34" type="radio" value="TRUE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">TRUE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q34" type="radio" value="FALSE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">FALSE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q34" type="radio" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Question 35 -->
                                    <div id="question-35" class="relative p-2" @mouseenter="hoveredQuestion = 35"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                            @click.stop="toggleFlag(35)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p class="mb-3 text-gray-700" data-segment-id="segment_38"
                                            v-html="getHighlightedSegment(allStaticTexts[38])"></p>
                                        <div class="space-x-4 ml-6">
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q35" type="radio" value="TRUE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">TRUE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q35" type="radio" value="FALSE"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">FALSE</span>
                                            </label>
                                            <label class="inline-flex items-center space-x-2">
                                                <input v-model="answers.q35" type="radio" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 36-40: Summary completion with drag and drop -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_39"
                                                v-html="getHighlightedSegment(allStaticTexts[39])"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed font-medium text-gray-800">
                                        <span class="text-gray-700" data-segment-id="segment_40"
                                            v-html="getHighlightedSegment(allStaticTexts[40])"></span>
                                    </p>
                                </div>

                                <!-- Side by side layout: Notes (left) + Options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left side: Notes with drop zones -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                        <h4 class="mb-4 text-base font-bold text-gray-800">
                                            <span class="select-text" data-segment-id="segment_41"
                                                v-html="getHighlightedSegment(allStaticTexts[41])"></span>
                                        </h4>
                                        <div class="space-y-4 text-sm leading-relaxed text-gray-800">
                                            <p>
                                                <span class="select-text" data-segment-id="segment_42"
                                                    v-html="getHighlightedSegment(allStaticTexts[42])"></span>
                                                <span class="mx-2 inline-flex items-center space-x-2"
                                                    @mouseenter="hoveredQuestion = 36"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span class="font-bold text-gray-900 select-text">36.</span>
                                                    <span id="question-36"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                        :class="dragOverQuestion === 36 ? 'border-blue-500 bg-blue-50' : answers.q36 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 36)"
                                                        @dragleave="handleDragLeave" @drop="handleDrop($event, 36)"
                                                        @click="clearAnswer(36)"
                                                        :title="answers.q36 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q36 ? getOptionLabel(answers.q36) : '' }}
                                                    </span>
                                                    <button @click.stop="toggleFlag(36)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(36) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                                <span class="select-text" data-segment-id="segment_43"
                                                    v-html="getHighlightedSegment(allStaticTexts[43])"></span>
                                                <span class="mx-2 inline-flex items-center space-x-2"
                                                    @mouseenter="hoveredQuestion = 37"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span class="font-bold text-gray-900 select-text">37.</span>
                                                    <span id="question-37"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                        :class="dragOverQuestion === 37 ? 'border-blue-500 bg-blue-50' : answers.q37 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 37)"
                                                        @dragleave="handleDragLeave" @drop="handleDrop($event, 37)"
                                                        @click="clearAnswer(37)"
                                                        :title="answers.q37 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q37 ? getOptionLabel(answers.q37) : '' }}
                                                    </span>
                                                    <button @click.stop="toggleFlag(37)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(37) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                                <span class="select-text">.</span>
                                            </p>

                                            <p>
                                                <span class="select-text" data-segment-id="segment_44"
                                                    v-html="getHighlightedSegment(allStaticTexts[44])"></span>
                                                <span class="mx-2 inline-flex items-center space-x-2"
                                                    @mouseenter="hoveredQuestion = 38"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span class="font-bold text-gray-900 select-text">38.</span>
                                                    <span id="question-38"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                        :class="dragOverQuestion === 38 ? 'border-blue-500 bg-blue-50' : answers.q38 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 38)"
                                                        @dragleave="handleDragLeave" @drop="handleDrop($event, 38)"
                                                        @click="clearAnswer(38)"
                                                        :title="answers.q38 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q38 ? getOptionLabel(answers.q38) : '' }}
                                                    </span>
                                                    <button @click.stop="toggleFlag(38)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(38) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                                <span class="select-text" data-segment-id="segment_45"
                                                    v-html="getHighlightedSegment(allStaticTexts[45])"></span>
                                                <span class="mx-2 inline-flex items-center space-x-2"
                                                    @mouseenter="hoveredQuestion = 39"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span class="font-bold text-gray-900 select-text">39.</span>
                                                    <span id="question-39"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                        :class="dragOverQuestion === 39 ? 'border-blue-500 bg-blue-50' : answers.q39 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 39)"
                                                        @dragleave="handleDragLeave" @drop="handleDrop($event, 39)"
                                                        @click="clearAnswer(39)"
                                                        :title="answers.q39 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q39 ? getOptionLabel(answers.q39) : '' }}
                                                    </span>
                                                    <button @click.stop="toggleFlag(39)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(39) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                                <span class="select-text">.</span>
                                            </p>

                                            <p>
                                                <span class="select-text" data-segment-id="segment_46"
                                                    v-html="getHighlightedSegment(allStaticTexts[46])"></span>
                                                <span class="mx-2 inline-flex items-center space-x-2"
                                                    @mouseenter="hoveredQuestion = 40"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span class="font-bold text-gray-900 select-text">40.</span>
                                                    <span id="question-40"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all"
                                                        :class="dragOverQuestion === 40 ? 'border-blue-500 bg-blue-50' : answers.q40 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 40)"
                                                        @dragleave="handleDragLeave" @drop="handleDrop($event, 40)"
                                                        @click="clearAnswer(40)"
                                                        :title="answers.q40 ? 'Click to clear' : 'Drop answer here'">
                                                        {{ answers.q40 ? getOptionLabel(answers.q40) : '' }}
                                                    </span>
                                                    <button @click.stop="toggleFlag(40)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(40) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                                <span class="select-text" data-segment-id="segment_47"
                                                    v-html="getHighlightedSegment(allStaticTexts[47])"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Right side: Draggable options -->
                                    <div class="w-48 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium text-bold">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div v-for="option in availableOptions" :key="option.value"
                                                    class="flex cursor-grab items-center space-x-2 rounded border border-gray-300 px-2 py-1.5 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true" @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd">
                                                    <span class="font-bold text-gray-900">{{ option.value }}</span>
                                                    <span class="text-gray-700">{{ option.label }}</span>
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
                    "{{ selectedText }}"
                </p>
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
