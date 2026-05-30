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

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

// Hover state for showing bookmark buttons
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

// Check if bookmark should be visible (on hover or when flagged)
const isBookmarkVisible = (questionNumber: number): boolean => {
    return hoveredQuestion.value === questionNumber || isQuestionFlagged(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Reading Part 3 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part3-panel-width';
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
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

const answers = ref({
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

// Drag and drop functionality
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Paragraph options for Q28-32
const paragraphOptions = [
    { value: 'A', label: 'A' },
    { value: 'B', label: 'B' },
    { value: 'C', label: 'C' },
    { value: 'D', label: 'D' },
    { value: 'E', label: 'E' },
    { value: 'F', label: 'F' },
    { value: 'G', label: 'G' },
    { value: 'H', label: 'H' },
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

// Get paragraph label for display
const getParagraphLabel = (value: string): string => {
    return value ? `Paragraph ${value}` : '';
};

// Word list options for Q33-37 (always available, can be reused)
const wordListOptions = [
    { value: 'initial', label: 'initial' },
    { value: 'limb', label: 'limb' },
    { value: 'movement', label: 'movement' },
    { value: 'dark', label: 'dark' },
    { value: 'placement', label: 'placement' },
    { value: 'entrain', label: 'entrain' },
    { value: 'stability', label: 'stability' },
    { value: 'muscles', label: 'muscles' },
    { value: 'sensory organs', label: 'sensory organs' },
    { value: 'tongue', label: 'tongue' },
    { value: 'representation', label: 'representation' },
    { value: 'picture', label: 'picture' },
];

// Computed: available paragraph options for Q28-32 (removes used ones)
const availableParagraphOptions = computed(() => {
    const usedOptions = [
        answers.value.q28,
        answers.value.q29,
        answers.value.q30,
        answers.value.q31,
        answers.value.q32,
    ].filter(Boolean);
    return paragraphOptions.filter((opt) => !usedOptions.includes(opt.value));
});

const passageText = ref(`
<strong>The Myth of the Five Senses</strong>

A
We see with our eyes and taste with our tongues. Ears are for hearing, skin is for feeling and noses are for smelling. Would anyone claim that ears can smell, or that tongues can see? As a matter of fact, yes. Paul Bach-y-Rita, a neuroscientist at the University of Wisconsin at Madison, believes that the senses are interchangeable; for instance, a tongue can be used for seeing. This "revolutionary" study actually stems from a relatively popular concept among scientists; that the brain is an accommodating organ. It will attempt to carry out the same function, even when part of it is damaged, by redirecting the function to another area of the brain. As opposed to previous mainstream scientists' understanding that the brain is compartmentalized, it is now more acceptable that the individual "part" of the brain could be somewhat interchangeable.

B
Paul Bach-y-Rita's experiments suggest that "we experience the five senses, but where the data comes from may not be so important". In the article "Can You See With Your Tongue?" the journalist was blindfolded with a small video camera strapped to his forehead, connected to a long plastic strip which was inserted into his mouth. A laptop computer would convert the video's image into a fewer number of pixels, and those pixels would travel through the plastic strip as electric current, reaching the grid of electrodes that was placed inside the man's mouth. The scientist told the man that she would soon be rolling a ball towards his right side, left side, or center, and he would have to catch it. And as the journalist stated, "my eyes and ears have no way to tell where it's going. That leaves my tongue... has more tactile nerve endings than any part of the body other than the lips". The scientist rolled the ball and a "tingling" passed over the man's tongue, and he reached out with his left hand and caught the ball.

C
If the brain can see a ball through a camera and a wet tongue, many new questions arise. What does this concept imply in terms of blindness and deafness? Rather than attempting to reserve these sensory disabilities through surgeries and hearing aids, should we be trying to circumvent them by using different receptors? Can we still trust in the idea of the five senses, or was it wrong to categorize our perception of the outside world so strictly?

D
In fact, the "five senses" may well be another story that should be discarded in lieu of new observation. Aside from the emerging possibility of interchanging a tongue and an eye, there is the highly accepted possibility that our original list of senses is incomplete. Many scientists would add at least these two senses to the list: the kinesthetic sense and the vestibular sense. The first is a sense of self, mostly in terms of limbs and their placement. For instance, I know where my right foot is without looking or feeling for it. It is something that my brain "knows". This is said to be because of information sent to the brain by the muscles, implying that muscles should be added to the list of sensory organs. If more observations were to be collected on this subject, a more accommodating explanation could potentially be reached. Secondly, the vestibular sense is what most would consider a sense of balance.

E
Why were these two senses not included in our limited list? It might be the result of a lack of external symbolism. A nose or an eye is an obvious curiosity because of the question it generates: "What does this thing do?" But we have no limb or facial organ dedicated to balance or to kinesthetic awareness. On the other hand, if the vestibular sense and the kinesthetic senses occur solely in the brain, are they truly senses? Should experiences be labeled as senses without representation by an external organ? If one believes that the brain is the true sensory organ and the rest are simply interchangeable receptors, then yes, we should remain open to labeling many new "experiences" as "senses". But, is there perhaps an overlying truth that directly relates the five senses to the human experience of life?

F
One way of gaining new insight is to explore the animal world of senses. Migrating animals, for example, are said to have a "sixth sense", a term which alludes to all unexplainable phenomenon. In reality, what we call the sixth sense includes any number of unrelated senses that everyday humans do not possess and therefore know little about. Perhaps there is a sense of placement on the earth, similar to the kinesthetic sense of bodily placement, which helps animals return home. Perhaps it is simply a "sense of direction" that is more developed or more substantial than what humans possess. Scientists have even conjectured that traces of magnetite, found in pigeons and monarch butterflies, could be used as a compass, enabling the animal to sense the magnetic fields of the earth. Those who use the term "mysterious sixth sense" rarely give details about which of these strange abilities they are referring to. The term relating to "past our understanding" is used in such a sweeping, general way that there is no one solid, falsifiable hypothesis. This term does not bring us closer to our understanding of the senses.

G
In addition to internal mysteries, many animals also possess external sensory organs which we do not. Fish, for instance, have an organ that runs along the sides of their bodies called the lateral-line system. It is made of tiny hair-like sensors that receive information about movements in the water. There is even the ability to distinguish between ordinary, background movement and strange movement that could signify a predator or another creature. This sense also helps the fish to "orient themselves within the current and the stream flow". Interestingly, "land vertebrates... lost their lateral-line systems somewhere along the evolutionary path, all vertebrates started out with them... " Of course, we no longer consider this sense to be a human perception of life because we no longer possess the organ. But has the sense remained? Perhaps the feeling of being watched, of being followed on a dark sidewalk, is a dull shadow of the sense we used to possess. It is particularly noteworthy that this "feeling" of being followed is often referred to as "intuition". How is intuition related to senses? In the same sense, how are emotions and senses the same?

H
New stories that could expand our categorical concepts of the senses are emerging constantly, but we seem to prefer holding onto the old concept of five senses. We would urge towards expanding that category numerically and conceptually. There is much to be explored in terms of the relation of sense and emotion, the utilizations and disabilities of the senses, and a vertebrate's need for senses compared to other types of animals, in terms of participating in life. The interconnectedness of our senses within the brain and among the external organs is a concept worthy of more attention and exploration, and it will be explored more easily when the old, rather arbitrary myth of the five senses is discarded.`);

// Define all text segments in order of appearance
const allSegments = [
    // Part 3 Header
    { id: 'part-header', text: 'Part 3' },
    { id: 'part-instructions', text: 'Read the text and answer questions 28-40.' },
    // Passage Panel
    { id: 'passage-title', text: 'The Myth of the Five Senses' },
    // Passage text
    { id: 'passage', text: passageText.value },

    // Questions 28-32
    { id: 'q28-32-title', text: 'Questions 28-32' },
    { id: 'q28-32-instructions', text: 'Reading Passage 3 contains 8 paragraphs A-H.' },
    { id: 'q28-32-which', text: 'Which paragraphs state the following information?' },
    { id: 'q28-32-write', text: 'Write the appropriate letters ' },
    { id: 'q28-32-letters', text: 'A-H' },
    { id: 'q28-32-boxes', text: ' in boxes 28-32 on your answer sheet.' },

    // Question 28
    { id: 'q28-num', text: '28.' },
    { id: 'q28', text: 'Practices of animal migration have helped expand our knowledge of the senses.' },

    // Question 29
    { id: 'q29-num', text: '29.' },
    { id: 'q29', text: 'The subject caught the ball with the help of his tongue.' },

    // Question 30
    { id: 'q30-num', text: '30.' },
    { id: 'q30', text: 'The brain knows where my right foot is without looking at it.' },

    // Question 31
    { id: 'q31-num', text: '31.' },
    { id: 'q31', text: "An example showing that people's intuition may work." },

    // Question 32
    { id: 'q32-num', text: '32.' },
    { id: 'q32', text: 'Humans probably lost a kind of sensory organ during evolution.' },

    // Questions 33-37
    { id: 'q33-37-title', text: 'Questions 33-37' },
    { id: 'q33-37-complete', text: 'Complete the summary below.' },
    { id: 'q33-37-choose', text: 'Choose your answer from the list below and write them in boxes 33-37 on your answer sheet.' },
    { id: 'q33-37-nb', text: 'NB There are more words than spaces so you will not use them all.' },

    // Summary text broken into parts for inline drop zones
    { id: 'q33-37-summary-1', text: 'Many scientists believe that our' },
    { id: 'q33-37-summary-2', text: 'list of senses lacks other important elements, like the sense of kinesthetic and vestibular. For the first itself, majority cases are about the' },
    { id: 'q33-37-summary-3', text: 'of our arms and legs. For example, we can feel our feet without looking for them, due to the information link between brain and our' },
    { id: 'q33-37-summary-4', text: '. For the vestibular sense, it would provide us with' },
    { id: 'q33-37-summary-5', text: '. That these two senses are excluded from our list might be the result of a lack of external' },
    { id: 'q33-37-summary-6', text: '.' },

    // Word list items
    { id: 'wl-initial', text: 'initial' },
    { id: 'wl-limb', text: 'limb' },
    { id: 'wl-movement', text: 'movement' },
    { id: 'wl-dark', text: 'dark' },
    { id: 'wl-placement', text: 'placement' },
    { id: 'wl-entrain', text: 'entrain' },
    { id: 'wl-stability', text: 'stability' },
    { id: 'wl-muscles', text: 'muscles' },
    { id: 'wl-sensory-organs', text: 'sensory organs' },
    { id: 'wl-tongue', text: 'tongue' },
    { id: 'wl-representation', text: 'representation' },
    { id: 'wl-picture', text: 'picture' },

    // Questions 38-40
    { id: 'q38-40-title', text: 'Questions 38-40' },
    { id: 'q38-40-agree', text: 'Do the following statements agree with the claims of the writer in Reading Passage 3?' },
    { id: 'q38-40-true-label', text: 'TRUE' },
    { id: 'q38-40-true-desc', text: 'if the statement is true,' },
    { id: 'q38-40-false-label', text: 'FALSE' },
    { id: 'q38-40-false-desc', text: 'if the statement is false' },
    { id: 'q38-40-not-given-label', text: 'NOT GIVEN' },
    { id: 'q38-40-not-given-desc', text: 'if the information is not given in the passage.' },

    // Question 38
    { id: 'q38-num', text: '38.' },
    { id: 'q38', text: 'Senses are transposable just as the tongue can also be used to hear sounds.' },
    { id: 'q38-true', text: 'TRUE' },
    { id: 'q38-false', text: 'FALSE' },
    { id: 'q38-not-given', text: 'NOT GIVEN' },

    // Question 39
    { id: 'q39-num', text: '39.' },
    { id: 'q39', text: 'Animals are considered to have senses other than the original five.' },
    { id: 'q39-true', text: 'TRUE' },
    { id: 'q39-false', text: 'FALSE' },
    { id: 'q39-not-given', text: 'NOT GIVEN' },

    // Question 40
    { id: 'q40-num', text: '40.' },
    { id: 'q40', text: 'New stories and research have persuaded us to accept the conception of five senses.' },
    { id: 'q40-true', text: 'TRUE' },
    { id: 'q40-false', text: 'FALSE' },
    { id: 'q40-not-given', text: 'NOT GIVEN' },
];

// Calculate cumulative offsets dynamically
let currentOffset = 0;
const textSegments = ref(
    allSegments.map((segment) => {
        const segmentWithOffset = {
            id: segment.id,
            text: segment.text,
            offset: currentOffset,
        };
        currentOffset += segment.text.length;
        return segmentWithOffset;
    })
);

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

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegment = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;
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
        part: 'Part 3',
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
        }
    } else {
        closeDeleteTooltip();
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
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
                hoveredNoteId.value = note.id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    if (target.closest('mark[data-note-id]')) {
        if (relatedTarget && relatedTarget.closest('.note-hover-tooltip')) {
            return;
        }
        showNoteTooltip.value = false;
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
    }
};

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
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

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
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
        <!-- Part 3 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header"
                v-html="getHighlightedSegment('part-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions"
                v-html="getHighlightedSegment('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    

                    <div class="space-y-6 px-4" :style="contentZoom">
                        <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-base">
                            <div class="px-4">
                                <div class="text-justify leading-relaxed  whitespace-pre-wrap ">
                                    <span class="text-base text-gray-700 select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment('passage')"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 28-32: Paragraph Matching -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-base font-bold text-gray-900">
                <span class="text-gray-700 select-text" data-segment-id="q28-32-title"
                    v-html="getHighlightedSegment('q28-32-title')"></span>
            </h3>
        </div>
        <div class=" p-2">
            <p class="leading-relaxed text-sm  ">
                <span data-segment-id="q28-32-instructions"
                    v-html="getHighlightedSegment('q28-32-instructions')"></span><br />
                <span data-segment-id="q28-32-which"
                    v-html="getHighlightedSegment('q28-32-which')"></span><br />
                <span data-segment-id="q28-32-write"
                    v-html="getHighlightedSegment('q28-32-write')"></span><strong
                    class="text-gray-900" data-segment-id="q28-32-letters"
                    v-html="getHighlightedSegment('q28-32-letters')"></strong>
                <span data-segment-id="q28-32-boxes"
                    v-html="getHighlightedSegment('q28-32-boxes')"></span>
            </p>
        </div>
    </div>

    <!-- Questions with select dropdowns -->
    <div class="space-y-4">
        <!-- Question 28 -->
        <div id="question-28" class="relative flex items-start gap-3 pr-10"
            @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
            <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q28-num"
                v-html="getHighlightedSegment('q28-num')"></span>
            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                <span class="select-text" data-segment-id="q28"
                    v-html="getHighlightedSegment('q28')"></span>
            </p>
            <select v-model="answers.q28"
                class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
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
            <button v-show="isBookmarkVisible(28)" @click.stop="emit('toggleFlag', 28)"
                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>

        <!-- Question 29 -->
        <div id="question-29" class="relative flex items-start gap-3 pr-10"
            @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
            <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q29-num"
                v-html="getHighlightedSegment('q29-num')"></span>
            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                <span class="select-text" data-segment-id="q29"
                    v-html="getHighlightedSegment('q29')"></span>
            </p>
            <select v-model="answers.q29"
                class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
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
            <button v-show="isBookmarkVisible(29)" @click.stop="emit('toggleFlag', 29)"
                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>

        <!-- Question 30 -->
        <div id="question-30" class="relative flex items-start gap-3 pr-10"
            @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
            <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q30-num"
                v-html="getHighlightedSegment('q30-num')"></span>
            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                <span class="select-text" data-segment-id="q30"
                    v-html="getHighlightedSegment('q30')"></span>
            </p>
            <select v-model="answers.q30"
                class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
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
            <button v-show="isBookmarkVisible(30)" @click.stop="emit('toggleFlag', 30)"
                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>

        <!-- Question 31 -->
        <div id="question-31" class="relative flex items-start gap-3 pr-10"
            @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
            <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q31-num"
                v-html="getHighlightedSegment('q31-num')"></span>
            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                <span class="select-text" data-segment-id="q31"
                    v-html="getHighlightedSegment('q31')"></span>
            </p>
            <select v-model="answers.q31"
                class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
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
            <button v-show="isBookmarkVisible(31)" @click.stop="emit('toggleFlag', 31)"
                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>

        <!-- Question 32 -->
        <div id="question-32" class="relative flex items-start gap-3 pr-10"
            @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
            <span class="font-bold text-gray-900 select-text shrink-0" data-segment-id="q32-num"
                v-html="getHighlightedSegment('q32-num')"></span>
            <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                <span class="select-text" data-segment-id="q32"
                    v-html="getHighlightedSegment('q32')"></span>
            </p>
            <select v-model="answers.q32"
                class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
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
            <button v-show="isBookmarkVisible(32)" @click.stop="emit('toggleFlag', 32)"
                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>
    </div>
</div>

                            <!-- Questions 33-37: Summary Completion -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-base font-bold text-gray-900">
                <span class="text-gray-700 select-text" data-segment-id="q33-37-title"
                    v-html="getHighlightedSegment('q33-37-title')"></span>
            </h3>
        </div>
        <div class="p-2">
            <p class="leading-relaxed text-sm">
                <span data-segment-id="q33-37-complete"
                    v-html="getHighlightedSegment('q33-37-complete')"></span><br />
                <span data-segment-id="q33-37-choose"
                    v-html="getHighlightedSegment('q33-37-choose')"></span>
            </p>
            <p class="mt-2 text-sm font-medium text-gray-700">
                <strong data-segment-id="q33-37-nb"
                    v-html="getHighlightedSegment('q33-37-nb')"></strong>
            </p>
        </div>
    </div>

    <!-- Two-column layout: Summary on left, Word list on right -->
    <div class="flex gap-6">
        <!-- Left side: Summary paragraph with inline drop zones -->
        <div class="flex-1 border border-gray-900 p-4">
            <p class="text-sm leading-loose text-gray-800">
                <span data-segment-id="q33-37-summary-1"
                    v-html="getHighlightedSegment('q33-37-summary-1')"></span>
                <!-- Q33 drop zone -->
                <span id="question-33"
                    class="inline-flex min-h-7 min-w-24 mx-1 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-xs transition-all cursor-pointer align-middle relative"
                    :class="dragOverQuestion === 33 ? 'border-blue-500 bg-blue-50' : answers.q33 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 33)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 33)" @click="clearAnswer(33)"
                    @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null"
                    :title="answers.q33 ? 'Click to clear' : 'Drop answer here'">
                    {{ answers.q33 ? answers.q33 : '33' }}
                    <button v-show="isBookmarkVisible(33)"
                        @click.stop="emit('toggleFlag', 33)"
                        class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                        :class="isQuestionFlagged(33) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
                <span data-segment-id="q33-37-summary-2"
                    v-html="getHighlightedSegment('q33-37-summary-2')"></span>
                <!-- Q34 drop zone -->
                <span id="question-34"
                    class="inline-flex min-h-7 min-w-24 mx-1 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-xs transition-all cursor-pointer align-middle relative"
                    :class="dragOverQuestion === 34 ? 'border-blue-500 bg-blue-50' : answers.q34 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 34)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 34)" @click="clearAnswer(34)"
                    @mouseenter="hoveredQuestion = 34" @mouseleave="hoveredQuestion = null"
                    :title="answers.q34 ? 'Click to clear' : 'Drop answer here'">
                    {{ answers.q34 ? answers.q34 : '34' }}
                    <button v-show="isBookmarkVisible(34)"
                        @click.stop="emit('toggleFlag', 34)"
                        class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                        :class="isQuestionFlagged(34) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
                <span data-segment-id="q33-37-summary-3"
                    v-html="getHighlightedSegment('q33-37-summary-3')"></span>
                <!-- Q35 drop zone -->
                <span id="question-35"
                    class="inline-flex min-h-7 min-w-24 mx-1 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-xs transition-all cursor-pointer align-middle relative"
                    :class="dragOverQuestion === 35 ? 'border-blue-500 bg-blue-50' : answers.q35 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 35)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 35)" @click="clearAnswer(35)"
                    @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null"
                    :title="answers.q35 ? 'Click to clear' : 'Drop answer here'">
                    {{ answers.q35 ? answers.q35 : '35' }}
                    <button v-show="isBookmarkVisible(35)"
                        @click.stop="emit('toggleFlag', 35)"
                        class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                        :class="isQuestionFlagged(35) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
                <span data-segment-id="q33-37-summary-4"
                    v-html="getHighlightedSegment('q33-37-summary-4')"></span>
                <!-- Q36 drop zone -->
                <span id="question-36"
                    class="inline-flex min-h-7 min-w-24 mx-1 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-xs transition-all cursor-pointer align-middle relative"
                    :class="dragOverQuestion === 36 ? 'border-blue-500 bg-blue-50' : answers.q36 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 36)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 36)" @click="clearAnswer(36)"
                    @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null"
                    :title="answers.q36 ? 'Click to clear' : 'Drop answer here'">
                    {{ answers.q36 ? answers.q36 : '36' }}
                    <button v-show="isBookmarkVisible(36)"
                        @click.stop="emit('toggleFlag', 36)"
                        class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                        :class="isQuestionFlagged(36) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
                <span data-segment-id="q33-37-summary-5"
                    v-html="getHighlightedSegment('q33-37-summary-5')"></span>
                <!-- Q37 drop zone -->
                <span id="question-37"
                    class="inline-flex min-h-7 min-w-24 mx-1 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-xs transition-all cursor-pointer align-middle relative"
                    :class="dragOverQuestion === 37 ? 'border-blue-500 bg-blue-50' : answers.q37 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 37)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 37)" @click="clearAnswer(37)"
                    @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null"
                    :title="answers.q37 ? 'Click to clear' : 'Drop answer here'">
                    {{ answers.q37 ? answers.q37 : '37' }}
                    <button v-show="isBookmarkVisible(37)"
                        @click.stop="emit('toggleFlag', 37)"
                        class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                        :class="isQuestionFlagged(37) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                </span>
                <span data-segment-id="q33-37-summary-6"
                    v-html="getHighlightedSegment('q33-37-summary-6')"></span>
            </p>
        </div>

        <!-- Right side: Word list - draggable options -->
        <div class="w-56 shrink-0 self-start sticky top-12">
            <p class="mb-2 text-sm font-bold text-gray-900">Word List — drag words into the blanks:</p>
            <div class="border border-gray-900 p-3 bg-white">
                <div class="flex flex-col gap-2">
                    <div v-for="option in wordListOptions" :key="option.value"
                        class="flex cursor-grab items-center rounded border border-gray-300 px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                        :class="{ 'opacity-50': draggedOption === option.value }"
                        draggable="true" @dragstart="handleDragStart($event, option.value)"
                        @dragend="handleDragEnd">
                        <span class="text-gray-800 text-sm">{{ option.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                            <!-- Questions 38-40: TRUE / FALSE / NOT GIVEN -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q38-40-title"
                                                v-html="getHighlightedSegment('q38-40-title')"></span>
                                        </h3>
                                    </div>
                                    <div class="border border-gray-300 p-6">
                                        <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-base">
                                            <span data-segment-id="q38-40-agree"
                                                v-html="getHighlightedSegment('q38-40-agree')"></span>
                                        </p>
                                        <div class="grid grid-cols-1 gap-2 pt-4 text-base sm:text-base">
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900"
                                                    data-segment-id="q38-40-true-label"
                                                    v-html="getHighlightedSegment('q38-40-true-label')"></span>
                                                <span class="text-gray-700 select-text" data-segment-id="q38-40-true-desc"
                                                    v-html="getHighlightedSegment('q38-40-true-desc')"></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold"
                                                    data-segment-id="q38-40-false-label"
                                                    v-html="getHighlightedSegment('q38-40-false-label')"></span>
                                                <span class="text-gray-700 select-text" data-segment-id="q38-40-false-desc"
                                                    v-html="getHighlightedSegment('q38-40-false-desc')"></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                    data-segment-id="q38-40-not-given-label"
                                                    v-html="getHighlightedSegment('q38-40-not-given-label')"></span>
                                                <span class="text-gray-700 select-text" data-segment-id="q38-40-not-given-desc"
                                                    v-html="getHighlightedSegment('q38-40-not-given-desc')"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-6">
                                    <!-- Question 38 -->
                                    <div id="question-38" class="relative pr-10" @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q38-num"
                                                v-html="getHighlightedSegment('q38-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base font-bold leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q38"
                                                        v-html="getHighlightedSegment('q38')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q38" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q38-true"
                                                            v-html="getHighlightedSegment('q38-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q38" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q38-false"
                                                            v-html="getHighlightedSegment('q38-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q38" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q38-not-given"
                                                            v-html="getHighlightedSegment('q38-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(38)" @click="emit('toggleFlag', 38)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 39 -->
                                    <div id="question-39" class="relative pr-10" @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q39-num"
                                                v-html="getHighlightedSegment('q39-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base font-bold leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q39"
                                                        v-html="getHighlightedSegment('q39')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q39" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q39-true"
                                                            v-html="getHighlightedSegment('q39-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q39" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q39-false"
                                                            v-html="getHighlightedSegment('q39-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q39" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q39-not-given"
                                                            v-html="getHighlightedSegment('q39-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(39)" @click="emit('toggleFlag', 39)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 40 -->
                                    <div id="question-40" class="relative pr-10" @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q40-num"
                                                v-html="getHighlightedSegment('q40-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base font-bold leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q40"
                                                        v-html="getHighlightedSegment('q40')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q40" value="TRUE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q40-true"
                                                            v-html="getHighlightedSegment('q40-true')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q40" value="FALSE"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q40-false"
                                                            v-html="getHighlightedSegment('q40-false')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q40" value="NOT GIVEN"
                                                            class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q40-not-given"
                                                            v-html="getHighlightedSegment('q40-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(40)" @click="emit('toggleFlag', 40)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
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
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
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
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{
                            hoveredNoteText }}</span>
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
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{
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

.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
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

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>