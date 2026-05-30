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

const passageText = `

<b>A</b>
<br/>
Computer technology was supposed to replace paper. But that hasn't happened. Every country in the Western world uses more paper today, on a per-capita basis, than it did ten years ago. The consumption of uncoated free-sheet paper, for instance—the most common kind of office paper—rose almost fifteen per cent in the United States between 1995 and 2000. This is generally taken as evidence of how hard it is to eradicate old, wasteful habits and of how stubbornly resistant we are to the efficiencies offered by computerization. A number of cognitive psychologists and ergonomics experts, however, don't agree. Paper has persisted, they argue, for very good reasons: when it comes to performing certain kinds of cognitive tasks, paper has many advantages over computers. The dismay people feel at the sight of a messy desk—or the spectacle of air-traffic controllers tracking flights through notes scribbled on paper strips—arises from a fundamental confusion about the role that paper plays in our lives.
<br/><br/>
<b>B</b>
<br/>
The case for paper is made most eloquently in The Myth of the Paperless Office, by two social scientists, Abigail Sellen and Richard Harper. They begin their book with an account of a study they conducted at the International Monetary Fund, in Washington, D.C. Economists at the I.M.F. spend most of their time writing reports on complicated economic questions, work that would seem to be perfectly suited to sitting in front of a computer. Nonetheless, the I.M.F. is awash in paper, and Sellen and Harper wanted to find out why. Their answer is that the business of writing reports—at least at the I.M.F.—is an intensely collaborative process, involving the professional judgments and contributions of many people. The economists bring drafts of reports to conference rooms, spread out the relevant pages, and negotiate changes with one another. They go back to their offices and jot down comments in the margin, taking advantage of the freedom offered by the informality of the handwritten note. Then they deliver the annotated draft to the author in person, taking him, page by page, through the suggested changes. At the end of the process, the author spreads out all the pages with comments on his desk and starts to enter them on the computer—moving the pages around as he works, organizing and reorganizing, saving and discarding.
<br/><br/>
<b>C</b>
<br/>
Without paper, this kind of collaborative and iterative work process would be much more difficult. According to Sellen and Harper, paper has a unique set of "affordances"—that is, qualities that permit specific kinds of uses. Paper is tangible: we can pick up a document, flip through it, read little bits here and there, and quickly get a sense of it. Paper is spatially flexible, meaning that we can spread it out and arrange it in the way that suits us best. And it's tailorable: we can easily annotate it, and scribble on it as we read, without altering the original text. Digital documents, of course, have their own affordances. They can be easily searched, shared, stored, accessed remotely, and linked to other relevant material. But they lack the affordances that really matter to a group of people working together on a report.
<br/><br/>
<b>D</b>
<br/>
Paper enables a certain kind of thinking. Picture, for instance, the top of your desk. Chances are that you have a keyboard and a computer screen off to one side, and a clear space roughly eighteen inches square in front of your chair. What covers the rest of the desktop is probably piles—piles of papers, journals, magazines, binders, postcards, videotapes, and all the other artifacts of the knowledge economy. The piles look like a mess, but they aren't. When a group at Apple Computer studied piling behavior several years ago, they found that even the most disorderly piles usually make perfect sense to the piler, and that office workers could hold forth in great detail about the precise history and meaning of their piles. The pile closest to the cleared, eighteen-inch-square working area, for example, generally represents the most urgent business, and within that pile the most important document of all is likely to be at the top. Piles are living, breathing archives. Over time, they get broken down and resorted, sometimes chronologically and sometimes thematically and sometimes chronologically and thematically; clues about certain documents may be physically embedded in the file by, say, stacking a certain piece of paper at an angle or inserting dividers into the stack.
<br/><br/>
<b>E</b>
<br/>
But why do we pile documents instead of filing them? Because piles represent the process of active, ongoing thinking. The psychologist Alison Kidd, whose research Sellen and Harper refer to extensively, argues that "knowledge workers" use the physical space of the desktop to hold "ideas which they cannot yet categorize or even decide how they might use." The messy desk is not necessarily a sign of disorganization. It may be a sign of complexity: those who deal with many unresolved ideas simultaneously cannot sort and file the papers on their desks, because they haven't yet sorted and filed the ideas in their head. Kidd writes that many of the people she talked to use the papers on their desks as contextual cues to "recover a complex set of threads without difficulty and delay" when they come in on a Monday morning, or after their work has been interrupted by a phone call. What we see when we look at the piles on our desks is, in a sense, the contents of our brains.
<br/><br/>
<b>F</b>
<br/>
This idea that paper facilitates a highly specialized cognitive and social process is a far cry from the way we have historically thought about the stuff. Paper first began to proliferate in the workplace in the late nineteenth century as part of the move toward "systematic management." To cope with the complexity of the industrial economy, managers were instituting company-wide policies and demanding monthly, weekly, or even daily updates from their subordinates. Thus was born the monthly sales report, and the office manual and the internal company newsletter. The typewriter took off in the eighteen-eighties, making it possible to create documents in a fraction of the time it had previously taken, and that was followed closely by the advent of carbon paper, which meant that a typist could create ten copies of that document simultaneously. Paper was important not to facilitate creative collaboration and thought but as an instrument of control.`;

// Helper to get plain text length (strip HTML tags)
const getPlainTextLengthForOffset = (htmlText: string): number => {
    return htmlText.replace(/<[^>]*>/g, '').length;
};

// Define raw segments without offsets - offsets will be computed dynamically
const rawSegments = [
    { id: 'part-header', text: 'Part 3' },
    { id: 'part-instructions', text: 'Read the text and answer questions 27–40.' },
    { id: 'header-title', text: 'Paper or Computer?' },
    { id: 'passage', text: passageText },

    // Q27-32 Heading matching
    { id: 'q27-32-title', text: 'Questions 27-32' },
    { id: 'q27-32-instructions', text: 'The reading passage has six paragraphs, A-F. Choose the correct heading for each paragraph from the list of headings below.' },

    // List of Headings Title
    { id: 'heading-list-title', text: 'List of Headings' },

    // List of Headings
    { id: 'heading-i', text: 'I. Paper continued as a sharing or managing must' },
    { id: 'heading-ii', text: 'II. Piles can be more inspiring rather than disorganising' },
    { id: 'heading-iii', text: 'III. Favourable situation that economists used paper pages' },
    { id: 'heading-iv', text: 'IV. Overview of an unexpected situation: paper survived' },
    { id: 'heading-v', text: 'V. Comparison between efficiencies for using paper and using computer' },
    { id: 'heading-vi', text: 'VI. IMF\' paperless office seemed to be a waste of papers' },
    { id: 'heading-vii', text: 'VII. Example of failure for avoidance of paper record' },
    { id: 'heading-viii', text: 'VIII. There are advantages of using a paper in offices' },
    { id: 'heading-ix', text: 'IX. Piles reflect certain characteristics in people\' thought' },
    { id: 'heading-x', text: 'X. Joy of having the paper square in front of computer' },

    // Question labels
    { id: 'q27-label', text: '27. Paragraph A' },
    { id: 'q28-label', text: '28. Paragraph B' },
    { id: 'q29-label', text: '29. Paragraph C' },
    { id: 'q30-label', text: '30. Paragraph D' },
    { id: 'q31-label', text: '31. Paragraph E' },
    { id: 'q32-label', text: '32. Paragraph F' },

    // Q33-36 Summary completion
    { id: 'q33-36-title', text: 'Questions 33-36' },
    { id: 'q33-36-instructions', text: 'Complete the following summary of the paragraphs of Reading Passage. Using NO MORE THAN THREE WORDS from the Reading Passage for each answer.' },
    { id: 'q33-pre', text: 'Compared with digital documents, paper has several advantages. First it allows clerks to work in a' },
    { id: 'q33-post', text: 'way among colleagues. Next, paper is not like virtual digital version, it\'s' },
    { id: 'q34-post', text: 'Finally, because it is' },
    { id: 'q35-post', text: ', notes or comments can be effortlessly added as related information. However, shortcoming comes at the absence of convenience on task which is for a' },

    // Q37-40 Multiple choice
    { id: 'q37-40-title', text: 'Questions 37-40' },
    { id: 'q37-40-instructions', text: 'Choose the correct letter, A, B, C or D.' },
    { id: 'q37-num', text: '37.' },
    { id: 'q37', text: 'What do the economists from IMF say that their way of writing documents?' },
    { id: 'q37-a', text: 'they note down their comments for freedom on the drafts' },
    { id: 'q37-b', text: 'they finish all writing individually' },
    { id: 'q37-c', text: 'they share ideas on before electronic version was made' },
    { id: 'q37-d', text: 'they use electronic version fully' },
    { id: 'q38-num', text: '38.' },
    { id: 'q38', text: 'What is the implication of the "Piles" mentioned in the passage?' },
    { id: 'q38-a', text: 'they have underlying orders' },
    { id: 'q38-b', text: 'they are necessarily a mess' },
    { id: 'q38-c', text: 'they are in time sequence order' },
    { id: 'q38-d', text: 'they are in alphabetic order' },
    { id: 'q39-num', text: '39.' },
    { id: 'q39', text: 'What does the manager believe in sophisticated economy?' },
    { id: 'q39-a', text: 'recorded paper can be as management tool' },
    { id: 'q39-b', text: 'carbon paper should be compulsory' },
    { id: 'q39-c', text: 'Teamwork is the most important' },
    { id: 'q39-d', text: 'monthly report is the best way' },
    { id: 'q40-num', text: '40.' },
    { id: 'q40', text: 'According to the end of this passage, what is the reason why paper is not replaced by electronic version?' },
    { id: 'q40-a', text: 'paper is inexpensive to buy' },
    { id: 'q40-b', text: 'it contributed to management theories in western countries' },
    { id: 'q40-c', text: 'people need time for changing their old habit' },
    { id: 'q40-d', text: 'it is collaborative and functional for tasks implement and management' },
];

// Compute offsets dynamically based on cumulative plain text length
// Each segment gets a unique offset with a gap of 1 between segments to ensure no overlap
const computeSegmentOffsets = () => {
    let cumulativeOffset = 0;
    const SEGMENT_GAP = 1; // Gap between segments to ensure unique offsets

    return rawSegments.map((segment, index) => {
        const segmentWithOffset = {
            ...segment,
            offset: cumulativeOffset,
        };
        // Add the plain text length of this segment plus gap to the cumulative offset
        const textLength = getPlainTextLengthForOffset(segment.text);
        cumulativeOffset += textLength + SEGMENT_GAP;
        return segmentWithOffset;
    });
};

const textSegments = ref(computeSegmentOffsets());

// Debug: Verify all offsets are unique (can be removed in production)
const verifyUniqueOffsets = () => {
    const offsets = textSegments.value.map(s => s.offset);
    const uniqueOffsets = new Set(offsets);
    if (offsets.length !== uniqueOffsets.size) {
        console.warn('Duplicate offsets detected in textSegments!');
    }
};

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

const getPlainTextLength = (htmlText: string): number => {
    return htmlText.replace(/<[^>]*>/g, '').length;
};

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

const getAnswers = () => {
    return answers.value;
};

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
                // Walk up the DOM from the selection start to find an element with data-segment-id
                let node: Node | null = range.startContainer;
                let segmentEl: Element | null = null;

                while (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const el = node as Element;
                        if (el.hasAttribute('data-segment-id')) {
                            segmentEl = el;
                            break;
                        }
                    }
                    node = node.parentNode;
                }

                // If no data-segment-id found, fall back to class-based traversal
                if (!segmentEl) {
                    let targetSpan: Node | null = range.startContainer;
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
                    if (targetSpan && (targetSpan as Element).nodeType === Node.ELEMENT_NODE) {
                        segmentEl = targetSpan as Element;
                    }
                }

                if (!segmentEl) return;

                const segmentId = segmentEl.getAttribute('data-segment-id');
                let segment = null;

                if (segmentId) {
                    segment = textSegments.value.find((s) => s.id === segmentId);
                }

                // Fallback: match by text content
                if (!segment) {
                    const spanText = segmentEl.textContent || '';
                    const isPassageText = segmentEl.classList?.contains('text-lg') || spanText.length > 500;
                    if (isPassageText) {
                        segment = textSegments.value[3];
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
                }

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 bg-[#F1F2EC] px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header" v-html="getHighlightedSegment('Part 3')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions" v-html="getHighlightedSegment('Read the text and answer questions 27–40.')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" data-segment-id="header-title" v-html="getHighlightedSegment('Paper or Computer?')"></span>
                        </h2>
                    </div>

                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span
                                        class="text-lg text-gray-700"
                                        data-segment-id="passage"
                                        v-html="getHighlightedSegment(passageText)"
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
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 27-32: Heading Matching -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-lg font-bold text-gray-900">
                <span data-segment-id="q27-32-title" class="text-gray-700 select-text"
                    v-html="getHighlightedSegment('Questions 27-32')"></span>
            </h3>
        </div>
        <p class="mb-3 text-sm leading-relaxed text-gray-700">
            <span data-segment-id="q27-32-instructions" class="select-text"
                v-html="getHighlightedSegment('The reading passage has six paragraphs, A-F. Choose the correct heading for each paragraph from the list of headings below.')"></span>
        </p>

        <!-- List of Headings Box -->
        <div class="mb-6 border border-gray-300 bg-gray-50 p-4">
            <p class="mb-2 text-sm font-bold text-gray-900"
                data-segment-id="heading-list-title"
                v-html="getHighlightedSegment('List of Headings')"></p>
            <div class="space-y-1 text-sm text-gray-700">
                <p data-segment-id="heading-i"
                    v-html="getHighlightedSegment('I. Paper continued as a sharing or managing must')">
                </p>
                <p data-segment-id="heading-ii"
                    v-html="getHighlightedSegment('II. Piles can be more inspiring rather than disorganising')">
                </p>
                <p data-segment-id="heading-iii"
                    v-html="getHighlightedSegment('III. Favourable situation that economists used paper pages')">
                </p>
                <p data-segment-id="heading-iv"
                    v-html="getHighlightedSegment('IV. Overview of an unexpected situation: paper survived')">
                </p>
                <p data-segment-id="heading-v"
                    v-html="getHighlightedSegment('V. Comparison between efficiencies for using paper and using computer')">
                </p>
                <p data-segment-id="heading-vi"
                    v-html="getHighlightedSegment('VI. IMF\' paperless office seemed to be a waste of papers')">
                </p>
                <p data-segment-id="heading-vii"
                    v-html="getHighlightedSegment('VII. Example of failure for avoidance of paper record')">
                </p>
                <p data-segment-id="heading-viii"
                    v-html="getHighlightedSegment('VIII. There are advantages of using a paper in offices')">
                </p>
                <p data-segment-id="heading-ix"
                    v-html="getHighlightedSegment('IX. Piles reflect certain characteristics in people\' thought')">
                </p>
                <p data-segment-id="heading-x"
                    v-html="getHighlightedSegment('X. Joy of having the paper square in front of computer')">
                </p>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <!-- Question 27 -->
        <div id="question-27"
            class="flex flex-wrap items-center gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 27" 
            @mouseleave="hoveredQuestion = null">
            <span data-segment-id="q27-label" class="text-base text-gray-900 select-text"
                v-html="getHighlightedSegment('27. Paragraph A')"></span>
            <div class="flex items-center gap-2">
                <select v-model="answers.q27"
                    class="w-28 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                    <option value="" disabled selected>select</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                    <option value="VI">VI</option>
                    <option value="VII">VII</option>
                    <option value="VIII">VIII</option>
                    <option value="IX">IX</option>
                    <option value="X">X</option>
                </select>
                <div class="relative w-6 h-6">
                    <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                        @click.stop="toggleFlag(27)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Question 28 -->
        <div id="question-28"
            class="flex flex-wrap items-center gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 28" 
            @mouseleave="hoveredQuestion = null">
            <span data-segment-id="q28-label" class="text-base text-gray-900 select-text"
                v-html="getHighlightedSegment('28. Paragraph B')"></span>
            <div class="flex items-center gap-2">
                <select v-model="answers.q28"
                    class="w-28 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                    <option value="" disabled selected>select</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                    <option value="VI">VI</option>
                    <option value="VII">VII</option>
                    <option value="VIII">VIII</option>
                    <option value="IX">IX</option>
                    <option value="X">X</option>
                </select>
                <div class="relative w-6 h-6">
                    <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                        @click.stop="toggleFlag(28)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Question 29 -->
        <div id="question-29"
            class="flex flex-wrap items-center gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 29" 
            @mouseleave="hoveredQuestion = null">
            <span data-segment-id="q29-label" class="text-base text-gray-900 select-text"
                v-html="getHighlightedSegment('29. Paragraph C')"></span>
            <div class="flex items-center gap-2">
                <select v-model="answers.q29"
                    class="w-28 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                    <option value="" disabled selected>select</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                    <option value="VI">VI</option>
                    <option value="VII">VII</option>
                    <option value="VIII">VIII</option>
                    <option value="IX">IX</option>
                    <option value="X">X</option>
                </select>
                <div class="relative w-6 h-6">
                    <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                        @click.stop="toggleFlag(29)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Question 30 -->
        <div id="question-30"
            class="flex flex-wrap items-center gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 30" 
            @mouseleave="hoveredQuestion = null">
            <span data-segment-id="q30-label" class="text-base text-gray-900 select-text"
                v-html="getHighlightedSegment('30. Paragraph D')"></span>
            <div class="flex items-center gap-2">
                <select v-model="answers.q30"
                    class="w-28 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                    <option value="" disabled selected>select</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                    <option value="VI">VI</option>
                    <option value="VII">VII</option>
                    <option value="VIII">VIII</option>
                    <option value="IX">IX</option>
                    <option value="X">X</option>
                </select>
                <div class="relative w-6 h-6">
                    <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                        @click.stop="toggleFlag(30)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Question 31 -->
        <div id="question-31"
            class="flex flex-wrap items-center gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 31" 
            @mouseleave="hoveredQuestion = null">
            <span data-segment-id="q31-label" class="text-base text-gray-900 select-text"
                v-html="getHighlightedSegment('31. Paragraph E')"></span>
            <div class="flex items-center gap-2">
                <select v-model="answers.q31"
                    class="w-28 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                    <option value="" disabled selected>select</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                    <option value="VI">VI</option>
                    <option value="VII">VII</option>
                    <option value="VIII">VIII</option>
                    <option value="IX">IX</option>
                    <option value="X">X</option>
                </select>
                <div class="relative w-6 h-6">
                    <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                        @click.stop="toggleFlag(31)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Question 32 -->
        <div id="question-32"
            class="flex flex-wrap items-center gap-3 bg-white p-2"
            @mouseenter="hoveredQuestion = 32" 
            @mouseleave="hoveredQuestion = null">
            <span data-segment-id="q32-label" class="text-base text-gray-900 select-text"
                v-html="getHighlightedSegment('32. Paragraph F')"></span>
            <div class="flex items-center gap-2">
                <select v-model="answers.q32"
                    class="w-28 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                    <option value="" disabled selected>select</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                    <option value="VI">VI</option>
                    <option value="VII">VII</option>
                    <option value="VIII">VIII</option>
                    <option value="IX">IX</option>
                    <option value="X">X</option>
                </select>
                <div class="relative w-6 h-6">
                    <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                        @click.stop="toggleFlag(32)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

                            <!-- Questions 33-36: Summary Completion -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-lg font-bold text-gray-900">
                <span data-segment-id="q33-36-title" class="text-gray-700 select-text" v-html="getHighlightedSegment('Questions 33-36')"></span>
            </h3>
        </div>
        <p class="mb-2 text-sm leading-relaxed text-gray-700">
            <span data-segment-id="q33-36-instructions" class="select-text" v-html="getHighlightedSegment('Complete the following summary of the paragraphs of Reading Passage. Using NO MORE THAN THREE WORDS from the Reading Passage for each answer.')"></span>
        </p>
    </div>

    <!-- Summary paragraph with inline blanks - using bordered boxes instead of underlines -->
    <div class="rounded border border-gray-200 bg-gray-50 p-2 text-sm leading-relaxed text-gray-800">
        <p class="inline">
            <!-- pre-33 text -->
            <span
                data-segment-id="q33-pre"
                class="select-text inline"
                v-html="getHighlightedSegment('Compared with digital documents, paper has several advantages. First it allows clerks to work in a')"
            ></span>

            <!-- Q33 input - with fixed width container for bookmark -->
            <span
                id="question-33"
                class="inline-flex items-center mx-1 align-middle"
                @mouseenter="hoveredQuestion = 33"
                @mouseleave="hoveredQuestion = null"
            >
                <input
                    v-model="answers.q33"
                    type="text"
                    class="question-input border border-gray-900 bg-white px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none align-middle"
                    style="width: 200px; display: inline-block;"
                    placeholder="33"
                    maxlength="30"
                    spellcheck="false"
                    autocomplete="off"
                />
                <div class="relative w-5 h-5 ml-1 inline-block align-middle">
                    <button
                        v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                        @click.stop="toggleFlag(33)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </span>

            <!-- post-33 / pre-34 text -->
            <span
                data-segment-id="q33-post"
                class="select-text inline"
                v-html="getHighlightedSegment('way among colleagues. Next, paper is not like virtual digital version, it\'s')"
            ></span>

            <!-- Q34 input - with fixed width container for bookmark -->
            <span
                id="question-34"
                class="inline-flex items-center mx-1 mt-2 align-middle"
                @mouseenter="hoveredQuestion = 34"
                @mouseleave="hoveredQuestion = null"
            >
                <input
                    v-model="answers.q34"
                    type="text"
                    class="question-input border border-gray-900 bg-white px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none align-middle"
                    style="width: 200px; display: inline-block;"
                    placeholder="34"
                    maxlength="30"
                    spellcheck="false"
                    autocomplete="off"
                />
                <div class="relative w-5 h-5 ml-1 inline-block align-middle">
                    <button
                        v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                        @click.stop="toggleFlag(34)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </span>

            <!-- post-34 / pre-35 text -->
            <span
                data-segment-id="q34-post"
                class="select-text inline"
                v-html="getHighlightedSegment('Finally, because it is')"
            ></span>

            <!-- Q35 input - with fixed width container for bookmark -->
            <span
                id="question-35"
                class="inline-flex items-center mx-1 align-middle"
                @mouseenter="hoveredQuestion = 35"
                @mouseleave="hoveredQuestion = null"
            >
                <input
                    v-model="answers.q35"
                    type="text"
                    class="question-input border border-gray-900 bg-white px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none align-middle"
                    style="width: 200px; display: inline-block;"
                    placeholder="35"
                    maxlength="30"
                    spellcheck="false"
                    autocomplete="off"
                />
                <div class="relative w-5 h-5 ml-1 inline-block align-middle">
                    <button
                        v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                        @click.stop="toggleFlag(35)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </span>

            <!-- post-35 / pre-36 text -->
            <span
                data-segment-id="q35-post"
                class="select-text inline"
                v-html="getHighlightedSegment(', notes or comments can be effortlessly added as related information. However, shortcoming comes at the absence of convenience on task which is for a')"
            ></span>

            <!-- Q36 input - with fixed width container for bookmark -->
            <span
                id="question-36"
                class="inline-flex items-center mx-1 align-middle"
                @mouseenter="hoveredQuestion = 36"
                @mouseleave="hoveredQuestion = null"
            >
                <input
                    v-model="answers.q36"
                    type="text"
                    class="question-input border border-gray-900 bg-white px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none align-middle"
                    style="width: 200px; display: inline-block;"
                    placeholder="36"
                    maxlength="30"
                    spellcheck="false"
                    autocomplete="off"
                />
                <div class="relative w-5 h-5 ml-1 inline-block align-middle">
                    <button
                        v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                        @click.stop="toggleFlag(36)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </div>
            </span>
            <span class="select-text inline">.</span>
        </p>
    </div>
</div>

                            <!-- Questions 37-40: Multiple Choice -->
                            <div class="bg-white p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q37-40-title" v-html="getHighlightedSegment('Questions 37-40')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" data-segment-id="q37-40-instructions" v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Q37 -->
                                    <div
                                        id="question-37"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 37"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q37-num" v-html="getHighlightedSegment('37.')"></span>
                                            <p class="text-sm font-medium text-gray-900">
                                                <span class="text-gray-700" data-segment-id="q37" v-html="getHighlightedSegment('What do the economists from IMF say that their way of writing documents?')"></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="A" v-model="answers.q37" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q37-a" v-html="getHighlightedSegment('they note down their comments for freedom on the drafts')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="B" v-model="answers.q37" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q37-b" v-html="getHighlightedSegment('they finish all writing individually')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="C" v-model="answers.q37" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q37-c" v-html="getHighlightedSegment('they share ideas on before electronic version was made')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="D" v-model="answers.q37" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q37-d" v-html="getHighlightedSegment('they use electronic version fully')"></span>
                                            </label>
                                        </div>
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

                                    <!-- Q38 -->
                                    <div
                                        id="question-38"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q38-num" v-html="getHighlightedSegment('38.')"></span>
                                            <p class="text-sm font-medium text-gray-900">
                                                <span class="text-gray-700" data-segment-id="q38" v-html="getHighlightedSegment('What is the implication of the &quot;Piles&quot; mentioned in the passage?')"></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="A" v-model="answers.q38" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-a" v-html="getHighlightedSegment('they have underlying orders')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="B" v-model="answers.q38" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-b" v-html="getHighlightedSegment('they are necessarily a mess')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="C" v-model="answers.q38" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-c" v-html="getHighlightedSegment('they are in time sequence order')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="D" v-model="answers.q38" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-d" v-html="getHighlightedSegment('they are in alphabetic order')"></span>
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

                                    <!-- Q39 -->
                                    <div
                                        id="question-39"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q39-num" v-html="getHighlightedSegment('39.')"></span>
                                            <p class="text-sm font-medium text-gray-900">
                                                <span class="text-gray-700" data-segment-id="q39" v-html="getHighlightedSegment('What does the manager believe in sophisticated economy?')"></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="A" v-model="answers.q39" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-a" v-html="getHighlightedSegment('recorded paper can be as management tool')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="B" v-model="answers.q39" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-b" v-html="getHighlightedSegment('carbon paper should be compulsory')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="C" v-model="answers.q39" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-c" v-html="getHighlightedSegment('Teamwork is the most important')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="D" v-model="answers.q39" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-d" v-html="getHighlightedSegment('monthly report is the best way')"></span>
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

                                    <!-- Q40 -->
                                    <div
                                        id="question-40"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" data-segment-id="q40-num" v-html="getHighlightedSegment('40.')"></span>
                                            <p class="text-sm font-medium text-gray-900">
                                                <span class="text-gray-700" data-segment-id="q40" v-html="getHighlightedSegment('According to the end of this passage, what is the reason why paper is not replaced by electronic version?')"></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="A" v-model="answers.q40" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-a" v-html="getHighlightedSegment('paper is inexpensive to buy')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="B" v-model="answers.q40" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-b" v-html="getHighlightedSegment('it contributed to management theories in western countries')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="C" v-model="answers.q40" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-c" v-html="getHighlightedSegment('people need time for changing their old habit')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" value="D" v-model="answers.q40" class="mt-0.5 flex-shrink-0 accent-gray-900" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-d" v-html="getHighlightedSegment('it is collaborative and functional for tasks implement and management')"></span>
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
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
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
                        <button
                            @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                            title="Highlight"
                        >
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
                    <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                        Cancel
                    </button>
                    <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}

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