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

// Get label from option value for display (short label for drop zone)
const getOptionLabel = (value: string): string => {
    return value; // Just show the letter in drop zones for matching questions
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

// Passage text
const passageText =
    ref(`Researchers and designers around the globe endeavor to create new technologies that, by honoring the tenets of life, are both highly efficient and often environmentally friendly. And while biomimicry is not a new concept (Leonardo da Vinci looked to nature to design his flying machines, for example, and pharmaceutical companies have long been miming plant organisms in synthetic drugs), there is a greater need for products and manufacturing processes that use a minimum of energy, materials, and toxins. What's more, due to technological advancements and a newfound spirit of innovation among designers, there are now myriad ways to mimic Mother Nature's best assets.

"We have a perfect storm happening right now," says Jay Harman, an inventor and CEO of PAX Scientific, which designs fans, mixers, and pumps to achieve maximum efficiency by imitating the natural flow of fluids. "Shapes in nature are extremely simple once you understand them, but to understand what geometries are at play, and to adapt them, is a very complex process. We only just recently have had the computer power and manufacturing capability to produce these types of shapes."
"If we could capture nature's efficiencies across the board, we could decrease dependency on fuel by at least 50 percent," Harman says. "What we're finding already with the tools and methodology we have right now is that we can reduce energy consumption by between 30 and 40 percent."

It's only recently that mainstream companies have begun to equate biomimicry with the bottom line. DaimlerChrysler, for example, introduced a prototype car modeled on a coral reef fish. Despite its boxy, cube-shaped body, which defies a long-held aerodynamic standard in automotive design (the raindrop shape), the streamlined boxfish proved to be aerodynamically ideal and the unique construction of its skin—numerous hexagonal, bony plates—a perfect recipe for designing a car of maximum strength with minimal weight.

Companies and communities are flocking to Janine Benyus, author of the landmark book Biomimicry: Innovation Inspired by Nature (Perennial, 2002) and cofounder of the Biomimicry Guild, which seats biologists at the table with researchers and designers at companies such as Nike, Interface carpets, Novell, and Procter & Gamble. Their objective is to marry industrial problems with natural solutions.

Benyus, who hopes companies will ultimately transcend mere product design to embrace nature on a more holistic level, breaks biomimicry into three tiers. On a basic (albeit complicated) level, industry will mimic nature's precise and efficient shapes, structures, and geometries. The microstructure of the lotus leaf, for example, causes raindrops to bead and run off immediately, while self-cleaning and drying its surface—a discovery that the British paint company Sto has exploited in a line of building paints. The layered structure of a butterfly wing or a peacock plume, which creates iridescent color by refracting light, is being mimicked by cosmetics giant L'Oreal in a soon-to-be-released line of eye shadow, lipstick, and nail varnish.

The next level of biomimicry involves imitating natural processes and biochemical "recipes." Engineers and scientists are now looking at the nasal glands of seabirds to solve the problem of desalination; the abalone's ability to self-assemble its incredibly durable shell in water, using local ingredients, has inspired an alternative to the conventional, and often toxic, "heat, beat, and treat" manufacturing method. How other organisms deal with harmful bacteria can also be instructive. Researchers for the Australian company Biosignal, for instance, observed a seaweed that lives in an environment teeming with microbes to figure out how it kept free of the same sorts of bacterial colonies, called biofilms, that cause plaque on your teeth and clog up your bathroom drain. They determined that the seaweed uses natural chemicals, called furanones, that jam the cell-to-cell signaling systems that allow bacteria to communicate and gather.

Ultimately, the most sophisticated application of biomimicry, according to Benyus, is when a company starts seeing itself as an organism in an economic ecosystem that must make thrifty use of limited resources and creates symbiotic relationships with other organisms. A boardroom approach at this level begins with imagining any given company, or collection of industries, as a forest, prairie, or coral reef, with its own "food web" (manufacturing inputs and outputs) and asking whether waste products from one manufacturing process can be used, or perhaps sold, as an ingredient for another industrial activity. For instance, Geoffrey Coates, a chemist at Cornell, has developed a biodegradable plastic synthesized from carbon dioxide and limonene (a major component in the oil extracted from citrus rind) and is working with a cement factory to trap their waste CO₂ and use it as an ingredient.

Zero Emissions Research and Initiatives (ZERI), a global network of scientists, entrepreneurs, and educators, has initiated eco-industrial projects that attempt to find ways to reuse all wastes as raw materials for other processes. Storm Brewing in Newfoundland, Canada—in one of a growing number of projects around the world applying ZERI principles—is using spent grains, a by-product of the beer-making process, to make bread and grow mushrooms.
As industries continue to adopt nature's models, entire manufacturing processes could operate locally, with local ingredients like the factories that use liquefied beach sand to make windshields. As more scientists and engineers begin to embrace biomimicry, natural organisms will come to be regarded as mentors, their processes deemed masterful.`);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    // Part 3 header text segments
    { id: 'part-header', text: 'Part 3', offset: -100 },
    { id: 'part-instructions', text: 'You should spend about 20 minutes on Questions 27–40 which are based on Reading Passage 3 below.', offset: -93 },
    { id: 'header-title', text: 'Inspired by Mimicking Mother Nature', offset: -51 },
    { id: 'passage-subtitle', text: 'Using the environment not as an exploitable resource, but as a source of inspiration', offset: -35 },
    // Passage text segment
    {
        id: 'passage',
        text: passageText.value,
        offset: 0,
    },
    // Questions 27-32 (Matching)
    { id: 'q27-32-title', text: 'Questions 27–32', offset: passageText.value.length + 1 },
    { id: 'q27-32-inst1', text: 'Look at the following descriptions mentioned in Reading Passage 3.', offset: passageText.value.length + 17 },
    { id: 'q27-32-inst2', text: 'Match the three kinds of levels (A–C) listed below the descriptions.', offset: passageText.value.length + 84 },
    { id: 'q27-32-inst3', text: 'Write the appropriate letters A–C in boxes 27–32 on your answer sheet.', offset: passageText.value.length + 153 },
    // Level labels
    { id: 'level-A', text: 'A  First level: mimic nature\'s precise and efficient shapes, structures, and geometries', offset: passageText.value.length + 224 },
    { id: 'level-B', text: 'B  Second level: imitating natural processes and biochemical "recipes"', offset: passageText.value.length + 312 },
    { id: 'level-C', text: 'C  Third level: creates symbiotic relationships with other like organisms', offset: passageText.value.length + 383 },
    // Question 27
    { id: 'q27-num', text: '27.', offset: passageText.value.length + 455 },
    { id: 'q27', text: 'Synthesized plastic, developed together with a cement factory, can recycle waste gas.', offset: passageText.value.length + 459 },
    // Question 28
    { id: 'q28-num', text: '28.', offset: passageText.value.length + 545 },
    { id: 'q28', text: 'Cosmetics companies produce a series of shiny cosmetic colours.', offset: passageText.value.length + 549 },
    // Question 29
    { id: 'q29-num', text: '29.', offset: passageText.value.length + 613 },
    { id: 'q29', text: 'People are inspired by nature to remove excess salt.', offset: passageText.value.length + 617 },
    // Question 30
    { id: 'q30-num', text: '30.', offset: passageText.value.length + 670 },
    { id: 'q30', text: 'Daimler Chrysler introduced a fish-shaped car.', offset: passageText.value.length + 674 },
    // Question 31
    { id: 'q31-num', text: '31.', offset: passageText.value.length + 721 },
    { id: 'q31', text: 'Marine plant company integrated itself as a part of an economic ecosystem.', offset: passageText.value.length + 725 },
    // Question 32
    { id: 'q32-num', text: '32.', offset: passageText.value.length + 799 },
    { id: 'q32', text: 'Natural chemicals developed based on seaweed are known to kill bacteria.', offset: passageText.value.length + 803 },
    // Questions 33-40 (YES/NO/NOT GIVEN)
    { id: 'q33-40-title', text: 'Questions 33–40', offset: passageText.value.length + 876 },
    { id: 'q33-40-inst1', text: 'Do the following statements agree with the information given in Reading Passage 3?', offset: passageText.value.length + 892 },
    { id: 'q33-40-inst2', text: 'In boxes 33–40 on your answer sheet, write:', offset: passageText.value.length + 974 },
    { id: 'q33-40-inst-yes', text: 'YES', offset: passageText.value.length + 1018 },
    { id: 'q33-40-inst-yes-desc', text: '– if the statement agrees with the information', offset: passageText.value.length + 1022 },
    { id: 'q33-40-inst-no', text: 'NO', offset: passageText.value.length + 1069 },
    { id: 'q33-40-inst-no-desc', text: '– if the statement contradicts the information', offset: passageText.value.length + 1072 },
    { id: 'q33-40-inst-ng', text: 'NOT GIVEN', offset: passageText.value.length + 1119 },
    { id: 'q33-40-inst-ng-desc', text: '– if there is no information on this in the passage', offset: passageText.value.length + 1129 },
    // Question 33
    { id: 'q33-num', text: '33.', offset: passageText.value.length + 1182 },
    { id: 'q33', text: 'Biomimicry is a totally new concept that has been unveiled recently.', offset: passageText.value.length + 1186 },
    // Question 34
    { id: 'q34-num', text: '34.', offset: passageText.value.length + 1255 },
    { id: 'q34', text: 'Leonardo da Vinci has been the first designer to mimic nature.', offset: passageText.value.length + 1259 },
    // Question 35
    { id: 'q35-num', text: '35.', offset: passageText.value.length + 1321 },
    { id: 'q35', text: 'Scientists believe biomimicry involves more than mimicking shapes to capture designs in nature.', offset: passageText.value.length + 1325 },
    // Question 36
    { id: 'q36-num', text: '36.', offset: passageText.value.length + 1420 },
    { id: 'q36', text: 'We can save energy usage by up to 40% if we take advantage of current findings.', offset: passageText.value.length + 1424 },
    // Question 37
    { id: 'q37-num', text: '37.', offset: passageText.value.length + 1504 },
    { id: 'q37', text: 'Daimler Chrysler\'s prototype car modeled on a coral reef fish is a best-seller.', offset: passageText.value.length + 1508 },
    // Question 38
    { id: 'q38-num', text: '38.', offset: passageText.value.length + 1588 },
    { id: 'q38', text: 'Some major companies and communities are seeking solutions beyond their own industrial scope.', offset: passageText.value.length + 1592 },
    // Question 39
    { id: 'q39-num', text: '39.', offset: passageText.value.length + 1685 },
    { id: 'q39', text: 'The British paint company Sto did not apply the microstructure of the lotus leaf.', offset: passageText.value.length + 1689 },
    // Question 40
    { id: 'q40-num', text: '40.', offset: passageText.value.length + 1771 },
    { id: 'q40', text: 'A Canadian beer company increased production by applying ZERI principles.', offset: passageText.value.length + 1775 },
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
                        segment = textSegments.value[4];
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
        const input = document.querySelector<HTMLTextAreaElement>('.note-input-field');
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

watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

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
    <div class="pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2" @mouseup="handlePassageTextSelect" @click="handleContentClick">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="'Part 3'"></p>
            <p class="text-sm text-gray-700 select-text" v-html="'You should spend about 20 minutes on Questions 27–40 which are based on Reading Passage 3 below.'"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="header-title" v-html="getHighlightedSegment('Inspired by Mimicking Mother Nature')"></span>
                        </h2>
                        <p class="text-sm italic text-gray-600 mt-1">
                            <span class="select-text" data-segment-id="passage-subtitle" v-html="getHighlightedSegment('Using the environment not as an exploitable resource, but as a source of inspiration')"></span>
                        </p>
                    </div>

                    <div class="space-y-2" :style="contentZoom">
                        <div ref="passageTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span
                                        class="text-lg select-text"
                                        data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[4].text)"
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
                    <!-- Scrollable Questions Content -->
                    <div ref="questionsTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick" class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 27-32: Matching Levels -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="q27-32-title" v-html="getHighlightedSegment('Questions 27–32')"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span class="select-text" data-segment-id="q27-32-inst1" v-html="getHighlightedSegment('Look at the following descriptions mentioned in Reading Passage 3.')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span class="select-text" data-segment-id="q27-32-inst2" v-html="getHighlightedSegment('Match the three kinds of levels (A–C) listed below the descriptions.')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-4">
                                        <span class="select-text" data-segment-id="q27-32-inst3" v-html="getHighlightedSegment('Write the appropriate letters A–C in boxes 27–32 on your answer sheet.')"></span>
                                    </p>

                                    <!-- Levels Key -->
                                    <div class="border border-gray-300 bg-gray-50 p-3 mb-4 text-sm space-y-1">
                                        <p class="font-semibold text-gray-800 mb-2">Levels</p>
                                        <p class="text-gray-700 select-text" data-segment-id="level-A" v-html="getHighlightedSegment('A  First level: mimic nature\'s precise and efficient shapes, structures, and geometries')"></p>
                                        <p class="text-gray-700 select-text" data-segment-id="level-B" v-html="getHighlightedSegment('B  Second level: imitating natural processes and biochemical &quot;recipes&quot;')"></p>
                                        <p class="text-gray-700 select-text" data-segment-id="level-C" v-html="getHighlightedSegment('C  Third level: creates symbiotic relationships with other like organisms')"></p>
                                    </div>
                                </div>

                                <!-- Questions with dropdown selects -->
                                <div class="space-y-4">
                                    <!-- Question 27 -->
                                    <div
                                        id="question-27"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 27"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="q27-num" v-html="getHighlightedSegment('27.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q27" v-html="getHighlightedSegment('Synthesized plastic, developed together with a cement factory, can recycle waste gas.')"></span>
                                            <select
                                                v-model="answers.q27"
                                                class="border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:border-gray-500 focus:ring-1 focus:ring-gray-500 outline-none w-24 shrink-0"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                            @click.stop="toggleFlag(27)"
                                            class="absolute top-2 -right-6 flex h-7 w-7 items-center justify-center rounded border transition-all"
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
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="q28-num" v-html="getHighlightedSegment('28.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q28" v-html="getHighlightedSegment('Cosmetics companies produce a series of shiny cosmetic colours.')"></span>
                                            <select
                                                v-model="answers.q28"
                                                class="border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:border-gray-500 focus:ring-1 focus:ring-gray-500 outline-none w-24 shrink-0"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                            @click.stop="toggleFlag(28)"
                                            class="absolute top-2 -right-6 flex h-7 w-7 items-center justify-center rounded border transition-all"
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
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="q29-num" v-html="getHighlightedSegment('29.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q29" v-html="getHighlightedSegment('People are inspired by nature to remove excess salt.')"></span>
                                            <select
                                                v-model="answers.q29"
                                                class="border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:border-gray-500 focus:ring-1 focus:ring-gray-500 outline-none w-24 shrink-0"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                            @click.stop="toggleFlag(29)"
                                            class="absolute top-2 -right-6 flex h-7 w-7 items-center justify-center rounded border transition-all"
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
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="q30-num" v-html="getHighlightedSegment('30.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q30" v-html="getHighlightedSegment('Daimler Chrysler introduced a fish-shaped car.')"></span>
                                            <select
                                                v-model="answers.q30"
                                                class="border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:border-gray-500 focus:ring-1 focus:ring-gray-500 outline-none w-24 shrink-0"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                            @click.stop="toggleFlag(30)"
                                            class="absolute top-2 -right-6 flex h-7 w-7 items-center justify-center rounded border transition-all"
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
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="q31-num" v-html="getHighlightedSegment('31.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q31" v-html="getHighlightedSegment('Marine plant company integrated itself as a part of an economic ecosystem.')"></span>
                                            <select
                                                v-model="answers.q31"
                                                class="border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:border-gray-500 focus:ring-1 focus:ring-gray-500 outline-none w-24 shrink-0"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                            @click.stop="toggleFlag(31)"
                                            class="absolute top-2 -right-6 flex h-7 w-7 items-center justify-center rounded border transition-all"
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
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <span class="text-base font-bold text-gray-900 shrink-0" data-segment-id="q32-num" v-html="getHighlightedSegment('32.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q32" v-html="getHighlightedSegment('Natural chemicals developed based on seaweed are known to kill bacteria.')"></span>
                                            <select
                                                v-model="answers.q32"
                                                class="border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:border-gray-500 focus:ring-1 focus:ring-gray-500 outline-none w-24 shrink-0"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                            @click.stop="toggleFlag(32)"
                                            class="absolute top-2 -right-6 flex h-7 w-7 items-center justify-center rounded border transition-all"
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

                            <!-- Questions 33-40: YES / NO / NOT GIVEN -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="q33-40-title" v-html="getHighlightedSegment('Questions 33–40')"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span class="select-text" data-segment-id="q33-40-inst1" v-html="getHighlightedSegment('Do the following statements agree with the information given in Reading Passage 3?')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span class="select-text" data-segment-id="q33-40-inst2" v-html="getHighlightedSegment('In boxes 33–40 on your answer sheet, write:')"></span>
                                    </p>
                                    <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm leading-relaxed text-gray-700">
                                        <p>
                                            <span class="select-text font-bold text-gray-900" data-segment-id="q33-40-inst-yes" v-html="getHighlightedSegment('YES')"></span>
                                            <span class="select-text ml-1" data-segment-id="q33-40-inst-yes-desc" v-html="getHighlightedSegment('– if the statement agrees with the information')"></span>
                                        </p>
                                        <p>
                                            <span class="select-text font-bold text-gray-900" data-segment-id="q33-40-inst-no" v-html="getHighlightedSegment('NO')"></span>
                                            <span class="select-text ml-1" data-segment-id="q33-40-inst-no-desc" v-html="getHighlightedSegment('– if the statement contradicts the information')"></span>
                                        </p>
                                        <p>
                                            <span class="select-text font-bold text-gray-900" data-segment-id="q33-40-inst-ng" v-html="getHighlightedSegment('NOT GIVEN')"></span>
                                            <span class="select-text ml-1" data-segment-id="q33-40-inst-ng-desc" v-html="getHighlightedSegment('– if there is no information on this in the passage')"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 33 -->
                                    <div
                                        id="question-33"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 33"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q33-num" v-html="getHighlightedSegment('33.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q33" v-html="getHighlightedSegment('Biomimicry is a totally new concept that has been unveiled recently.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q33" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q33" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q33" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 34"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q34-num" v-html="getHighlightedSegment('34.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q34" v-html="getHighlightedSegment('Leonardo da Vinci has been the first designer to mimic nature.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q34" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q34" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q34" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 35"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q35-num" v-html="getHighlightedSegment('35.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q35" v-html="getHighlightedSegment('Scientists believe biomimicry involves more than mimicking shapes to capture designs in nature.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q35" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q35" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q35" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 36"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q36-num" v-html="getHighlightedSegment('36.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q36" v-html="getHighlightedSegment('We can save energy usage by up to 40% if we take advantage of current findings.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q36" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q36" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q36" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
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
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 37"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q37-num" v-html="getHighlightedSegment('37.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q37" v-html="getHighlightedSegment('Daimler Chrysler\'s prototype car modeled on a coral reef fish is a best-seller.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q37" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q37" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q37" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
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

                                    <!-- Question 38 -->
                                    <div
                                        id="question-38"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q38-num" v-html="getHighlightedSegment('38.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q38" v-html="getHighlightedSegment('Some major companies and communities are seeking solutions beyond their own industrial scope.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q38" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
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
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q39-num" v-html="getHighlightedSegment('39.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q39" v-html="getHighlightedSegment('The British paint company Sto did not apply the microstructure of the lotus leaf.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q39" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
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
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <span class="text-base font-bold text-gray-900" data-segment-id="q40-num" v-html="getHighlightedSegment('40.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1" data-segment-id="q40" v-html="getHighlightedSegment('A Canadian beer company increased production by applying ZERI principles.')"></span>
                                        </div>
                                        <div class="ml-6 mt-2 flex gap-4 select-none flex-wrap">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="YES" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="NO" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q40" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-sm text-gray-900">NOT GIVEN</span>
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