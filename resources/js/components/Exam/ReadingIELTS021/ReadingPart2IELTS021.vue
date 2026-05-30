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
const PANEL_WIDTH_KEY = 'reading-laughter-part2-panel-width';
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

// Q14-15: choose TWO checkboxes (A-E)
// Q16-20: YES/NO/NOT GIVEN
// Q21-26: fill-in-the-blank text inputs
const answers = ref({
    q14: [] as string[],
    q15: [] as string[],
    q16: '', q17: '', q18: '', q19: '', q20: '',
    q21: '', q22: '', q23: '', q24: '', q25: '', q26: '',
});

// Q14-15: handle checkbox selection (max 2 items combined)
const q1415Options = [
    { value: 'A', label: 'All animals share the phenomenon of laughter.' },
    { value: 'B', label: 'Laughter can influence both adult and child health.' },
    { value: 'C', label: 'Laughter is not unique to humans.' },
    { value: 'D', label: 'Human mental, physical and social well-being are closely related.' },
    { value: 'E', label: 'Laughter teaches us how to behave.' },
];

// Combined selection for Q14-15 (choose exactly TWO from A-E)
const q1415Selected = ref<string[]>([]);

const toggleQ1415Option = (value: string) => {
    const idx = q1415Selected.value.indexOf(value);
    if (idx !== -1) {
        q1415Selected.value.splice(idx, 1);
    } else if (q1415Selected.value.length < 2) {
        q1415Selected.value.push(value);
    }
    // Sync to answers
    answers.value.q14 = q1415Selected.value[0] ? [q1415Selected.value[0]] : [];
    answers.value.q15 = q1415Selected.value[1] ? [q1415Selected.value[1]] : [];
};

const isQ1415Selected = (value: string) => q1415Selected.value.includes(value);

// ===== PASSAGE PARAGRAPHS =====
const paragraphP1 = `Humans don't have a monopoly on laughter, says Silvia Cardoso. A behavioral biologist at the State University of Campinas, Brazil, she says it's a primitive reflex common to most animals; even rats laugh. She believes that too little laughter could have serious consequences for our mental, physical and social well-being.`;

const paragraphP2 = `Laughter is a universal phenomenon, and one of the most common things we do. We laugh many times a day, for many different reasons, but rarely think about it, and seldom consciously control it. We know so little about the different kinds and functions of laughter, and our interest really starts there. Why do we do it? What can laughter teach us about our positive emotions and social behavior? There's so much we don't know about how the brain contributes to emotion and many scientists think we can get at understanding this by studying laughter.`;

const paragraphP3 = `Only 10 or 20 percent of laughing is a response to humor. Most of the time, it's a message we send to other people, communicating joyful disposition, a willingness to bond and so on. It occupies a special place in social interaction and is a fascinating feature of our biology, with motor, emotional and cognitive components. Scientists study all kinds of emotions and behavior, but few focuses in this most basic ingredient.`;

const paragraphP4 = `Laughter gives us a clue that we have powerful systems in our brain which respond to pleasure, happiness and joy. It's also involved in events such as release of fear.`;

const paragraphP5 = `Many professionals have always focused on emotional behavior. Researchers spent many years investigating the neural basis of fear in rats, and came to laughter via that route. It is noticed that when they were alone, in an exposed environment, they were scared and quite uncomfortable. Back in a cage with others, they seemed much happier. It looked as if they played with one another real rough and tumble, and researchers wondered whether they were also laughing. The neurobiologist Jaak Panksepp had shown that juvenile rats make short vocalizations, pitched too high for humans to hear, during rough-and-tumble play. He thinks these are similar to laughter. This made us wonder about the roots of laughter.`;

const paragraphP6 = `We only have to look at the primate closest to humans to see that laughter is clearly not unique to us. This is not too surprising, because humans are only one among many social species and there's no reason why we should have a monopoly on laughter as a social tool. The great apes, such as chimpanzees, do something similar to humans.`;

const paragraphP7 = `They open their mouths wide, expose their teeth, retract the corners of their lips, and make loud and repetitive vocalizations in situations that tend to evoke human laughter, like when playing with one another or with humans, or when tickled. Laughter may even have evolved long before primates. We know that dogs at play have strange patterns of exhalation that differ from other sounds made during passive or aggressive confrontation.`;

const paragraphP8 = `But we need to be careful about over-interpreting panting behavior in animals at play. It's nice to think of it as homologous to human laughter, but it could just be something similar but with entirely different purposes and evolutionary advantages. Everything humans do has a function, and laughing is no exception. Its function is surely communication. We need to build social structures in order to live well in our society and evolution has selected laughter as a useful device for promoting social communication. In other words, it must have a survival advantage for the species.`;

const paragraphP9 = `The brain scans are usually done while people are responding to humorous material. Brainwave activity spread from the sensory processing area of the occipital lobe, the bit at the back of the brain that processes visual signals, to the brain's frontal lobe. It seems that the frontal lobe is involved in recognizing things as funny. The left side of the frontal lobe analyses the words and structure of jokes while the right side does the intellectual analyses required to "get" jokes. Finally, activity spreads to the motor areas of the brain controlling the physical task of laughing. Researchers also found out that these complex pathways involved in laughter from neurological illness and injury.`;

const paragraphP10 = `Sometimes after brain damage, tumors, stroke or brain disorders such as Parkinson's disease, people get "stonefaced" syndrome and can't laugh.`;

const paragraphP11 = `We are sure that laughter should differ between the sexes, particularly the uses to which the sexes put laughter as a social tool. For instance, women smile more than laugh, and are particularly adept at smiling and laughing with men as a kind of "social lubricant". It might even be possible that this has a biological origin, because women don't or can't use their physical size as a threat, which men do, even if unconsciously.`;

const paragraphP12 = `Laughter is believed to be one of the best medicines. For one thing, it's exercise. It activates the cardiovascular system, so heart rate and blood pressure increase, then the arteries dilate, causing blood pressure to fall again. Repeated short, strong contractions of the chest muscles, diaphragm and abdomen increase blood flow into our internal organs, and forced respiration — the ha! ha! — making sure that this blood is well oxygenated. Muscle tension decreases, and indeed we may temporarily lose control of our limbs, as in the expression "weak with laughter". It may also release brain endorphins, reducing sensitivity to pain and boosting endurance and pleasurable sensations. Some studies suggest that laughter affects the immune system by reducing the production of hormones associated with stress, and that when you laugh the immune system produces more T-cells. But no rigorously controlled studies have confirmed these effects. Laughter's social role is definitely important.`;

const paragraphP13 = `Today's children may be heading for a whole lot of social ills because their play and leisure time is so isolated and they lose out on lots of chances for laughter. When children stare at computer screens, rather than laughing with each other, this is at odds with what's natural for them. Natural social behavior in children is playful behavior, and in such situations laughter indicates that make-believe aggression is just fun, not for real, and this is an important way in which children form positive emotional bonds, gain new social skills and generally start to move from childhood to adulthood. Parents need to be very careful to ensure that their children play in groups, with both peers and adults, and laugh more.`;

const textSegments = ref([
    { id: 'part2-header', text: 'Reading Passage 2', offset: 0 },
    { id: 'part2-instruction', text: 'You should spend about 20 minutes on Questions 14–26 which are based on Reading Passage 2 below.', offset: 18 },
    { id: 'passage-title', text: 'The study of laughter', offset: 120 },
    // paragraph segments
    { id: 'para-p1', text: paragraphP1, offset: 142 },
    { id: 'para-p2', text: paragraphP2, offset: 142 + paragraphP1.length + 1 },
    { id: 'para-p3', text: paragraphP3, offset: 142 + paragraphP1.length + 1 + paragraphP2.length + 1 },
    { id: 'para-p4', text: paragraphP4, offset: 142 + paragraphP1.length + 1 + paragraphP2.length + 1 + paragraphP3.length + 1 },
    { id: 'para-p5', text: paragraphP5, offset: 142 + paragraphP1.length + 1 + paragraphP2.length + 1 + paragraphP3.length + 1 + paragraphP4.length + 1 },
    { id: 'para-p6', text: paragraphP6, offset: 1800 },
    { id: 'para-p7', text: paragraphP7, offset: 2100 },
    { id: 'para-p8', text: paragraphP8, offset: 2500 },
    { id: 'para-p9', text: paragraphP9, offset: 3000 },
    { id: 'para-p10', text: paragraphP10, offset: 3600 },
    { id: 'para-p11', text: paragraphP11, offset: 3800 },
    { id: 'para-p12', text: paragraphP12, offset: 4100 },
    { id: 'para-p13', text: paragraphP13, offset: 5000 },
    // Q14-15
    { id: 'q14-15-title', text: 'Questions 14–15', offset: 6000 },
    { id: 'q14-15-inst1', text: 'Which of the following claims and arguments are presented in the passage above? Choose TWO letters from A–E', offset: 6016 },
    { id: 'optA-text', text: 'All animals share the phenomenon of laughter.', offset: 6124 },
    { id: 'optB-text', text: 'Laughter can influence both adult and child health.', offset: 6170 },
    { id: 'optC-text', text: 'Laughter is not unique to humans.', offset: 6221 },
    { id: 'optD-text', text: 'Human mental, physical and social well-being are closely related.', offset: 6255 },
    { id: 'optE-text', text: 'Laughter teaches us how to behave.', offset: 6321 },
    // Q16-20
    { id: 'q16-20-title', text: 'Questions 16–20', offset: 6400 },
    { id: 'q16-20-inst1', text: 'Do the following statements agree with the claims of the writer in Reading Passage 2? On your answer sheet please write', offset: 6416 },
    { id: 'q16-20-yes', text: 'YES', offset: 6535 },
    { id: 'q16-20-yes-desc', text: 'if the statement agrees with the writer', offset: 6539 },
    { id: 'q16-20-no', text: 'NO', offset: 6578 },
    { id: 'q16-20-no-desc', text: 'if the statement contradicts with the writer', offset: 6581 },
    { id: 'q16-20-ng', text: 'NOT GIVEN', offset: 6626 },
    { id: 'q16-20-ng-desc', text: 'if there is no information about this in the passage.', offset: 6636 },
    { id: 'q16-num', text: '16', offset: 6690 },
    { id: 'q16-text', text: 'Laughter is one of the most common expressions shared by all humans.', offset: 6693 },
    { id: 'q17-num', text: '17', offset: 6762 },
    { id: 'q17-text', text: 'There are complicated systems in the human brain that take the responsibility of our emotions as happiness and fear.', offset: 6765 },
    { id: 'q18-num', text: '18', offset: 6880 },
    { id: 'q18-text', text: 'Communication is the only purpose of laughter.', offset: 6883 },
    { id: 'q19-num', text: '19', offset: 6930 },
    { id: 'q19-text', text: 'Reduced blood pressure would lead to a stimulated cardiovascular system.', offset: 6933 },
    { id: 'q20-num', text: '20', offset: 7005 },
    { id: 'q20-text', text: 'With the mass production of T-cells from the laughter, stress hormones would be deducted from the immune system.', offset: 7008 },
    // Q21-26
    { id: 'q21-26-title', text: 'Questions 21–26', offset: 7120 },
    { id: 'q21-26-inst1', text: 'Complete the summary below.', offset: 7136 },
    { id: 'q21-26-inst2', text: 'Choose NO MORE THAN THREE WORDS from the passage for each answer.', offset: 7164 },
    { id: 'q21-26-summary-1', text: 'Emotional behavior takes academic concerns. For years scientists have been examining the origin of', offset: 7230 },
    { id: 'q21-26-summary-2', text: 'and laughter that comes from the same route as rats. Within an open environment, they have been noticed to be', offset: 7330 },
    { id: 'q21-26-summary-3', text: 'when they are alone, and happier when they are back with others. Jaak Panksepp even found that rats make', offset: 7440 },
    { id: 'q21-26-summary-4', text: 'when they are in a chaotic state. It is well understood that humans are not the only living species that laughs and laughter may have developed long before', offset: 7550 },
    { id: 'q21-26-summary-5', text: 'Despite such facts, we need to pay attention when we explain various animal behavior, as they may express with differed', offset: 7700 },
    { id: 'q21-26-summary-6', text: 'and', offset: 7820 },
]);

const getPlainTextLength = (htmlText: string): number => {
    return htmlText.replace(/<[^>]*>/g, '').length;
};

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

const getAnswers = () => ({
    ...answers.value,
    q14_q15_selected: q1415Selected.value,
});

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
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
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
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Part 2' });
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
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32"
    >
        <!-- Part 2 Header -->
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part2-header" v-html="getHighlightedSegmentById('part2-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part2-instruction" v-html="getHighlightedSegmentById('part2-instruction')"></p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ===== READING PASSAGE ===== -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-shrink-0 border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <span class="select-text" data-segment-id="passage-title" v-html="getHighlightedSegmentById('passage-title')"></span>
                        </h2>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text sm:text-base">

                            <!-- Paragraph 1 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p1" v-html="getHighlightedSegment(paragraphP1)"></span>
                            </div>

                            <!-- Paragraph 2 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p2" v-html="getHighlightedSegment(paragraphP2)"></span>
                            </div>

                            <!-- Paragraph 3 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p3" v-html="getHighlightedSegment(paragraphP3)"></span>
                            </div>

                            <!-- Paragraph 4 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p4" v-html="getHighlightedSegment(paragraphP4)"></span>
                            </div>

                            <!-- Paragraph 5 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p5" v-html="getHighlightedSegment(paragraphP5)"></span>
                            </div>

                            <!-- Paragraph 6 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p6" v-html="getHighlightedSegment(paragraphP6)"></span>
                            </div>

                            <!-- Paragraph 7 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p7" v-html="getHighlightedSegment(paragraphP7)"></span>
                            </div>

                            <!-- Paragraph 8 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p8" v-html="getHighlightedSegment(paragraphP8)"></span>
                            </div>

                            <!-- Paragraph 9 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p9" v-html="getHighlightedSegment(paragraphP9)"></span>
                            </div>

                            <!-- Paragraph 10 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p10" v-html="getHighlightedSegment(paragraphP10)"></span>
                            </div>

                            <!-- Paragraph 11 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p11" v-html="getHighlightedSegment(paragraphP11)"></span>
                            </div>

                            <!-- Paragraph 12 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p12" v-html="getHighlightedSegment(paragraphP12)"></span>
                            </div>

                            <!-- Paragraph 13 -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="select-text" data-segment-id="para-p13" v-html="getHighlightedSegment(paragraphP13)"></span>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ===== QUESTIONS PANEL ===== -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- ===== Q14–15: Choose TWO ===== -->
                            <div
                                id="question-14"
                                class="relative p-6"
                                @mouseenter="hoveredQuestion = 14"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <!-- Single flag button for the whole Q14-15 block -->
                                <button
                                    v-if="hoveredQuestion === 14 || isQuestionFlagged(14) || isQuestionFlagged(15)"
                                    @click.stop="emit('toggleFlag', 14); emit('toggleFlag', 15)"
                                    class="absolute top-6 right-6 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(14) || isQuestionFlagged(15) ? 'border-red-400 text-red-500' : 'border-gray-400 text-gray-500'"
                                    :title="isQuestionFlagged(14) || isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>

                                <div class="mb-4 pr-8">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q14-15-title" v-html="getHighlightedSegmentById('q14-15-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-4">
                                        <span data-segment-id="q14-15-inst1" v-html="getHighlightedSegmentById('q14-15-inst1')"></span>
                                    </p>
                                    <p class="text-xs text-gray-500 mb-3">
                                        Selected: {{ q1415Selected.length }}/2
                                        <span v-if="q1415Selected.length === 2" class="text-green-600 font-medium"> ✓ Complete</span>
                                    </p>
                                </div>

                                <!-- Options A-E as checkboxes -->
                                <div class="space-y-2">
                                    <div
                                        v-for="option in q1415Options"
                                        :key="option.value"
                                        class="relative border border-gray-300 p-3 cursor-pointer transition-colors"
                                        :class="isQ1415Selected(option.value) ? 'border-gray-900 bg-gray-50' : 'hover:bg-gray-50'"
                                        @click="toggleQ1415Option(option.value)"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center border-2 rounded transition-colors"
                                                :class="isQ1415Selected(option.value) ? 'border-gray-900 bg-gray-900' : 'border-gray-400'">
                                                <svg v-if="isQ1415Selected(option.value)" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div class="flex items-start gap-2 flex-1">
                                                <span class="font-bold text-gray-900 text-sm shrink-0">{{ option.value }}</span>
                                                <span class="select-text text-sm text-gray-700"
                                                    :data-segment-id="`opt${option.value}-text`"
                                                    v-html="getHighlightedSegmentById(`opt${option.value}-text`)"
                                                ></span>
                                            </div>
                                        </div>
                                        <div v-if="q1415Selected.length === 2 && !isQ1415Selected(option.value)" class="absolute inset-0 bg-white opacity-40 pointer-events-none rounded"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== Q16–20: YES/NO/NOT GIVEN ===== -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="q16-20-title" v-html="getHighlightedSegmentById('q16-20-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q16-20-inst1" v-html="getHighlightedSegmentById('q16-20-inst1')"></span>
                                    </p>
                                    <div class="mt-2 space-y-0.5 text-sm text-gray-700">
                                        <p><span class="font-bold text-gray-900 w-24 inline-block" data-segment-id="q16-20-yes" v-html="getHighlightedSegmentById('q16-20-yes')"></span>&nbsp;&nbsp;<span data-segment-id="q16-20-yes-desc" v-html="getHighlightedSegmentById('q16-20-yes-desc')"></span></p>
                                        <p><span class="font-bold text-gray-900 w-24 inline-block" data-segment-id="q16-20-no" v-html="getHighlightedSegmentById('q16-20-no')"></span>&nbsp;&nbsp;<span data-segment-id="q16-20-no-desc" v-html="getHighlightedSegmentById('q16-20-no-desc')"></span></p>
                                        <p><span class="font-bold text-gray-900 w-24 inline-block" data-segment-id="q16-20-ng" v-html="getHighlightedSegmentById('q16-20-ng')"></span>&nbsp;&nbsp;<span data-segment-id="q16-20-ng-desc" v-html="getHighlightedSegmentById('q16-20-ng-desc')"></span></p>
                                    </div>
                                </div>

                                <div class="space-y-4">

                                    <!-- Q16 -->
                                    <div
                                        id="question-16"
                                        class="relative border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 16"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-if="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="emit('toggleFlag', 16)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q16-num" v-html="getHighlightedSegmentById('q16-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q16-text" v-html="getHighlightedSegmentById('q16-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q16" type="radio" value="YES" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">YES</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q16" type="radio" value="NO" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NO</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q16" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q17 -->
                                    <div
                                        id="question-17"
                                        class="relative border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 17"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-if="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="emit('toggleFlag', 17)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q17-num" v-html="getHighlightedSegmentById('q17-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q17-text" v-html="getHighlightedSegmentById('q17-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q17" type="radio" value="YES" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">YES</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q17" type="radio" value="NO" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NO</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q17" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q18 -->
                                    <div
                                        id="question-18"
                                        class="relative border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 18"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-if="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="emit('toggleFlag', 18)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q18-num" v-html="getHighlightedSegmentById('q18-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q18-text" v-html="getHighlightedSegmentById('q18-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q18" type="radio" value="YES" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">YES</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q18" type="radio" value="NO" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NO</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q18" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q19 -->
                                    <div
                                        id="question-19"
                                        class="relative border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 19"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-if="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                            @click.stop="emit('toggleFlag', 19)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q19-num" v-html="getHighlightedSegmentById('q19-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q19-text" v-html="getHighlightedSegmentById('q19-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q19" type="radio" value="YES" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">YES</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q19" type="radio" value="NO" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NO</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q19" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q20 -->
                                    <div
                                        id="question-20"
                                        class="relative border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 20"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-if="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                            @click.stop="emit('toggleFlag', 20)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                        <p class="mb-2.5 text-sm text-gray-700 pr-8">
                                            <span class="font-bold select-text" data-segment-id="q20-num" v-html="getHighlightedSegmentById('q20-num')"></span>
                                            &nbsp;
                                            <span class="select-text" data-segment-id="q20-text" v-html="getHighlightedSegmentById('q20-text')"></span>
                                        </p>
                                        <div class="flex items-center gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q20" type="radio" value="YES" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">YES</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q20" type="radio" value="NO" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NO</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q20" type="radio" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ===== Q21–26: Summary Completion ===== -->
                            <!-- Summary paragraph with inline blanks -->
<div class="border border-gray-200 bg-gray-50 p-4 text-sm leading-loose text-gray-700 select-text">
    <span data-segment-id="q21-26-summary-1" v-html="getHighlightedSegmentById('q21-26-summary-1')"></span>

    <!-- Q21 blank -->
    <span id="question-21" class="inline-block align-middle mx-1 group">
        <span class="relative inline-flex items-center gap-1">
            <input v-model="answers.q21" type="text" placeholder="21"
                class="summary-blank-input w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center text-sm text-gray-900 placeholder:font-bold placeholder:text-gray-500 placeholder:text-center focus:border-gray-900 focus:outline-none" />
            <button @click.stop="emit('toggleFlag', 21)"
                class="inline-flex h-5 w-5 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(21) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-0 group-hover:opacity-100'"
                :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark'">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
            </button>
        </span>
    </span>

    <span data-segment-id="q21-26-summary-2" v-html="getHighlightedSegmentById('q21-26-summary-2')"></span>

    <!-- Q22 blank -->
    <span id="question-22" class="inline-block align-middle mx-1 group">
        <span class="relative inline-flex items-center gap-1">
            <input v-model="answers.q22" type="text" placeholder="22"
                class="summary-blank-input w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center text-sm text-gray-900 placeholder:font-bold placeholder:text-gray-500 placeholder:text-center focus:border-gray-900 focus:outline-none" />
            <button @click.stop="emit('toggleFlag', 22)"
                class="inline-flex h-5 w-5 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(22) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-0 group-hover:opacity-100'"
                :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark'">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
            </button>
        </span>
    </span>

    <span data-segment-id="q21-26-summary-3" v-html="getHighlightedSegmentById('q21-26-summary-3')"></span>

    <!-- Q23 blank -->
    <span id="question-23" class="inline-block align-middle mx-1 group">
        <span class="relative inline-flex items-center gap-1">
            <input v-model="answers.q23" type="text" placeholder="23"
                class="summary-blank-input w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center text-sm text-gray-900 placeholder:font-bold placeholder:text-gray-500 placeholder:text-center focus:border-gray-900 focus:outline-none" />
            <button @click.stop="emit('toggleFlag', 23)"
                class="inline-flex h-5 w-5 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(23) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-0 group-hover:opacity-100'"
                :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark'">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
            </button>
        </span>
    </span>

    <span data-segment-id="q21-26-summary-4" v-html="getHighlightedSegmentById('q21-26-summary-4')"></span>

    <!-- Q24 blank -->
    <span id="question-24" class="inline-block align-middle mx-1 group">
        <span class="relative inline-flex items-center gap-1">
            <input v-model="answers.q24" type="text" placeholder="24"
                class="summary-blank-input w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center text-sm text-gray-900 placeholder:font-bold placeholder:text-gray-500 placeholder:text-center focus:border-gray-900 focus:outline-none" />
            <button @click.stop="emit('toggleFlag', 24)"
                class="inline-flex h-5 w-5 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(24) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-0 group-hover:opacity-100'"
                :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark'">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
            </button>
        </span>
    </span>

    <span>. </span>
    <span data-segment-id="q21-26-summary-5" v-html="getHighlightedSegmentById('q21-26-summary-5')"></span>

    <!-- Q25 blank -->
    <span id="question-25" class="inline-block align-middle mx-1 group">
        <span class="relative inline-flex items-center gap-1">
            <input v-model="answers.q25" type="text" placeholder="25"
                class="summary-blank-input w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center text-sm text-gray-900 placeholder:font-bold placeholder:text-gray-500 placeholder:text-center focus:border-gray-900 focus:outline-none" />
            <button @click.stop="emit('toggleFlag', 25)"
                class="inline-flex h-5 w-5 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(25) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-0 group-hover:opacity-100'"
                :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark'">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
            </button>
        </span>
    </span>

    <span data-segment-id="q21-26-summary-6" v-html="getHighlightedSegmentById('q21-26-summary-6')"></span>

    <!-- Q26 blank -->
    <span id="question-26" class="inline-block align-middle mx-1 group">
        <span class="relative inline-flex items-center gap-1">
            <input v-model="answers.q26" type="text" placeholder="26"
                class="summary-blank-input w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center text-sm text-gray-900 placeholder:font-bold placeholder:text-gray-500 placeholder:text-center focus:border-gray-900 focus:outline-none" />
            <button @click.stop="emit('toggleFlag', 26)"
                class="inline-flex h-5 w-5 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(26) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-0 group-hover:opacity-100'"
                :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark'">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
            </button>
        </span>
    </span>
</div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>


        <!-- Context Menu for Highlighting -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
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
                <div
                    class="delete-highlight-tooltip fixed z-9999"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                >
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>
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
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
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
        </Teleport>

        <!-- Note Input Modal -->
        <Teleport to="body">
            <div
                v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="rounded border border-gray-300 bg-white px-3 py-0.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</button>
                    <button @click="saveNote" class="rounded bg-gray-900 px-3 py-0.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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

.passage-panel { width: 100%; }
.answer-panel { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel { width: calc(100% - var(--panel-width) - 0.5rem); }
}

mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; }

.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question { animation: highlightPulse 2s ease-in-out; }

@keyframes highlightPulse {
    0% { background-color: rgba(0, 0, 0, 0.1); transform: scale(1); }
    50% { background-color: rgba(0, 0, 0, 0.2); transform: scale(1.02); }
    100% { background-color: rgba(0, 0, 0, 0.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

.summary-blank-input {
    vertical-align: middle;
    min-width: 8rem;
}

.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}

.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-bottom: 8px solid white; filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db; z-index: -1;
}

.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}

.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-icon { color: #6b7280; }
.note-hover-tooltip .note-tooltip-text { line-height: 1.4; }
.note-hover-tooltip .note-delete-btn { color: #9ca3af; }
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
.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>
