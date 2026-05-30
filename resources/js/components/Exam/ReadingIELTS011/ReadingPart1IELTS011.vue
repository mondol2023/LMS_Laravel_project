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

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part2-panel-width';
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
});

// Paragraph options for Q1-5
const paragraphOptions = ['A', 'B', 'C', 'D', 'E', 'F'];

// University options for Q6-9
const universityOptions = [
    { value: 'A', label: 'University of Albany' },
    { value: 'B', label: 'University of Leeds' },
    { value: 'C', label: 'University of London' },
];

// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
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

// ── PASSAGE TEXT ─────────────────────────────────────────────────
const buildTextSegments = () => {
    const segments: { id: string; text: string; offset: number }[] = [];
    let currentOffset = 0;

    const addSegment = (id: string, text: string) => {
        const plainTextLength = text.replace(/<[^>]*>/g, '').length;
        segments.push({ id, text, offset: currentOffset });
        currentOffset += plainTextLength + 500;
    };

    addSegment('section2', 'Part 1');
    addSegment('questions-1-13', 'You should spend about 20 minutes on Questions 1–13 which are based on Reading Passage 1 below.');
    addSegment('yawning-title', 'The Natural of Yawning');

    addSegment('para-a', `While fatigue, drowsiness or boredom easily bring on yawns, scientists are discovering there is more to yawning than most people think. Not much is known about why we yawn or if it serves any useful function. People have already learned that yawning can be infectious. "Contagious yawning" is the increase in likelihood that you will yawn after watching or hearing someone else yawn, but not much is known about the underlying causes, and very little research has been done on the subject. However, scientists at the University of Albany, as well as the University of Leeds and the University of London have done some exploration.`);
    addSegment('para-b', `It is commonly believed that people yawn as a result of being sleepy or tired because they need oxygen. However, the latest research shows that a yawn can help cool the brain and help it work more effectively, which is quite different from the popular belief that yawning promotes sleep and is a sign of tiredness. Dr Andrew Gallup and his colleagues at the University of Albany in New York State said their experiments on 44 students showed that raising or lowering oxygen and carbon dioxide levels in the blood did not produce that reaction. In the study participants were shown videos of people laughing and yawning, and researchers counted how many times the volunteers responded to the "contagious yawns". The researchers found that those who breathed through the nose rather than the mouth were less likely to yawn when watching a video of other people yawning. The same effect was found among those who held a cool pack to their forehead, whereas those who held a warm pack yawned while watching the video. Since yawning occurs when brain temperature rises, sending cool blood to the brain serves to maintain the best levels of mental efficiency.`);
    addSegment('para-c', `Yawning is universal to humans and many animals. Cats, dogs and fish yawn just like humans do, but they yawn spontaneously. Only humans and chimpanzees, our closest relatives in the animal kingdom, have shown definite contagious yawning. Though much of yawning is due to suggestibility, sometimes people do not need to actually see a person yawn to involuntarily yawn themselves: hearing someone yawning or even reading about yawning can cause the same reaction.`);
    addSegment('para-d', `However, contagious yawning goes beyond mere suggestibility. Recent studies show that contagious yawning is also related to our predisposition toward empathy— the ability to understand and connect with others' emotional states. So empathy is important, sure, but how could it possibly be related to contagious yawning? Leave it up to psychologists at Leeds University in England to answer that. In their study, researchers selected 40 psychology students and 40 engineering students. Generally, psychology students are more likely to feel empathy for others, while engineering students are thought to be concerned with objects and science. Each student was made to wait individually in a waiting room, along with an undercover assistant who yawned 10 times in as many minutes. The students were then administered an emotional quotient test: students were shown 40 images of eyes and asked what emotion each one displayed. The results of the test support the idea that contagious yawning is linked to empathy. The psychology students—whose future profession requires them to focus on others—yawned contagiously an average of 5.5 times in the waiting room and scored 28 out of 40 on the emotional test. The engineering students—who tend to focus on things like numbers and systems—yawned an average of 1.5 times and scored 25.5 out of 40 on the subsequent test. The difference doesn't sound like much, but researchers consider it significant. Strangely enough, women, who are generally considered more emotionally attuned, didn't score any higher than men.`);
    addSegment('para-e', `Another study, led by Atsushi Senju, a cognitive researcher at the University of London, also sought to answer that question. People with autism disorder are considered to be developmentally impaired emotionally. Autistics have trouble connecting with others and find it difficult to feel empathy. Since autistics have difficulty feeling empathy, then they shouldn't be susceptible to contagious yawning. To find out, Senju and his colleagues placed 49 kids aged 7 to 15 in a room with a television. 24 of the test subjects had been diagnosed with autism spectrum disorder, the other 25 were non-autistic kids. The test subjects were shown short clips of people yawning as well as clips of people opening their mouths but not yawning. While the kids with autism had the same lack of reaction to both kinds of clips, the non-autistic kids yawned more after the clips of people yawning.`);
    addSegment('para-f', `There also have been studies that suggest yawning, especially psychological "contagious" yawning, may have developed as a way of keeping a group of animals alert and bonding members of a group into a more unit one. If an animal is drowsy or bored, it may not be as alert as it should to be prepared to spring into action and its yawning is practically saying, "Hey, I need some rest, you stay awake". Therefore, a contagious yawn could be an instinctual reaction to a signal from one member of the herd reminding the others to stay alert when danger comes. So the theory suggests evidence that yawning comes from the evolution of early humans to be ready to physically exert themselves at any given moment.`);

    // Questions 1-5
    addSegment('q1-5-title', 'Questions 1–5');
    addSegment('instructions-1-5', 'Read paragraphs A–F. Which paragraph contains the following information?');
    addSegment('note-1-5', 'NB You may use any letter more than once.');
    addSegment('q1-statement', 'Humans\' imaginations can cause yawning.');
    addSegment('q2-statement', 'Research shows that yawning is closely related to occupations.');
    addSegment('q3-statement', 'An overview of the latest research in yawning.');
    addSegment('q4-statement', 'Yawning is used to regulate brain temperature.');
    addSegment('q5-statement', 'Scientists discovered some evidence disproving the early understanding of yawning.');

    // Questions 6-9
    addSegment('q6-9-title', 'Questions 6–9');
    addSegment('instructions-6-9', 'Match each of the following research results with the university which it comes from.');
    addSegment('note-6-9', 'NB You may use any letter more than once.');
    addSegment('univ-a-def', 'University of Albany');
    addSegment('univ-b-def', 'University of Leeds');
    addSegment('univ-c-def', 'University of London');
    addSegment('q6-statement', 'There is no gender difference in the cause of yawning.');
    addSegment('q7-statement', 'People with certain disorders are less likely to be affected by other people yawning.');
    addSegment('q8-statement', 'Yawning is associated with the way people breathe.');
    addSegment('q9-statement', 'People who are trained to feel empathy for others are more likely to yawn than those who are untrained.');

    // Questions 10-13
    addSegment('q10-13-title', 'Questions 10–13');
    addSegment('instructions-10-13', 'Complete the summary below. Choose ONE WORD from the passage for each answer.');
    addSegment('summary-10-before', 'Another theory shows that yawning is used for');
    addSegment('summary-10-after', 'individuals into a tighter social unit.');
    addSegment('summary-11-before', 'Alternatively, yawning can help increase alertness of group members in case');
    addSegment('summary-11-after', 'is close.');
    addSegment('summary-12-before', 'For example, yawning signals that a member of the group needs some');
    addSegment('summary-12-after', 'and requires the others to stay aware of the surrounding situation.');
    addSegment('summary-13-before', 'This theory proves that yawning is only a spontaneous behaviour resulting from some part of a simple');
    addSegment('summary-13-after', 'system in early humans.');

    return segments;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (!rect) return;

        const vw = window.innerWidth;
        const menuHeight = 50;
        contextMenuPosition.value = {
            x: Math.min(Math.max(rect.left + rect.width / 2, 90), vw - 90),
            y: Math.max(rect.top - menuHeight - 8, 10),
        };
        showContextMenu.value = true;

        if (!selection || !range) return;

        const findSegmentEl = (node: Node | null): Element | null => {
            let current = node;
            if (current?.nodeType === Node.TEXT_NODE) current = current.parentNode;
            while (current) {
                if ((current as Element).hasAttribute?.('data-segment-id')) return current as Element;
                current = (current as Element).parentElement ?? null;
            }
            return null;
        };

        const startSegmentEl = findSegmentEl(range.startContainer);
        const endSegmentEl   = findSegmentEl(range.endContainer);
        if (!startSegmentEl) return;

        const startSegId = startSegmentEl.getAttribute('data-segment-id')!;
        const startSeg   = textSegments.value.find(s => s.id === startSegId);
        if (!startSeg) return;

        const preStartRange = document.createRange();
        preStartRange.selectNodeContents(startSegmentEl);
        preStartRange.setEnd(range.startContainer, range.startOffset);
        const startPlain = preStartRange.toString().length;
        const globalStart = startSeg.offset + startPlain;

        let globalEnd: number;
        if (!endSegmentEl || endSegmentEl === startSegmentEl) {
            globalEnd = globalStart + selected.length;
        } else {
            const endSegId = endSegmentEl.getAttribute('data-segment-id')!;
            const endSeg   = textSegments.value.find(s => s.id === endSegId);
            if (!endSeg) {
                globalEnd = globalStart + selected.length;
            } else {
                const preEndRange = document.createRange();
                preEndRange.selectNodeContents(endSegmentEl);
                preEndRange.setEnd(range.endContainer, range.endOffset);
                const endPlain = preEndRange.toString().length;
                globalEnd = endSeg.offset + endPlain;
            }
        }

        if (globalEnd <= globalStart) return;
        selectedText.value   = selected;
        selectionRange.value = { start: globalStart, end: globalEnd };
    }, 10);
};

const textSegments = ref(buildTextSegments());

const segmentMap = computed(() => {
    const map = new Map<string, { id: string; text: string; offset: number }>();
    for (const segment of textSegments.value) map.set(segment.text, segment);
    return map;
});

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

const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(plainText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return plainText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ];

    const sorted = annotations.sort((a, b) => b.start - a.start);
    let result = plainText;

    for (const annotation of sorted) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd   = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);
        if (plainStart < plainEnd) {
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd   = plainToHtmlOffset(result, plainEnd);
            const before    = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after     = result.substring(htmlEnd);
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
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (!rect) return;

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

        if (!selection || !range) return;

        let node: Node | null = range.startContainer;
        let segmentEl: Element | null = null;
        while (node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                const el = node as Element;
                if (el.hasAttribute('data-segment-id')) { segmentEl = el; break; }
            }
            node = node.parentNode;
        }

        if (!segmentEl) { showContextMenu.value = false; return; }

        const segmentId = segmentEl.getAttribute('data-segment-id')!;
        const segment   = textSegments.value.find((s) => s.id === segmentId);
        if (!segment) { showContextMenu.value = false; return; }

        const preSelectionRange = document.createRange();
        preSelectionRange.selectNodeContents(segmentEl);
        preSelectionRange.setEnd(range.startContainer, range.startOffset);
        const plainTextOffset = preSelectionRange.toString().length;

        selectedText.value   = selected;
        selectionRange.value = { start: segment.offset + plainTextOffset, end: segment.offset + plainTextOffset + selected.length };
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

    const modalWidth  = 320;
    const modalHeight = 180;
    const padding     = 10;

    const selection = window.getSelection();
    let x: number, y: number;

    if (selection && selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const rect  = range.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }

    const viewportWidth  = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const halfWidth      = modalWidth / 2;

    if (x - halfWidth < padding) x = halfWidth + padding;
    else if (x + halfWidth > viewportWidth - padding) x = viewportWidth - halfWidth - padding;

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect  = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) y = padding;
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value     = true;
    showContextMenu.value   = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLTextAreaElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd   = selectionRange.value.end;

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
    showDeleteTooltip.value  = false;
    highlightToDelete.value  = null;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const handleContentClick = (event: MouseEvent) => {
    const target        = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value     = parseInt(highlightId, 10);
            showDeleteTooltip.value     = true;
            showContextMenu.value       = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value)   showContextMenu.value = false;
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
    const target   = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note      = notes.value.find((n) => n.id === noteIdNum);
            if (note) {
                const rect         = noteMark.getBoundingClientRect();
                const tooltipHeight = 50;
                let y = rect.top - tooltipHeight - 8;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value       = noteIdNum;
                hoveredNoteText.value     = note.note;
                showNoteTooltip.value     = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target        = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (relatedTarget?.closest('.note-hover-tooltip')) return;
    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value   = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value   = null;
    hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value   = null;
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
    const offsetX       = event.clientX - containerRect.left;
    const newLeftWidth  = (offsetX / containerRect.width) * 100;
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

// Expose methods for parent component
const getAnswers = () => answers.value;
defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <!-- Part Header -->
        <div class="mx-5 mt-4 bg-gray-100 px-4 py-2" style="background-color: #F1F2EC;">
            <p class="text-sm font-bold text-gray-900 select-text">
                <span data-segment-id="section2" v-html="getHighlightedSegmentById('section2')"></span>
            </p>
            <p class="text-sm text-gray-700 select-text">
                <span data-segment-id="questions-1-13" v-html="getHighlightedSegmentById('questions-1-13')"></span>
            </p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ══════════════════════════════════════════════════
                     READING PASSAGE (left panel)
                ═══════════════════════════════════════════════════ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">

                        <!-- Passage title -->
                        <div class="mb-4 text-center text-lg font-bold">
                            <p class="select-text">
                                <span data-segment-id="yawning-title"
                                    v-html="getHighlightedSegmentById('yawning-title')"></span>
                            </p>
                        </div>

                        <!-- Passage body -->
                        <div class="space-y-4" :style="contentZoom">

                            <!-- Paragraph A -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">A </span>
                                    <span class="select-text" data-segment-id="para-a"
                                        v-html="getHighlightedSegmentById('para-a')"></span>
                                </div>
                            </div>

                            <!-- Paragraph B -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">B </span>
                                    <span class="select-text" data-segment-id="para-b"
                                        v-html="getHighlightedSegmentById('para-b')"></span>
                                </div>
                            </div>

                            <!-- Paragraph C -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">C </span>
                                    <span class="select-text" data-segment-id="para-c"
                                        v-html="getHighlightedSegmentById('para-c')"></span>
                                </div>
                            </div>

                            <!-- Paragraph D -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">D </span>
                                    <span class="select-text" data-segment-id="para-d"
                                        v-html="getHighlightedSegmentById('para-d')"></span>
                                </div>
                            </div>

                            <!-- Paragraph E -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">E </span>
                                    <span class="select-text" data-segment-id="para-e"
                                        v-html="getHighlightedSegmentById('para-e')"></span>
                                </div>
                            </div>

                            <!-- Paragraph F -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">F </span>
                                    <span class="select-text" data-segment-id="para-f"
                                        v-html="getHighlightedSegmentById('para-f')"></span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ── Resize Handle ─────────────────────────────── -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════
                     QUESTIONS PANEL (right panel)
                ═══════════════════════════════════════════════════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════════════════════════════════════════
                                 QUESTIONS 1–5 — Paragraph Matching
                            ═════════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="q1-5-title" class="select-text"
                                                v-html="getHighlightedSegmentById('q1-5-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-1-5" class="select-text"
                                            v-html="getHighlightedSegmentById('instructions-1-5')"></span>
                                    </p>
                                    <p class="mb-4 text-sm italic text-gray-500">
                                        <span data-segment-id="note-1-5" class="select-text"
                                            v-html="getHighlightedSegmentById('note-1-5')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">

                                    <!-- Q1 -->
                                    <div id="question-1" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 1"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">1.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q1-statement"
                                                    v-html="getHighlightedSegmentById('q1-statement')"></span>
                                                <select v-model="answers.q1"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in paragraphOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                            @click.stop="toggleFlag(1)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q2 -->
                                    <div id="question-2" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 2"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">2.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q2-statement"
                                                    v-html="getHighlightedSegmentById('q2-statement')"></span>
                                                <select v-model="answers.q2"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in paragraphOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                            @click.stop="toggleFlag(2)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q3 -->
                                    <div id="question-3" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 3"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">3.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q3-statement"
                                                    v-html="getHighlightedSegmentById('q3-statement')"></span>
                                                <select v-model="answers.q3"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in paragraphOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                            @click.stop="toggleFlag(3)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q4 -->
                                    <div id="question-4" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 4"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">4.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q4-statement"
                                                    v-html="getHighlightedSegmentById('q4-statement')"></span>
                                                <select v-model="answers.q4"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in paragraphOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                            @click.stop="toggleFlag(4)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q5 -->
                                    <div id="question-5" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 5"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">5.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q5-statement"
                                                    v-html="getHighlightedSegmentById('q5-statement')"></span>
                                                <select v-model="answers.q5"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in paragraphOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                            @click.stop="toggleFlag(5)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ════════════════════════════════════════
                                 QUESTIONS 6–9 — University Matching
                            ═════════════════════════════════════════ -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q6-9-title" class="text-gray-700 select-text"
                                                v-html="getHighlightedSegmentById('q6-9-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-6-9" class="select-text"
                                            v-html="getHighlightedSegmentById('instructions-6-9')"></span>
                                    </p>
                                    <p class="mb-4 text-sm italic text-gray-500">
                                        <span data-segment-id="note-6-9" class="select-text"
                                            v-html="getHighlightedSegmentById('note-6-9')"></span>
                                    </p>
                                    <!-- University key -->
                                    <div class="mb-4 border border-gray-300 p-3 text-sm">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-gray-900">A</span>
                                            <span class="select-text" data-segment-id="univ-a-def"
                                                v-html="getHighlightedSegmentById('univ-a-def')"></span>
                                        </div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-gray-900">B</span>
                                            <span class="select-text" data-segment-id="univ-b-def"
                                                v-html="getHighlightedSegmentById('univ-b-def')"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-900">C</span>
                                            <span class="select-text" data-segment-id="univ-c-def"
                                                v-html="getHighlightedSegmentById('univ-c-def')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">

                                    <!-- Q6 -->
                                    <div id="question-6" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 6"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">6.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q6-statement"
                                                    v-html="getHighlightedSegmentById('q6-statement')"></span>
                                                <select v-model="answers.q6"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in universityOptions" :key="opt.value" :value="opt.value">{{ opt.value }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                            @click.stop="toggleFlag(6)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q7 -->
                                    <div id="question-7" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 7"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">7.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q7-statement"
                                                    v-html="getHighlightedSegmentById('q7-statement')"></span>
                                                <select v-model="answers.q7"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in universityOptions" :key="opt.value" :value="opt.value">{{ opt.value }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                            @click.stop="toggleFlag(7)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q8 -->
                                    <div id="question-8" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 8"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">8.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q8-statement"
                                                    v-html="getHighlightedSegmentById('q8-statement')"></span>
                                                <select v-model="answers.q8"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in universityOptions" :key="opt.value" :value="opt.value">{{ opt.value }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                            @click.stop="toggleFlag(8)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q9 -->
                                    <div id="question-9" class="group relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 9"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="shrink-0 text-base font-bold text-gray-900">9.</span>
                                            <div class="flex flex-1 flex-wrap items-center gap-2">
                                                <span class="text-base text-gray-900 select-text flex-1 min-w-0"
                                                    data-segment-id="q9-statement"
                                                    v-html="getHighlightedSegmentById('q9-statement')"></span>
                                                <select v-model="answers.q9"
                                                    class="shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm font-bold focus:outline-none focus:border-black cursor-pointer">
                                                    <option value="">select</option>
                                                    <option v-for="opt in universityOptions" :key="opt.value" :value="opt.value">{{ opt.value }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="toggleFlag(9)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ════════════════════════════════════════
                                 QUESTIONS 10–13
                                 Inline summary completion with text inputs
                            ═════════════════════════════════════════ -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q10-13-title" class="text-gray-700 select-text"
                                                v-html="getHighlightedSegmentById('q10-13-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="instructions-10-13" class="text-gray-700 select-text"
                                            v-html="getHighlightedSegmentById('instructions-10-13')"></span>
                                    </p>
                                </div>

                                <div class="">

                                    <!-- Q10 -->
                                    <div id="question-10" class="relative mb-6 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 10"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0">•</span>
                                            <p class="text-sm leading-relaxed text-gray-800">
                                                <span data-segment-id="summary-10-before" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-10-before')"></span>
                                                <input type="text" v-model="answers.q10"
                                                    spellcheck="false" autocomplete="off" placeholder="10"
                                                    class="mx-2 inline-block w-32 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                                <span data-segment-id="summary-10-after" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-10-after')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q11 -->
                                    <div id="question-11" class="relative mb-6 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 11"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0">•</span>
                                            <p class="text-sm leading-relaxed text-gray-800">
                                                <span data-segment-id="summary-11-before" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-11-before')"></span>
                                                <input type="text" v-model="answers.q11"
                                                    spellcheck="false" autocomplete="off" placeholder="11"
                                                    class="mx-2 inline-block w-32 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                                <span data-segment-id="summary-11-after" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-11-after')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q12 -->
                                    <div id="question-12" class="relative mb-6 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 12"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0">•</span>
                                            <p class="text-sm leading-relaxed text-gray-800">
                                                <span data-segment-id="summary-12-before" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-12-before')"></span>
                                                <input type="text" v-model="answers.q12"
                                                    spellcheck="false" autocomplete="off" placeholder="12"
                                                    class="mx-2 inline-block w-32 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                                <span data-segment-id="summary-12-after" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-12-after')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q13 -->
                                    <div id="question-13" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 shrink-0">•</span>
                                            <p class="text-sm leading-relaxed text-gray-800">
                                                <span data-segment-id="summary-13-before" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-13-before')"></span>
                                                <input type="text" v-model="answers.q13"
                                                    spellcheck="false" autocomplete="off" placeholder="13"
                                                    class="mx-2 inline-block w-32 font-bold text-center border-2 border-gray-900 px-2 py-1 text-sm focus:border-black focus:outline-none" />
                                                <span data-segment-id="summary-13-after" class="text-gray-700 select-text"
                                                    v-html="getHighlightedSegmentById('summary-13-after')"></span>
                                            </p>
                                        </div>
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
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

    <!-- ======================== PORTALS ======================== -->
    <Teleport to="body">

        <!-- Context Menu (Highlight + Note) — speech-bubble style -->
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
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
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
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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

.passage-panel { width: 100%; }
.answer-panel  { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel  { width: calc(100% - var(--panel-width) - 0.5rem); }
}

.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question { animation: highlightPulse 2s ease-in-out; }

@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,0.2); transform: scale(1.02); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}

.highlight-tooltip .tooltip-arrow {
    position: absolute;
    left: 50%;
    bottom: -8px;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: '';
    position: absolute;
    left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: '';
    position: absolute;
    left: -9px; top: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db;
    z-index: -1;
}

.note-hover-tooltip .tooltip-arrow-down {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: '';
    position: absolute;
    left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
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
.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>