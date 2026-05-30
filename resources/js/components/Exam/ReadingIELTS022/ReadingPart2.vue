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

const answers = ref({
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
    q21_22: [] as string[],
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

// Drag and drop functionality for questions 14-18
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const speciesOptions = [
    { value: 'A', label: 'Homo neanderthalensis', fullText: 'A Homo neanderthalensis' },
    { value: 'B', label: 'Homo sapiens', fullText: 'B Homo sapiens' },
    { value: 'C', label: 'Both species', fullText: 'C Both species' },
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
const getSpeciesLabel = (value: string): string => {
    const option = speciesOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Text segments with their cumulative offsets in the full text
const passageText = `<b>A. </b>The connection between science and play is not often discussed, perhaps because scientists take it for granted, or because they are a little self-conscious about it and therefore try to hide it. In this context, play might describe a number of different activities. Play might involve the exploration of new uses for everyday objects just for curiosity; it could also include word jokes, or the playing of jokes or tricks upon other scientists. The element of play in science is thus an elusive and difficult topic, but it is fundamental to the experience of scientists.
</br> </br><b>B. </b>One example of this connection is the jigsaw puzzle. Such puzzles present their players with two-dimensional fragments, each with a characteristic shape, from which to reconstruct an overall picture. Guessing the solution of a scientific problem has many similarities to completing a jigsaw puzzle. The scientists inspect each piece of data for a possible fit with its neighbours and, bit by bit, construct a whole argument. People who are good at jigsaw puzzles are able to guess which piece will fit even before trying it. In the same way, the best scientists are those who make the best guesses.
</br> </br><b>C. </b>But scientists do not play only at out-guessing each other, or at pulling together pieces to complete a puzzle. They also regard their work as “playing” with “toys”. Joseph Lambert, a professor of chemistry at Northwestern University in the US, sheds some light on this tendency. In a letter he wrote the followingWhen I grew up, every kid put in some serious sandbox time, and it often involved building complex sand structures around which fantasies were composed. The organic chemistry labs at Yale were similar. Chemical transformations took place, the odours were pleasant, and they were fun in the same way. We mixed things up. The physical process of working with our hands, as with sand, was satisfying. By the end of the year, I knew that I wanted to be an organic chemist, as I realised one could play in the sandbox for a livingIndeed, many scientists amuse themselves by “playing” with various “toys” of their trade, perhaps coming up with ingenious devices to get a particular job done, or diverting a piece of commercial equipment for novel scientific purposes. The apparatus put together by Robert Millikan and Harvey Fletcher to measure the charge of the electron, which involved a perfume atomiser bought at a local pharmacy, is a classic example of such inspired tinkering.

</br> </br><b>D. </b>Whereas society often keeps a lid on playfulness, science encourages and nurtures it. Take, for example, the funny names that scientists have given to various chemical substances: trislane, windowpane, penguinone or megaphone. Each class of organic molecules includes a few such humorous names. Similar fun is had in other fields of science.
</br> </br><b>E. </b>Hoaxes are a further aspect of the playfulness of scientists. A relatively recent example concerns the “Plate of Brasses”, which stated England’s claim to California and was supposedly left by the English navigator Sir Francis Drake during his visit in 1579. A brass plate thought to be Drake’s was discovered in the 1930s. But in 1977, Helen Michel and Frank Asaro, of the Lawrence Berkeley National Laboratory, showed that the copper and zinc used in the plate were of a higher quality than would have been available in the 1500s. They concluded that Drake’s plate was most likely crafted much closer to the time when it was first brought to light.
</br> </br><b>F. </b>Shortly afterwards, staff at the University of California announced that the artefact was devised as a practical joke by a group of friends of Herbert E. Bolton, who directed the Bancroft Library at the University’s Berkeley campus. Bolton, it seems, was intrigued by tales of Drake having installed a plate to record his visit to California, and often urged his students to look for it. Some of Bolton’s friends decided to play a joke on him, but things miscarried after Bolton went public and announced that the relic was authentic before he could be told about the joke.
</br> </br><b>G. </b>Sometimes hoaxes serve a useful purpose. Some years ago, Nathan Lewis, a professor of chemistry at the California Institute of Technology, and a graduate student were doing experiments in the laboratory of a senior professor, Harry Gray. Another co-worker had the habit of going through their data and rushing to Gray with his interpretation. Lewis decided to set a trap for the co-worker. He recalls:“I manufactured an NMR spectrum. We left it out as bait. Our colleague took it and wrote up a paper on how important this result was. He was ready to go right to the Journal of the American Chemical Society. We did not let him mail it, but this stopped him temporarily from taking our data and interpreting it before making sure that it was right.”
</br> </br><b>H. </b>But is the playfulness of science usually so helpful? One might argue that to play a practical joke is a waste of time. So why do it? Perhaps play is an inherent part of the human condition. The psychologist’s answer might be that scientists tend to play because science presents them with too much seriousness. Another possibility might be that scientists like to play because they tend to be very young. Some disciplines, mathematics especially, have a reputation for the narrow window of creativity in youth. But it may also be that there is some cognitive value to the playful element in science. Playing with ideas, after all, is what science is all about. A playful, childlike attitude maybe extremely fruitful, and scientists should not be too embarrassed to acknowledge that play is often what motivates them`;

// Text segments with unique, non-overlapping offsets
// Each offset = previous offset + previous text length + gap (10-15 chars)
const textSegments = ref([
    // Part header
    { id: 'part2-title', text: 'Part 2', offset: 0 },                                            // len=6, ends=6
    { id: 'part2-desc', text: 'Read the text and answer questions 14–26.', offset: 20 },        // len=42, ends=62
    // Passage title and intro
    { id: 0, text: 'Playing with science', offset: 100 },                                        // len=20, ends=120
    { id: 1, text: 'Is science always a serious matter?', offset: 135 },                         // len=35, ends=170
    { id: 2, text: 'According to Pierre Laszlo, it is not.', offset: 185 },                      // len=38, ends=223
    { id: 3, text: 'He believes that scientists are often similar to children at play.', offset: 240 }, // len=66, ends=306
    // Main passage content (~7600 chars)
    { id: 4, text: passageText, offset: 320 },                                                   // ends ~7920
    // Questions section header (start at 8000)
    { id: 5, text: 'Questions', offset: 8000 },                                                  // len=9, ends=8009
    { id: 6, text: 'Answer all questions based on Reading Passage 2.', offset: 8025 },           // len=48, ends=8073
    // Questions 14–20
    { id: 'q14-20', text: 'Questions 14–20', offset: 8090 },                                     // len=15, ends=8105
    { id: 7, text: 'Reading Passage 2 has eight paragraphs, A–H.', offset: 8120 },               // len=45, ends=8165
    { id: 8, text: 'Which paragraph contains the following information?', offset: 8180 },        // len=51, ends=8231
    { id: 9, text: 'a description of how a test showed some evidence to be fake', offset: 8250 }, // len=59, ends=8309
    { id: 10, text: 'reasons why scientists may be unwilling to admit that they play', offset: 8325 }, // len=63, ends=8388
    { id: 11, text: 'the result of a trick going wrong', offset: 8405 },                         // len=33, ends=8438
    { id: 12, text: 'the similarity between a skill used in play and one used in science', offset: 8455 }, // len=67, ends=8522
    { id: 13, text: 'how a group of scientists stopped a colleague misusing their results', offset: 8540 }, // len=68, ends=8608
    { id: 14, text: 'some examples of the playful nature of scientific language', offset: 8625 }, // len=58, ends=8683
    { id: 15, text: 'an example of an everyday object put to a scientific use', offset: 8700 },  // len=56, ends=8756
    // Questions 21–22
    { id: 'q21-22', text: 'Questions 21–22', offset: 8775 },                                     // len=15, ends=8790
    { id: 16, text: 'Choose TWO correct answers.', offset: 8805 },                               // len=27, ends=8832
    { id: 17, text: 'Which TWO of the following reasons are given as possible explanations of scientists play behaviour?', offset: 8850 }, // len=100, ends=8950
    { id: 18, text: 'Write the correct letters A–E in boxes 21–22 on your answer sheet.', offset: 8970 }, // len=67, ends=9037
    { id: 19, text: 'A Play may provide relief from their work.', offset: 9055 },                // len=42, ends=9097
    { id: 20, text: 'B Scientists sometimes spend time as teachers.', offset: 9115 },            // len=46, ends=9161
    { id: 21, text: 'C The age of scientists predisposes them to play.', offset: 9180 },         // len=49, ends=9229
    { id: 22, text: 'D Scientists get ideas from childrens questions.', offset: 9250 },         // len=49, ends=9299
    { id: 23, text: 'E Scientists are secretive about their work.', offset: 9320 },              // len=44, ends=9364
    // Questions 23–26
    { id: 'q23-26', text: 'Questions 23–26', offset: 9385 },                                     // len=15, ends=9400
    { id: 24, text: 'Complete the summary below.', offset: 9420 },                               // len=27, ends=9447
    { id: 25, text: 'Write NO MORE THAN THREE WORDS from the passage for each answer.', offset: 9465 }, // len=65, ends=9530
    { id: 26, text: 'Scientists play tricks', offset: 9550 },                                    // len=22, ends=9572
    { id: 27, text: 'The passage gives two examples of tricks arising from the playfulness of scientists.', offset: 9590 }, // len=84, ends=9674
    { id: 28, text: 'Researchers at Caltech found that a colleague had been stealing their', offset: 9695 }, // len=70, ends=9765
    { id: 29, text: '', offset: 9780 },                                                          // Q23 blank placeholder
    { id: 30, text: 'and used a hoax involving a fake NMR spectrum to trap the offender.', offset: 9795 }, // len=67, ends=9862
    { id: 31, text: 'Another story concerns a plate which a famous', offset: 9880 },             // len=45, ends=9925
    { id: 32, text: '', offset: 9940 },                                                          // Q24 blank placeholder
    { id: 33, text: 'is said to have left behind on a visit to California.', offset: 9955 },     // len=53, ends=10008
    { id: 34, text: 'The', offset: 10025 },                                                      // len=3, ends=10028
    { id: 35, text: '', offset: 10045 },                                                         // Q25 blank placeholder
    { id: 36, text: 'that the plate was made of later revealed that it was a fake,', offset: 10060 }, // len=61, ends=10121
    { id: 37, text: 'a hoax carried out by friends of the director of the', offset: 10140 },     // len=52, ends=10192
    { id: 38, text: '', offset: 10210 },                                                         // Q26 blank placeholder
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

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: number | string) => {
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
    return {
        ...answers.value,
        q21: answers.value.q21_22[0] ?? '',
        q22: answers.value.q21_22[1] ?? ''
    };
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

        // Function to get absolute offset for a node position
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
            // Handle both string and number segment IDs
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
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 part-header-box  px-4 py-2">
    <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part2-title" v-html="getHighlightedSegmentById('part2-title')"></p>
    <p class="text-sm text-gray-700 select-text" data-segment-id="part2-desc" v-html="getHighlightedSegmentById('part2-desc')"></p>
</div>

<div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
    <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

        <!-- Reading Passage -->
        <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
            <div class="p-6">
                <h2 class="text-xl font-bold">
                    <span class="text-gray-900 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></span>
                </h2>
                <p class="mt-1 text-sm italic text-gray-600">
                    <span class="select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></span>
                    <span class="select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></span>
                    <span class="select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                </p>
            </div>
            <div class="space-y-6 p-6" :style="contentZoom">
                <div class="space-y-6 text-sm leading-relaxed select-text">
                    <div class="">
                        <div class="text-justify leading-relaxed text-gray-700">
                            <span
                                class="text-lg text-gray-700"
                                data-segment-id="4"
                                v-html="getHighlightedSegmentById(4)"
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

                    <!-- Questions 14-20 (Paragraph Matching A-H) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span
                                        class="text-gray-700 select-text"
                                        data-segment-id="q14-20"
                                        v-html="getHighlightedSegmentById('q14-20')"
                                    ></span>
                                </h3>
                            </div>
                            <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                <span class="text-gray-700 select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span><br />
                                <span class="text-gray-700 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
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
                                <div class="flex items-start gap-4">
                                    <span class="font-bold text-gray-900 select-text flex-shrink-0">14.</span>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3 min-w-0">
                                            <select v-model="answers.q14" class="w-[120px] min-w-[120px] max-w-[120px] border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none">
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
                                        </div>
                                        <div class="col-span-9">
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                            </p>
                                        </div>
                                    </div>
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
                                <div class="flex items-start gap-4">
                                    <span class="font-bold text-gray-900 select-text flex-shrink-0">15.</span>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3 min-w-0">
                                            <select v-model="answers.q15" class="w-[120px] min-w-[120px] max-w-[120px] border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none">
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
                                        </div>
                                        <div class="col-span-9">
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                            </p>
                                        </div>
                                    </div>
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
                                <div class="flex items-start gap-4">
                                    <span class="font-bold text-gray-900 select-text flex-shrink-0">16.</span>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3 min-w-0">
                                            <select v-model="answers.q16" class="w-[120px] min-w-[120px] max-w-[120px] border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none">
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
                                        </div>
                                        <div class="col-span-9">
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                            </p>
                                        </div>
                                    </div>
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
                                <div class="flex items-start gap-4">
                                    <span class="font-bold text-gray-900 select-text flex-shrink-0">17.</span>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3 min-w-0">
                                            <select v-model="answers.q17" class="w-[120px] min-w-[120px] max-w-[120px] border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none">
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
                                        </div>
                                        <div class="col-span-9">
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                            </p>
                                        </div>
                                    </div>
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
                                <div class="flex items-start gap-4">
                                    <span class="font-bold text-gray-900 select-text flex-shrink-0">18.</span>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3 min-w-0">
                                            <select v-model="answers.q18" class="w-[120px] min-w-[120px] max-w-[120px] border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none">
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
                                        </div>
                                        <div class="col-span-9">
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                            </p>
                                        </div>
                                    </div>
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
                                <div class="flex items-start gap-4">
                                    <span class="font-bold text-gray-900 select-text flex-shrink-0">19.</span>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3 min-w-0">
                                            <select v-model="answers.q19" class="w-[120px] min-w-[120px] max-w-[120px] border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none">
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
                                        </div>
                                        <div class="col-span-9">
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                            </p>
                                        </div>
                                    </div>
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

                            <!-- Question 20 -->
                            <div
                                id="question-20"
                                class="relative bg-white p-2"
                                @mouseenter="hoveredQuestion = 20"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-4">
                                    <span class="font-bold text-gray-900 select-text flex-shrink-0">20.</span>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3">
                                            <select v-model="answers.q20" class="w-[120px] min-w-[120px] max-w-[120px] border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none">
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
                                        </div>
                                        <div class="col-span-9">
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span class="text-gray-700" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
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
                    </div>

                    <!-- Questions 21-22 (Choose TWO letters A-E) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span
                                        class="text-gray-700"
                                        data-segment-id="q21-22"
                                        v-html="getHighlightedSegmentById('q21-22')"
                                    ></span>
                                </h3>
                            </div>
                            <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                <span class="text-gray-700" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                            </p>
                            <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                <span class="text-gray-700" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                            </p>
                            <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                <span class="text-gray-700" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                            </p>
                        </div>

                        <div class="space-y-3">
                            <label class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors">
                                <input
                                    type="checkbox"
                                    value="A"
                                    v-model="answers.q21_22"
                                    :disabled="!answers.q21_22.includes('A') && answers.q21_22.length >= 2"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                            </label>

                            <label class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors">
                                <input
                                    type="checkbox"
                                    value="B"
                                    v-model="answers.q21_22"
                                    :disabled="!answers.q21_22.includes('B') && answers.q21_22.length >= 2"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                            </label>

                            <label class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors">
                                <input
                                    type="checkbox"
                                    value="C"
                                    v-model="answers.q21_22"
                                    :disabled="!answers.q21_22.includes('C') && answers.q21_22.length >= 2"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                            </label>

                            <label class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors">
                                <input
                                    type="checkbox"
                                    value="D"
                                    v-model="answers.q21_22"
                                    :disabled="!answers.q21_22.includes('D') && answers.q21_22.length >= 2"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                            </label>

                            <label class="relative flex items-start gap-3 rounded border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors">
                                <input
                                    type="checkbox"
                                    value="E"
                                    v-model="answers.q21_22"
                                    :disabled="!answers.q21_22.includes('E') && answers.q21_22.length >= 2"
                                    class="mt-0.5 h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500"
                                />
                                <span class="select-text text-sm text-gray-700" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                            </label>

                            <p class="text-xs text-gray-500 mt-2">Selected: {{ answers.q21_22.length }} / 2</p>
                        </div>
                    </div>

                    <!-- Questions 23-26 (Summary Completion) -->
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="mb-4 flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-gray-900">
                                    <span
                                        class="text-gray-700"
                                        data-segment-id="q23-26"
                                        v-html="getHighlightedSegmentById('q23-26')"
                                    ></span>
                                </h3>
                            </div>
                            <p class="mb-2 text-sm text-gray-700">
                                <span class="text-gray-700" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                            </p>
                            <p class="mb-4 text-sm text-gray-700">
                                <span class="text-gray-700" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                            </p>
                        </div>

                        <div class="bg-white p-4">
                            <h4 class="mb-4 font-bold text-gray-900 text-base">
                                <span class="text-gray-700" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                            </h4>
                            <div class="space-y-4 text-sm leading-relaxed text-gray-700">

                                <!-- Intro sentence -->
                                <p>
                                    <span class="text-gray-700" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                                </p>

                                <!-- Q23 -->
                                <div
                                    id="question-23"
                                    class="relative flex flex-wrap items-center gap-2 p-2"
                                    @mouseenter="hoveredQuestion = 23"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-700" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>

                                    <input
                                        v-model="answers.q23"
                                        type="text"
                                        class="border-2 border-gray-900 bg-white px-2 py-1 text-center transition-colors focus:border-black focus:bg-white focus:outline-none"
                                        style="width: 140px"
                                        placeholder="23"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <span class="text-gray-700" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                                    <button
                                        v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                        @click.stop="toggleFlag(23)"
                                        class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q24 -->
                                <div
                                    id="question-24"
                                    class="relative flex flex-wrap items-center gap-2 p-2"
                                    @mouseenter="hoveredQuestion = 24"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-700" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>

                                    <input
                                        v-model="answers.q24"
                                        type="text"
                                        class="border-2 border-gray-900 bg-white px-2 py-1 text-center transition-colors focus:border-black focus:bg-white focus:outline-none"
                                        style="width: 140px"
                                        placeholder="24"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <span class="text-gray-700" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
                                    <button
                                        v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                        @click.stop="toggleFlag(24)"
                                        class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q25 -->
                                <div
                                    id="question-25"
                                    class="relative flex flex-wrap items-center gap-2 p-2"
                                    @mouseenter="hoveredQuestion = 25"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-700" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>

                                    <input
                                        v-model="answers.q25"
                                        type="text"
                                        class="border-2 border-gray-900 bg-white px-2 py-1 text-center transition-colors focus:border-black focus:bg-white focus:outline-none"
                                        style="width: 140px"
                                        placeholder="25"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <span class="text-gray-700" data-segment-id="36" v-html="getHighlightedSegmentById(36)"></span>
                                    <button
                                        v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="toggleFlag(25)"
                                        class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q26 -->
                                <div
                                    id="question-26"
                                    class="relative flex flex-wrap items-center gap-2 p-2"
                                    @mouseenter="hoveredQuestion = 26"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-gray-700" data-segment-id="37" v-html="getHighlightedSegmentById(37)"></span>

                                    <input
                                        v-model="answers.q26"
                                        type="text"
                                        class="border-2 border-gray-900 bg-white px-2 py-1 text-center transition-colors focus:border-black focus:bg-white focus:outline-none"
                                        style="width: 140px"
                                        placeholder="26"
                                        spellcheck="false"
                                        autocomplete="off"
                                        autocorrect="off"
                                        autocapitalize="off"
                                    />
                                    <button
                                        v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="toggleFlag(26)"
                                        class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
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

</template>

<style scoped>

.part-header-box {
    background-color: #F1F2EC;
}
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
