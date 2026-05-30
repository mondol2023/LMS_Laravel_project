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

// Passage text
const passageText = ref(`A
Each year, massive swirling storms bringing along winds greater than 74 miles per hour wipe across tropical oceans and land on shorelines—usually devastating vast swaths of territory. When these roiling tempests strike densely inhabited territories, they have the power to kill thousands and cause property damage worth of billions of dollars. Besides, absolutely nothing stands in their way. But can we ever find a way to control these formidable forces of nature?

B
To see why hurricanes and other severe tropical storms may be susceptible to human intervention, a researcher must first learn about their nature and origins. Hurricanes grow in the form of thunderstorm clusters above the tropical seas. Oceans in low-latitude areas never stop giving out heat and moisture to the atmosphere, which brings about warm, wet air above the sea surface. When this kind of air rises, the water vapour in it condenses to form clouds and precipitation. Condensation gives out heat in the process the solar heat is used to evaporate the water at the ocean surface. This so-called invisible heat of condensation makes the air more buoyant, leading to it ascending higher while reinforcing itself in the feedback process. At last, the tropical depression starts to form and grow stronger, creating the familiar eye — the calm centre hub that a hurricane spins around. When reaching the land, the hurricane no longer has a continuous supply of warm water, which causes it to swiftly weaken.

C
Our current studies are inspired by my past intuition when I was learning about chaos theory 30 years ago. The reason why long-range forecasting is complicated is that the atmosphere is highly sensitive to small influences and tiny mistakes can compound fast in the weather-forecasting models. However, this sensitivity also made me realize a possibility: if we intentionally applied some slight inputs to a hurricane, we might create a strong influence that could affect the storms, either by steering them away from densely populated areas or by slowing them down. Back then, I was not able to test my ideas, but thanks to the advancement of computer simulation and remote-sensing technologies over the last 10 years, I can now renew my enthusiasm in large-scale weather control.

D
To find out whether the sensitivity of the atmospheric system could be exploited to adjust such robust atmospheric phenomena as hurricanes, our research team ran simulation experiments on computers for a hurricane named Iniki that occurred in 1992. The current forecasting technologies were far from perfect, so it took us by surprise that our first simulation turned out to be an immediate success. With the goal of altering the path of Iniki in mind, we first picked the spot where we wanted the storm to stop after six hours. Then we used this target to generate artificial observations and put these into the computer model.

E
The most significant alteration turned out to be the initial temperatures and winds. Usually, the temperature changes across the grid were only tenths of a degree, but the most noteworthy change, which was an increase of almost two degrees Celsius, took place in the lowest model layer to the west of the storm centre. The calculations produced wind-speed changes of two or three miles per hour. However, in several spots, the rates shifted by as much as 20 mph due to minor redirections of the winds close to the storm's centre. In terms of structure, the initial and altered versions of Hurricane Iniki seemed almost the same, but the changes in critical variables were so substantial that the latter one went off the track to the west during the first six hours of the simulation and then travelled due north, leaving Kauai untouched.

F
Future earth-orbiting solar power stations, equipped with large mirrors to focus the sun's rays and panels of photovoltaic cells to gather and send energy to the Earth, might be adapted to beam microwaves which turn to be absorbed by water vapour molecules inside or around the storm. The microwaves would cause the water molecules to vibrate and heat up the surrounding air, which then leads to the hurricane slowing down or moving in a preferred direction.

G
Simulations of hurricanes conducted on a computer have implied that by changing the precipitation, evaporation and air temperature, we could make a difference to a storm's route or abate its winds. Intervention could be in many different forms: exquisitely targeted clouds bearing silver iodide or other rainfall-inducing elements might deprive a hurricane of the water it needs to grow and multiply from its formidable eye wall, which is the essential characteristic of a severe tropical storm.`);

// Text segments
const textSegments = ref([
    { id: 'part-header', text: 'Part 3', offset: -100 },
    { id: 'part-instructions', text: 'Read the text and answer questions 27–40.', offset: -93 },
    { id: 'header-title', text: 'Can Hurricanes be Moderated or Diverted?', offset: -51 },
    { id: 'passage', text: passageText.value, offset: 0 },
    // Q27-33 heading matching
    { id: 'q27-33-title', text: 'Questions 27-33', offset: passageText.value.length + 1 },
    { id: 'q27-33-inst1', text: 'Reading Passage 3 has seven paragraphs, A-G', offset: passageText.value.length + 17 },
    { id: 'q27-33-inst2', text: 'Choose the correct heading for each paragraph from the list of headings below.', offset: passageText.value.length + 62 },
    { id: 'q27-33-inst3', text: 'Write the correct number, i-viii, in boxes 27-33 on your answer sheet.', offset: passageText.value.length + 141 },
    { id: 'headings-title', text: 'List of Headings', offset: passageText.value.length + 213 },
    { id: 'h-i', text: 'i    Hurricanes in history', offset: passageText.value.length + 230 },
    { id: 'h-ii', text: 'ii   How hurricanes form', offset: passageText.value.length + 256 },
    { id: 'h-iii', text: 'iii  How a laboratory exercise re-route a hurricane', offset: passageText.value.length + 280 },
    { id: 'h-iv', text: 'iv   Exciting ways to utilise future technologies', offset: passageText.value.length + 332 },
    { id: 'h-v', text: 'v    Are hurricanes unbeatable?', offset: passageText.value.length + 382 },
    { id: 'h-vi', text: 'vi   Re-visit earlier ideas', offset: passageText.value.length + 414 },
    { id: 'h-vii', text: 'vii  How lives might have been saved', offset: passageText.value.length + 442 },
    { id: 'h-viii', text: 'viii A range of low-tech methods', offset: passageText.value.length + 479 },
    { id: 'q27-text', text: 'Paragraph A', offset: passageText.value.length + 512 },
    { id: 'q28-text', text: 'Paragraph B', offset: passageText.value.length + 524 },
    { id: 'q29-text', text: 'Paragraph C', offset: passageText.value.length + 536 },
    { id: 'q30-text', text: 'Paragraph D', offset: passageText.value.length + 548 },
    { id: 'q31-text', text: 'Paragraph E', offset: passageText.value.length + 560 },
    { id: 'q32-text', text: 'Paragraph F', offset: passageText.value.length + 572 },
    { id: 'q33-text', text: 'Paragraph G', offset: passageText.value.length + 584 },
    // Q34-38 summary completion
    { id: 'q34-38-title', text: 'Questions 34-38', offset: passageText.value.length + 597 },
    { id: 'q34-38-inst1', text: 'Complete the summary below.', offset: passageText.value.length + 613 },
    { id: 'q34-38-inst2', text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: passageText.value.length + 641 },
    { id: 'q34-38-inst3', text: 'Write your answers in boxes 34-38 on your answer sheet.', offset: passageText.value.length + 696 },
    { id: 'sum-p1', text: 'Hurricanes originate as groups of', offset: passageText.value.length + 753 },
    { id: 'sum-p2', text: 'over the tropical oceans. Low-latitude seas continuously provide heat and moisture to the atmosphere, producing warm, humid air above the sea surface. When this air rises, the water vapour in it condenses to form clouds and precipitation.', offset: passageText.value.length + 787 },
    { id: 'sum-p3', text: 'releases heat—the solar heat it took to evaporate the water at the ocean surface. This so-called latent', offset: passageText.value.length + 1028 },
    { id: 'sum-p4', text: 'of condensation makes the air more buoyant, causing it to ascend still higher in a self-reinforcing feedback process. Eventually, the tropical depression begins to organise and strengthen, forming the familiar', offset: passageText.value.length + 1132 },
    { id: 'sum-p5', text: '— the calm central hub around which a hurricane spins. On passing over', offset: passageText.value.length + 1341 },
    { id: 'sum-p6', text: ', the hurricane\'s sustaining source of warm water is cut off, which leads to the storm\'s rapid weakening.', offset: passageText.value.length + 1413 },
    // Q39-40 multiple choice
    { id: 'q39-40-title', text: 'Questions 39-40', offset: passageText.value.length + 1520 },
    { id: 'q39-40-inst', text: 'Choose the correct letter, A, B, C or D.', offset: passageText.value.length + 1536 },
    { id: 'q39-40-inst2', text: 'Write the correct letter in boxes 39 and 40 on your answer sheet.', offset: passageText.value.length + 1578 },
    { id: 'q39-stem', text: 'What encouraged the writer to restart researching hurricane control?', offset: passageText.value.length + 1645 },
    { id: 'q39-A', text: 'the huge damage hurricane triggers', offset: passageText.value.length + 1714 },
    { id: 'q39-B', text: 'the developments in computer technologies', offset: passageText.value.length + 1749 },
    { id: 'q39-C', text: 'the requirement of some local people', offset: passageText.value.length + 1792 },
    { id: 'q39-D', text: 'the chaos theory learnt as a student', offset: passageText.value.length + 1829 },
    { id: 'q40-stem', text: 'What was the writer\'s reaction after their first experiment?', offset: passageText.value.length + 1867 },
    { id: 'q40-A', text: 'surprised that their intervention had not achieve a lot.', offset: passageText.value.length + 1928 },
    { id: 'q40-B', text: 'ecstatic with the achievement the first experiment had', offset: passageText.value.length + 1985 },
    { id: 'q40-C', text: 'surprised that their intervention had the intended effect', offset: passageText.value.length + 2040 },
    { id: 'q40-D', text: 'regretful about the impending success.', offset: passageText.value.length + 2098 },
]);

// Convert plain text offset to HTML offset
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;
    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') htmlIndex++;
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

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
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
    return getHighlightedSegment(segment.text);
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
                        segment = textSegments.value[3];
                    } else {
                        segment = textSegments.value.find((s) => s.text === spanText.trim());
                        if (!segment) {
                            segment = textSegments.value.find((s) => {
                                const normalizedSpan = spanText.trim().replace(/\s+/g, ' ');
                                const normalizedSegment = s.text.trim().replace(/\s+/g, ' ');
                                return normalizedSpan === normalizedSegment || normalizedSpan.includes(normalizedSegment) || normalizedSegment.includes(normalizedSpan);
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
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
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
    if (x - halfWidth < padding) x = halfWidth + padding;
    else if (x + halfWidth > viewportWidth - padding) x = viewportWidth - halfWidth - padding;

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) y = padding;
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
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    const noteId = Date.now();
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });
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
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
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
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
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
    if (relatedTarget?.closest('.note-hover-tooltip')) return;
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
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};

const stopResize = () => { isResizing.value = false; };

watch(notes, (newNotes) => { emit('notesChange', newNotes); }, { deep: true });

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

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div class="pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2" @mouseup="handlePassageTextSelect"
            @click="handleContentClick">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('part-header')">
            </p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegmentById('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="header-title"
                                v-html="getHighlightedSegmentById('header-title')"></span>
                        </h2>
                    </div>

                    <div class="space-y-2" :style="contentZoom">
                        <div ref="passageTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick"
                            class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span class="text-lg select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[3].text)"></span>
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
                    <div ref="questionsTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick"
                        class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Q27 -->
                            <div id="question-27" class="relative flex items-start gap-3 bg-white p-2"
                                @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                <span class="font-bold text-gray-900 shrink-0">27</span>
                                <span class="text-gray-700 flex-1 select-text" data-segment-id="q27-text"
                                    v-html="getHighlightedSegmentById('q27-text')"></span>
                                <select v-model="answers.q27"
                                    class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                                    style="width: 80px">
                                    <option value="" disabled selected>select</option>
                                    <option value="i">i</option>
                                    <option value="ii">ii</option>
                                    <option value="iii">iii</option>
                                    <option value="iv">iv</option>
                                    <option value="v">v</option>
                                    <option value="vi">vi</option>
                                    <option value="vii">vii</option>
                                    <option value="viii">viii</option>
                                </select>
                                <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                    @click.stop="toggleFlag(27)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q28 -->
                            <div id="question-28" class="relative flex items-start gap-3 bg-white p-2"
                                @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                <span class="font-bold text-gray-900 shrink-0">28</span>
                                <span class="text-gray-700 flex-1 select-text" data-segment-id="q28-text"
                                    v-html="getHighlightedSegmentById('q28-text')"></span>
                                <select v-model="answers.q28"
                                    class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                                    style="width: 80px">
                                    <option value="" disabled selected>select</option>
                                    <option value="i">i</option>
                                    <option value="ii">ii</option>
                                    <option value="iii">iii</option>
                                    <option value="iv">iv</option>
                                    <option value="v">v</option>
                                    <option value="vi">vi</option>
                                    <option value="vii">vii</option>
                                    <option value="viii">viii</option>
                                </select>
                                <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                    @click.stop="toggleFlag(28)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q29 -->
                            <div id="question-29" class="relative flex items-start gap-3 bg-white p-2"
                                @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                <span class="font-bold text-gray-900 shrink-0">29</span>
                                <span class="text-gray-700 flex-1 select-text" data-segment-id="q29-text"
                                    v-html="getHighlightedSegmentById('q29-text')"></span>
                                <select v-model="answers.q29"
                                    class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                                    style="width: 80px">
                                    <option value="" disabled selected>select</option>
                                    <option value="i">i</option>
                                    <option value="ii">ii</option>
                                    <option value="iii">iii</option>
                                    <option value="iv">iv</option>
                                    <option value="v">v</option>
                                    <option value="vi">vi</option>
                                    <option value="vii">vii</option>
                                    <option value="viii">viii</option>
                                </select>
                                <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                    @click.stop="toggleFlag(29)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q30 -->
                            <div id="question-30" class="relative flex items-start gap-3 bg-white p-2"
                                @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                <span class="font-bold text-gray-900 shrink-0">30</span>
                                <span class="text-gray-700 flex-1 select-text" data-segment-id="q30-text"
                                    v-html="getHighlightedSegmentById('q30-text')"></span>
                                <select v-model="answers.q30"
                                    class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                                    style="width: 80px">
                                    <option value="" disabled selected>select</option>
                                    <option value="i">i</option>
                                    <option value="ii">ii</option>
                                    <option value="iii">iii</option>
                                    <option value="iv">iv</option>
                                    <option value="v">v</option>
                                    <option value="vi">vi</option>
                                    <option value="vii">vii</option>
                                    <option value="viii">viii</option>
                                </select>
                                <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                    @click.stop="toggleFlag(30)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q31 -->
                            <div id="question-31" class="relative flex items-start gap-3 bg-white p-2"
                                @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
                                <span class="font-bold text-gray-900 shrink-0">31</span>
                                <span class="text-gray-700 flex-1 select-text" data-segment-id="q31-text"
                                    v-html="getHighlightedSegmentById('q31-text')"></span>
                                <select v-model="answers.q31"
                                    class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                                    style="width: 80px">
                                    <option value="" disabled selected>select</option>
                                    <option value="i">i</option>
                                    <option value="ii">ii</option>
                                    <option value="iii">iii</option>
                                    <option value="iv">iv</option>
                                    <option value="v">v</option>
                                    <option value="vi">vi</option>
                                    <option value="vii">vii</option>
                                    <option value="viii">viii</option>
                                </select>
                                <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                    @click.stop="toggleFlag(31)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q32 -->
                            <div id="question-32" class="relative flex items-start gap-3 bg-white p-2"
                                @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
                                <span class="font-bold text-gray-900 shrink-0">32</span>
                                <span class="text-gray-700 flex-1 select-text" data-segment-id="q32-text"
                                    v-html="getHighlightedSegmentById('q32-text')"></span>
                                <select v-model="answers.q32"
                                    class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                                    style="width: 80px">
                                    <option value="" disabled selected>select</option>
                                    <option value="i">i</option>
                                    <option value="ii">ii</option>
                                    <option value="iii">iii</option>
                                    <option value="iv">iv</option>
                                    <option value="v">v</option>
                                    <option value="vi">vi</option>
                                    <option value="vii">vii</option>
                                    <option value="viii">viii</option>
                                </select>
                                <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                    @click.stop="toggleFlag(32)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q33 -->
                            <div id="question-33" class="relative flex items-start gap-3 bg-white p-2"
                                @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null">
                                <span class="font-bold text-gray-900 shrink-0">33</span>
                                <span class="text-gray-700 flex-1 select-text" data-segment-id="q33-text"
                                    v-html="getHighlightedSegmentById('q33-text')"></span>
                                <select v-model="answers.q33"
                                    class="question-select border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none bg-white shrink-0"
                                    style="width: 80px">
                                    <option value="" disabled selected>select</option>
                                    <option value="i">i</option>
                                    <option value="ii">ii</option>
                                    <option value="iii">iii</option>
                                    <option value="iv">iv</option>
                                    <option value="v">v</option>
                                    <option value="vi">vi</option>
                                    <option value="vii">vii</option>
                                    <option value="viii">viii</option>
                                </select>
                                <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                    @click.stop="toggleFlag(33)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Questions 34-38: Summary Completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                                        <span class="select-text" data-segment-id="q34-38-title"
                                            v-html="getHighlightedSegmentById('q34-38-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q34-38-inst1"
                                            v-html="getHighlightedSegmentById('q34-38-inst1')"></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q34-38-inst2"
                                            v-html="getHighlightedSegmentById('q34-38-inst2')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q34-38-inst3"
                                            v-html="getHighlightedSegmentById('q34-38-inst3')"></span>
                                    </p>
                                </div>

                                <div
                                    class=" text-sm leading-relaxed text-gray-800">
                                    <p class="text-gray-800 leading-relaxed">
                                        <span class="select-text" data-segment-id="sum-p1"
                                            v-html="getHighlightedSegmentById('sum-p1')"></span>
                                        <!-- Q34 -->
                                        <span id="question-34"
                                            class="relative inline-flex items-center mx-1 align-middle"
                                            @mouseenter="hoveredQuestion = 34" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q34" type="text"
                                                class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 130px" placeholder="34" spellcheck="false"
                                                autocomplete="off" />
                                            <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                                @click.stop="toggleFlag(34)"
                                                class="ml-1 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(34) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="sum-p2"
                                            v-html="getHighlightedSegmentById('sum-p2')"></span>
                                        <!-- Q35 -->
                                        <span id="question-35"
                                            class="relative inline-flex items-center mx-1 align-middle"
                                            @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q35" type="text"
                                                class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 130px" placeholder="35" spellcheck="false"
                                                autocomplete="off" />
                                            <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                @click.stop="toggleFlag(35)"
                                                class="ml-1 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(35) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="sum-p3"
                                            v-html="getHighlightedSegmentById('sum-p3')"></span>
                                        <!-- Q36 -->
                                        <span id="question-36"
                                            class="relative inline-flex items-center mx-1 align-middle"
                                            @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q36" type="text"
                                                class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 130px" placeholder="36" spellcheck="false"
                                                autocomplete="off" />
                                            <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                @click.stop="toggleFlag(36)"
                                                class="ml-1 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(36) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="sum-p4"
                                            v-html="getHighlightedSegmentById('sum-p4')"></span>
                                        <!-- Q37 -->
                                        <span id="question-37"
                                            class="relative inline-flex items-center mx-1 align-middle"
                                            @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q37" type="text"
                                                class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 130px" placeholder="37" spellcheck="false"
                                                autocomplete="off" />
                                            <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                @click.stop="toggleFlag(37)"
                                                class="ml-1 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(37) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="sum-p5"
                                            v-html="getHighlightedSegmentById('sum-p5')"></span>
                                        <!-- Q38 -->
                                        <span id="question-38"
                                            class="relative inline-flex items-center mx-1 align-middle"
                                            @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q38" type="text"
                                                class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 130px" placeholder="38" spellcheck="false"
                                                autocomplete="off" />
                                            <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                @click.stop="toggleFlag(38)"
                                                class="ml-1 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(38) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="sum-p6"
                                            v-html="getHighlightedSegmentById('sum-p6')"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Questions 39-40: Multiple Choice -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                                        <span class="select-text" data-segment-id="q39-40-title"
                                            v-html="getHighlightedSegmentById('q39-40-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q39-40-inst"
                                            v-html="getHighlightedSegmentById('q39-40-inst')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span class="select-text" data-segment-id="q39-40-inst2"
                                            v-html="getHighlightedSegmentById('q39-40-inst2')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Q39 -->
                                    <div id="question-39" class="relative p-2" @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                            @click.stop="toggleFlag(39)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700">
                                                <span class="font-bold text-gray-900 select-text mr-1">39.</span>
                                                <span class="select-text" data-segment-id="q39-stem"
                                                    v-html="getHighlightedSegmentById('q39-stem')"></span>
                                            </p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q39" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">A</span>
                                                    <span class="select-text" data-segment-id="q39-A"
                                                        v-html="getHighlightedSegmentById('q39-A')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q39" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">B</span>
                                                    <span class="select-text" data-segment-id="q39-B"
                                                        v-html="getHighlightedSegmentById('q39-B')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q39" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">C</span>
                                                    <span class="select-text" data-segment-id="q39-C"
                                                        v-html="getHighlightedSegmentById('q39-C')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q39" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">D</span>
                                                    <span class="select-text" data-segment-id="q39-D"
                                                        v-html="getHighlightedSegmentById('q39-D')"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Q40 -->
                                    <div id="question-40" class="relative p-2" @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                            @click.stop="toggleFlag(40)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700">
                                                <span class="font-bold text-gray-900 select-text mr-1">40.</span>
                                                <span class="select-text" data-segment-id="q40-stem"
                                                    v-html="getHighlightedSegmentById('q40-stem')"></span>
                                            </p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q40" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">A</span>
                                                    <span class="select-text" data-segment-id="q40-A"
                                                        v-html="getHighlightedSegmentById('q40-A')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q40" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">B</span>
                                                    <span class="select-text" data-segment-id="q40-B"
                                                        v-html="getHighlightedSegmentById('q40-B')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q40" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">C</span>
                                                    <span class="select-text" data-segment-id="q40-C"
                                                        v-html="getHighlightedSegmentById('q40-C')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q40" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="font-semibold text-gray-900 mr-1">D</span>
                                                    <span class="select-text" data-segment-id="q40-D"
                                                        v-html="getHighlightedSegmentById('q40-D')"></span>
                                                </label>
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

    <!-- Context Menu for Highlighting - Speech Bubble Style -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
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
            <div class="delete-highlight-tooltip fixed z-9999"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
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
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
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
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p
                    class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"</p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save
                    Note</button>
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

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>