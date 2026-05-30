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
const contentTextRef = ref<HTMLDivElement | null>(null);

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


const questions2935 = [
    { num: 29, text: "The origin of the word 'calisthenics'" },
    { num: 30, text: 'The last popular supporter of calisthenics' },
    { num: 31, text: 'The first use of calisthenics as a training method' },
    { num: 32, text: 'A multidisciplinary approach to all-round health and strength' },
    { num: 33, text: 'Reasons for the survival of calisthenics throughout the ages' },
    { num: 34, text: 'The use of a medical substance to increase muscle mass and strength' },
    { num: 35, text: 'A reference to travelling showmen who displayed their strength for audiences' },
];


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
const passageText = ref(`

<b>A</b><br/> From the very first caveman to scale a tree or hang from a cliff face, to the mighty armies of the Greco-Roman empires and the gymnasiums of modern American high schools, calisthenics has endured and thrived because of its simplicity and utility. Unlike strength training which involves weights, machines or resistance bands, calisthenics uses only the body's own weight for physical development.<br/><br/>

<b>B</b><br/> Calisthenics enters the historical record at around 480 B.C., with Herodotus' account of the Battle of Thermopylae. Herodotus reported that, prior to the battle, the god-king Xerxes sent a scout party to spy on his Spartan enemies. The scouts informed Xerxes that the Spartans, under the leadership of King Leonidas, were practicing some kind of bizarre, synchronised movements akin to a tribal dance. Xerxes was greatly amused. His own army was comprised of over 120,000 men, while the Spartans had just 300. Leonidas was informed that he must retreat or face annihilation. The Spartans did not retreat, however, and in the ensuing battle they managed to hold Xerxes' enormous army at bay for some time until reinforcements arrived. It turns out their tribal dance was not a superstitious ritual but a form of calisthenics by which they were building awe-inspiring physical strength and endurance.<br/><br/>

<b>C</b><br/> The Greeks took calisthenics seriously not only as a form of military discipline and strength, but also as an artistic expression of movement and an aesthetically ideal physique. Indeed, the term calisthenics itself is derived from the Greek words for beauty and strength. We know from historical records and images from pottery, mosaics and sculpture that they were greatly admired - and still are, today - for their combination of athleticism and beauty. We often say someone 'has the body of a Greek god'. This expression has traveled through centuries and continents, and the source of this envy and admiration is the calisthenics method itself.<br/><br/>

<b>D</b><br/> Calisthenics experienced its second golden age in the 1800s. This century saw the birth of gymnastics, an organised sport that uses a range of bars, rings, vaulting horses and balancing beams to display physical prowess. This period is also when the phenomena of strongmen developed. These were people of astounding physical strength and development who forged nomadic careers by demonstrating outlandish feats of strength to stunned populations. Most of these men trained using hand balancing and horizontal bars, as modern weight machines had not yet been invented.<br/><br/>

<b>E</b><br/> In the 1950s, Angelo Siciliano – who went by the stage name Charles Atlas – was crowned “The World's Most Perfectly Developed Man”. Atlas's own approach stemmed from traditional calisthenics, and through a series of mail order comic books he taught these methods to hundreds of thousands of children and young adults through the 1960s and 1970s. But Atlas was the last of a dying breed. The tides were turning, fitness methods were drifting away from calisthenics, and no widely-regarded proponent of the method would ever succeed him.<br/><br/>

<b>F</b><br/> In the 1960s and 1970s calisthenics and the goal of functional strength combined with physical beauty was replaced by an emphasis on huge muscles at any cost. This became the sport of body building. Although body building's pioneers were drawn from the calisthenics tradition, the sole goal soon became an increase in muscle size. Body building icons, people such as Arnold Schwarzenegger and Sergio Oliva, were called mass monsters because of their imposing physiques. Physical development of this nature was only attainable through the use of anabolic steroids, synthetic hormones which boosted muscle development while harming overall health. These body builders also relied on free weights and machines, which allowed them to target and bloat the size of individual muscles rather than develop a naturally proportioned body. Calisthenics, with its emphasis on physical beauty and a balance in proportions, had little to offer the mass monsters.<br/><br/>

<b>G</b><br/> In this “bigger is better” climate, calisthenics was relegated to groups perceived to be vulnerable, such as women, people recuperating from injuries and school students. Although some of the strongest and most physically developed human beings ever to have lived acquired their abilities through the use of sophisticated calisthenics, a great deal of this knowledge was discarded and the method was reduced to nothing more than an easily accessible and readily available activity. Those who mastered the rudimentary skills of calisthenics could expect to graduate to weight training rather than advanced calisthenics.<br/><br/>

<b>H</b><br/> In recent years, however, fitness trends have been shifting back toward the use of calisthenics. Bodybuilding approaches that promote excessive muscle development frequently lead to joint pain, injuries, unbalanced physiques and weak cardiovascular health. As a result, many of the newest and most popular gyms and programmes emphasise calisthenics-based methods instead. Modern practices often combine elements from a number of related traditions such as yoga, Pilates, kettle-ball training, gymnastics and traditional Greco-Roman calisthenics. Many people are keen to recover the original Greek vision of physical beauty and strength and harmony of the mind-body connection.`);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    // Header text segments
    { id: 'header-label', text: 'Part 3', offset: -80 },
    { id: 'header-title', text: 'CALISTHENICS', offset: -62 },
    { id: 'passage-title', text: "The world's oldest form of resistance training", offset: -49 },

    // Passage text segment
    {
        id: 'passage',
        text: passageText.value,
        offset: 0,
    },

    // Questions 29-35 (Matching paragraphs)
    { id: 'q29-35-title', text: 'Questions 29–35', offset: passageText.value.length + 1 },
    {
        id: 'q29-35-instructions',
        text: 'The text has eight paragraphs, A–H. Which paragraph contains the following information? Write the correct letter, A–H, in boxes 29–35 on your answer sheet.',
        offset: passageText.value.length + 25,
    },
    { id: 'q29', text: "The origin of the word 'calisthenics'", offset: passageText.value.length + 125 },
    { id: 'q30', text: 'The last popular supporter of calisthenics', offset: passageText.value.length + 175 },
    { id: 'q31', text: 'The first use of calisthenics as a training method', offset: passageText.value.length + 225 },
    { id: 'q32', text: 'A multidisciplinary approach to all-round health and strength', offset: passageText.value.length + 285 },
    { id: 'q33', text: 'Reasons for the survival of calisthenics throughout the ages', offset: passageText.value.length + 355 },
    { id: 'q34', text: 'The use of a medical substance to increase muscle mass and strength', offset: passageText.value.length + 425 },
    { id: 'q35', text: 'A reference to travelling showmen who displayed their strength for audiences', offset: passageText.value.length + 505 },

    // Questions 36-40 (Summary completion)
    { id: 'q36-40-title', text: 'Questions 36–40', offset: passageText.value.length + 595 },
    {
        id: 'q36-40-instructions',
        text: 'Complete the summary below. Choose NO MORE THAN TWO WORDS from the text for each answer. Write your answers in boxes 36–40 on your answer sheet.',
        offset: passageText.value.length + 615,
    },
    {
        id: 'summary-p1-part1',
        text: 'During the sixties and seventies, attaining huge muscles became more important than',
        offset: passageText.value.length + 735,
    },
    { id: 'summary-p1-part2', text: 'or having an attractive-looking body.', offset: passageText.value.length + 825 },
    {
        id: 'summary-p2-part1',
        text: 'The first people to take up this new sport of body building had a background in calisthenics but the most famous practitioners became known as',
        offset: passageText.value.length + 875,
    },
    { id: 'summary-p2-part2', text: 'on account of the impressive size of their muscles.', offset: passageText.value.length + 1025 },
    {
        id: 'summary-p3-part1',
        text: "Drugs and mechanical devices were used to develop individual muscles to a monstrous size. Calisthenics then became the domain of 'weaker' people: females, children and those recovering from",
        offset: passageText.value.length + 1095,
    },
    { id: 'summary-p3-part2', text: '.', offset: passageText.value.length + 1275 },
    {
        id: 'summary-p4-part1',
        text: 'Much of the advanced knowledge about calisthenics was lost and the method was subsequently downgraded to the status of a simple, user-friendly activity. Once a person became skilled at this, he would progress to',
        offset: passageText.value.length + 1280,
    },
    { id: 'summary-p4-part2', text: '.', offset: passageText.value.length + 1470 },
    {
        id: 'summary-p5-part1',
        text: 'Currently a revival of calisthenics is under way as extreme muscle building can harm the body leaving it sore, out of balance, and in poor',
        offset: passageText.value.length + 1475,
    },
    { id: 'summary-p5-part2', text: '.', offset: passageText.value.length + 1615 },
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

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (!rect || !contentTextRef.value) return;

        // Position tooltip above selection
        const vw = window.innerWidth;
        contextMenuPosition.value = {
            x: Math.min(Math.max(rect.left + rect.width / 2, 90), vw - 90),
            y: Math.max(rect.top - 58, 10),
        };
        showContextMenu.value = true;

        if (!selection || !range) return;

        // --- Find start segment (where selection begins) ---
        let startNode: Node | null = range.startContainer;
        if (startNode?.nodeType === Node.TEXT_NODE) startNode = startNode.parentNode;
        let startSegmentEl: Element | null = null;
        let node: Node | null = startNode;
        while (node && node !== contentTextRef.value) {
            if ((node as Element).hasAttribute?.('data-segment-id')) {
                startSegmentEl = node as Element;
                break;
            }
            node = node.parentNode;
        }

        // --- Find end segment (where selection ends) ---
        let endNode: Node | null = range.endContainer;
        if (endNode?.nodeType === Node.TEXT_NODE) endNode = endNode.parentNode;
        let endSegmentEl: Element | null = null;
        node = endNode;
        while (node && node !== contentTextRef.value) {
            if ((node as Element).hasAttribute?.('data-segment-id')) {
                endSegmentEl = node as Element;
                break;
            }
            node = node.parentNode;
        }

        if (!startSegmentEl) return;

        const startSegId = startSegmentEl.getAttribute('data-segment-id')!;
        const startSegment = textSegments.value.find((s) => s.id === startSegId);
        if (!startSegment) return;

        // --- Single segment selection (original logic) ---
        if (!endSegmentEl || startSegmentEl === endSegmentEl) {
            const segPlainLen = getPlainTextLength(startSegment.text);
            const preRange = document.createRange();
            preRange.selectNodeContents(startSegmentEl);
            preRange.setEnd(range.startContainer, range.startOffset);
            const rawPre = preRange.toString().length;
            const pStart = Math.max(0, Math.min(rawPre, segPlainLen));
            const pEnd = Math.min(pStart + selected.length, segPlainLen);
            if (pEnd <= pStart) return;

            selectedText.value = selected;
            selectionRange.value = {
                start: startSegment.offset + pStart,
                end: startSegment.offset + pEnd,
            };
            return;
        }

        // --- Cross-segment selection ---
        const endSegId = endSegmentEl.getAttribute('data-segment-id')!;
        const endSegment = textSegments.value.find((s) => s.id === endSegId);
        if (!endSegment) return;

        // Offset within start segment
        const preStartRange = document.createRange();
        preStartRange.selectNodeContents(startSegmentEl);
        preStartRange.setEnd(range.startContainer, range.startOffset);
        const startPlainOffset = preStartRange.toString().length;

        // Offset within end segment
        const preEndRange = document.createRange();
        preEndRange.selectNodeContents(endSegmentEl);
        preEndRange.setEnd(range.endContainer, range.endOffset);
        const endPlainOffset = preEndRange.toString().length;

        const globalStart = startSegment.offset + startPlainOffset;
        const globalEnd = endSegment.offset + endPlainOffset;

        if (globalEnd <= globalStart) return;

        selectedText.value = selected;
        selectionRange.value = { start: globalStart, end: globalEnd };
    }, 10);
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <!-- Part 3 Header -->
        <div class="mx-5 mt-4  border-gray-400 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text"
               data-segment-id="header-label"
               v-html="getHighlightedSegment('READING PASSAGE 3')"></p>
            <p class="text-sm text-gray-700 select-text">Read the text and answer questions 29–40.</p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage Panel ── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="header-title"
                                  v-html="getHighlightedSegment('CALISTHENICS')"></span>
                        </h2>
                    </div>

                    <div class="space-y-2" :style="contentZoom">
                        <div class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <h3 class="mb-4 text-lg font-bold text-gray-800">
                                    <span class="select-text" data-segment-id="passage-title"
                                          v-html="getHighlightedSegment('The world\'s oldest form of resistance training')"></span>
                                </h3>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="text-lg select-text" data-segment-id="passage"
                                          v-html="getHighlightedSegment(textSegments.find(s => s.id === 'passage')!.text)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Panel ── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ── Questions 29–35: Paragraph matching ── -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q29-35-title"
                                              v-html="getHighlightedSegment('Questions 29–35')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="select-text" data-segment-id="q29-35-instructions"
                                              v-html="getHighlightedSegment('The text has eight paragraphs, A–H. Which paragraph contains the following information? Write the correct letter, A–H, in boxes 29–35 on your answer sheet.')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <div v-for="q in questions2935" :key="q.num"
                                         :id="`question-${q.num}`"
                                         class="relative flex items-start gap-3 bg-white p-2"
                                         @mouseenter="hoveredQuestion = q.num"
                                         @mouseleave="hoveredQuestion = null">
                                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full">
                                            <span class="text-sm font-bold text-black">{{ q.num }}</span>
                                        </div>
                                        <div class="flex flex-1 items-center gap-3">
                                            <select v-model="(answers as any)[`q${q.num}`]"
                                                    class="w-25 border border-gray-900 bg-white px-4 py-1 text-sm">
                                                <option value="">Select</option>
                                                <option v-for="opt in ['A','B','C','D','E','F','G','H']" :key="opt" :value="opt">{{ opt }}</option>
                                            </select>
                                            <span class="text-sm text-gray-700 select-text"
                                                  :data-segment-id="`q${q.num}`"
                                                  v-html="getHighlightedSegment(q.text)"></span>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === q.num || isQuestionFlagged(q.num)"
                                            @click.stop="toggleFlag(q.num)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(q.num) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Questions 36–40: Summary completion ── -->
                            <div class="border-t-2 border-gray-200 p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-lg font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q36-40-title"
                                              v-html="getHighlightedSegment('Questions 36–40')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q36-40-instructions"
                                              v-html="getHighlightedSegment('Complete the summary below. Choose NO MORE THAN TWO WORDS from the text for each answer. Write your answers in boxes 36–40 on your answer sheet.')"></span>
                                    </p>
                                </div>

                                <div class="border border-gray-900 bg-gray-50 p-6">
                                    <div class="space-y-4 text-sm leading-relaxed text-gray-800">

                                        <!-- Line 1 — Q36 -->
                                        <div id="question-36"
                                             class="relative flex flex-wrap items-center gap-2"
                                             @mouseenter="hoveredQuestion = 36"
                                             @mouseleave="hoveredQuestion = null">
                                            <span class="select-text" data-segment-id="summary-p1-part1"
                                                  v-html="getHighlightedSegment('During the sixties and seventies, attaining huge muscles became more important than')"></span>

                                            <input v-model="answers.q36" type="text"
                                                   class="border border-gray-900 bg-white px-2 py-1 text-center text-sm"
                                                   style="width:120px" placeholder="36" />
                                            <span class="select-text" data-segment-id="summary-p1-part2"
                                                  v-html="getHighlightedSegment('or having an attractive-looking body.')"></span>
                                            <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                    @click.stop="toggleFlag(36)"
                                                    class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Line 2 — Q37 -->
                                        <div id="question-37"
                                             class="relative flex flex-wrap items-center gap-2"
                                             @mouseenter="hoveredQuestion = 37"
                                             @mouseleave="hoveredQuestion = null">
                                            <span class="select-text" data-segment-id="summary-p2-part1"
                                                  v-html="getHighlightedSegment('The first people to take up this new sport of body building had a background in calisthenics but the most famous practitioners became known as')"></span>

                                            <input v-model="answers.q37" type="text"
                                                   class="border border-gray-900 bg-white px-2 py-1 text-center text-sm"
                                                   style="width:120px" placeholder="37" />
                                            <span class="select-text" data-segment-id="summary-p2-part2"
                                                  v-html="getHighlightedSegment('on account of the impressive size of their muscles.')"></span>
                                            <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                    @click.stop="toggleFlag(37)"
                                                    class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Line 3 — Q38 -->
                                        <div id="question-38"
                                             class="relative flex flex-wrap items-center gap-2"
                                             @mouseenter="hoveredQuestion = 38"
                                             @mouseleave="hoveredQuestion = null">
                                            <span class="select-text" data-segment-id="summary-p3-part1"
                                                  v-html="getHighlightedSegment('Drugs and mechanical devices were used to develop individual muscles to a monstrous size. Calisthenics then became the domain of \'weaker\' people: females, children and those recovering from')"></span>

                                            <input v-model="answers.q38" type="text"
                                                   class="border border-gray-900 bg-white px-2 py-1 text-center text-sm"
                                                   style="width:120px" placeholder="38" />
                                            <span class="select-text" data-segment-id="summary-p3-part2"
                                                  v-html="getHighlightedSegment('.')"></span>
                                            <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                    @click.stop="toggleFlag(38)"
                                                    class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Line 4 — Q39 -->
                                        <div id="question-39"
                                             class="relative flex flex-wrap items-center gap-2"
                                             @mouseenter="hoveredQuestion = 39"
                                             @mouseleave="hoveredQuestion = null">
                                            <span class="select-text" data-segment-id="summary-p4-part1"
                                                  v-html="getHighlightedSegment('Much of the advanced knowledge about calisthenics was lost and the method was subsequently downgraded to the status of a simple, user-friendly activity. Once a person became skilled at this, he would progress to')"></span>

                                            <input v-model="answers.q39" type="text"
                                                   class="border border-gray-900 bg-white px-2 py-1 text-center text-sm"
                                                   style="width:120px" placeholder="39" />
                                            <span class="select-text" data-segment-id="summary-p4-part2"
                                                  v-html="getHighlightedSegment('.')"></span>
                                            <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                    @click.stop="toggleFlag(39)"
                                                    class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Line 5 — Q40 -->
                                        <div id="question-40"
                                             class="relative flex flex-wrap items-center gap-2"
                                             @mouseenter="hoveredQuestion = 40"
                                             @mouseleave="hoveredQuestion = null">
                                            <span class="select-text" data-segment-id="summary-p5-part1"
                                                  v-html="getHighlightedSegment('Currently a revival of calisthenics is under way as extreme muscle building can harm the body leaving it sore, out of balance, and in poor')"></span>

                                            <input v-model="answers.q40" type="text"
                                                   class="border border-gray-900 bg-white px-2 py-1 text-center text-sm"
                                                   style="width:120px" placeholder="40" />
                                            <span class="select-text" data-segment-id="summary-p5-part2"
                                                  v-html="getHighlightedSegment('.')"></span>
                                            <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                    @click.stop="toggleFlag(40)"
                                                    class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
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

        <!-- ── Teleported Overlays ── -->
        <Teleport to="body">

            <!-- Context Menu: Note + Highlight speech bubble -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                     :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                     @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                                class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                                title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                                class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                                title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                        </button>
                    </div>
                    <div class="tooltip-arrow"></div>
                </div>
            </div>

            <!-- Delete Highlight Tooltip -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div class="delete-highlight-tooltip fixed z-[9999]"
                     :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                                class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                            <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                            <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                     :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                     @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                 class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
                 :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                 @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                           placeholder="Write your note here..."
                           class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                           @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                            class="border border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
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
