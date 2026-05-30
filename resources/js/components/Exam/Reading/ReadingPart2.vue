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
    q21: '',
    q22: '',
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
const passageText = `<b>A. </b>The evolutionary processes that have made modern humans so different from other animals are hard to determine without an ability to examine human species that have not achieved similar things. However, in a scientific masterpiece, Svante Paabo and his colleagues from the Max Planck Institute for Evolutionary Anthropology, in Leipzig, have made such a comparison possible. In 2009, at a meeting of the American Association for the Advancement of Science, they made public an analysis of the genome of Neanderthal man.
<br/><br/>
<b>B. </b>Homo neanderthalensis, to give its proper name, lived in Europe and parts of Asia from 400,000 years ago to 30,000 years ago. Towards the end of this period, it shared its range with interlopers in the form of Homo sapiens, who were spreading out from Africa. However, the two species did not settle down to a stable cohabitation. For reasons which are as yet unknown, the arrival of Homo sapiens in a region was always quickly followed by the disappearance of Neanderthals.
<br/> <br/>
<b>C. </b>Before 2009, Dr Paabo and his team had conducted only a superficial comparison between the DNA of Neanderthals and modern humans. Since then, they have performed a more thorough study and, in doing so, have shed a fascinating light on the intertwined history of the two species. That history turns out to be more intertwined than many had previously believed.
<br/> <br/>
<b>D. </b>Dr Paabo and his colleagues compared their Neanderthal genome (painstakingly reconstructed from three bone samples collected from a cave in Croatia) with that of five living humans from various parts of Africa and Eurasia. Previous genetic analysis, which had only examined DNA passed from mother to child in cellular structures called mitochondria, had suggested no interbreeding between Neanderthals and modern humans. The new, more extensive examination, which looks at DNA in the cell nucleus rather than in the mitochondria, shows this conclusion is wrong. By comparing the DNA in the cell nucleus of Africans (whose ancestors could not have crossbred with Neanderthals, since they did not overlap with them) and various Eurasians (whose ancestors could have crossbred with Neanderthals), Dr Paabo has shown that Eurasians are between one percent and four percent Neanderthal.
<br/> <br/>
<b>E. </b>That is intriguing. It shows that even after several hundred thousand years of separation, the two species were inter-fertile. It is strange, though, that no Neanderthal mitochondrial DNA has turned up in modern humans, since the usual pattern of invasion in historical times was for the invaders' males to mate with the invaded's females. One piece of self-knowledge, then - at least for non-Africans - is that they have a dash of Neanderthal in them. But Dr Paabo's work also illuminates the differences between the species. By comparing modem humans, Neanderthals, and chimpanzees, it is possible to distinguish genetic changes which are shared by several species of human in their evolution away from the great-ape lineage, from those which are unique to Homo sapiens.
<br/> <br/>
<b>F. </b>More than 90 percent of the 'human accelerated regions' that have been identified in modem people are found in Neanderthals too. However, the rest are not. Dr Paabo has identified 212 parts of the genome that seem to have undergone significant evolution since the species split. The state of genome science is still quite primitive, and it is often unclear what any given bit of DNA is actually doing. But an examination of the 20 largest regions of DNA that have evolved in this way shows that they include several genes which are associated with cognitive ability, and whose malfunction causes serious mental problems. These genes, therefore, look like good places to start the search for modern humanity's essence.
<br/> <br/>
<b>G. </b>The newly evolved regions of DNA also include a gene called RUNX2, which controls bone growth. That may account for differences in the shape of the skull and the rib cage between the two species. By contrast, an earlier phase of the study had already shown that Neanderthals and moderns share the same version of a gene called FOXP2, which is involved in the ability to speak, and which differs in chimpanzees. It is all, then, very promising - and a second coup in quick succession for Dr Paabo. Another of his teams has revealed the existence of a hitherto unsuspected species of human, using mitochondrial DNA found in a little-finger bone. If that species, too, could have its full genome read, humanity's ability to know itself would be enhanced even further.`;

const textSegments = ref([
    // Part 2 header text segments (negative offsets to come before passage)
    { id: 'part-header', text: 'Part 2', offset: -100 },
    { id: 'part-instructions', text: 'Read the text and answer questions 14–26.', offset: -93 },
    { id: 'header-title', text: 'Neanderthals and modern humans', offset: -51 },
    // Single passage text segment
    {
        id: 'passage',
        text: passageText,
        offset: 0,
    },
    // Question text segments with unique IDs
    { id: 'q14-18-title', text: 'Questions 14-18', offset: 4838 },
    {
        id: 'q14-18-instructions',
        text: 'Look at the following characteristics (Questions 14-18) and the list of species below. Match each feature with the correct species, A, B or C. Write the correct letter, A, B or C in boxes 14-18 on your answer sheet. NB. You may use any letter more than once.',
        offset: 4853,
    },
    // Question numbers 14-18
    { id: 'q14-num', text: '14.', offset: 5110 },
    { id: 'q14', text: 'Once lived in Europe and Asia.', offset: 5115 },
    { id: 'q15-num', text: '15.', offset: 5150 },
    { id: 'q15', text: 'Originated in Africa.', offset: 5155 },
    { id: 'q16-num', text: '16.', offset: 5180 },
    { id: 'q16', text: 'Had ability to speak.', offset: 5185 },
    { id: 'q17-num', text: '17.', offset: 5210 },
    { id: 'q17', text: 'Interbred with other species.', offset: 5215 },
    { id: 'q18-num', text: '18.', offset: 5250 },
    { id: 'q18', text: 'Disappeared from certain regions.', offset: 5255 },
    { id: 'species-list-title', text: 'List of species:', offset: 5295 },
    { id: 'species-a', text: 'A Homo neanderthalensis', offset: 5315 },
    { id: 'species-b', text: 'B Homo sapiens', offset: 5340 },
    { id: 'species-c', text: 'C Both species', offset: 5360 },
    { id: 'q19-23-title', text: 'Questions 19-23', offset: 5380 },
    {
        id: 'q19-23-instructions',
        text: 'Reading Passage 2 has seven paragraphs, A-G. Which paragraph contains the following information? Write the correct letter, A-G, in boxes 13-19 on your answer sheet.',
        offset: 5400,
    },
    // Question numbers 19-23
    { id: 'q19-num', text: '19.', offset: 5570 },
    { id: 'q19', text: 'reference to a discovery that may help us understand human evolution', offset: 5575 },
    { id: 'q20-num', text: '20.', offset: 5650 },
    { id: 'q20', text: 'the results of comparing Neanderthal and modern human DNA', offset: 5655 },
    { id: 'q21-num', text: '21.', offset: 5720 },
    { id: 'q21', text: 'the identification of a skill-related gene common to both Neanderthals and modern humans', offset: 5725 },
    { id: 'q22-num', text: '22.', offset: 5820 },
    { id: 'q22', text: 'the announcement of a scientific breakthrough', offset: 5825 },
    { id: 'q23-num', text: '23.', offset: 5875 },
    { id: 'q23', text: 'an interesting gap in existing knowledge', offset: 5880 },
    { id: 'q24-26-title', text: 'Questions 24-26', offset: 5925 },
    {
        id: 'q24-26-instructions',
        text: 'Complete the summary below. Choose NO MORE THAN THREE WORDS from the passage for each answer. Write your answers in boxes 24-26 in below.',
        offset: 5945,
    },
    {
        id: 'q24-26-intro',
        text: 'Recent work in the field of evolutionary anthropology has made it possible to compare modern humans with other related species. Genetic analysis resulted in several new findings.',
        offset: 6100,
    },
    // Question numbers 24-26
    { id: 'q24-num', text: '24.', offset: 6285 },
    {
        id: 'q24-text',
        text: 'First, despite the length of time for which Homo sapiens and Homo neanderthalensis had developed separately,',
        offset: 6290,
    },
    { id: 'q24-end', text: 'did take place.', offset: 6405 },
    { id: 'q25-num', text: '25.', offset: 6425 },
    {
        id: 'q25-text',
        text: 'Secondly, genes which evolved after modern humans split from Neanderthals are connected with cognitive ability and skeletal',
        offset: 6430,
    },
    { id: 'q25-end', text: '.', offset: 6560 },
    { id: 'q26-num', text: '26.', offset: 6565 },
    {
        id: 'q26-text',
        text: 'The potential for this line of research to shed light on the nature of modern humans was further strengthened when analysis of a',
        offset: 6570,
    },
    { id: 'q26-end', text: 'led to the discovery of a new human species.', offset: 6705 },
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
                        // For passage text, use the passageText segment directly (index 2)
                        segment = textSegments.value[3];
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
        <div class="mx-5 mt-4 part-header-box border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegment('Part 2')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegment('Read the text and answer questions 14–26.')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" v-html="getHighlightedSegment('Neanderthals and modern humans')"></span>
                        </h2>
                    </div>

                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span
                                        class="text-lg text-gray-700"
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
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">
                            <!-- Questions 14-18 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q14-18-title"
                                                v-html="getHighlightedSegment('Questions 14-18')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="text-gray-700"
                                            data-segment-id="q14-18-instructions"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Look at the following characteristics (Questions 14-18) and the list of species below. Match each feature with the correct species, A, B or C. Write the correct letter, A, B or C in boxes 14-18 on your answer sheet. NB. You may use any letter more than once.',
                                                )
                                            "
                                        ></span>
                                    </p>
                                </div>

                                <!-- Side by side layout: Questions (left) + Species Options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left side: Questions with drop zones -->
                                    <div class="flex-1 space-y-4">
                                        <div
                                            id="question-14"
                                            class="relative flex items-center gap-3 bg-white p-2"
                                        >
                                            <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegment('14.')"></span>
                                            <span
                                                class="inline-flex min-h-8 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 14 ? 'border-blue-500 bg-blue-50' : answers.q14 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 14)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 14)"
                                                @click="clearAnswer(14)"
                                                :title="answers.q14 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                {{ answers.q14 ? getSpeciesLabel(answers.q14) : '' }}
                                            </span>
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q14"
                                                v-html="getHighlightedSegment('Once lived in Europe and Asia.')"
                                            ></span>
                                            <button
                                                @click.stop="toggleFlag(14)"
                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(14) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div
                                            id="question-15"
                                            class="relative flex items-center gap-3 bg-white p-2"
                                        >
                                            <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegment('15.')"></span>
                                            <span
                                                class="inline-flex min-h-8 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 15 ? 'border-blue-500 bg-blue-50' : answers.q15 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 15)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 15)"
                                                @click="clearAnswer(15)"
                                                :title="answers.q15 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                {{ answers.q15 ? getSpeciesLabel(answers.q15) : '' }}
                                            </span>
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q15"
                                                v-html="getHighlightedSegment('Originated in Africa.')"
                                            ></span>
                                            <button
                                                @click.stop="toggleFlag(15)"
                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(15) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div
                                            id="question-16"
                                            class="relative flex items-center gap-3 bg-white p-2"
                                        >
                                            <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegment('16.')"></span>
                                            <span
                                                class="inline-flex min-h-8 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 16 ? 'border-blue-500 bg-blue-50' : answers.q16 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 16)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 16)"
                                                @click="clearAnswer(16)"
                                                :title="answers.q16 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                {{ answers.q16 ? getSpeciesLabel(answers.q16) : '' }}
                                            </span>
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q16"
                                                v-html="getHighlightedSegment('Had ability to speak.')"
                                            ></span>
                                            <button
                                                @click.stop="toggleFlag(16)"
                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(16) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div
                                            id="question-17"
                                            class="relative flex items-center gap-3 bg-white p-2"
                                        >
                                            <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegment('17.')"></span>
                                            <span
                                                class="inline-flex min-h-8 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 17 ? 'border-blue-500 bg-blue-50' : answers.q17 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 17)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 17)"
                                                @click="clearAnswer(17)"
                                                :title="answers.q17 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                {{ answers.q17 ? getSpeciesLabel(answers.q17) : '' }}
                                            </span>
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q17"
                                                v-html="getHighlightedSegment('Interbred with other species.')"
                                            ></span>
                                            <button
                                                @click.stop="toggleFlag(17)"
                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(17) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div
                                            id="question-18"
                                            class="relative flex items-center gap-3 bg-white p-2"
                                        >
                                            <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegment('18.')"></span>
                                            <span
                                                class="inline-flex min-h-8 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 18 ? 'border-blue-500 bg-blue-50' : answers.q18 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 18)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 18)"
                                                @click="clearAnswer(18)"
                                                :title="answers.q18 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                {{ answers.q18 ? getSpeciesLabel(answers.q18) : '' }}
                                            </span>
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q18"
                                                v-html="getHighlightedSegment('Disappeared from certain regions.')"
                                            ></span>
                                            <button
                                                @click.stop="toggleFlag(18)"
                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(18) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Right side: Draggable species options -->
                                    <div class="w-48 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium text-bold">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <h4 class="mb-2 font-bold text-gray-900 text-sm">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="species-list-title"
                                                    v-html="getHighlightedSegment('List of species:')"
                                                ></span>
                                            </h4>
                                            <div class="space-y-1 text-sm">
                                                <div
                                                    v-for="option in speciesOptions"
                                                    :key="option.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd"
                                                >
                                                    <span class="font-bold text-gray-900 text-xs">{{ option.value }}</span>
                                                    <span class="text-gray-700 text-xs">{{ option.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 19-23 -->
                            <div class="bg-white p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q19-23-title"
                                                v-html="getHighlightedSegment('Questions 19-23')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="text-gray-700"
                                            data-segment-id="q19-23-instructions"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Reading Passage 2 has seven paragraphs, A-G. Which paragraph contains the following information? Write the correct letter, A-G, in boxes 13-19 on your answer sheet.',
                                                )
                                            "
                                        ></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        id="question-19"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 19"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('19.')"></span>
                                            <select
                                                v-model="answers.q19"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q19"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'reference to a discovery that may help us understand human evolution',
                                                        )
                                                    "
                                                ></span>
                                            </p>
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

                                    <div
                                        id="question-20"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 20"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('20.')"></span>
                                            <select
                                                v-model="answers.q20"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q20"
                                                    v-html="
                                                        getHighlightedSegment('the results of comparing Neanderthal and modern human DNA')
                                                    "
                                                ></span>
                                            </p>
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

                                    <div
                                        id="question-21"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 21"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('21.')"></span>
                                            <select
                                                v-model="answers.q21"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q21"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'the identification of a skill-related gene common to both Neanderthals and modern humans',
                                                        )
                                                    "
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                            @click.stop="toggleFlag(21)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        id="question-22"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 22"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('22.')"></span>
                                            <select
                                                v-model="answers.q22"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q22"
                                                    v-html="getHighlightedSegment('the announcement of a scientific breakthrough')"
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        id="question-23"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 23"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('23.')"></span>
                                            <select
                                                v-model="answers.q23"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
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
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q23"
                                                    v-html="getHighlightedSegment('an interesting gap in existing knowledge')"
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                            @click.stop="toggleFlag(23)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 24-26 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q24-26-title"
                                                v-html="getHighlightedSegment('Questions 24-26')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span
                                            class="text-gray-700"
                                            data-segment-id="q24-26-instructions"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Complete the summary below. Choose NO MORE THAN THREE WORDS from the passage for each answer. Write your answers in boxes 24-26 in below.',
                                                )
                                            "
                                        ></span>
                                    </p>
                                </div>

                                <div class="bg-white p-4">
                                    <div class="space-y-4 text-sm leading-relaxed text-gray-700">
                                        <p>
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q24-26-intro"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Recent work in the field of evolutionary anthropology has made it possible to compare modern humans with other related species. Genetic analysis resulted in several new findings.',
                                                    )
                                                "
                                            ></span>
                                        </p>

                                        <div
                                            id="question-24"
                                            class="relative flex flex-wrap items-center gap-2 p-2"
                                            @mouseenter="hoveredQuestion = 24"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q24-text"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'First, despite the length of time for which Homo sapiens and Homo neanderthalensis had developed separately,',
                                                    )
                                                "
                                            ></span>
                                            <input
                                                v-model="answers.q24"
                                                type="text"
                                                class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                style="width: 140px"
                                                placeholder="24"
                                            />
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q24-end"
                                                v-html="getHighlightedSegment('did take place.')"
                                            ></span>
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

                                        <div
                                            id="question-25"
                                            class="relative flex flex-wrap items-center gap-2 p-2"
                                            @mouseenter="hoveredQuestion = 25"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q25-text"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Secondly, genes which evolved after modern humans split from Neanderthals are connected with cognitive ability and skeletal',
                                                    )
                                                "
                                            ></span>
                                            <input
                                                v-model="answers.q25"
                                                type="text"
                                                class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                style="width: 140px"
                                                placeholder="25"
                                            />
                                            <span class="text-gray-700" data-segment-id="q25-end" v-html="getHighlightedSegment('.')"></span>
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

                                        <div
                                            id="question-26"
                                            class="relative flex flex-wrap items-center gap-2 p-2"
                                            @mouseenter="hoveredQuestion = 26"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q26-text"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'The potential for this line of research to shed light on the nature of modern humans was further strengthened when analysis of a',
                                                    )
                                                "
                                            ></span>
                                            <input
                                                v-model="answers.q26"
                                                type="text"
                                                class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                style="width: 140px"
                                                placeholder="26"
                                            />
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q26-end"
                                                v-html="getHighlightedSegment('led to the discovery of a new human species.')"
                                            ></span>
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
