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

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Delete highlight tooltip state
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note hover tooltip state
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

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
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
});

// Drag and drop functionality for questions 28-33
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);


const headingOptions14 = [
    { value: 'i', label: 'The focus of pollution moves to the home.' },
    { value: 'ii', label: 'The levels of carbon monoxide rise.' },
    { value: 'iii', label: 'The world’s natural resources are unequally shared.' },
    { value: 'iv', label: 'People demand an explanation.' },
    { value: 'v', label: 'Environmentalists look elsewhere for an explanation.' },
    { value: 'vi', label: 'Chemicals are effectively stripped from the water' },
    { value: 'vii', label: 'A clean odour is produced.' },
    { value: 'viii', label: 'Sales of bottled water increase.' },
    { value: 'ix', label: 'The levels of carbon dioxide rise.' },
    { value: 'x', label: 'The chlorine content of drinking water increased.' },
];

// Filter out used heading options

//const availableHeadingOptions = computed(() => {
   // const usedOptions = [//answers.value.q28, answers.value.q29, answers.value.q30, answers.value.q31, answers.value.q32, answers.value.q33].filter(Boolean);
   // return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
//});

const handleHeadingDragStart = (e: DragEvent, option: string) => {
    draggedHeading.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleHeadingDragEnd = () => {
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};

const handleHeadingDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
    dragOverQuestion.value = questionNum;
};

const handleHeadingDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleHeadingDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedHeading.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};

const clearHeadingAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// ── Q7–13 Drag & Drop ───────────────────────────────────────────────────
const draggingLetter = ref<string | null>(null);
const dragOverKey = ref<string | null>(null);

const listBOptions = [
    { letter: 'A', text: 'The focus of pollution moves to the home.' },
    { letter: 'B', text: 'The levels of carbon monoxide rise.' },
    { letter: 'C', text: 'The world\u2019s natural resources are unequally shared.' },
    { letter: 'D', text: 'People demand an explanation.' },
    { letter: 'E', text: 'Environmentalists look elsewhere for an explanation.' },
    { letter: 'F', text: 'Chemicals are effectively stripped from the water.' },
    { letter: 'G', text: 'A clean odour is produced.' },
    { letter: 'H', text: 'Sales of bottled water increase.' },
    { letter: 'I', text: 'The levels of carbon dioxide rise.' },
    { letter: 'J', text: 'The chlorine content of drinking water increased.' },
];

const onDragStart = (event: DragEvent, letter: string) => {
    draggingLetter.value = letter;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', letter);
    }
};

const onDragEnd = () => {
    draggingLetter.value = null;
    dragOverKey.value = null;
};

const onDragOver = (event: DragEvent, key: string) => {
    event.preventDefault();
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    dragOverKey.value = key;
};

const onDragLeave = (event: DragEvent, key: string) => {
    if (!(event.currentTarget as HTMLElement).contains(event.relatedTarget as Node)) {
        dragOverKey.value = null;
    }
};

const onDrop = (event: DragEvent, key: string) => {
    event.preventDefault();
    const letter = event.dataTransfer?.getData('text/plain') || draggingLetter.value;
    if (!letter) return;

    // Free any slot that already holds this letter
    (Object.keys(answers.value) as Array<keyof typeof answers.value>).forEach((k) => {
        if (answers.value[k] === letter && k !== key) {
            answers.value[k] = '';
        }
    });

    answers.value[key as keyof typeof answers.value] = letter;
    dragOverKey.value = null;
    draggingLetter.value = null;
};

const clearAnswer = (key: string) => {
    answers.value[key as keyof typeof answers.value] = '';
};

const isOptionUsed = (letter: string): boolean => {
    return Object.values(answers.value).includes(letter);
};

const getLabelForLetter = (letter: string): string => {
    const opt = listBOptions.find((o) => o.letter === letter);
    return opt ? opt.text.slice(0, 28) + (opt.text.length > 28 ? '…' : '') : '';
};

// Helper to get label for q14-19 answers
const getHeadingLabel14 = (value: string): string => {
    const option = headingOptions14.find(opt => opt.value === value);
    return option ? option.label : '';
};

// Paragraph texts for A-F (split from passage)


const passageText = `Since the early eighties, we have been only too aware of the devastating effects of large-scale environmental pollution. Such pollution is generally the result of poor government planning in many developing nations or the short-sighted, selfish policies of the already industrialised countries which encourage a minority of the world’s population to squander the majority of its natural resources.

While events such as the deforestation of the Amazon jungle or the nuclear disaster in Chernobyl continue to receive high media exposure, as do acts of environmental sabotage, it must be remembered that not all pollution is on this grand scale. A large proportion of the world’s pollution has its source much closer to home. The recent spillage of crude oil from an oil tanker accidentally discharging its cargo straight into Sydney Harbour not only caused serious damage to the harbour foreshores but also created severely toxic fumes which hung over the suburbs for days and left the angry residents wondering how such a disaster could have been allowed to happen.

Avoiding pollution can be a fulltime job. Try not to inhale traffic fumes; keep away from chemical plants and building sites; wear a mask when cycling. It is enough to make you want to stay at home. But that, according to a growing body of scientific evidence, would also be a bad idea. Research shows that levels of pollutants such as hazardous gases, particulate matter and other chemical ‘nasties’ are usually higher indoors than out, even in the most polluted cities. Since the average American spends 18 hours indoors for every hour outside, it looks as though many environmentalists may be attacking the wrong target.

The latest study, conducted by two environmental engineers, Richard Corsi and Cynthia Howard-Reed, of the University of Texas in Austin, and published in Environmental Science and Technology, suggests that it is the process of keeping clean that may be making indoor pollution worse. The researchers found that baths, showers, dishwashers and washing machines can all be significant sources of indoor pollution, because they extract trace amounts of chemicals from the water that they use and transfer them to the air.

Nearly all public water supplies contain very low concentrations of toxic chemicals, most of them left over from the otherwise beneficial process of chlorination. Dr. Corsi wondered whether they stay there when water is used, or whether they end up in the air that people breathe. The team conducted a series of experiments in which known quantities of five such chemicals were mixed with water and passed through a dishwasher, a washing machine, a shower head inside a shower stall or a tap in a bath, all inside a specially designed chamber. The levels of chemicals in the effluent water and in the air extracted from the chamber were then measured to see how much of each chemical had been transferred from the water into the air.

The degree to which the most volatile elements could be removed from the water, a process known as chemical stripping, depended on a wide range of factors, including the volatility of the chemical, the temperature of the water and the surface area available for transfer. Dishwashers were found to be particularly effective: the high-temperature spray, splashing against the crockery and cutlery, results in a nasty plume of toxic chemicals that escape when the door is opened at the end of the cycle.

In fact, in many cases, the degree of exposure to toxic chemicals in tap water by inhalation is comparable to the exposure that would result from drinking the stuff. This is significant because many people are so concerned about water-borne pollutants that they drink only bottled water, worldwide sales of which are forecast to reach $72 billion by next year. D. Corsi’s results suggest that they are being exposed to such pollutants anyway simply by breathing at home.

hIn fact, in many cases, the degree of exposure to toxic chemicals in tap water by inhalation is comparable to the exposure that would result from drinking the stuff. This is significant because many people are so concerned about water-borne pollutants that they drink only bottled water, worldwide sales of which are forecast to reach $72 billion by next year. D. Corsi’s results suggest that they are being exposed to such pollutants anyway simply by breathing at home.

iUsing gas cookers or burning candles, for example, both result in indoor levels of carbon monoxide and particulate matter that are just as high as those to be found outside, amid heavy traffic. Overcrowded classrooms whose ventilation systems were designed for smaller numbers of children frequently contain levels of carbon dioxide that would be regarded as unacceptable on board a submarine. ‘New car smell’ is the result of high levels of toxic chemicals, not cleanliness. Laser printers, computers, carpets and paints all contribute to the noxious indoor mix.

The implications of indoor pollution for health are unclear. But before worrying about the problems caused by large scale industry, it makes sense to consider the small-scale pollution at home and welcome international debate about this. Scientists investigating indoor pollution will gather next month in Edinburgh at the Indoor Air conference to discuss the problem. Perhaps unwisely, the meeting is being held indoors.`;

// Helper: measure each block sequentially
// offset(n) = offset(n-1) + text(n-1).length

const textSegments = ref([
    { text: 'READING PASSAGE 1', offset: 0 },
    { text: 'You should spend about 20 minutes on Question 1-14, which are based on Reading passage 1 below.', offset: 18 },
    { text: 'Indoor Pollution', offset: 114 },

    { id: 'passage', text: passageText, offset: 131 },  // len=5382, ends=5513

    // ── Questions 1–6 ───────────────────────────────────────────────
    { text: 'Questions 1–6', offset: 5514 },
    { text: 'Choose the correct letter,', offset: 5528 },
    { text: 'A, B, C or D', offset: 5555 },
    { text: 'Write your answers in boxes', offset: 5568 },
    { text: '1–6', offset: 5596 },
    { text: 'on your answer sheet.', offset: 5600 },

    // Q1
    { text: '1. In the first paragraph, the writer argues that pollution .......', offset: 5622 },
    { text: 'A. has increased since the eighties.', offset: 5690 },
    { text: 'B. is at its worst in industrialised countries.', offset: 5727 },
    { text: 'C. results from poor relations between nations.', offset: 5775 },
    { text: 'D. is caused by human self-interest.', offset: 5823 },

    // Q2
    { text: '2. The Sydney Harbour oil spill was the result of a .......', offset: 5860 },
    { text: 'A. ship refueling in the harbour.', offset: 5920 },
    { text: 'B. tanker pumping oil into the sea.', offset: 5954 },
    { text: 'C. collision between two oil tankers.', offset: 5990 },
    { text: 'D. deliberate act of sabotage.', offset: 6028 },

    // Q3
    { text: '3. In the 3rd paragraph, the writer suggests that .......', offset: 6059 },
    { text: 'A. people should avoid working in cities.', offset: 6117 },
    { text: 'B. Americans spend too little time outdoors.', offset: 6159 },
    { text: 'C. hazardous gases are concentrated in industrial suburbs.', offset: 6204 },
    { text: 'D. there are several ways to avoid city pollution.', offset: 6263 },

    // Q4
    { text: '4. The Corsi research team hypothesised that .......', offset: 6314 },
    { text: 'A. toxic chemicals can pass from air to water.', offset: 6367 },
    { text: 'B. pollution is caused by dishwashers and baths.', offset: 6414 },
    { text: 'C. city water contains insufficient chlorine.', offset: 6463 },
    { text: 'D. household appliances are poorly designed.', offset: 6509 },

    // Q5
    { text: '5. As a result of their experiments, Dr Corsi\u2019s team found that .......', offset: 6554 },
    { text: 'A. dishwashers are very efficient machines.', offset: 6626 },
    { text: 'B. tap water is as polluted as bottled water.', offset: 6670 },
    { text: 'C. indoor pollution rivals outdoor pollution.', offset: 6716 },
    { text: 'D. gas masks are a useful protective device.', offset: 6762 },

    // Q6
    { text: '6. Regarding the dangers of pollution, the writer believes that .......', offset: 6807 },
    { text: 'A. there is a need for rational discussion.', offset: 6879 },
    { text: 'B. indoor pollution is a recent phenomenon.', offset: 6923 },
    { text: 'C. people should worry most about their work environment.', offset: 6967 },
    { text: 'D. industrial pollution causes specific diseases.', offset: 7025 },

    // ── Questions 7–13 ──────────────────────────────────────────────
    { text: 'Questions 7-13', offset: 7075 },
    { text: 'List B: EFFECTS', offset: 7090 },
    { text: 'The focus of pollution moves to the home.', offset: 7106 },
    { text: 'The levels of carbon monoxide rise.', offset: 7148 },
    { text: "The world's natural resources are unequally shared.", offset: 7184 },
    { text: 'People demand an explanation.', offset: 7236 },
    { text: 'Environmentalists look elsewhere for an explanation.', offset: 7266 },
    { text: 'Chemicals are effectively stripped from the water.', offset: 7319 },
    { text: 'A clean odour is produced.', offset: 7370 },
    { text: 'Sales of bottled water increase.', offset: 7397 },
    { text: 'The levels of carbon dioxide rise.', offset: 7430 },
    { text: 'The chlorine content of drinking water increased.', offset: 7465 },

    { text: 'List A: CAUSES', offset: 7515 },
    { text: '7. Industrialised nations use a lot of energy.', offset: 7530 },
    { text: '8. Oil spills into the sea.', offset: 7577 },
    { text: '9. The researchers publish their findings.', offset: 7605 },
    { text: '10. Water is brought to a high temperature.', offset: 7648 },
    { text: '11. People fear pollutants in tap water.', offset: 7692 },
    { text: '12. Air conditioning systems are inadequate.', offset: 7733 },
    { text: '13. Toxic chemicals are abundant in new cars.', offset: 7778 },

    // ── Questions 14–19 ─────────────────────────────────────────────
    { text: 'Questions 14-19', offset: 7993 },
    { text: 'List of headings', offset: 8009 },
    { text: 'i. Some success has resulted from observing how the brain functions.', offset: 8026 },
    { text: 'ii. Are we expecting too much from one robot?', offset: 8095 },
    { text: 'iii. Scientists are examining the humanistic possibilities.', offset: 8141 },
    { text: 'iv. There are judgements that robots cannot make.', offset: 8201 },
    { text: 'v. Has the power of robots become too great?', offset: 8251 },
    { text: 'vi. Human skills have been heightened with the help of robotics.', offset: 8296 },
    { text: 'vii. There are some things we prefer the brain to control.', offset: 8361 },
    { text: 'viii. Robots have quietly infiltrated our lives.', offset: 8420 },
    { text: 'ix. Original predictions have been revised.', offset: 8469 },
    { text: 'x. Another approach meets the same result.', offset: 8513 },
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
    // Use data-segment-text instead of data-segment-id
    const segmentEl = (container as Element).closest('[data-segment-text]') as HTMLElement;

    if (!segmentEl) return null;

    const segmentText = segmentEl.getAttribute('data-segment-text') || '';
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return null;

    // TreeWalker to get plain-text offset within the segment element
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
            // Position tooltip ABOVE the selection with arrow pointing down
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70; // Approximate tooltip height
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

    // Position note input below the context menu
    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    // Focus the input after a small delay
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
        part: 'Part 3',
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

// Handle note hover to show tooltip
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
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
};

// Confirm delete highlight
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

// Cancel delete highlight
const cancelDeleteHighlight = () => {
    closeDeleteTooltip();
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

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

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
                y: rect.bottom + 8, // Position below the highlight
            };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
        }
    } else {
        // Clicked elsewhere - close delete tooltip
        closeDeleteTooltip();
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

// Save panel width to localStorage
// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
            <div class="flex flex-col">
                <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase"
                                        :data-segment-text="'READING PASSAGE 1'"
                                        v-html="getHighlightedSegment('READING PASSAGE 1')"></p>
                <p class="mb-1 text-base sm:text-sm"
                                        :data-segment-text="'You should spend about 20 minutes on Question 1-14, which are based on Reading passage 1 below.'"
                                        v-html="getHighlightedSegment('You should spend about 20 minutes on Question 1-14, which are based on Reading passage 1 below.')">
                                    </p>
            </div>
        </div>
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">


                <!-- ══════════════════════════════════════════
                     READING PASSAGE (LEFT PANEL)
                     ══════════════════════════════════════════ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto rounded-r-lg bg-white shadow-lg lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">


                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">

                        <!-- Passage Body -->
                        <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                            <div class="space-y-6 text-base leading-relaxed select-text sm:text-lg">

                                <!-- Main passage text (Q1-13 reference) -->
                                <div class="space-y-6 px-4" :style="contentZoom">
                                    <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-base">
                                        <div class="px-4">
                                            <div class="text-justify leading-relaxed font-medium whitespace-pre-wrap text-gray-700">
                                                <h2
                                        class="bg--black-600  bg-clip-text text-center text-xl font-bold mb-2 text-grey-900">
                                        <span :data-segment-text="'Indoor Pollution'"
                                            v-html="getHighlightedSegment('Indoor Pollution')"></span>
                                    </h2>
                                                <span
                                                    class="text-base text-gray-700 select-text"
                                                    :data-segment-text="passageText"
                                                    v-html="getHighlightedSegment(passageText)"
                                                ></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ─────────────────────────────────────────
                                     Q14-19: Paragraphs with INLINE DROP ZONES
                                     ───────────────────────────────────────── -->

                            </div>
                        </div><!-- end passage body -->
                    </div>
                </div><!-- end passage panel -->

                <!-- ══════════════════════════════════════════
                     RESIZE HANDLE
                     ══════════════════════════════════════════ -->
                <div class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-blue-50 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div
                            class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-blue-500">
                        </div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════
                     QUESTIONS PANEL (RIGHT)
                     ══════════════════════════════════════════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">


                        <!-- Scrollable Questions -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">

                                <!-- ─── Q1–6: Multiple Choice ─── -->
                                <div
                                    class="rounded-xl  p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <div class="mb-6">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3
                                                class=" bg-clip-text text-lg font-bold text-grey-800">
                                                <span :data-segment-text="'Questions 1–6'"
                                                    v-html="getHighlightedSegment('Questions 1–6')"></span>
                                            </h3>
                                        </div>

                                    </div>

                                    <div class="space-y-4">
                                        <!-- Q1 -->
                                        <div id="question-1"
                                            class="grid grid-cols-12 gap-2  bg-white p-3 ">

                                            <div class="col-span-11 text-base ">
                                                <p class="mb-3 ml-2 font-bold text-gray-700">
                                                    <span
                                                        :data-segment-text="'1. In the first paragraph, the writer argues that pollution .......'"
                                                        v-html="getHighlightedSegment('1. In the first paragraph, the writer argues that pollution .......')">
                                                    </span>
                                                </p>
                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q1" type="radio" value="A" class="h-4 w-4 " /><span :data-segment-text="'A. has increased since the eighties.'" v-html="getHighlightedSegment('A. has increased since the eighties.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q1" type="radio" value="B" class="h-4 w-4 " /><span :data-segment-text="'B. is at its worst in industrialised countries.'" v-html="getHighlightedSegment('B. is at its worst in industrialised countries.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q1" type="radio" value="C" class="h-4 w-4 " /><span :data-segment-text="'C. results from poor relations between nations.'" v-html="getHighlightedSegment('C. results from poor relations between nations.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q1" type="radio" value="D" class="h-4 w-4 " /><span :data-segment-text="'D. is caused by human self-interest.'" v-html="getHighlightedSegment('D. is caused by human self-interest.')"></span></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Q2 -->
                                        <div id="question-2"
                                            class="grid grid-cols-12 gap-2   bg-white p-3 ">

                                            <div class="col-span-11 text-base ">
                                                <p class="mb-3 ml-2 font-bold text-gray-700">
                                                    <span
                                                        :data-segment-text="'2. The Sydney Harbour oil spill was the result of a .......'"
                                                        v-html="getHighlightedSegment('2. The Sydney Harbour oil spill was the result of a .......')">
                                                    </span>
                                                </p>
                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q2" type="radio" value="A" class="h-4 w-4 " /><span :data-segment-text="'A. ship refueling in the harbour.'" v-html="getHighlightedSegment('A. ship refueling in the harbour.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q2" type="radio" value="B" class="h-4 w-4 " /><span :data-segment-text="'B. tanker pumping oil into the sea.'" v-html="getHighlightedSegment('B. tanker pumping oil into the sea.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q2" type="radio" value="C" class="h-4 w-4 " /><span :data-segment-text="'C. collision between two oil tankers.'" v-html="getHighlightedSegment('C. collision between two oil tankers.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q2" type="radio" value="D" class="h-4 w-4 " /><span :data-segment-text="'D. deliberate act of sabotage.'" v-html="getHighlightedSegment('D. deliberate act of sabotage.')"></span></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Q3 -->
                                        <div id="question-3"
                                            class="grid grid-cols-12 gap-2   bg-white p-3 ">

                                            <div class="col-span-11 text-base ">
                                                <p class="mb-3 ml-2 font-bold text-gray-700">
                                                    <span
                                                        :data-segment-text="'3. In the 3rd paragraph, the writer suggests that .......'"
                                                        v-html="getHighlightedSegment('3. In the 3rd paragraph, the writer suggests that .......')">
                                                    </span>
                                                </p>
                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q3" type="radio" value="A" class="h-4 w-4 " /><span :data-segment-text="'A. people should avoid working in cities.'" v-html="getHighlightedSegment('A. people should avoid working in cities.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q3" type="radio" value="B" class="h-4 w-4 " /><span :data-segment-text="'B. Americans spend too little time outdoors.'" v-html="getHighlightedSegment('B. Americans spend too little time outdoors.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q3" type="radio" value="C" class="h-4 w-4 " /><span :data-segment-text="'C. hazardous gases are concentrated in industrial suburbs.'" v-html="getHighlightedSegment('C. hazardous gases are concentrated in industrial suburbs.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q3" type="radio" value="D" class="h-4 w-4 " /><span :data-segment-text="'D. there are several ways to avoid city pollution.'" v-html="getHighlightedSegment('D. there are several ways to avoid city pollution.')"></span></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Q4 -->
                                        <div id="question-4"
                                            class="grid grid-cols-12 gap-2   bg-white p-3 ">

                                            <div class="col-span-11 text-base ">
                                                <p class="mb-3 ml-2 font-bold text-gray-700">
                                                    <span
                                                        :data-segment-text="'4. The Corsi research team hypothesised that .......'"
                                                        v-html="getHighlightedSegment('4. The Corsi research team hypothesised that .......')">
                                                    </span>
                                                </p>
                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q4" type="radio" value="A" class="h-4 w-4 " /><span :data-segment-text="'A. toxic chemicals can pass from air to water.'" v-html="getHighlightedSegment('A. toxic chemicals can pass from air to water.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q4" type="radio" value="B" class="h-4 w-4 " /><span :data-segment-text="'B. pollution is caused by dishwashers and baths.'" v-html="getHighlightedSegment('B. pollution is caused by dishwashers and baths.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q4" type="radio" value="C" class="h-4 w-4 " /><span :data-segment-text="'C. city water contains insufficient chlorine.'" v-html="getHighlightedSegment('C. city water contains insufficient chlorine.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q4" type="radio" value="D" class="h-4 w-4 " /><span :data-segment-text="'D. household appliances are poorly designed.'" v-html="getHighlightedSegment('D. household appliances are poorly designed.')"></span></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Q5 -->
                                        <div id="question-5"
                                            class="grid grid-cols-12 gap-2   bg-white p-3 ">

                                            <div class="col-span-11 text-base ">
                                                <p class="mb-3 ml-1 font-bold text-gray-700">
                                                    <span
                                                        :data-segment-text="'5. As a result of their experiments, Dr Corsi\u2019s team found that .......'"
                                                        v-html="getHighlightedSegment('5. As a result of their experiments, Dr Corsi\u2019s team found that .......')">
                                                    </span>
                                                </p>
                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q5" type="radio" value="A" class="h-4 w-4 " /><span :data-segment-text="'A. dishwashers are very efficient machines.'" v-html="getHighlightedSegment('A. dishwashers are very efficient machines.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q5" type="radio" value="B" class="h-4 w-4 " /><span :data-segment-text="'B. tap water is as polluted as bottled water.'" v-html="getHighlightedSegment('B. tap water is as polluted as bottled water.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q5" type="radio" value="C" class="h-4 w-4 " /><span :data-segment-text="'C. indoor pollution rivals outdoor pollution.'" v-html="getHighlightedSegment('C. indoor pollution rivals outdoor pollution.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q5" type="radio" value="D" class="h-4 w-4 " /><span :data-segment-text="'D. gas masks are a useful protective device.'" v-html="getHighlightedSegment('D. gas masks are a useful protective device.')"></span></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Q6 -->
                                        <div id="question-6"
                                            class="grid grid-cols-12 gap-2   bg-white p-3 ">

                                            <div class="col-span-11 text-base ">
                                                <p class="mb-3 ml-1 font-bold text-gray-700">
                                                    <span
                                                        :data-segment-text="'6. Regarding the dangers of pollution, the writer believes that .......'"
                                                        v-html="getHighlightedSegment('6. Regarding the dangers of pollution, the writer believes that .......')">
                                                    </span>
                                                </p>
                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q6" type="radio" value="A" class="h-4 w-4 " /><span :data-segment-text="'A. there is a need for rational discussion.'" v-html="getHighlightedSegment('A. there is a need for rational discussion.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q6" type="radio" value="B" class="h-4 w-4 " /><span :data-segment-text="'B. indoor pollution is a recent phenomenon.'" v-html="getHighlightedSegment('B. indoor pollution is a recent phenomenon.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q6" type="radio" value="C" class="h-4 w-4 " /><span :data-segment-text="'C. people should worry most about their work environment.'" v-html="getHighlightedSegment('C. people should worry most about their work environment.')"></span></label>
                                                    <label class="flex items-center space-x-3"><input v-model="answers.q6" type="radio" value="D" class="h-4 w-4 " /><span :data-segment-text="'D. industrial pollution causes specific diseases.'" v-html="getHighlightedSegment('D. industrial pollution causes specific diseases.')"></span></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- end Q1-6 -->

                                <!-- ─── Q7–13: Drag and Drop (Side by Side Layout) ─── -->
                                <div class="p-6 shadow-sm transition-shadow">
                                    <div class="mb-4">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3 class="bg-clip-text font-bold text-grey-900">
                                                <span :data-segment-text="'Questions 7-13'"
                                                    v-html="getHighlightedSegment('Questions 7-13')"></span>
                                            </h3>
                                        </div>
                                        <p class="text-base leading-relaxed text-gray-700">
                                            Look at the following causes (Questions <strong>7-13</strong>) and the list of effects below.
                                            Match each cause in <strong>List A</strong> with its effect in <strong>List B</strong>.
                                            Drag an option from <strong>List B</strong> and drop it onto the matching question.
                                        </p>
                                    </div>

                                    <!-- Side by Side: Questions (Left) + Drag Options (Right) -->
                                    <div class="flex flex-col lg:flex-row gap-4">
                                        <!-- Left: Drop Targets (Q7–Q13) -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="mb-3 font-bold text-gray-800"
                                                :data-segment-text="'List A: CAUSES'"
                                                v-html="getHighlightedSegment('List A: CAUSES')"></h4>

                                            <div class="space-y-3">
                                                <!-- Q7 -->
                                                <div
                                                    id="question-7"
                                                    class="group/q bg-white p-3 rounded-lg transition-all duration-200"
                                                    :class="dragOverKey === 'q7' ? 'ring-2 ring-blue-400 bg-blue-50' : answers.q7 ? 'border border-gray-300' : 'border border-gray-200'"
                                                    @dragover.prevent="onDragOver($event, 'q7')"
                                                    @dragleave="onDragLeave($event, 'q7')"
                                                    @drop.prevent="onDrop($event, 'q7')"
                                                >
                                                    <div class="flex items-start gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium leading-relaxed text-gray-700 mb-2"
                                                                :data-segment-text="'7. Industrialised nations use a lot of energy.'"
                                                                v-html="getHighlightedSegment('7. Industrialised nations use a lot of energy.')">
                                                            </p>
                                                            <div
                                                                class="flex items-center min-w-0 overflow-hidden min-h-[36px] rounded border-2 border-dashed px-3 py-1.5 text-sm transition-all duration-200"
                                                                :class="[
                                                                    dragOverKey === 'q7'
                                                                        ? 'border-blue-500 bg-blue-50'
                                                                        : answers.q7
                                                                            ? 'border-gray-400 bg-gray-50'
                                                                            : 'border-gray-300 bg-gray-50'
                                                                ]"
                                                            >
                                                                <template v-if="answers.q7">
                                                                    <span class="flex items-center gap-1.5 font-semibold text-sm flex-1 min-w-0 text-gray-800">
                                                                        <span class="font-bold">{{ answers.q7 }}.</span>
                                                                        <span class="truncate">{{ getLabelForLetter(answers.q7) }}</span>
                                                                    </span>
                                                                    <button @click="clearAnswer('q7')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0" title="Remove">✕</button>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="text-gray-400 text-xs w-full text-center">
                                                                        {{ dragOverKey === 'q7' ? '⬇ Drop here' : 'Drop answer here' }}
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <button
                                                            @click.stop="emit('toggleFlag', 7)"
                                                            class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150 opacity-5 group-hover/q:opacity-100"
                                                            :class="isQuestionFlagged(7) ? 'border-red-400 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Q8 -->
                                                <div
                                                    id="question-8"
                                                    class="group/q bg-white p-3 rounded-lg transition-all duration-200"
                                                    :class="dragOverKey === 'q8' ? 'ring-2 ring-blue-400 bg-blue-50' : answers.q8 ? 'border border-gray-300' : 'border border-gray-200'"
                                                    @dragover.prevent="onDragOver($event, 'q8')"
                                                    @dragleave="onDragLeave($event, 'q8')"
                                                    @drop.prevent="onDrop($event, 'q8')"
                                                >
                                                    <div class="flex items-start gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium leading-relaxed text-gray-700 mb-2"
                                                                :data-segment-text="'8. Oil spills into the sea.'"
                                                                v-html="getHighlightedSegment('8. Oil spills into the sea.')">
                                                            </p>
                                                            <div
                                                                class="flex items-center min-w-0 overflow-hidden min-h-[36px] rounded border-2 border-dashed px-3 py-1.5 text-sm transition-all duration-200"
                                                                :class="[
                                                                    dragOverKey === 'q8'
                                                                        ? 'border-blue-500 bg-blue-50'
                                                                        : answers.q8
                                                                            ? 'border-gray-400 bg-gray-50'
                                                                            : 'border-gray-300 bg-gray-50'
                                                                ]"
                                                            >
                                                                <template v-if="answers.q8">
                                                                    <span class="flex items-center gap-1.5 font-semibold text-sm flex-1 min-w-0 text-gray-800">
                                                                        <span class="font-bold">{{ answers.q8 }}.</span>
                                                                        <span class="truncate">{{ getLabelForLetter(answers.q8) }}</span>
                                                                    </span>
                                                                    <button @click="clearAnswer('q8')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0" title="Remove">✕</button>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="text-gray-400 text-xs w-full text-center">
                                                                        {{ dragOverKey === 'q8' ? '⬇ Drop here' : 'Drop answer here' }}
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <button
                                                            @click.stop="emit('toggleFlag', 8)"
                                                            class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150 opacity-5 group-hover/q:opacity-100"
                                                            :class="isQuestionFlagged(8) ? 'border-red-400 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Q9 -->
                                                <div
                                                    id="question-9"
                                                    class="group/q bg-white p-3 rounded-lg transition-all duration-200"
                                                    :class="dragOverKey === 'q9' ? 'ring-2 ring-blue-400 bg-blue-50' : answers.q9 ? 'border border-gray-300' : 'border border-gray-200'"
                                                    @dragover.prevent="onDragOver($event, 'q9')"
                                                    @dragleave="onDragLeave($event, 'q9')"
                                                    @drop.prevent="onDrop($event, 'q9')"
                                                >
                                                    <div class="flex items-start gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium leading-relaxed text-gray-700 mb-2"
                                                                :data-segment-text="'9. The researchers publish their findings.'"
                                                                v-html="getHighlightedSegment('9. The researchers publish their findings.')">
                                                            </p>
                                                            <div
                                                                class="flex items-center min-w-0 overflow-hidden min-h-[36px] rounded border-2 border-dashed px-3 py-1.5 text-sm transition-all duration-200"
                                                                :class="[
                                                                    dragOverKey === 'q9'
                                                                        ? 'border-blue-500 bg-blue-50'
                                                                        : answers.q9
                                                                            ? 'border-gray-400 bg-gray-50'
                                                                            : 'border-gray-300 bg-gray-50'
                                                                ]"
                                                            >
                                                                <template v-if="answers.q9">
                                                                    <span class="flex items-center gap-1.5 font-semibold text-sm flex-1 min-w-0 text-gray-800">
                                                                        <span class="font-bold">{{ answers.q9 }}.</span>
                                                                        <span class="truncate">{{ getLabelForLetter(answers.q9) }}</span>
                                                                    </span>
                                                                    <button @click="clearAnswer('q9')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0" title="Remove">✕</button>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="text-gray-400 text-xs w-full text-center">
                                                                        {{ dragOverKey === 'q9' ? '⬇ Drop here' : 'Drop answer here' }}
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <button
                                                            @click.stop="emit('toggleFlag', 9)"
                                                            class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150 opacity-5 group-hover/q:opacity-100"
                                                            :class="isQuestionFlagged(9) ? 'border-red-400 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Q10 -->
                                                <div
                                                    id="question-10"
                                                    class="group/q bg-white p-3 rounded-lg transition-all duration-200"
                                                    :class="dragOverKey === 'q10' ? 'ring-2 ring-blue-400 bg-blue-50' : answers.q10 ? 'border border-gray-300' : 'border border-gray-200'"
                                                    @dragover.prevent="onDragOver($event, 'q10')"
                                                    @dragleave="onDragLeave($event, 'q10')"
                                                    @drop.prevent="onDrop($event, 'q10')"
                                                >
                                                    <div class="flex items-start gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium leading-relaxed text-gray-700 mb-2"
                                                                :data-segment-text="'10. Water is brought to a high temperature.'"
                                                                v-html="getHighlightedSegment('10. Water is brought to a high temperature.')">
                                                            </p>
                                                            <div
                                                                class="flex items-center min-w-0 overflow-hidden min-h-[36px] rounded border-2 border-dashed px-3 py-1.5 text-sm transition-all duration-200"
                                                                :class="[
                                                                    dragOverKey === 'q10'
                                                                        ? 'border-blue-500 bg-blue-50'
                                                                        : answers.q10
                                                                            ? 'border-gray-400 bg-gray-50'
                                                                            : 'border-gray-300 bg-gray-50'
                                                                ]"
                                                            >
                                                                <template v-if="answers.q10">
                                                                    <span class="flex items-center gap-1.5 font-semibold text-sm flex-1 min-w-0 text-gray-800">
                                                                        <span class="font-bold">{{ answers.q10 }}.</span>
                                                                        <span class="truncate">{{ getLabelForLetter(answers.q10) }}</span>
                                                                    </span>
                                                                    <button @click="clearAnswer('q10')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0" title="Remove">✕</button>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="text-gray-400 text-xs w-full text-center">
                                                                        {{ dragOverKey === 'q10' ? '⬇ Drop here' : 'Drop answer here' }}
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <button
                                                            @click.stop="emit('toggleFlag', 10)"
                                                            class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150 opacity-5 group-hover/q:opacity-100"
                                                            :class="isQuestionFlagged(10) ? 'border-red-400 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Q11 -->
                                                <div
                                                    id="question-11"
                                                    class="group/q bg-white p-3 rounded-lg transition-all duration-200"
                                                    :class="dragOverKey === 'q11' ? 'ring-2 ring-blue-400 bg-blue-50' : answers.q11 ? 'border border-gray-300' : 'border border-gray-200'"
                                                    @dragover.prevent="onDragOver($event, 'q11')"
                                                    @dragleave="onDragLeave($event, 'q11')"
                                                    @drop.prevent="onDrop($event, 'q11')"
                                                >
                                                    <div class="flex items-start gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium leading-relaxed text-gray-700 mb-2"
                                                                :data-segment-text="'11. People fear pollutants in tap water.'"
                                                                v-html="getHighlightedSegment('11. People fear pollutants in tap water.')">
                                                            </p>
                                                            <div
                                                                class="flex items-center min-w-0 overflow-hidden min-h-[36px] rounded border-2 border-dashed px-3 py-1.5 text-sm transition-all duration-200"
                                                                :class="[
                                                                    dragOverKey === 'q11'
                                                                        ? 'border-blue-500 bg-blue-50'
                                                                        : answers.q11
                                                                            ? 'border-gray-400 bg-gray-50'
                                                                            : 'border-gray-300 bg-gray-50'
                                                                ]"
                                                            >
                                                                <template v-if="answers.q11">
                                                                    <span class="flex items-center gap-1.5 font-semibold text-sm flex-1 min-w-0 text-gray-800">
                                                                        <span class="font-bold">{{ answers.q11 }}.</span>
                                                                        <span class="truncate">{{ getLabelForLetter(answers.q11) }}</span>
                                                                    </span>
                                                                    <button @click="clearAnswer('q11')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0" title="Remove">✕</button>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="text-gray-400 text-xs w-full text-center">
                                                                        {{ dragOverKey === 'q11' ? '⬇ Drop here' : 'Drop answer here' }}
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <button
                                                            @click.stop="emit('toggleFlag', 11)"
                                                            class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150 opacity-5 group-hover/q:opacity-100"
                                                            :class="isQuestionFlagged(11) ? 'border-red-400 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Q12 -->
                                                <div
                                                    id="question-12"
                                                    class="group/q bg-white p-3 rounded-lg transition-all duration-200"
                                                    :class="dragOverKey === 'q12' ? 'ring-2 ring-blue-400 bg-blue-50' : answers.q12 ? 'border border-gray-300' : 'border border-gray-200'"
                                                    @dragover.prevent="onDragOver($event, 'q12')"
                                                    @dragleave="onDragLeave($event, 'q12')"
                                                    @drop.prevent="onDrop($event, 'q12')"
                                                >
                                                    <div class="flex items-start gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium leading-relaxed text-gray-700 mb-2"
                                                                :data-segment-text="'12. Air conditioning systems are inadequate.'"
                                                                v-html="getHighlightedSegment('12. Air conditioning systems are inadequate.')">
                                                            </p>
                                                            <div
                                                                class="flex items-center min-w-0 overflow-hidden min-h-[36px] rounded border-2 border-dashed px-3 py-1.5 text-sm transition-all duration-200"
                                                                :class="[
                                                                    dragOverKey === 'q12'
                                                                        ? 'border-blue-500 bg-blue-50'
                                                                        : answers.q12
                                                                            ? 'border-gray-400 bg-gray-50'
                                                                            : 'border-gray-300 bg-gray-50'
                                                                ]"
                                                            >
                                                                <template v-if="answers.q12">
                                                                    <span class="flex items-center gap-1.5 font-semibold text-sm flex-1 min-w-0 text-gray-800">
                                                                        <span class="font-bold">{{ answers.q12 }}.</span>
                                                                        <span class="truncate">{{ getLabelForLetter(answers.q12) }}</span>
                                                                    </span>
                                                                    <button @click="clearAnswer('q12')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0" title="Remove">✕</button>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="text-gray-400 text-xs w-full text-center">
                                                                        {{ dragOverKey === 'q12' ? '⬇ Drop here' : 'Drop answer here' }}
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <button
                                                            @click.stop="emit('toggleFlag', 12)"
                                                            class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150 opacity-5 group-hover/q:opacity-100"
                                                            :class="isQuestionFlagged(12) ? 'border-red-400 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Q13 -->
                                                <div
                                                    id="question-13"
                                                    class="group/q bg-white p-3 rounded-lg transition-all duration-200"
                                                    :class="dragOverKey === 'q13' ? 'ring-2 ring-blue-400 bg-blue-50' : answers.q13 ? 'border border-gray-300' : 'border border-gray-200'"
                                                    @dragover.prevent="onDragOver($event, 'q13')"
                                                    @dragleave="onDragLeave($event, 'q13')"
                                                    @drop.prevent="onDrop($event, 'q13')"
                                                >
                                                    <div class="flex items-start gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium leading-relaxed text-gray-700 mb-2"
                                                                :data-segment-text="'13. Toxic chemicals are abundant in new cars.'"
                                                                v-html="getHighlightedSegment('13. Toxic chemicals are abundant in new cars.')">
                                                            </p>
                                                            <div
                                                                class="flex items-center min-w-0 overflow-hidden min-h-[36px] rounded border-2 border-dashed px-3 py-1.5 text-sm transition-all duration-200"
                                                                :class="[
                                                                    dragOverKey === 'q13'
                                                                        ? 'border-blue-500 bg-blue-50'
                                                                        : answers.q13
                                                                            ? 'border-gray-400 bg-gray-50'
                                                                            : 'border-gray-300 bg-gray-50'
                                                                ]"
                                                            >
                                                                <template v-if="answers.q13">
                                                                    <span class="flex items-center gap-1.5 font-semibold text-sm flex-1 min-w-0 text-gray-800">
                                                                        <span class="font-bold">{{ answers.q13 }}.</span>
                                                                        <span class="truncate">{{ getLabelForLetter(answers.q13) }}</span>
                                                                    </span>
                                                                    <button @click="clearAnswer('q13')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0" title="Remove">✕</button>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="text-gray-400 text-xs w-full text-center">
                                                                        {{ dragOverKey === 'q13' ? '⬇ Drop here' : 'Drop answer here' }}
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <button
                                                            @click.stop="emit('toggleFlag', 13)"
                                                            class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150 opacity-5 group-hover/q:opacity-100"
                                                            :class="isQuestionFlagged(13) ? 'border-red-400 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: Draggable Options (Sticky) -->
                                        <div class="lg:w-72 shrink-0 lg:sticky lg:top-4 lg:self-start">
                                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                                <h4 class="mb-3 font-bold text-gray-800"
                                                    :data-segment-text="'List B: EFFECTS'"
                                                    v-html="getHighlightedSegment('List B: EFFECTS')"></h4>

                                                <div class="flex flex-col gap-2 max-h-[400px] overflow-y-auto">
                                                    <template v-for="opt in listBOptions" :key="opt.letter">
                                                        <div
                                                            v-if="!isOptionUsed(opt.letter)"
                                                            :draggable="true"
                                                            @dragstart="onDragStart($event, opt.letter)"
                                                            @dragend="onDragEnd"
                                                            class="cursor-grab active:cursor-grabbing select-none
                                                                flex items-start gap-1.5 px-3 py-2 rounded-lg
                                                                border border-gray-200 bg-gray-50 hover:bg-gray-100 hover:border-gray-300
                                                                text-sm transition-all duration-150"
                                                            :class="{ 'opacity-50 scale-95': draggingLetter === opt.letter }"
                                                        >
                                                            <span class="font-bold text-gray-700 shrink-0">{{ opt.letter }}.</span>
                                                            <span class="text-gray-600 text-xs leading-relaxed" :data-segment-text="opt.text" v-html="getHighlightedSegment(opt.text)"></span>
                                                        </div>
                                                    </template>

                                                    <!-- Show message when all options are used -->
                                                    <div v-if="listBOptions.every(opt => isOptionUsed(opt.letter))"
                                                        class="text-center text-gray-400 text-xs py-4">
                                                        All options have been used
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- end Q7-13 -->

                                <!-- ─── Q14–19: List of Headings (Draggable source) ─── -->


                            </div><!-- end questions space-y-8 -->
                        </div><!-- end scrollable questions -->
                    </div>
                </div><!-- end answer panel -->

            </div><!-- end flex container -->
        </div>

        <!-- ══ Context Menu (Highlight) ══ -->
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
                            <svg
                                class="h-5 w-5 text-gray-900"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                            >
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
                            <svg
                                class="h-5 w-5 text-gray-700"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
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
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                ></path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button
                            @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                            title="Delete note"
                        >
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                ></path>
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
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                        "{{ selectedText }}"
                    </p>
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
                    <button
                        @click="cancelNote"
                        class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                    >
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

/* Bold placeholder styling for question inputs */
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
