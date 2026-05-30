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

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-tea-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

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

// Q27-31: heading matching answers (select/option)
// Q32-35: paragraph identification answers (select/option)
// Q36-40: summary completion answers (text input)
const answers = ref({
    q27: '', q28: '', q29: '', q30: '', q31: '',
    q32: '', q33: '', q34: '', q35: '',
    q36: '', q37: '', q38: '', q39: '', q40: '',
});

// Drag and drop functionality for questions 27-31
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Heading options for Q27-31
const headingOptions = [
    { value: 'i', label: 'Camera settings for observation' },
    { value: 'ii', label: 'Collisions on stage' },
    { value: 'iii', label: 'Size of comet' },
    { value: 'iv', label: 'String of pearls' },
    { value: 'v', label: 'Scientific explanations' },
    { value: 'vi', label: 'Hubble Space Telescope' },
    { value: 'vii', label: 'First discovery of the squashed comet' },
    { value: 'viii', label: 'Power generated from the collisions' },
    { value: 'ix', label: 'Calculations, expectations and predictions' },
    { value: 'x', label: 'Change of the fragment\'s shape' },
];

// Filter out used heading options
const availableHeadingOptions = computed(() => {
    const usedOptions = [
        answers.value.q27, answers.value.q28, answers.value.q29,
        answers.value.q30, answers.value.q31,
    ].filter(Boolean);
    return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
});

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
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
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

const getHeadingLabel = (value: string): string => {
    const option = headingOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Paragraph options for Q32-35
const paragraphOptions = [
    { value: 'A', label: 'A' },
    { value: 'B', label: 'B' },
    { value: 'C', label: 'C' },
    { value: 'D', label: 'D' },
    { value: 'E', label: 'E' },
    { value: 'F', label: 'F' },
    { value: 'G', label: 'G' },
    { value: 'H', label: 'H' },
    { value: 'I', label: 'I' },
    { value: 'J', label: 'J' },
];

// ===== PASSAGE PARAGRAPHS =====
const paragraphA = `The last half of July 1994 witnessed much interest among the astronomical community and the wider public in the collision of comet Shoemaker-Levy 9 with Jupiter. The comet was discovered on 25 March 1993 by Eugene and Carolyn Shoemaker and David Levy, using a 450 mm Schmidt camera at the Mount Palomar Observatory. The discovery was based on a photographic plate exposed two days earlier. The Shoemakers are particularly experienced comet hunters with 61 discoveries to their credit. Their technique relied on the proper motion of a comet to identify the object as a non-stellar body. They photograph large areas of the sky, typically with an eight minute exposure, and repeat the photograph 45 minutes later. Comparison of the two photographs with a stereo-microscope reveals any bodies which have moved against the background of fixed stars.`;

const paragraphB = `As so often in science, serendipity played a large part in the discovery of the Shoemaker-Levy 9. The weather in the night of 23 March was so poor that the observers would not normally have bothered putting film into their camera. However, they had a box of old film to hand which had been partially exposed by accident some days previously, so decided to insert it into the camera rather than waste good film. Fortunately, two of the film plates, despite being fogged round the edges captured the first image of a very strange, bar-shaped object. This object, which Carolyn Shoemaker first described as a squashed comet, later became known as comet Shoemaker-Levy 9.`;

const paragraphC = `Other, more powerful, telescopes revealed that the comet was in fact composed of 21 cemetery fragments, strung out in a line, which accounted for the unusual shape. The term string of pearls was soon coined. Some graphic proofs obtained by the Hubble Space Telescope shows the main fragments which at that time spanned a linear distance of approximately 600,000 km. Initially the fragments were surrounded by extensive dust clouds in the line of the nuclei but these later disappeared. Some of the nuclei also faded out, while others split into multiple fragments.`;

const paragraphD = `The size of the original comet and each of the fragments was, and still is, something of a mystery. The first analysis of the orbital dynamics of the fragments suggested that the comet was originally some 2.5 km in diameter with an average fragment diameter of 0.75 km. Later work gave corresponding diameters of approximately 10 km and 2 km and these values are now considered more likely. There was considerable variation in the diameters of different fragments.`;

const paragraphE = `Further calculations revealed that the cemetery fragments were on course to collide with Jupiter during July 1994, and that each fragment could deliver an energy equivalent to approximately 500,000 million tons of TNT. The prospect of celestial fireworks on such a grand scale immediately captured the attention of astronomers worldwide!`;

const paragraphF = `Each fragment was assigned an identity letter A-W and a coordinated program of observations was put in place worldwide to track their progress towards impact with Jupiter. As the cemetery fragments reached the cloud tops of Jupiter, they were travelling at approximately 30,000,000 km. The impacts occurred during 16–22 July. All took place at a latitude of approximately 48 degrees south which nominally placed them in the SSS Temperate Region, however visually they appeared close to the Jovian polar region. The impacts all occurred some 10–15 degrees round the limb in the far side of the planet as seen from Earth. However the rapid rotation of the planet soon carried the impact sites into the view of Earth-based telescopes. The collisions lived up to all but the wildest expectations and provided a truly impressive spectacle.`;

const paragraphG = `Jupiter is composed of a relatively small core of iron and silicates surrounded by hydrogen. In the depths of the planet the hydrogen is so compressed that it is metallic in form; further from the center, the pressure is lower and the hydrogen is in its normal molecular form. The Jovian cloud tops visible from Earth consist primarily of methane and ammonia. There are other elements and compounds lurking in the cloud tops and below which are thought to be responsible for the colors seen in the atmosphere.`;

const paragraphH = `The smaller cemetery fragments plunged into Jupiter, rapidly disintegrated and left little trace; three of the smallest fragments, namely T, U and V left no discernible traces whatsoever. However, many of the cemetery fragments were sufficiently large to produce a spectacular display. Each large fragment punched through the cloud tops, heated the surrounding gases to some 20,000 K on the way, and caused a massive plume or fireball up to 2,000 km in diameter to rise above the cloud tops. Before encountering thicker layers of the atmosphere and disintegrating in a mammoth shock wave, the large fragments raised dark dust particles and ultra violet absorbing gases high into the Jovian cloud tops. The dark particles and ultra violet absorbing gases manifested themselves as a dark scar surrounding the impact site in visible light.`;

const paragraphI = `Some days after collision the impact sites began to evolve and fade as they became subject to the dynamics of Jupiter's atmosphere. No one knows how long they will remain visible from Earth, but it is thought that the larger scars may persist for a year or more. The interest of professional astronomers in Jupiter is now waning and valuable work can therefore be performed by amateurs in tracking the evolution of the collision scars. The scars are easily visible in a modest telescope, and a large reflector will show them in some detail. There is scope for valuable observing work from now until Jupiter reaches conjunction with the Sun in November 2004.`;

const paragraphJ = `Astronomers and archivists are now searching old records for possible previously unrecognized impacts on Jupiter. Several spots were reported from 1690 to 1872 by observers including William Herschel and Giovanni Cassini. The records of the BAA in 1927 and 1948 contain drawings of Jupiter with black dots or spots visible. It may be possible that comet impacts have been observed before, without their identity being realized, but no one can be sure.`;

// ===== Compute offsets programmatically =====
const rawSegments = [
    { id: 'part3-header', text: 'READING PASSAGE 3' },
    { id: 'part3-instruction', text: 'You should spend about 20 minutes on Questions 27-40 which are based on Reading Passage 3 below.' },
    { id: 'part3-title', text: 'Shoemaker-Levy 9 Collision with Jupiter' },
    { id: 'para-a', text: paragraphA },
    { id: 'para-b', text: paragraphB },
    { id: 'para-c', text: paragraphC },
    { id: 'para-d', text: paragraphD },
    { id: 'para-e', text: paragraphE },
    { id: 'para-f', text: paragraphF },
    { id: 'para-g', text: paragraphG },
    { id: 'para-h', text: paragraphH },
    { id: 'para-i', text: paragraphI },
    { id: 'para-j', text: paragraphJ },
    // Q27-31
    { id: 'q27-31-title', text: 'Questions 27–31' },
    { id: 'q27-31-inst1', text: 'Choose the most suitable headings for paragraphs B–F from the list of headings below.' },
    { id: 'q27-31-inst2', text: 'Write the appropriate number i–x in boxes 27–31 on your answer sheet.' },
    { id: 'headings-title', text: 'List of Headings' },
    { id: 'heading-i', text: 'i – Camera settings for observation' },
    { id: 'heading-ii', text: 'ii – Collisions on stage' },
    { id: 'heading-iii', text: 'iii – Size of comet' },
    { id: 'heading-iv', text: 'iv – String of pearls' },
    { id: 'heading-v', text: 'v – Scientific explanations' },
    { id: 'heading-vi', text: 'vi – Hubble Space Telescope' },
    { id: 'heading-vii', text: 'vii – First discovery of the squashed comet' },
    { id: 'heading-viii', text: 'viii – Power generated from the collisions' },
    { id: 'heading-ix', text: 'ix – Calculations, expectations and predictions' },
    { id: 'heading-x', text: 'x – Change of the fragment\'s shape' },
    { id: 'q27-label', text: 'Paragraph B' },
    { id: 'q28-label', text: 'Paragraph C' },
    { id: 'q29-label', text: 'Paragraph D' },
    { id: 'q30-label', text: 'Paragraph E' },
    { id: 'q31-label', text: 'Paragraph F' },
    // Q32-35
    { id: 'q32-35-title', text: 'Questions 32–35' },
    { id: 'q32-35-inst1', text: 'Which paragraphs state the following information?' },
    { id: 'q32-35-inst2', text: 'Write the appropriate letters A–J in boxes 32–35 on your answer sheet.' },
    { id: 'q32-num', text: '32' },
    { id: 'q32-text', text: 'Shoemaker-Levy 9 comets had been accidentally detected.' },
    { id: 'q33-num', text: '33' },
    { id: 'q33-text', text: 'The collision caused a spectacular vision on Jupiter.' },
    { id: 'q34-num', text: '34' },
    { id: 'q34-text', text: 'Every single element of Shoemaker-Levy 9 was labeled.' },
    { id: 'q35-num', text: '35' },
    { id: 'q35-text', text: 'Visual evidence explains the structure of Shoemaker-Levy 9.' },
    // Q36-40
    { id: 'q36-40-title', text: 'Questions 36–40' },
    { id: 'q36-40-inst1', text: 'Complete the summary below.' },
    { id: 'q36-40-inst2', text: 'Choose NO MORE THAN THREE WORDS from the passage for each answer.' },
    { id: 'q36-40-summary', text: 'The core of Jupiter, which is enclosed by hydrogen, consists of' },
    { id: 'q36-label', text: '36' },
    { id: 'q36-40-and', text: 'and' },
    { id: 'q37-label', text: '37' },
    { id: 'q36-40-part2', text: '. Hydrogen is in metallic form as it is squeezed by pressure generated from the depths of the planet. The pressure is gradually reduced from the center to the outside layers, where hydrogen is in its normal molecular form of' },
    { id: 'q38-label', text: '38' },
    { id: 'q36-40-part3', text: '. Far from the ground, methane and ammonia structure the' },
    { id: 'q39-label', text: '39' },
    { id: 'q36-40-part4', text: ', which can be observed from Earth. Colors seen in the atmosphere is largely due to other particles' },
    { id: 'q40-label', text: '40' },
    { id: 'q36-40-part5', text: 'in the cloud.' },
];

// Auto-compute cumulative offsets
const textSegments = computed(() => {
    let offset = 0;
    return rawSegments.map((seg) => {
        const result = { ...seg, offset };
        offset += seg.text.length;
        return result;
    });
});

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

const getAnswers = () => answers.value;

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) { showContextMenu.value = false; return; }

        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) container = container.parentNode as Node;
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find((s) => s.id === segmentIdAttr);
            if (!segment) return null;

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) { offsetInSegment += offsetInNode; break; }
                else offsetInSegment += currentNode.textContent?.length || 0;
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

        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];

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
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => { document.querySelector<HTMLInputElement>('.note-input-field')?.focus(); }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    const noteId = Date.now();
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Part 3' });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((note) => note.id !== id); };

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
    if (noteMark) { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

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
        }
    } else {
        closeDeleteTooltip();
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) showContextMenu.value = false;
};

const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};
const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, (newWidth) => { localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString()); });

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part3-header"
                v-html="getHighlightedSegmentById('part3-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part3-instruction"
                v-html="getHighlightedSegmentById('part3-instruction')"></p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ===== READING PASSAGE ===== -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-shrink-0 border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 select-text" data-segment-id="part3-title"
                            v-html="getHighlightedSegmentById('part3-title')"></h2>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text sm:text-base">

                            <!-- Paragraph A (no drop zone - not in Q27-31) -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">A. </span><span class="select-text" data-segment-id="para-a"
                                        v-html="getHighlightedSegment(paragraphA)"></span>
                                </div>
                            </div>

                            <!-- Paragraph B with drop zone above (Q27) -->
                            <div class="px-4">
                                <div
                                    id="question-27"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 27"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 27)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 27)"
                                        @click="clearHeadingAnswer(27)"
                                        :title="answers.q27 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q27" class="font-medium">{{ getHeadingLabel(answers.q27) }}</span>
                                        <span v-else class="font-bold text-gray-500">27</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 27)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(27) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 27 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">B. </span><span class="select-text" data-segment-id="para-b"
                                        v-html="getHighlightedSegment(paragraphB)"></span>
                                </div>
                            </div>

                            <!-- Paragraph C with drop zone above (Q28) -->
                            <div class="px-4">
                                <div
                                    id="question-28"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 28"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 28 ? 'border-blue-500 bg-blue-50' : answers.q28 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 28)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 28)"
                                        @click="clearHeadingAnswer(28)"
                                        :title="answers.q28 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q28" class="font-medium">{{ getHeadingLabel(answers.q28) }}</span>
                                        <span v-else class="font-bold text-gray-500">28</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 28)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(28) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 28 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">C. </span><span class="select-text" data-segment-id="para-c"
                                        v-html="getHighlightedSegment(paragraphC)"></span>
                                </div>
                            </div>

                            <!-- Paragraph D with drop zone above (Q29) -->
                            <div class="px-4">
                                <div
                                    id="question-29"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 29"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 29 ? 'border-blue-500 bg-blue-50' : answers.q29 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 29)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 29)"
                                        @click="clearHeadingAnswer(29)"
                                        :title="answers.q29 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q29" class="font-medium">{{ getHeadingLabel(answers.q29) }}</span>
                                        <span v-else class="font-bold text-gray-500">29</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 29)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(29) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 29 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">D. </span><span class="select-text" data-segment-id="para-d"
                                        v-html="getHighlightedSegment(paragraphD)"></span>
                                </div>
                            </div>

                            <!-- Paragraph E with drop zone above (Q30) -->
                            <div class="px-4">
                                <div
                                    id="question-30"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 30"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 30 ? 'border-blue-500 bg-blue-50' : answers.q30 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 30)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 30)"
                                        @click="clearHeadingAnswer(30)"
                                        :title="answers.q30 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q30" class="font-medium">{{ getHeadingLabel(answers.q30) }}</span>
                                        <span v-else class="font-bold text-gray-500">30</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 30)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(30) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 30 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">E. </span><span class="select-text" data-segment-id="para-e"
                                        v-html="getHighlightedSegment(paragraphE)"></span>
                                </div>
                            </div>

                            <!-- Paragraph F with drop zone above (Q31) -->
                            <div class="px-4">
                                <div
                                    id="question-31"
                                    class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 31"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 31 ? 'border-blue-500 bg-blue-50' : answers.q31 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 31)"
                                        @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 31)"
                                        @click="clearHeadingAnswer(31)"
                                        :title="answers.q31 ? 'Click to clear' : 'Drop heading here'"
                                    >
                                        <span v-if="answers.q31" class="font-medium">{{ getHeadingLabel(answers.q31) }}</span>
                                        <span v-else class="font-bold text-gray-500">31</span>
                                    </span>
                                    <button
                                        @click.stop="emit('toggleFlag', 31)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(31) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 31 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">F. </span><span class="select-text" data-segment-id="para-f"
                                        v-html="getHighlightedSegment(paragraphF)"></span>
                                </div>
                            </div>

                            <!-- Paragraph G (no drop zone - not in Q27-31) -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">G. </span><span class="select-text" data-segment-id="para-g"
                                        v-html="getHighlightedSegment(paragraphG)"></span>
                                </div>
                            </div>

                            <!-- Paragraph H (no drop zone - not in Q27-31) -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">H. </span><span class="select-text" data-segment-id="para-h"
                                        v-html="getHighlightedSegment(paragraphH)"></span>
                                </div>
                            </div>

                            <!-- Paragraph I (no drop zone - not in Q27-31) -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">I. </span><span class="select-text" data-segment-id="para-i"
                                        v-html="getHighlightedSegment(paragraphI)"></span>
                                </div>
                            </div>

                            <!-- Paragraph J (no drop zone - not in Q27-31) -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">J. </span><span class="select-text" data-segment-id="para-j"
                                        v-html="getHighlightedSegment(paragraphJ)"></span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ===== QUESTIONS PANEL ===== -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- ===== Q27–31: Heading Matching (Drag & Drop) ===== -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q27-31-title"
                                            v-html="getHighlightedSegmentById('q27-31-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-0.5">
                                        <span data-segment-id="q27-31-inst1"
                                            v-html="getHighlightedSegmentById('q27-31-inst1')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-4">
                                        <span data-segment-id="q27-31-inst2"
                                            v-html="getHighlightedSegmentById('q27-31-inst2')"></span>
                                    </p>
                                </div>

                                <!-- Draggable Heading Cards -->
                                <div class="mb-2">
                                    <h4 class="text-sm font-bold text-gray-900 mb-3">
                                        <span data-segment-id="headings-title"
                                            v-html="getHighlightedSegmentById('headings-title')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <div
                                            v-for="option in availableHeadingOptions"
                                            :key="option.value"
                                            class="flex items-center gap-3 border border-gray-300 bg-white px-3 py-2.5 cursor-grab hover:bg-gray-50 transition-colors active:cursor-grabbing"
                                            :class="{ 'opacity-40': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd"
                                        >
                                            <span class="text-gray-400 text-base leading-none shrink-0 font-bold tracking-tighter">=</span>
                                            <span class="font-bold text-gray-900 text-sm shrink-0 w-8">{{ option.value }}</span>
                                            <span class="text-sm text-gray-700">{{ option.label }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== Q32–35: Paragraph Identification (select/option) ===== -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="q32-35-title"
                                                v-html="getHighlightedSegmentById('q32-35-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q32-35-inst1"
                                            v-html="getHighlightedSegmentById('q32-35-inst1')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700 italic">
                                        <span data-segment-id="q32-35-inst2"
                                            v-html="getHighlightedSegmentById('q32-35-inst2')"></span>
                                    </p>
                                </div>

                                <div class="space-y-3">

                                    <!-- Q32 -->
                                    <div id="question-32" class="relative p-3" @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-center gap-3 pr-8">
                                            <span class="text-sm font-bold text-gray-900 select-text"
                                                data-segment-id="q32-num"
                                                v-html="getHighlightedSegmentById('q32-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1"
                                                data-segment-id="q32-text"
                                                v-html="getHighlightedSegmentById('q32-text')"></span>
                                            <select v-model="answers.q32"
                                                class="w-20 rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none">
                                                <option value="">select</option>
                                                <option v-for="opt in paragraphOptions" :key="opt.value"
                                                    :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                            @click.stop="emit('toggleFlag', 32)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(32) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q33 -->
                                    <div id="question-33" class="relative p-3" @mouseenter="hoveredQuestion = 33"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-center gap-3 pr-8">
                                            <span class="text-sm font-bold text-gray-900 select-text"
                                                data-segment-id="q33-num"
                                                v-html="getHighlightedSegmentById('q33-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1"
                                                data-segment-id="q33-text"
                                                v-html="getHighlightedSegmentById('q33-text')"></span>
                                            <select v-model="answers.q33"
                                                class="w-20 rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none">
                                                <option value="">select</option>
                                                <option v-for="opt in paragraphOptions" :key="opt.value"
                                                    :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                            @click.stop="emit('toggleFlag', 33)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(33) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q34 -->
                                    <div id="question-34" class="relative p-3" @mouseenter="hoveredQuestion = 34"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-center gap-3 pr-8">
                                            <span class="text-sm font-bold text-gray-900 select-text"
                                                data-segment-id="q34-num"
                                                v-html="getHighlightedSegmentById('q34-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1"
                                                data-segment-id="q34-text"
                                                v-html="getHighlightedSegmentById('q34-text')"></span>
                                            <select v-model="answers.q34"
                                                class="w-20 rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none">
                                                <option value="">select</option>
                                                <option v-for="opt in paragraphOptions" :key="opt.value"
                                                    :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                            @click.stop="emit('toggleFlag', 34)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(34) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q35 -->
                                    <div id="question-35" class="relative p-3" @mouseenter="hoveredQuestion = 35"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-center gap-3 pr-8">
                                            <span class="text-sm font-bold text-gray-900 select-text"
                                                data-segment-id="q35-num"
                                                v-html="getHighlightedSegmentById('q35-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1"
                                                data-segment-id="q35-text"
                                                v-html="getHighlightedSegmentById('q35-text')"></span>
                                            <select v-model="answers.q35"
                                                class="w-20 rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none">
                                                <option value="">select</option>
                                                <option v-for="opt in paragraphOptions" :key="opt.value"
                                                    :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                            @click.stop="emit('toggleFlag', 35)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(35) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ===== Q36–40: Summary Completion ===== -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4">
                                        <h3 class="text-base font-bold text-gray-900 mb-1">
                                            <span data-segment-id="q36-40-title"
                                                v-html="getHighlightedSegmentById('q36-40-title')"></span>
                                        </h3>
                                        <p class="mb-0.5 text-base leading-relaxed text-gray-700">
                                            <span data-segment-id="q36-40-inst1"
                                                v-html="getHighlightedSegmentById('q36-40-inst1')"></span>
                                        </p>
                                        <p class="mb-4 text-sm text-gray-700 italic">
                                            <span data-segment-id="q36-40-inst2"
                                                v-html="getHighlightedSegmentById('q36-40-inst2')"></span>
                                        </p>
                                    </div>

                                    <!-- Summary paragraph with inline inputs -->
                                    <div
                                        class="rounded border border-gray-200 bg-gray-50 p-4 text-sm leading-loose text-gray-700 select-text">
                                        <span data-segment-id="q36-40-summary"
                                            v-html="getHighlightedSegmentById('q36-40-summary')"></span>

                                        <!-- Q36 -->
                                        <span id="question-36" class="inline-flex items-center mx-1 relative"
                                            @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q36" type="text" placeholder="36"
                                                class="inline-block w-28 rounded border border-gray-400 bg-white px-2 py-0.5  font-bold text-center focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none" />
                                            <button
                                                @click.stop="emit('toggleFlag', 36)"
                                                class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(36) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 36 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-5 hover:opacity-100'"
                                                :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q36-40-and"
                                            v-html="getHighlightedSegmentById('q36-40-and')"></span>

                                        <!-- Q37 -->
                                        <span id="question-37" class="inline-flex items-center mx-1 relative"
                                            @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q37" type="text" placeholder="37"
                                                class="inline-block w-28 rounded border border-gray-400 bg-white px-2 py-0.5 pl-6 font-bold text-center focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none" />
                                            <button
                                                @click.stop="emit('toggleFlag', 37)"
                                                class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(37) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 37 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-5 hover:opacity-100'"
                                                :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q36-40-part2"
                                            v-html="getHighlightedSegmentById('q36-40-part2')"></span>

                                        <!-- Q38 -->
                                        <span id="question-38" class="inline-flex items-center mx-1 relative"
                                            @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q38" type="text" placeholder="38"
                                                class="inline-block w-28 rounded border border-gray-400 bg-white px-2 py-0.5 pl-6 font-bold text-center focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none" />
                                            <button
                                                @click.stop="emit('toggleFlag', 38)"
                                                class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(38) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 38 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-5 hover:opacity-100'"
                                                :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q36-40-part3"
                                            v-html="getHighlightedSegmentById('q36-40-part3')"></span>

                                        <!-- Q39 -->
                                        <span id="question-39" class="inline-flex items-center mx-1 relative"
                                            @mouseenter="hoveredQuestion = 39" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q39" type="text" placeholder="39"
                                                class="inline-block w-28 rounded border border-gray-400 bg-white px-2 py-0.5 my-1 font-bold text-center focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none" />
                                            <button
                                                @click.stop="emit('toggleFlag', 39)"
                                                class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(39) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 39 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-5 hover:opacity-100'"
                                                :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q36-40-part4"
                                            v-html="getHighlightedSegmentById('q36-40-part4')"></span>

                                        <!-- Q40 -->
                                        <span id="question-40" class="inline-flex items-center mx-1 relative"
                                            @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q40" type="text" placeholder="40"
                                                class="inline-block w-28 rounded border border-gray-400 bg-white px-2 py-0.5 pl-6 font-bold text-center focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none" />
                                            <button
                                                @click.stop="emit('toggleFlag', 40)"
                                                class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(40) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 40 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-5 hover:opacity-100'"
                                                :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q36-40-part5"
                                            v-html="getHighlightedSegmentById('q36-40-part5')"></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu for Highlighting -->
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
        </Teleport>

        <!-- Delete Highlight Tooltip -->
        <Teleport to="body">
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
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path
                                    d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2">
                                </path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Note Hover Tooltip -->
        <Teleport to="body">
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
        </Teleport>

        <!-- Note Input Modal -->
        <Teleport to="body">
            <div v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{
                        selectedText }}"</p>
                    <input v-model="noteInputText" type="text" placeholder="Write your note here..."
                        class="note-input-field w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                        class="rounded border border-gray-300 bg-white px-3 py-0.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</button>
                    <button @click="saveNote"
                        class="rounded bg-gray-900 px-3 py-0.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save
                        Note</button>
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
</style>

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
