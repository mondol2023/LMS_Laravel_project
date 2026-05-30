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

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const isBookmarkVisible = (questionNumber: number): boolean => {
    return hoveredQuestion.value === questionNumber || isQuestionFlagged(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-section3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// ── Answers ───────────────────────────────────────────────────────────────────
const answers = ref({
    // Q28-33: match description to term (A–J)
    q28: '',
    q29: '',
    q30: '',
    q31: '',
    q32: '',
    q33: '',
    // Q34-37: short answer
    q34: '',
    q35: '',
    q36: '',
    q37: '',
    // Q38-40: summary completion
    q38: '',
    q39: '',
    q40: '',
});

// ── Q28-33: Match drag-and-drop ───────────────────────────────────────────────
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const termOptions = [
    { value: 'A', label: 'Trunk shot' },
    { value: 'B', label: 'Dutch tilt' },
    { value: 'C', label: 'Establishing shot' },
    { value: 'D', label: 'Money shot' },
    { value: 'E', label: 'American shot' },
    { value: 'F', label: 'Long shot' },
    { value: 'G', label: 'Extreme close-up' },
    { value: 'H', label: 'Third-person shot' },
    { value: 'I', label: 'First-person shot' },
    { value: 'J', label: 'Close-up' },
];

const availableTermOptions = computed(() => {
    const used = [
        answers.value.q28, answers.value.q29, answers.value.q30,
        answers.value.q31, answers.value.q32, answers.value.q33,
    ].filter(Boolean);
    return termOptions.filter(opt => !used.includes(opt.value));
});

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
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
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

// Q28-33 descriptions
const descriptions = [
    { qNum: 28, text: 'A group of people, full length body shot' },
    { qNum: 29, text: 'Two people, only one facing camera, head and shoulders shot' },
    { qNum: 30, text: 'Distance shot of central city, from the air' },
    { qNum: 31, text: 'A single person, head and shoulders, off-centre angle shot' },
    { qNum: 32, text: 'Lone pedestrian, walking a city street' },
    { qNum: 33, text: 'A flaming bus, about to crash' },
];

// ── Passage text ──────────────────────────────────────────────────────────────
const passageText = ref(
    `<strong>Making the Cut</strong>

When we talk about how films convey meaning we tend to refer to acting, music, dialogue, props and narrative developments, but often forgotten is the visual essence of a film itself, which is the cutting together of moving images – "motion pictures" – each one carefully tailored to meet a particular need or purpose.

Most films and many important scenes within them open with an establishing shot. Typically this shot precedes our introduction to the main characters by presenting us with the locale in which the scene's action or dialogue is about to occur. Occasionally, however, a director will use an establishing shot with another goal in mind. An opening view of a thousand soldiers parading in synchronized fashion might have little to reveal about the film's geography, for example, but it does inform the audience that ideas about discipline and conformity are likely to arise in the material that follows. In this way, establishing shots can also introduce a film's theme.

After an establishing shot, most directors choose a long shot in order to progress the narrative. This type of shot displays the entire human physique in relation to its surroundings, so it is ideal for bridging the narrative divide between location and individual activity. A long shot is therefore often used to centre on a pivotal character in the scene. A film might begin with an establishing shot of bleak, snowy mountains and then cut to a long shot of a lone skier, for example, or a sweeping panorama of a bustling metropolis could segue into a street view of someone entering a building.

From here the door is wide open for directors to choose whichever shots will enhance the narration. Close-up shots are popular in suspense sequences – a handgun being loaded, a doorknob being turned, the startled expression of someone freshly roused from sleep. Confining the visual field in this way adds to the viewer's apprehension. Dramatic films will probably want to emphasise character interaction. The third-person shot – in which a third of the frame consists of a rear view of a person's upper torso and head – can be effectively utilised here. This shot encourages us to actually slip into the persona of that character, and vicariously live through their experiences.

A number of special-purpose shots are used quite rarely – once, if at all, in most films. One such type is the money shot. A money shot has no specific technical features or content, but is typically the most expensive element of a film's production values and comes with a cost massively disproportionate to its screen time (which may be limited to just a brief glimpse). Because of its spectacular, extravagant nature, however, the money shot is a major revenue generator and is widely exploited for use in promotional materials. Money shots are most popular amongst – but not limited to – high visual-impact genres such as action, war, thriller and disaster films.

But more affordable shots can also add an interesting twist to the story. The Dutch tilt can depict a character in a state of psychological unease by shooting them from a jaunty angle. In this way they appear literally and metaphorically unbalanced. A trunk shot often shows a small group of characters peering into the trunk of a vehicle. It is filmed from a perspective within the trunk itself, although frequently to avoid camera damage directors will simply place a detached piece of trunk door in the corner of the frame. This shot was a favourite of Quentin Tarantino and has been used in many crime and gangster films, often as a first-person shot through the eyes of someone who is tied up and lying inside the vehicle. A shot that has gained traction in avant-garde circles is the extreme close-up. This is when a single detail of the subject fills up the entire frame. Alfred Hitchcock famously used an extreme close-up in 'Psycho', when he merged a shot of a shower drain into a view of a victim's eye. It has also been used in Westerns to depict tension between duelling gunmen eyeing each other up before a shoot out.

Not all types of shots are used in order to enhance the narrative. Sometimes financial restrictions or technical limitations are a more pressing concern, especially for low-budget film makers. In the early murder mysteries of the 1920s and 1930s, the American shot – which acquired its name from French critics who referred to a "plan américain" – was used widely for its ability to present complex dialogue scenes without alterations in camera position. Using the American shot, directors have their cast assemble in single file while discussing key plot points. The result is an efficiently produced scene that conveys all relevant information, but the trade off is a natural tone. Because few people in real life would ever associate in such an awkward manner, American shots tend to result in a hammy, stiff feel to the production.`
);

// ── Text segments ─────────────────────────────────────────────────────────────
const allSegments = [
    { id: 'part-header', text: 'SECTION 3' },
    { id: 'part-instructions', text: 'Questions 28–40' },
    { id: 'passage-intro', text: 'Read the text below and answer Questions 28–40.' },
    { id: 'passage', text: passageText.value },

    // Q28-33
    { id: 'q28-33-title', text: 'Questions 28–33' },
    { id: 'q28-33-instructions', text: 'Look at the following descriptions (Questions 28–33) and the list of terms below. Match each description with the correct term, A–J.' },
    { id: 'q28-33-write', text: 'Write the correct letter, A–J, in boxes 28–33 on your answer sheet.' },
    { id: 'q28-text', text: 'A group of people, full length body shot' },
    { id: 'q29-text', text: 'Two people, only one facing camera, head and shoulders shot' },
    { id: 'q30-text', text: 'Distance shot of central city, from the air' },
    { id: 'q31-text', text: 'A single person, head and shoulders, off-centre angle shot' },
    { id: 'q32-text', text: 'Lone pedestrian, walking a city street' },
    { id: 'q33-text', text: 'A flaming bus, about to crash' },

    // Q34-37
    { id: 'q34-37-title', text: 'Questions 34–37' },
    { id: 'q34-37-instructions', text: 'Answer the questions below. Choose NO MORE THAN THREE WORDS from the passage for each answer. Write your answers in boxes 34–37 on your answer sheet.' },
    { id: 'q34-text', text: 'Which TWO aspects of story can be shown with an establishing shot?' },
    { id: 'q35-text', text: 'What does a long shot focus our attention on?' },
    { id: 'q36-text', text: 'What do close-ups restrict in order to make audiences nervous?' },
    { id: 'q37-text', text: 'What does a third-person shot place importance on?' },

    // Q38-40
    { id: 'q38-40-title', text: 'Questions 38–40' },
    { id: 'q38-40-instructions', text: 'Complete the summary below. Choose NO MORE THAN TWO WORDS from the text for each answer.' },
    { id: 'sum-1', text: 'Some shots are not used very often. Money shots have a high' },
    { id: 'sum-2', text: 'considering that they only last for a few seconds. The money shot brings in a lot of money, however, and is an important part of the film\'s' },
    { id: 'sum-3', text: '. Other, less expensive shots can still be fascinating: a character can be made to seem' },
    { id: 'sum-4', text: 'in both mind and body when filmed with a Dutch tilt, for instance.' },
];

let currentOffset = 0;
const textSegments = ref(
    allSegments.map(segment => {
        const s = { id: segment.id, text: segment.text, offset: currentOffset };
        currentOffset += segment.text.length;
        return s;
    })
);

// ── Highlight helpers ─────────────────────────────────────────────────────────
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

const getPlainTextLength = (htmlText: string): number =>
    htmlText.replace(/<[^>]*>/g, '').length;

const getHighlightedSegment = (segmentId: string): string => {
    const segment = textSegments.value.find(s => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(
        h => h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(
        n => n.start < segmentEnd && n.end > segmentOffset
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ].sort((a, b) => b.start - a.start);

    let result = segmentText;
    for (const annotation of annotations) {
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

// ── Expose ────────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;

watch(leftPanelWidth, newWidth => localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString()));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

// ── Text selection & highlight ────────────────────────────────────────────────
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
            const segment = textSegments.value.find(s => s.id === segmentIdAttr);
            if (!segment) return null;

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode: Node | null;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) { offsetInSegment += offsetInNode; break; }
                else offsetInSegment += currentNode.textContent?.length || 0;
            }
            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);
        if (startAbsOffset === null || endAbsOffset === null) { showContextMenu.value = false; window.getSelection()?.removeAllRanges(); return; }
        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 70 - 8;
            const viewportWidth = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), viewportWidth - 80),
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
    notes.value = notes.value.filter(n => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
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
    let x: number, y: number;

    if (selection && selection.rangeCount > 0) {
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }

    const vw = window.innerWidth, vh = window.innerHeight;
    if (x - modalWidth / 2 < padding) x = modalWidth / 2 + padding;
    else if (x + modalWidth / 2 > vw - padding) x = vw - modalWidth / 2 - padding;
    if (y + modalHeight > vh - padding) {
        if (selection && selection.rangeCount > 0) y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
        if (y < padding) y = padding;
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: newStart, end: newEnd } = selectionRange.value;
    findOverlappingHighlights(newStart, newEnd).forEach(h => deleteHighlight(h.id));
    notes.value = notes.value.filter(n => !(n.start < newEnd && n.end > newStart));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Section 3' });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter(note => note.id !== id); };

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

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find(n => n.id === noteIdNum);
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
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (target.closest('mark[data-note-id]')) {
        if (relatedTarget && relatedTarget.closest('.note-hover-tooltip')) return;
        showNoteTooltip.value = false;
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
    }
};

const handleClickOutside = (event: MouseEvent) => { if (showContextMenu.value) showContextMenu.value = false; };
const handleKeyDown = (event: KeyboardEvent) => { if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); } };

const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const newLeftWidth = ((event.clientX - containerRect.left) / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};
const stopResize = () => { isResizing.value = false; };

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
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

        <!-- Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header"
                v-html="getHighlightedSegment('part-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions"
                v-html="getHighlightedSegment('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage Panel ── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6 pb-2">
                        <p class="text-base text-gray-700 select-text" data-segment-id="passage-intro"
                            v-html="getHighlightedSegment('passage-intro')"></p>
                    </div>
                    <div class="space-y-4 px-4" :style="contentZoom">
                        <div class="px-4">
                            <div class="text-justify leading-relaxed whitespace-pre-wrap">
                                <span class="text-base text-gray-700 select-text" data-segment-id="passage"
                                    v-html="getHighlightedSegment('passage')"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Panel ── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════════════════════════════════════
                                 Questions 28–33  (Match descriptions to terms)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q28-33-title"
                                                v-html="getHighlightedSegment('q28-33-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q28-33-instructions"
                                            v-html="getHighlightedSegment('q28-33-instructions')"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span data-segment-id="q28-33-write"
                                            v-html="getHighlightedSegment('q28-33-write')"></span>
                                    </p>
                                </div>

                                <!-- Side by side: drop zones (left) + draggable options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left: description drop zones -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                        <div class="space-y-4 text-sm leading-relaxed text-gray-800">

                                            <div v-for="desc in descriptions" :key="desc.qNum"
                                                :id="`question-${desc.qNum}`"
                                                class="relative flex items-center gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = desc.qNum"
                                                @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 w-8 shrink-0">{{ desc.qNum
                                                    }}.</span>
                                                <div class="flex-1">
                                                    <span class="select-text" :data-segment-id="`q${desc.qNum}-text`"
                                                        v-html="getHighlightedSegment(`q${desc.qNum}-text`)"></span>
                                                </div>
                                                <span
                                                    class="inline-flex min-h-8 min-w-14 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm font-bold transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === desc.qNum ? 'border-blue-500 bg-blue-50' : (answers as any)[`q${desc.qNum}`] ? 'border-gray-900 bg-white' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver($event, desc.qNum)"
                                                    @dragleave="handleDragLeave" @drop="handleDrop($event, desc.qNum)"
                                                    @click="clearAnswer(desc.qNum)"
                                                    :title="(answers as any)[`q${desc.qNum}`] ? 'Click to clear' : 'Drop term here'">
                                                    {{ (answers as any)[`q${desc.qNum}`] || '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(desc.qNum)"
                                                    @click.stop="emit('toggleFlag', desc.qNum)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(desc.qNum) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(desc.qNum) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Right: Draggable terms list -->
                                    <div class="w-44 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium">List of Terms</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div v-for="option in availableTermOptions" :key="option.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true" @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd">
                                                    <span class="font-bold text-gray-900 text-xs shrink-0">{{
                                                        option.value }}.</span>
                                                    <span class="text-gray-700 text-xs">{{ option.label }}</span>
                                                </div>
                                                <p v-if="availableTermOptions.length === 0"
                                                    class="text-xs text-gray-400 italic px-2 py-1">All terms placed</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════
                                 Questions 34–37  (Short answer)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q34-37-title"
                                                v-html="getHighlightedSegment('q34-37-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-base leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q34-37-instructions"
                                            v-html="getHighlightedSegment('q34-37-instructions')"></span>
                                    </p>
                                </div>

                                <div class="space-y-5  text-sm leading-relaxed text-gray-800">


                                    <!-- Q34 -->
<div id="question-34" class="relative pr-10" @mouseenter="hoveredQuestion = 34"
    @mouseleave="hoveredQuestion = null">
    <div class="flex items-start gap-3 mb-2">
        <span class="font-bold text-gray-900 w-8 shrink-0">•</span>
        <span class="select-text flex-1" data-segment-id="q34-text"
            v-html="getHighlightedSegment('q34-text')"></span>
    </div>
    <!-- ADD THIS BLOCK -->
    <div class="ml-11">
        <input v-model="answers.q34" type="text" placeholder="34"
            class="w-1/2 border border-gray-900 px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-black text-center"
            spellcheck="false" autocomplete="off" />
    </div>
    <button v-show="isBookmarkVisible(34)" @click.stop="emit('toggleFlag', 34)"
        class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
        :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
        :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
        </svg>
    </button>
</div>


                                    <!-- Q35 -->
                                    <div id="question-35" class="relative pr-10" @mouseenter="hoveredQuestion = 35"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">•</span>
                                            <span class="select-text flex-1" data-segment-id="q35-text"
                                                v-html="getHighlightedSegment('q35-text')"></span>
                                        </div>
                                        <div class="ml-11">
                                            <input v-model="answers.q35" type="text" placeholder="35"
                                                class="w-1/2 border border-gray-900 px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-black text-center"
                                                spellcheck="false" autocomplete="off" />
                                        </div>
                                        <button v-show="isBookmarkVisible(35)" @click.stop="emit('toggleFlag', 35)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>



                                    <!-- Q36 -->
                                    <div id="question-36" class="relative pr-10" @mouseenter="hoveredQuestion = 36"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">•</span>
                                            <span class="select-text flex-1" data-segment-id="q36-text"
                                                v-html="getHighlightedSegment('q36-text')"></span>
                                        </div>
                                        <div class="ml-11">
                                            <input v-model="answers.q36" type="text" placeholder="36"
                                                class="w-1/2 border border-gray-900 px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-black text-center"
                                                spellcheck="false" autocomplete="off" />
                                        </div>
                                        <button v-show="isBookmarkVisible(36)" @click.stop="emit('toggleFlag', 36)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>



                                    <!-- Q37 -->
                                    <div id="question-37" class="relative pr-10" @mouseenter="hoveredQuestion = 37"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="font-bold text-gray-900 w-8 shrink-0">•</span>
                                            <span class="select-text flex-1" data-segment-id="q37-text"
                                                v-html="getHighlightedSegment('q37-text')"></span>
                                        </div>
                                        <div class="ml-11">
                                            <input v-model="answers.q37" type="text" placeholder="37"
                                                class="w-1/2 border border-gray-900 px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-black text-center"
                                                spellcheck="false" autocomplete="off" />
                                        </div>
                                        <button v-show="isBookmarkVisible(37)" @click.stop="emit('toggleFlag', 37)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                            :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ════════════════════════════════════
                                 Questions 38–40  (Summary completion)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q38-40-title"
                                                v-html="getHighlightedSegment('q38-40-title')"></span>
                                        </h3>
                                    </div>
                                    <div class=" ">
                                        <p class="text-base leading-relaxed font-medium text-gray-800">
                                            <span data-segment-id="q38-40-instructions"
                                                v-html="getHighlightedSegment('q38-40-instructions')"></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Summary paragraph with inline inputs -->
                                <div
                                    class="border border-gray-200 bg-gray-50 rounded p-4 text-base leading-loose text-gray-800">
                                    <p class="leading-loose">
                                        <span class="select-text" data-segment-id="sum-1"
                                            v-html="getHighlightedSegment('sum-1')"></span>

                                        <!-- Q38 -->
                                        <span class="group inline-flex items-center gap-1 align-middle">
                                            <input id="question-38" v-model="answers.q38" type="text" placeholder="38"
                                                class="mx-2 w-36 border border-gray-900 px-2 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                            <button @click.stop="toggleFlag(38)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                                :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span class="select-text" data-segment-id="sum-2"
                                            v-html="getHighlightedSegment('sum-2')"></span>

                                        <!-- Q39 -->
                                        <span class="group inline-flex items-center gap-1 align-middle">
                                            <input id="question-39" v-model="answers.q39" type="text" placeholder="39"
                                                class="mx-2 w-36 border border-gray-900 px-2 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                            <button @click.stop="toggleFlag(39)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                                :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span class="select-text" data-segment-id="sum-3"
                                            v-html="getHighlightedSegment('sum-3')"></span>

                                        <!-- Q40 -->
                                        <span class="group inline-flex items-center gap-1 align-middle">
                                            <input id="question-40" v-model="answers.q40" type="text" placeholder="40"
                                                class="mx-2 w-36 border border-gray-900 px-2 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none"
                                                spellcheck="false" autocomplete="off" />
                                            <button @click.stop="toggleFlag(40)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                                :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span class="select-text" data-segment-id="sum-4"
                                            v-html="getHighlightedSegment('sum-4')"></span>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Context Menu for Highlighting ── -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div class="highlight-tooltip pointer-events-auto fixed z-9999" :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @click.stop>
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
                <div class="delete-highlight-tooltip fixed z-9999" :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                        @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
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
                <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @mouseleave="handleTooltipMouseLeave" @click.stop>
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

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{
                    left: `${noteInputPosition.x}px`,
                    top: `${noteInputPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @click.stop>
                <div class="mb-3">
                    <p
                        class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
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

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
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

/* Highlight tooltip */
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

/* Delete tooltip */
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

/* Note tooltip */
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

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>