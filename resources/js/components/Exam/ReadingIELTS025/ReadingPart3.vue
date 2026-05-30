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
    ref(`Packaging
    <b></b>
One of the most prominent design issues in pharmacy is that of drag packaging and patient information leaflets (Pits). Many letters have appeared in The Journal’s letters pages over the years from pharmacists dismayed at the designs of packaging that are “accidents waiting to happen”.
Packaging design in the pharmaceutical industry is handled by either in-house teams or design agencies. Designs for over-the-counter medicines, where characteristics such as attractiveness and distinguish- ability are regarded as significant, are usually commissioned from design agencies. A marketing team will prepare a brief and the designers will come up with perhaps six or seven designs. These are whittled down to two or three that might be tested on a consumer group. In contrast, most designs for prescription-only products are created in-house. In some cases, this may simply involve applying a company’s house design (ie, logo, colour, font, etc). The chosen design is then handed over to design engineers who work out how the packaging will be produced.
<b></b>
Design considerations
<b></b>
The author of the recently published “Information design for patient safety,” Thea Swayne, tracked the journey of a medicine from manufacturing plant, through distribution warehouses, pharmacies and hospital wards, to patients’ homes. Her book highlights a multitude of design problems with current packaging, such as look-alikes and sound-alikes, small type sizes and glare on blister foils. Situations in which medicines are used include a parent giving a cough medicine to a child in the middle of the night and a busy pharmacists electing one box from hundreds. It is argued that packaging should be designed for moments such as these. “Manufacturers are not aware of the complex situations into which products go. As designers, we are interested in not what is supposed to happen in hospital wards, but what happens in the real world,” Ms Swayne said.
Incidents where vein has been injected intrathecally instead of spine are a classic example of how poor design can contribute to harm. Investigations following these tragedies have attributed some blame to poor typescript.
<b></b>
Safety and compliance
<b></b>
Child protection is another area that gives designers opportunities to improve safety. According to the Child Accident Prevention Trust, seven out of 10 children admitted to hospital with suspected poisoning haveswallowedmedicines.Althoughchild-resistantclosureshavereducedthenumberofincidents,they are not: fully child-proof. The definition of such a closure is one that not more than 15 percent of children aged between 42 and 51 months can open within five minutes. There is scope for improving what is currently available, according to Richard Mawle, a freelance product designer. “Many child-resistant packs are based on strength. They do not necessarily prevent a child from access, but may prevent people with a disability,” he told The Journal. “The legal requirements are there for a good reason, but they are not good enough in terms of the users,” he said. “Older people, especially those with arthritis, may have the same level of strength as a child,” he explained, and suggested that better designs could rely on cognitive skills (eg, making the opening of a container a three-step process) or be based on the physical size of hands. 
Mr. Mawle worked with GlaxoSmithKline on a project to improve compliance through design, which involved applying his skills to packaging and PILs. Commenting on the information presented, he said: “There can be an awful lot of junk at the beginning of PILs. For example, why are company details listed towards the beginning of a leaflet when what might be more important for the patient is that the medicine should not be taken with alcohol?”
<b></b>
Design principles and guidelines
<b></b>
Look-alike boxes present a potential for picking errors and an obvious solution would be to use colours to highlight different strengths. However, according to Ms.Swayne, colour differentiation needs to be approached with care. Not only should strong colour contrasts be used, but designating a colour to a particular strength (colour coding) is not recommended because this could lead to the user not reading the text on a box.
Design features can provide the basis for lengthy debates. For example, one argument is that if all packaging is white with black lettering, people would have no choice but to read every box carefully. The problem is that trials of drug packaging design are few—common studies of legibility and comprehensibility concern road traffic signs and visual display units. Although some designers take results from such studies into account, proving that a particular feature is beneficial can be difficult. For example, EU legislation requires that packaging must now include the name of the medicine in Braille but, according to Karel van der Waarde, a design consultant to the pharmaceutical industry, “it is not known how much visually impaired patients will benefit nor how much the reading of visually able patients will be impaired”.
More evidence might, however, soon be available. EU legislation requires PILs to reflect consultations with target patient groups to ensure they are legible, clear and easy to use. This implies that industry will have to start conducting tests. Dr. van der Waarde has performed readability studies on boxes and PILs for industry. A typical study involves showing a leaflet or package to a small group and asking them questions to test understanding. Results and comments are used to modify the material, which is then tested on a larger group. A third group is used to show that any further changes made are an improvement. Dr. van der Waarde is, however, sceptical about the legal requirements and says that many regulatory authorities do not have the resources to handle packaging information properly. “They do not look at the use of packaging in a practical context—they only see one box at a time and not several together as pharmacists would do,” he said.
<b></b>
Innovations
<b></b>
The RCA innovation exhibition this year revealed designs for a number of innovative objects. “The popper”, by Hugo Glover, aims to help arthritis sufferers remove tablets from blister packs, and “plus point”, by James Cobb, is an adrenaline auto-injector that aims to overcome the fact that many patients do not carry their auto-injectors due to their prohibitive size. The aim of good design, according Roger Coleman, professor of inclusive design at the RCA, is to try to make things more user-friendly as well as safer. Surely, in a patient-centred health system, that can only be a good thing. “Information design for patient safety” is not intended to be mandatory. Rather, its purpose is to create a basic design standard and to stimulate innovation. The challenge for the pharmaceutical industry, as a whole, is to adopt such a standard.
`);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    { id: 'part-header', text: 'Part 3', offset: -100 },
    { id: 'part-instructions', text: 'Read the text and answer questions 27–40.', offset: -93 },
    { id: 'passage-title', text: 'Improving Patient Safety', offset: -35 },

    {
        id: 'passage',
        text: passageText.value,
        offset: 0,
    },

    // ===============================
    // Questions 27–32
    // ===============================

    { id: 'q27-32-title', text: 'Questions 27–32', offset: 5000 },

    {
        id: 'q27-32-instructions',
        text: 'Look at the following statements (Questions 27–32) and the list of people or organisation below. Match each statement with the correct person or organisation, A–D.',
        offset: 5015,
    },

    {
        id: 'q27-32-write',
        text: 'Write the correct letter, A–D, in boxes 27–32 on your answer sheet.',
        offset: 5200,
    },

    { id: 'q27-32-nb', text: 'NB You may use any letter more than once.', offset: 5280 },

    { id: 'people-A', text: 'A  Thea Swayne', offset: 5340 },
    { id: 'people-B', text: 'B  Children Accident Prevention Trust', offset: 5360 },
    { id: 'people-C', text: 'C  Richard Mawle', offset: 5400 },
    { id: 'people-D', text: 'D  Karel van der Waarde', offset: 5430 },

    { id: 'q27-num', text: '27.', offset: 5480 },
    {
        id: 'q27',
        text: 'Elderly people may have the same problem with children if the lids of containers require too much strength to open.',
        offset: 5485,
    },

    { id: 'q28-num', text: '28.', offset: 5620 },
    {
        id: 'q28',
        text: 'Adapting packaging for the blind may disadvantage the sighted people.',
        offset: 5625,
    },

    { id: 'q29-num', text: '29.', offset: 5730 },
    {
        id: 'q29',
        text: 'Specially designed lids cannot eliminate the possibility of children swallowing pills accidentally.',
        offset: 5735,
    },

    { id: 'q30-num', text: '30.', offset: 5860 },
    {
        id: 'q30',
        text: 'Container design should consider situations, such as drug used at home.',
        offset: 5865,
    },

    { id: 'q31-num', text: '31.', offset: 5960 },
    {
        id: 'q31',
        text: 'Governing bodies should investigate many different container cases rather than individual ones.',
        offset: 5965,
    },

    { id: 'q32-num', text: '32.', offset: 6090 },
    {
        id: 'q32',
        text: 'Information on the list of a leaflet hasn’t been in the right order.',
        offset: 6095,
    },

    // ===============================
    // Questions 33–37
    // ===============================

    { id: 'q33-37-title', text: 'Questions 33–37', offset: 6200 },

    {
        id: 'q33-37-inst',
        text: 'Complete the notes using the list of words, A–G, below. Write the correct letter, A–G, in boxes 33–37 on your answer sheet.',
        offset: 6215,
    },

    { id: 'notes-title', text: 'Packaging in pharmaceutical industry', offset: 6350 },
    { id: 'notes-subtitle', text: 'Designs for over-the-counter medicines', offset: 6380 },

    { id: 'notes-p1', text: 'First,', offset: 6420 },
    { id: 'q33-num', text: '33.', offset: 6450 },

    { id: 'notes-p2', text: 'make the proposal, then pass them to the', offset: 6460 },
    { id: 'q34-num', text: '34.', offset: 6510 },

    { id: 'notes-p3', text: 'Finally, these designs will be tested by', offset: 6520 },
    { id: 'q35-num', text: '35.', offset: 6560 },

    { id: 'notes-p4', text: 'Prescription-only medicines', offset: 6600 },
    { id: 'notes-p5', text: 'First, the design is made by', offset: 6630 },
    { id: 'q36-num', text: '36.', offset: 6660 },

    { id: 'notes-p6', text: 'and then subjected to', offset: 6670 },
    { id: 'q37-num', text: '37.', offset: 6700 },

    { id: 'opt-A', text: 'A consumers', offset: 6750 },
    { id: 'opt-B', text: 'B marketing teams', offset: 6770 },
    { id: 'opt-C', text: 'C pharmaceutical industry', offset: 6790 },
    { id: 'opt-D', text: 'D external designers', offset: 6815 },
    { id: 'opt-E', text: 'E in-house designers', offset: 6840 },
    { id: 'opt-F', text: 'F design engineers', offset: 6860 },
    { id: 'opt-G', text: 'G pharmacist', offset: 6880 },

    // ===============================
    // Questions 38–40
    // ===============================

    { id: 'q38-40-title', text: 'Questions 38–40', offset: 6940 },

    {
        id: 'q38-40-inst',
        text: 'Choose the correct letter, A, B, C or D. Write the correct letter in boxes 38–40 on your answer sheet.',
        offset: 6955,
    },

    { id: 'q38-num', text: '38.', offset: 7050 },
    { id: 'q38', text: 'What may cause the accident in “design container”?', offset: 7055 },
    { id: 'q38-A', text: 'A a print error', offset: 7105 },
    { id: 'q38-B', text: 'B style of print', offset: 7125 },
    { id: 'q38-C', text: 'C wrong label', offset: 7145 },
    { id: 'q38-D', text: 'D the shape of the bottle', offset: 7165 },

    { id: 'q39-num', text: '39.', offset: 7205 },
    { id: 'q39', text: 'What do people think about the black and white only print?', offset: 7210 },
    { id: 'q39-A', text: 'A Consumers dislike these products.', offset: 7265 },
    { id: 'q39-B', text: 'B People have to pay more attention to the information.', offset: 7295 },
    { id: 'q39-C', text: 'C That makes all products look alike.', offset: 7335 },
    { id: 'q39-D', text: 'D Sighted people may feel it more helpful.', offset: 7365 },

    { id: 'q40-num', text: '40.', offset: 7405 },
    { id: 'q40', text: 'Why does the writer mention “popper” and “plus point”?', offset: 7410 },
    { id: 'q40-A', text: 'A to show that container design has made some progress', offset: 7470 },
    { id: 'q40-B', text: 'B to illustrate an example of inappropriate design which can lead to accidents', offset: 7515 },
    { id: 'q40-C', text: 'C to show that the industry still needs more to improve', offset: 7580 },
    { id: 'q40-D', text: 'D to point out that consumers should be more informed about the information', offset: 7620 },
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
                        segment = textSegments.value.find(s => s.id === 'passage');
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
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegment('Part 3')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegment('Read the text and answer questions 27–40.')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="passage-title" v-html="getHighlightedSegment('Improving Patient Safety')"></span>
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

                            <!-- =============================== -->
                            <!-- Questions 27–32 (Matching/Dropdown) -->
                            <!-- =============================== -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="select-text"
                                                data-segment-id="q27-32-title"
                                                v-html="getHighlightedSegment('Questions 27–32')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="select-text"
                                            data-segment-id="q27-32-instructions"
                                            v-html="getHighlightedSegment('Look at the following statements (Questions 27–32) and the list of people or organisation below. Match each statement with the correct person or organisation, A–D.')"
                                        ></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span
                                            class="select-text"
                                            data-segment-id="q27-32-write"
                                            v-html="getHighlightedSegment('Write the correct letter, A–D, in boxes 27–32 on your answer sheet.')"
                                        ></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span
                                            class="select-text"
                                            data-segment-id="q27-32-nb"
                                            v-html="getHighlightedSegment('NB You may use any letter more than once.')"
                                        ></span>
                                    </p>
                                </div>

                                <div class="mt-6 border border-gray-900 bg-white p-4 w-fit">
                                    <h4 class="mb-2 font-bold text-gray-900 text-sm">List of People / Organisation</h4>
                                    <div class="space-y-1 text-sm">
                                        <p><span data-segment-id="people-A" v-html="getHighlightedSegment('A  Thea Swayne')"></span></p>
                                        <p><span data-segment-id="people-B" v-html="getHighlightedSegment('B  Children Accident Prevention Trust')"></span></p>
                                        <p><span data-segment-id="people-C" v-html="getHighlightedSegment('C  Richard Mawle')"></span></p>
                                        <p><span data-segment-id="people-D" v-html="getHighlightedSegment('D  Karel van der Waarde')"></span></p>
                                    </div>
                                </div>

                                <!-- Questions 27–32 -->
                                <div class="space-y-4">

                                    <!-- Q27 -->
                                    <div
                                        id="question-27"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 27"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('27.')"></span>
                                            <select
                                                v-model="answers.q27"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q27"
                                                    v-html="getHighlightedSegment('Elderly people may have the same problem with children if the lids of containers require too much strength to open.')"
                                                ></span>
                                            </p>
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

                                    <!-- Q28 -->
                                    <div
                                        id="question-28"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 28"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('28.')"></span>
                                            <select
                                                v-model="answers.q28"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q28"
                                                    v-html="getHighlightedSegment('Adapting packaging for the blind may disadvantage the sighted people.')"
                                                ></span>
                                            </p>
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

                                    <!-- Q29 -->
                                    <div
                                        id="question-29"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 29"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('29.')"></span>
                                            <select
                                                v-model="answers.q29"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q29"
                                                    v-html="getHighlightedSegment('Specially designed lids cannot eliminate the possibility of children swallowing pills accidentally.')"
                                                ></span>
                                            </p>
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

                                    <!-- Q30 -->
                                    <div
                                        id="question-30"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 30"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('30.')"></span>
                                            <select
                                                v-model="answers.q30"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q30"
                                                    v-html="getHighlightedSegment('Container design should consider situations, such as drug used at home.')"
                                                ></span>
                                            </p>
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

                                    <!-- Q31 -->
                                    <div
                                        id="question-31"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 31"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('31.')"></span>
                                            <select
                                                v-model="answers.q31"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q31"
                                                    v-html="getHighlightedSegment('Governing bodies should investigate many different container cases rather than individual ones.')"
                                                ></span>
                                            </p>
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

                                    <!-- Q32 -->
                                    <div
                                        id="question-32"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('32.')"></span>
                                            <select
                                                v-model="answers.q32"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q32"
                                                    v-html="getHighlightedSegment('Information on the list of a leaflet hasn\'t been in the right order.')"
                                                ></span>
                                            </p>
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

                                <!-- List of People / Organisation (static reference) -->
                                
                            </div>

                            <!-- =============================== -->
                            <!-- Questions 33–37 (Notes Completion / Dropdown A–G) -->
                            <!-- =============================== -->
                            <div class="bg-white p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="select-text"
                                                data-segment-id="q33-37-title"
                                                v-html="getHighlightedSegment('Questions 33–37')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="select-text"
                                            data-segment-id="q33-37-inst"
                                            v-html="getHighlightedSegment('Complete the notes using the list of words, A–G, below. Write the correct letter, A–G, in boxes 33–37 on your answer sheet.')"
                                        ></span>
                                    </p>
                                </div>

                                <!-- Options A–G (static reference) -->
                                <div class="mt-6 border border-gray-900 bg-white p-4">
                                    <h4 class="mb-2 font-bold text-gray-900 text-sm">Word List</h4>
                                    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                                        <p><span data-segment-id="opt-A" v-html="getHighlightedSegment('A consumers')"></span></p>
                                        <p><span data-segment-id="opt-B" v-html="getHighlightedSegment('B marketing teams')"></span></p>
                                        <p><span data-segment-id="opt-C" v-html="getHighlightedSegment('C pharmaceutical industry')"></span></p>
                                        <p><span data-segment-id="opt-D" v-html="getHighlightedSegment('D external designers')"></span></p>
                                        <p><span data-segment-id="opt-E" v-html="getHighlightedSegment('E in-house designers')"></span></p>
                                        <p><span data-segment-id="opt-F" v-html="getHighlightedSegment('F design engineers')"></span></p>
                                        <p><span data-segment-id="opt-G" v-html="getHighlightedSegment('G pharmacist')"></span></p>
                                    </div>
                                </div>

                                <!-- Notes box -->
                                <div class="border border-gray-900 p-5 bg-white">
                                    <h4 class="mb-1 text-base font-bold text-gray-900">
                                        <span data-segment-id="notes-title" v-html="getHighlightedSegment('Packaging in pharmaceutical industry')"></span>
                                    </h4>
                                    <h5 class="mb-4 text-sm font-semibold text-gray-700">
                                        <span data-segment-id="notes-subtitle" v-html="getHighlightedSegment('Designs for over-the-counter medicines')"></span>
                                    </h5>

                                    <!-- Over-the-counter block -->
                                    <div class="mb-5 text-sm leading-relaxed text-gray-700">
                                        <p class="flex flex-wrap items-center gap-x-1 gap-y-2">
                                            <span data-segment-id="notes-p1" v-html="getHighlightedSegment('First,')"></span>

                                            <!-- Q33 -->
                                            <span
                                                id="question-33"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 33"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="font-bold text-gray-900 flex-shrink-0" v-html="getHighlightedSegment('33.')"></span>
                                                <select
                                                    v-model="answers.q33"
                                                    class="border-2 border-gray-900 bg-white px-2 py-0.5 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                    <option value="E">E</option>
                                                    <option value="F">F</option>
                                                    <option value="G">G</option>
                                                </select>
                                                <button
                                                    v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                                    @click.stop="toggleFlag(33)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <span data-segment-id="notes-p2" v-html="getHighlightedSegment('make the proposal, then pass them to the')"></span>

                                            <!-- Q34 -->
                                            <span
                                                id="question-34"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 34"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="font-bold text-gray-900 flex-shrink-0" v-html="getHighlightedSegment('34.')"></span>
                                                <select
                                                    v-model="answers.q34"
                                                    class="border-2 border-gray-900 bg-white px-2 py-0.5 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                    <option value="E">E</option>
                                                    <option value="F">F</option>
                                                    <option value="G">G</option>
                                                </select>
                                                <button
                                                    v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                                    @click.stop="toggleFlag(34)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <span data-segment-id="notes-p3" v-html="getHighlightedSegment('Finally, these designs will be tested by')"></span>

                                            <!-- Q35 -->
                                            <span
                                                id="question-35"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 35"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="font-bold text-gray-900 flex-shrink-0" v-html="getHighlightedSegment('35.')"></span>
                                                <select
                                                    v-model="answers.q35"
                                                    class="border-2 border-gray-900 bg-white px-2 py-0.5 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                    <option value="E">E</option>
                                                    <option value="F">F</option>
                                                    <option value="G">G</option>
                                                </select>
                                                <button
                                                    v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                    @click.stop="toggleFlag(35)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Prescription-only block -->
                                    <h5 class="mb-3 text-sm font-semibold text-gray-700">
                                        <span data-segment-id="notes-p4" v-html="getHighlightedSegment('Prescription-only medicines')"></span>
                                    </h5>
                                    <div class="text-sm leading-relaxed text-gray-700">
                                        <p class="flex flex-wrap items-center gap-x-1 gap-y-2">
                                            <span data-segment-id="notes-p5" v-html="getHighlightedSegment('First, the design is made by')"></span>

                                            <!-- Q36 -->
                                            <span
                                                id="question-36"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 36"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="font-bold text-gray-900 flex-shrink-0" v-html="getHighlightedSegment('36.')"></span>
                                                <select
                                                    v-model="answers.q36"
                                                    class="border-2 border-gray-900 bg-white px-2 py-0.5 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                    <option value="E">E</option>
                                                    <option value="F">F</option>
                                                    <option value="G">G</option>
                                                </select>
                                                <button
                                                    v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                    @click.stop="toggleFlag(36)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <span data-segment-id="notes-p6" v-html="getHighlightedSegment('and then subjected to')"></span>

                                            <!-- Q37 -->
                                            <span
                                                id="question-37"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 37"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="font-bold text-gray-900 flex-shrink-0" v-html="getHighlightedSegment('37.')"></span>
                                                <select
                                                    v-model="answers.q37"
                                                    class="border-2 border-gray-900 bg-white px-2 py-0.5 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                    <option value="E">E</option>
                                                    <option value="F">F</option>
                                                    <option value="G">G</option>
                                                </select>
                                                <button
                                                    v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                    @click.stop="toggleFlag(37)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                
                            </div>

                            <!-- =============================== -->
                            <!-- Questions 38–40 (MCQ / Radio)   -->
                            <!-- =============================== -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="select-text"
                                                data-segment-id="q38-40-title"
                                                v-html="getHighlightedSegment('Questions 38–40')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="select-text"
                                            data-segment-id="q38-40-inst"
                                            v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D. Write the correct letter in boxes 38–40 on your answer sheet.')"
                                        ></span>
                                    </p>
                                </div>

                                <div class="space-y-8">

                                    <!-- Q38 -->
                                    <div
                                        id="question-38"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 mb-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('38.')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q38"
                                                    v-html="getHighlightedSegment('What may cause the accident in “design container”?')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q38" value="A" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-A" v-html="getHighlightedSegment('A a print error')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q38" value="B" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-B" v-html="getHighlightedSegment('B style of print')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q38" value="C" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-C" v-html="getHighlightedSegment('C wrong label')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q38" value="D" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q38-D" v-html="getHighlightedSegment('D the shape of the bottle')"></span>
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
                                        <div class="flex items-start gap-2 mb-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('39.')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q39"
                                                    v-html="getHighlightedSegment('What do people think about the black and white only print?')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q39" value="A" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-A" v-html="getHighlightedSegment('A Consumers dislike these products.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q39" value="B" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-B" v-html="getHighlightedSegment('B People have to pay more attention to the information.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q39" value="C" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-C" v-html="getHighlightedSegment('C That makes all products look alike.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q39" value="D" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q39-D" v-html="getHighlightedSegment('D Sighted people may feel it more helpful.')"></span>
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
                                        <div class="flex items-start gap-2 mb-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('40.')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="select-text"
                                                    data-segment-id="q40"
                                                    v-html="getHighlightedSegment('Why does the writer mention “popper” and “plus point”?')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q40" value="A" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-A" v-html="getHighlightedSegment('A to show that container design has made some progress')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q40" value="B" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-B" v-html="getHighlightedSegment('B to illustrate an example of inappropriate design which can lead to accidents')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q40" value="C" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-C" v-html="getHighlightedSegment('C to show that the industry still needs more to improve')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q40" value="D" class="mt-0.5 flex-shrink-0 h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-700 select-text" data-segment-id="q40-D" v-html="getHighlightedSegment('D to point out that consumers should be more informed about the information')"></span>
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
