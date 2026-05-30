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

const passageText = `<b>WATER TREATMENT: REED BED</b><br/><br/>
In recent years, it has been shown that plants, more accurately roots, play a crucial part in purifying dirty water before it enters seas and rivers. In 15th-century Britain, dirty water was purified by passing through the wetlands. People began to realize that the "natural" way of water purification was effective. Nowadays subsurface flow wetlands (SSFW) are a common alternative in Europe for the treatment of wastewater in rural areas. Mainly in the last 10 to 12 years there has been a significant growth in the number and size of the systems in use. The conventional mechanism of water purification used in big cities where there are large volumes of water to be purified is inappropriate in rural areas.
<br/><br/>
The common reed has the ability to transfer oxygen from its leaves, down through its stem and rhizomes, and out via its root system. As a result of this action, a very high population of microorganisms occurs in the root system, in zones of aerobic, anoxic, and anaerobic conditions. As the waste water moves very slowly through the mass of reed roots, this liquid can be successfully treated. The reason why they are so effective is often because within the bed's root sector, natural biological, physical and chemical processes interact with one another to degrade or remove a good range of pollutants.
<br/><br/>
Dirty water from households, farms and factories consume a lot of oxygen in the water, which will lead to the death of aquatic creatures. Several aquatic plants are important in purifying water. They not only absorb carbon dioxide and release oxygen into the water, improving the environment for fish, but absorb nutrients from the water as well. Britain and the U.S. differ in their preference of plants to purify water. Bulrushes (Scirpus spp.) and rushes (Juncus spp.) are excellent water purifiers. They remove excess nutrients from the water as well as oil and bacteria such as Escherichia coli and Salmonella. However, algae grow freely in summer and die off in winter. Their remains foul the bottom of the pool.
<br/><br/>
Artificial reed beds purify water in both horizontal and downflow ways. The reeds succeed best when a dense layer of root hairs has formed. It takes three years for the roots to fully develop. Which type of wetland a certain country applies varies widely depending on the country in Europe and its main lines of development. Besides the development of horizontal or vertical flow wetlands for wastewater treatment, the use of wetlands for sludge treatment has been very successful in Europe. Some special design lines offer the retention of microbiological organisms in constructed wetlands, the treatment of agricultural wastewater, treatment of some kinds of industrial wastewater, and the control of diffuse pollution.
<br/><br/>
If the water is slightly polluted, a horizontal system is used. Horizontal-flow wetlands may be of two types: free-water surface-flow (FWF) or sub-surface water-flow (SSF). In the former the effluent flows freely above the sand/gravel bed in which the reeds etc. are planted; in the latter effluent passes through the sand/gravel bed. In FWF-type wetlands, effluent is treated by plant stems, leaves and rhizomes. Such FWF wetlands are densely planted and typically have water-depths of less than 0.4m. However, dense planting can limit the diffusion of oxygen into the water. These systems work particularly well for low strength effluents or effluents that have undergone some forms of pretreatment and play an invaluable role in tertiary treatment and the polishing of effluents. The horizontal reed flow system uses a long reed bed, where the liquid slowly flows horizontally through. The length of the reed bed is about 100 meters. The downside of horizontal reed beds is that they use up lots of land space and they do take quite a long time to produce clean water.
<br/><br/>
A vertical flow (downflow) reed bed is a sealed, gravel filled trench with reeds growing in it. The reeds in a downflow system are planted in a bed 60cm deep. In vertical flow reed beds, the wastewater is applied to the top of the reed bed, flows down through a rhizome zone with sludge as a substrate, then through a root zone with sand as a substrate, followed by a layer of gravel for drainage, and is collected in an under drainage system of large stones. The effluent flows onto the surface of the bed and percolates slowly through the different layers into an outlet pipe, which leads to a horizontal flow bed where it is cleaned by millions of bacteria, algae, fungi, and microorganisms that digest the waste, including sewage. There is no standing water so there should be no unpleasant smells.
<br/><br/>
Vertical flow reed bed systems are much more effective than horizontal flow reedbeds not only in reducing biochemical oxygen demanded (BOD) and suspended solids (SS) levels but also in reducing ammonia levels and eliminating smells. Usually considerably smaller than horizontal flow beds, they are capable of handling much stronger effluents which contain heavily polluted matters and have a longer lifetime value. A vertical reed bed system works more efficiently than a horizontal reed bed system, but it requires more management, and its reed beds are often operated for a few days then rested, so several beds and a distribution system are needed.
<br/><br/>
The natural way of water purification has many advantages over the conventional mechanism. The natural way requires less expenditure for installation, operation and maintenance. Besides, it looks attractive and can improve the surrounding landscape. Reed beds are natural habitats found in floodplains, waterlogged depressions and estuaries. The natural bed systems are a biologically proved, an environmentally friendly and visually unobtrusive way of treating wastewater, and have the extra virtue of frequently being better than mechanical wastewater treatment systems. Over the medium to long term reed bed systems are, in most cases, more cost-effective to install than any other wastewater treatment. They are naturally environmentally sound protecting groundwater, dams, creeks, rivers and estuaries.`;

const textSegments = ref([
    // Part 2 header text segments
    { id: 'part2-title', text: 'Part 2', offset: -100 },
    { id: 'part2-desc', text: 'Read the text and answer questions 14–26.', offset: -93 },
    { id: 'part2-passage-title', text: 'WATER TREATMENT: REED BED', offset: -51 },
    // Single passage text segment
    { id: 'passage', text: passageText, offset: 0 },
    // Questions 14-16
    { id: 'q14-16-title', text: 'Questions 14-16', offset: 6000 },
    { id: 'q14-16-inst1', text: 'Do the following statements agree with the information given in Reading Passage 2?', offset: 6016 },
    { id: 'q14-16-inst2', text: 'In boxes 14-16 on your answer sheet, write', offset: 6099 },
    { id: 'q14-16-true', text: 'TRUE', offset: 6143 },
    { id: 'q14-16-true-desc', text: 'if the statement agrees with the information', offset: 6148 },
    { id: 'q14-16-false', text: 'FALSE', offset: 6193 },
    { id: 'q14-16-false-desc', text: 'if the statement contradicts the information', offset: 6199 },
    { id: 'q14-16-ng', text: 'NOT GIVEN', offset: 6244 },
    { id: 'q14-16-ng-desc', text: 'if there is no information on this', offset: 6254 },
    { id: 'q14-num', text: '14', offset: 6290 },
    { id: 'q14-text', text: 'The reed bed system is a conventional method for water treatment in urban areas.', offset: 6293 },
    { id: 'q15-num', text: '15', offset: 6375 },
    { id: 'q15-text', text: 'In the reed roots, there is a series of processes that help break down the pollutants.', offset: 6378 },
    { id: 'q16-num', text: '16', offset: 6465 },
    { id: 'q16-text', text: 'Escherichia coli is the most difficult bacteria to eliminate.', offset: 6468 },
    // Questions 17-19
    { id: 'q17-19-title', text: 'Questions 17-19', offset: 6530 },
    { id: 'q17-19-inst1', text: 'Complete the diagram below.', offset: 6546 },
    { id: 'q17-19-inst2', text: 'Choose NO MORE THAN THREE WORDS from the passage for each answer.', offset: 6574 },
    // diagram labels
    { id: 'q17-label-pre', text: 'Rhizome zone with', offset: 6640 },
    { id: 'q17-label-post', text: 'as a substrate', offset: 6658 },
    { id: 'q18-label-pre', text: 'Root zone with', offset: 6673 },
    { id: 'q18-label-post', text: 'as a substrate', offset: 6688 },
    { id: 'q19-label-pre', text: 'Layer of gravel for', offset: 6703 },
    { id: 'q19-label-post', text: '', offset: 6723 },
    // Questions 20-24
    { id: 'q20-24-title', text: 'Questions 20-24', offset: 6800 },
    { id: 'q20-24-inst', text: 'Use the information in the passage to match the advantages and disadvantages of the two systems: horizontal flow system and down-flow system (listed A–H) below.', offset: 6816 },
    { id: 'q20-24-inst2', text: 'Write the appropriate letters A-H in boxes 20-24 on your answer sheet.', offset: 6978 },
    { id: 'q20-24-stem', text: 'The advantage of the downflow system is', offset: 7050 },
    { id: 'q20-24-stem2', text: '; however,', offset: 7090 },
    { id: 'q20-24-stem3', text: 'and', offset: 7101 },
    { id: 'q20-24-stem4', text: 'The two advantages of the horizontal system are', offset: 7105 },
    { id: 'q20-24-stem5', text: 'and', offset: 7153 },
    { id: 'q20-24-stem6', text: 'In comparison with the downflow system, the horizontal system is less effective.', offset: 7157 },
    { id: 'optA', text: 'A  it requires several beds', offset: 7240 },
    { id: 'optB', text: 'B  it is easier to construct', offset: 7267 },
    { id: 'optC', text: 'C  it builds on a gradient', offset: 7295 },
    { id: 'optD', text: 'D  it doesn\'t need much attention', offset: 7321 },
    { id: 'optE', text: 'E  it produces less sludges', offset: 7354 },
    { id: 'optF', text: 'F  it isn\'t always working', offset: 7381 },
    { id: 'optG', text: 'G  it needs deeper bed', offset: 7407 },
    { id: 'optH', text: 'H  it can deal with more heavily polluted water', offset: 7430 },
    // Questions 25-26
    { id: 'q25-26-title', text: 'Questions 25-26', offset: 7500 },
    { id: 'q25-26-inst', text: 'Choose two correct letters, from the following A, B, C, D or E.', offset: 7516 },
    { id: 'q25-26-inst2', text: 'Write your answers in boxes 25–26 on your answer sheet.', offset: 7580 },
    { id: 'q25-26-stem', text: 'What are the TWO advantages of the natural water purification system mentioned in the passage:', offset: 7637 },
    { id: 'q25-optA', text: 'A  It uses micro-organisms', offset: 7735 },
    { id: 'q25-optB', text: 'B  It involves a low operating cost', offset: 7761 },
    { id: 'q25-optC', text: 'C  It prevents flooding.', offset: 7796 },
    { id: 'q25-optD', text: 'D  It is visually good-looking', offset: 7820 },
    { id: 'q25-optE', text: 'E  It can function in all climates', offset: 7851 },
]);

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 2 Header -->
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('part2-title')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegmentById('part2-desc')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-1">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" v-html="getHighlightedSegmentById('part2-passage-title')"></span>
                        </h2>
                    </div>

                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
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
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
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

                            <!-- Questions 14-16: TRUE / FALSE / NOT GIVEN -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" v-html="getHighlightedSegmentById('q14-16-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q14-16-inst1')"></span>
                                    </p>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q14-16-inst2')"></span>
                                    </p>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="font-bold text-gray-900" v-html="getHighlightedSegmentById('q14-16-true')"></span>
                                        <span class="text-gray-700 ml-1" v-html="getHighlightedSegmentById('q14-16-true-desc')"></span>
                                    </p>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="font-bold text-gray-900" v-html="getHighlightedSegmentById('q14-16-false')"></span>
                                        <span class="text-gray-700 ml-1" v-html="getHighlightedSegmentById('q14-16-false-desc')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="font-bold text-gray-900" v-html="getHighlightedSegmentById('q14-16-ng')"></span>
                                        <span class="text-gray-700 ml-1" v-html="getHighlightedSegmentById('q14-16-ng-desc')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 14 -->
                                    <div
                                        id="question-14"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" v-html="getHighlightedSegmentById('q14-num')"></span>
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q14-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q14" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q14" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q14" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
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
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" v-html="getHighlightedSegmentById('q15-num')"></span>
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q15-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q15" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q15" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q15" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
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
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900" v-html="getHighlightedSegmentById('q16-num')"></span>
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q16-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q16" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q16" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q16" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
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
                                </div>
                            </div>

                            <!-- Questions 17-19: Diagram Completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" v-html="getHighlightedSegmentById('q17-19-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q17-19-inst1')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q17-19-inst2')"></span>
                                    </p>
                                </div>

                                <!-- Vertical Flow Reed Bed Diagram -->
                                <div class="mb-6 border border-gray-300 bg-gray-50 p-4">
                                    <p class="mb-3 text-center text-sm font-semibold text-gray-800">Vertical Flow (Downflow) Reed Bed</p>

                                    <!-- Diagram layers with labels -->
                                    <div class="mx-auto max-w-md space-y-1">
                                        <!-- Wastewater applied to top -->
                                        <div class="text-center text-xs text-gray-600 mb-2">↓ Wastewater applied to top</div>

                                        <!-- Layer 1: Rhizome zone -->
                                        <div
                                            id="question-17"
                                            class="relative flex items-center gap-2 rounded border border-gray-400 bg-amber-100 px-3 py-2"
                                            @mouseenter="hoveredQuestion = 17"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <div class="flex flex-1 flex-wrap items-center gap-1 text-sm text-gray-700">
                                                <span v-html="getHighlightedSegmentById('q17-label-pre')"></span>
                                                <input
                                                    v-model="answers.q17"
                                                    type="text"
                                                    class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                    style="width: 130px"
                                                    placeholder="17"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    autocorrect="off"
                                                    autocapitalize="off"
                                                />
                                                <span v-html="getHighlightedSegmentById('q17-label-post')"></span>
                                            </div>
                                            <button
                                                v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                                @click.stop="toggleFlag(17)"
                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Layer 2: Root zone -->
                                        <div
                                            id="question-18"
                                            class="relative flex items-center gap-2 rounded border border-gray-400 bg-yellow-50 px-3 py-2"
                                            @mouseenter="hoveredQuestion = 18"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <div class="flex flex-1 flex-wrap items-center gap-1 text-sm text-gray-700">
                                                <span v-html="getHighlightedSegmentById('q18-label-pre')"></span>
                                                <input
                                                    v-model="answers.q18"
                                                    type="text"
                                                    class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                    style="width: 130px"
                                                    placeholder="18"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    autocorrect="off"
                                                    autocapitalize="off"
                                                />
                                                <span v-html="getHighlightedSegmentById('q18-label-post')"></span>
                                            </div>
                                            <button
                                                v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                                @click.stop="toggleFlag(18)"
                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Layer 3: Gravel for drainage -->
                                        <div
                                            id="question-19"
                                            class="relative flex items-center gap-2 rounded border border-gray-400 bg-gray-200 px-3 py-2"
                                            @mouseenter="hoveredQuestion = 19"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <div class="flex flex-1 flex-wrap items-center gap-1 text-sm text-gray-700">
                                                <span v-html="getHighlightedSegmentById('q19-label-pre')"></span>
                                                <input
                                                    v-model="answers.q19"
                                                    type="text"
                                                    class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                    style="width: 130px"
                                                    placeholder="19"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    autocorrect="off"
                                                    autocapitalize="off"
                                                />
                                            </div>
                                            <button
                                                v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                                @click.stop="toggleFlag(19)"
                                                class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Under drainage -->
                                        <div class="rounded border border-gray-400 bg-stone-300 px-3 py-2 text-center text-sm text-gray-700">
                                            Under drainage system (large stones)
                                        </div>

                                        <div class="text-center text-xs text-gray-600 mt-1">↓ Outlet pipe → Horizontal flow bed</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 20-24: Match advantages/disadvantages -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-inst')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-inst2')"></span>
                                    </p>
                                </div>

                                <!-- Options A-H reference -->
                                <div class="mb-6 rounded border border-gray-200 bg-gray-50 p-3">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Options</p>
                                    <div class="space-y-1 text-sm text-gray-700">
                                        <p v-html="getHighlightedSegmentById('optA')"></p>
                                        <p v-html="getHighlightedSegmentById('optB')"></p>
                                        <p v-html="getHighlightedSegmentById('optC')"></p>
                                        <p v-html="getHighlightedSegmentById('optD')"></p>
                                        <p v-html="getHighlightedSegmentById('optE')"></p>
                                        <p v-html="getHighlightedSegmentById('optF')"></p>
                                        <p v-html="getHighlightedSegmentById('optG')"></p>
                                        <p v-html="getHighlightedSegmentById('optH')"></p>
                                    </div>
                                </div>

                                <!-- Sentence fill with inline inputs -->
                                <div class="space-y-4 text-sm leading-relaxed text-gray-800">
                                    <!-- Line 1: downflow advantage (Q20), however Q21 and Q22 -->
                                    <div class="flex flex-wrap items-center gap-x-1 gap-y-2">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-stem')"></span>

                                        <!-- Q20 -->
                                        <div
                                            id="question-20"
                                            class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 20"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q20"
                                                type="text"
                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 60px"
                                                placeholder="20"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                                @click.stop="toggleFlag(20)"
                                                class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                                                :class="isQuestionFlagged(20) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-stem2')"></span>

                                        <!-- Q21 -->
                                        <div
                                            id="question-21"
                                            class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 21"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q21"
                                                type="text"
                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 60px"
                                                placeholder="21"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                                @click.stop="toggleFlag(21)"
                                                class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                                                :class="isQuestionFlagged(21) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-stem3')"></span>

                                        <!-- Q22 -->
                                        <div
                                            id="question-22"
                                            class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 22"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q22"
                                                type="text"
                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 60px"
                                                placeholder="22"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                                @click.stop="toggleFlag(22)"
                                                class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                                                :class="isQuestionFlagged(22) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Line 2: horizontal advantages Q23 and Q24 -->
                                    <div class="flex flex-wrap items-center gap-x-1 gap-y-2">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-stem4')"></span>

                                        <!-- Q23 -->
                                        <div
                                            id="question-23"
                                            class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 23"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q23"
                                                type="text"
                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 60px"
                                                placeholder="23"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                                @click.stop="toggleFlag(23)"
                                                class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                                                :class="isQuestionFlagged(23) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q20-24-stem5')"></span>

                                        <!-- Q24 -->
                                        <div
                                            id="question-24"
                                            class="relative inline-flex items-center"
                                            @mouseenter="hoveredQuestion = 24"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q24"
                                                type="text"
                                                class="question-input mx-1 border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 60px"
                                                placeholder="24"
                                                spellcheck="false"
                                                autocomplete="off"
                                                autocorrect="off"
                                                autocapitalize="off"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                                @click.stop="toggleFlag(24)"
                                                class="absolute -top-3 -right-3 flex h-5 w-5 items-center justify-center rounded border transition-all z-10"
                                                :class="isQuestionFlagged(24) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <p class="text-sm text-gray-700" v-html="getHighlightedSegmentById('q20-24-stem6')"></p>
                                </div>
                            </div>

                            <!-- Questions 25-26: Choose TWO correct letters -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" v-html="getHighlightedSegmentById('q25-26-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q25-26-inst')"></span>
                                    </p>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q25-26-inst2')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-800 font-medium">
                                        <span class="text-gray-700" v-html="getHighlightedSegmentById('q25-26-stem')"></span>
                                    </p>
                                </div>

                                <!-- Q25 -->
                                <div
                                    id="question-25"
                                    class="relative mb-4 bg-white p-2"
                                    @mouseenter="hoveredQuestion = 25"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <p class="mb-2 text-sm font-semibold text-gray-700">Question 25</p>
                                    <div class="space-y-2 select-none ml-2">
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q25" value="A" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optA')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q25" value="B" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optB')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q25" value="C" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optC')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q25" value="D" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optD')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q25" value="E" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optE')"></span>
                                        </label>
                                    </div>
                                    <button
                                        v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="toggleFlag(25)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q26 -->
                                <div
                                    id="question-26"
                                    class="relative bg-white p-2"
                                    @mouseenter="hoveredQuestion = 26"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <p class="mb-2 text-sm font-semibold text-gray-700">Question 26</p>
                                    <div class="space-y-2 select-none ml-2">
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q26" value="A" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optA')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q26" value="B" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optB')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q26" value="C" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optC')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q26" value="D" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optD')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2">
                                            <input type="radio" v-model="answers.q26" value="E" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base text-gray-900" v-html="getHighlightedSegmentById('q25-optE')"></span>
                                        </label>
                                    </div>
                                    <button
                                        v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="toggleFlag(26)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
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
                    class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                >
                    Cancel
                </button>
                <button @click="saveNote" class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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