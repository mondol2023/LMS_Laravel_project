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

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part2-panel-width';
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
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const answers = ref({
    q14: '', q15: '', q16: '', q17: '', q18: '',
    q19: '', q20: '', q21: '', q22: '',
    q23: '', q24: '', q25: '', q26: '',
});

// Drag and drop for Q19-22
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const classificationOptions = [
    { value: 'A', label: 'adopted the writing system from another country' },
    { value: 'B', label: 'used organic materials to record stories' },
    { value: 'C', label: 'used tools to help to tell stories' },
];

const handleDragStart = (e: DragEvent, option: string) => {
    draggedOption.value = option;
    if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', option); }
};
const handleDragEnd = () => { draggedOption.value = null; dragOverQuestion.value = null; };
const handleDragOver = (e: DragEvent, qNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    dragOverQuestion.value = qNum;
};
const handleDragLeave = () => { dragOverQuestion.value = null; };
const handleDrop = (e: DragEvent, qNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (option) { const key = `q${qNum}` as keyof typeof answers.value; answers.value[key] = option; }
    draggedOption.value = null; dragOverQuestion.value = null;
};
const clearAnswer = (qNum: number) => {
    const key = `q${qNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};
const getClassificationLabel = (value: string): string => {
    const opt = classificationOptions.find(o => o.value === value);
    return opt ? `${value}. ${opt.label}` : '';
};

// ── Passage text ──────────────────────────────────────────────────────────────
const passageText = `<b>A. </b>It was told, we suppose, to people crouched around a fire: a tale of adventure, most likely—relating some close encounter with death; a remarkable hunt, an escape from mortal danger; a vision, or something else out of the ordinary. Whatever its thread, the weaving of this story was done with a prime purpose. The listeners must be kept listening. They must not fall asleep. So, as the story went on, its audience should be sustained by one question above all. What happens next?
<br/><br/>
<b>B. </b>The first fireside stories in human history can never be known. They were kept in the heads of those who told them. This method of storage is not necessarily inefficient. From documented oral traditions in Australia, the Balkans and other parts of the world we know that specialised storytellers and poets can recite from memory literally thousands of lines, in verse or prose, verbatim—word for word. But while memory is rightly considered an art in itself, it is clear that a primary purpose of making symbols is to have a system of reminders or mnemonic cues—signs that assist us to recall certain information in the mind's eye.
<br/><br/>
<b>C. </b>In some Polynesian communities a notched memory stick may help to guide a storyteller through successive stages of recitation. But in other parts of the world, the activity of storytelling historically resulted in the development or even the invention of writing systems. One theory about the arrival of literacy in ancient Greece, for example, argues that the epic tales about the Trojan War and the wanderings of Odysseus—traditionally attributed to Homer—were just so enchanting to hear that they had to be preserved. So the Greeks, c. 750–700 BC, borrowed an alphabet from their neighbours in the eastern Mediterranean, the Phoenicians.
<br/><br/>
<b>D. </b>The custom of recording stories on parchment and other materials can be traced in many manifestations around the world, from the priestly papyrus archives of ancient Egypt to the birch-bark scrolls on which the North American Ojibway Indians set down their creation-myth. It is a well-tried and universal practice: so much so that to this day storytime is probably most often associated with words on paper. The formal practice of narrating a story aloud would seem—so we assume—to have given way to newspapers, novels and comic strips. This, however, is not the case. Statistically it is doubtful that the majority of humans currently rely upon the written word to get access to stories. So what is the alternative source?
<br/><br/>
<b>E. </b>Each year, over 7 billion people will go to watch the latest offering from Hollywood, Bollywood and beyond. The supreme storyteller of today is cinema. The movies, as distinct from still photography, seem to be an essentially modern phenomenon. This is an illusion, for there are, as we shall see, certain ways in which the medium of film is indebted to very old precedents of arranging 'sequences' of images. But any account of visual storytelling must begin with the recognition that all storytelling beats with a deeply atavistic pulse: that is, a 'good story' relies upon formal patterns of plot and characterisation that have been embedded in the practice of storytelling over many generations.
<br/><br/>
<b>F. </b>Thousands of scripts arrive every week at the offices of the major film studios. But aspiring screenwriters really need look no further for essential advice than the fourth-century BC Greek philosopher Aristotle. He left some incomplete lecture notes on the art of telling stories in various literary and dramatic modes, a slim volume known as The Poetics. Though he can never have envisaged the popcorn-fuelled actuality of a multiplex cinema, Aristotle is almost prescient about the key elements required to get the crowds flocking to such a cultural hub. He analysed the process with cool rationalism. When a story enchants us, we lose the sense of where we are; we are drawn into the story so thoroughly that we forget it is a story being told. This is, in Aristotle's phrase, 'the suspension of disbelief'.
<br/><br/>
<b>G. </b>We know the feeling. If ever we have stayed in our seats, stunned with grief, as the credits roll by, or for days after seeing that vivid evocation of horror have been nervous about taking a shower at home, then we have suspended disbelief. We have been caught, or captivated, in the storyteller's web. Did it all really happen? We really thought so—for a while. Aristotle must have witnessed often enough this suspension of disbelief. He taught at Athens, the city where theatre developed as a primary form of civic ritual and recreation. Two theatrical types of storytelling, tragedy and comedy, caused Athenian audiences to lose themselves in sadness and laughter respectively. Tragedy, for Aristotle, was particularly potent in its capacity to enlist and then purge the emotions of those watching the story unfold on the stage, so he tried to identify those factors in the storyteller's art that brought about such engagement. He had, as an obvious sample for analysis, not only the fifth-century BC masterpieces of Classical Greek tragedy written by Aeschylus, Sophocles and Euripides. Beyond them stood Homer, whose stories even then had canonical status: The Iliad and The Odyssey were already considered literary landmarks—stories by which all other stories should be measured. So what was the secret of Homer's narrative art?
<br/><br/>
<b>H. </b>It was not hard to find. Homer created credible heroes. His heroes belonged to the past; they were mighty and magnificent, yet they were not, in the end, fantasy figures. He made his heroes sulk, bicker, cheat and cry. They were, in short, characters—protagonists of a story that an audience would care about, would want to follow, would want to know what happens next. As Aristotle saw, the hero who shows a human side—some flaw or weakness to which mortals are prone—is intrinsically dramatic.`;

// ── Segment registry ──────────────────────────────────────────────────────────
// Every span rendered with v-html must have a matching entry here AND carry
// data-segment-id in the template. getAbsoluteOffset() uses .closest('[data-segment-id]')
// to find the enclosing segment, so the id must match exactly.
const passagePlainLen = passageText.replace(/<[^>]*>/g, '').length;
const Q = passagePlainLen; // question-side offsets start after the passage

const segmentList = ref([
    { id: 'passage-title',  text: 'Ancient Storytelling',                                                                                                                              offset: Q+1500},
    { id: 'passage',        text: passageText,                                                                                                                                          offset: 0     },
    // Q14-18
    { id: 'q14-18-title',   text: 'Questions 14-18',                                                                                                                                   offset: Q+100 },
    { id: 'q14-18-inst',    text: 'The Reading Passage has eight paragraphs A-H. Which paragraph contains the following information? Write the correct letter, A-H, in boxes 14-18 on your answer sheet.', offset: Q+120 },
    { id: 'q14-text',       text: 'A misunderstanding of a modern way for telling stories',                                                                                             offset: Q+350 },
    { id: 'q15-text',       text: 'The typical forms mentioned for telling stories',                                                                                                    offset: Q+420 },
    { id: 'q16-text',       text: 'The fundamental aim of storytelling',                                                                                                               offset: Q+480 },
    { id: 'q17-text',       text: 'A description of reciting stories without any assistance',                                                                                          offset: Q+530 },
    { id: 'q18-text',       text: 'How to make story characters attractive',                                                                                                           offset: Q+600 },
    // Q19-22
    { id: 'q19-22-title',   text: 'Questions 19-22',                                                                                                                                   offset: Q+660 },
    { id: 'q19-22-inst',    text: 'Classify the following information as referring to',                                                                                                 offset: Q+680 },
    { id: 'q19-22-write',   text: 'Write the correct letter, A, B or C in boxes 19-22 on your answer sheet.',                                                                          offset: Q+740 },
    { id: 'q19-text',       text: 'Egyptians',                                                                                                                                          offset: Q+820 },
    { id: 'q20-text',       text: 'Ojibway',                                                                                                                                            offset: Q+840 },
    { id: 'q21-text',       text: 'Polynesians',                                                                                                                                        offset: Q+860 },
    { id: 'q22-text',       text: 'Greek',                                                                                                                                              offset: Q+880 },
    // Q23-26
    { id: 'q23-26-title',   text: 'Questions 23-26',                                                                                                                                   offset: Q+920 },
    { id: 'q23-26-inst1',   text: 'Complete the sentences below with ONE WORD ONLY from the passage.',                                                                                 offset: Q+940 },
    { id: 'q23-26-inst2',   text: 'Write your answer in boxes 23-26 on your answer sheet.',                                                                                            offset: Q+1010},
    { id: 'q23-text',       text: '• Aristotle wrote a book on the art of storytelling called',                                                                                        offset: Q+1080},
    { id: 'q24-text',       text: '• Aristotle believed the most powerful type of story to move listeners is',                                                                         offset: Q+1150},
    { id: 'q25-text',       text: '• Aristotle viewed Homers works as',                                                                                                               offset: Q+1230},
    { id: 'q26-text',       text: '• Aristotle believed attractive heroes should have some',                                                                                           offset: Q+1280},
]);

// ── Annotation helpers ────────────────────────────────────────────────────────
const getPlainLen = (html: string) => html.replace(/<[^>]*>/g, '').length;

const plainToHtmlOffset = (html: string, plainOffset: number): number => {
    let plain = 0, idx = 0;
    while (plain < plainOffset && idx < html.length) {
        if (html[idx] === '<') { while (idx < html.length && html[idx] !== '>') idx++; idx++; }
        else { plain++; idx++; }
    }
    return idx;
};

const applyAnnotations = (text: string, segOffset: number): string => {
    const plainLen = getPlainLen(text);
    const segEnd = segOffset + plainLen;

    const anns = [
        ...highlights.value
            .filter(h => h.start_offset < segEnd && h.end_offset > segOffset)
            .map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...notes.value
            .filter(n => n.start < segEnd && n.end > segOffset)
            .map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    if (anns.length === 0) return text;

    let result = text;
    for (const ann of anns) {
        const pStart = Math.max(0, ann.start - segOffset);
        const pEnd   = Math.min(plainLen, ann.end - segOffset);
        if (pStart >= pEnd) continue;
        const hStart = plainToHtmlOffset(result, pStart);
        const hEnd   = plainToHtmlOffset(result, pEnd);
        const before = result.substring(0, hStart);
        const marked = result.substring(hStart, hEnd);
        const after  = result.substring(hEnd);
        result = ann.type === 'note'
            ? `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${marked}</mark>${after}`
            : `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${marked}</mark>${after}`;
    }
    return result;
};

/** Render a segment by its unique id */
const seg = (id: string): string => {
    const s = segmentList.value.find(x => x.id === id);
    if (!s) return '';
    return applyAnnotations(s.text, s.offset);
};

// ── Core fix: offset resolution using data-segment-id ────────────────────────
/**
 * Walk up from a DOM node to the nearest [data-segment-id] ancestor.
 * Count plain-text characters inside that element up to the given node/offset.
 * Return the absolute document offset (segment.offset + chars counted).
 *
 * This correctly handles:
 *  - Single-line selections within one segment
 *  - Multiline selections within the passage (one big segment)
 *  - Cross-segment selections (each endpoint resolved independently)
 *  - Selections starting/ending inside <b>, <mark> or other inline tags
 */
const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
    const container: Element | null =
        node.nodeType === Node.TEXT_NODE
            ? (node.parentNode as Element | null)
            : (node as Element);

    const segEl = container?.closest('[data-segment-id]');
    if (!segEl) return null;

    const segId = segEl.getAttribute('data-segment-id') ?? '';
    const segment = segmentList.value.find(s => s.id === segId);
    if (!segment) return null;

    const walker = document.createTreeWalker(segEl, NodeFilter.SHOW_TEXT);
    let offsetInSeg = 0;
    let found = false;
    let cur: Node | null;

    while ((cur = walker.nextNode())) {
        if (cur === node) {
            offsetInSeg += offsetInNode;
            found = true;
            break;
        }
        offsetInSeg += cur.textContent?.length ?? 0;
    }

    if (!found) {
        // Boundary case: clamp to segment plain-text length
        const maxLen = getPlainLen(segment.text);
        offsetInSeg = Math.min(offsetInSeg + offsetInNode, maxLen);
    }

    return segment.offset + offsetInSeg;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) { showContextMenu.value = false; return; }

        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection.getRangeAt(0);

        let startAbs = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbs   = getAbsoluteOffset(range.endContainer,   range.endOffset);

        if (startAbs === null || endAbs === null) { showContextMenu.value = false; return; }
        if (startAbs > endAbs) [startAbs, endAbs] = [endAbs, startAbs];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const vw = window.innerWidth;
            const menuWidth = 140;
            const menuHeight = 70;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), vw - menuWidth / 2 - 10),
                y: Math.max(rect.top - menuHeight - 8, 10),
            };
            showContextMenu.value = true;
            selectedText.value    = selection.toString();
            selectionRange.value  = { start: startAbs, end: endAbs };
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
    const modalWidth = 320; const modalHeight = 180; const padding = 10;
    const selection = window.getSelection();
    let x: number, y: number;
    if (selection && selection.rangeCount > 0) {
        const r = selection.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    } else {
        x = contextMenuPosition.value.x; y = contextMenuPosition.value.y + 70;
    }
    const vw = window.innerWidth; const vh = window.innerHeight;
    const hw = modalWidth / 2;
    if (x - hw < padding) x = hw + padding;
    else if (x + hw > vw - padding) x = vw - hw - padding;
    if (y + modalHeight > vh - padding) {
        if (selection?.rangeCount) y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
        if (y < padding) y = padding;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true; showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: ns, end: ne } = selectionRange.value;
    findOverlappingHighlights(ns, ne).forEach(h => deleteHighlight(h.id));
    notes.value = notes.value.filter(n => !(n.start < ne && n.end > ns));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: ns, end: ne });
    noteInputText.value = ''; showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter(n => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const handleContentClick = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement;
    if (mark) {
        const id = mark.getAttribute('data-highlight-id');
        if (id) {
            event.stopPropagation();
            const rect = mark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(id, 10);
            showDeleteTooltip.value = true; showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        showDeleteTooltip.value = false; highlightToDelete.value = null;
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (mark) {
        const noteId = mark.getAttribute('data-note-id');
        if (noteId) {
            const id = parseInt(noteId, 10);
            const note = notes.value.find(n => n.id === id);
            if (note) {
                const rect = mark.getBoundingClientRect();
                let y = rect.top - 58;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = id; hoveredNoteText.value = note.note; showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const getAnswers = () => ({ ...answers.value });

watch(leftPanelWidth, v => localStorage.setItem(PANEL_WIDTH_KEY, v.toString()));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const pct = ((e.clientX - rect.left) / rect.width) * 100;
    if (pct >= 20 && pct <= 80) leftPanelWidth.value = pct;
};
const stopResize = () => { isResizing.value = false; };

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

        <!-- Part 2 Header -->
        <div class="mx-5 mt-4  px-4 py-2" style="background-color: #F1F2EC;">
            <p class="text-sm font-bold text-gray-900 select-text">READING PASSAGE 2</p>
            <p class="text-sm text-gray-700 select-text">You should spend about 20 minutes on Questions 14–26, which are
                based on Reading Passage 2 below.</p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage ─────────────────────────────────── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900" data-segment-id="passage-title" v-html="seg('passage-title')"></h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="p-4">
                            <!--
                                THE KEY FIX for the passage:
                                Add data-segment-id="passage" directly on the wrapper div.
                                getAbsoluteOffset() walks up via .closest('[data-segment-id]'),
                                so every text node inside — including those inside <b> and <br>
                                tags — resolves to segment id "passage" with the correct offset.
                                This makes single-line AND multiline selections work correctly.
                            -->
                            <div class="text-justify leading-relaxed text-gray-700 select-text"
                                 data-segment-id="passage"
                                 v-html="seg('passage')">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Resize Handle ───────────────────────────────────── -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Section ───────────────────────────────── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ══ Questions 14-18 ══ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <!--
                                            THE KEY FIX for questions:
                                            Add data-segment-id="<id>" to every span that renders
                                            question text via v-html. Without this attribute,
                                            getAbsoluteOffset() returns null and highlighting is silently
                                            ignored. With it, both single and multiline selections work.
                                        -->
                                        <span class="text-gray-700 select-text"
                                              data-segment-id="q14-18-title"
                                              v-html="seg('q14-18-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700 select-text"
                                       data-segment-id="q14-18-inst"
                                       v-html="seg('q14-18-inst')"></p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Q14 -->
                                    <div id="question-14" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900">14.</span>
                                        <select v-model="answers.q14"
                                            class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F','G','H']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <span class="text-base text-gray-900 flex-1 select-text"
                                              data-segment-id="q14-text"
                                              v-html="seg('q14-text')"></span>
                                        <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                            :class="[ isQuestionFlagged(14) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===14||isQuestionFlagged(14))?'opacity-100':'opacity-0' ]"
                                            @click.stop="toggleFlag(14)">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>
                                    <!-- Q15 -->
                                    <div id="question-15" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900">15.</span>
                                        <select v-model="answers.q15"
                                            class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F','G','H']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <span class="text-base text-gray-900 flex-1 select-text"
                                              data-segment-id="q15-text"
                                              v-html="seg('q15-text')"></span>
                                        <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                            :class="[ isQuestionFlagged(15) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===15||isQuestionFlagged(15))?'opacity-100':'opacity-0' ]"
                                            @click.stop="toggleFlag(15)">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>
                                    <!-- Q16 -->
                                    <div id="question-16" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900">16.</span>
                                        <select v-model="answers.q16"
                                            class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F','G','H']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <span class="text-base text-gray-900 flex-1 select-text"
                                              data-segment-id="q16-text"
                                              v-html="seg('q16-text')"></span>
                                        <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                            :class="[ isQuestionFlagged(16) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===16||isQuestionFlagged(16))?'opacity-100':'opacity-0' ]"
                                            @click.stop="toggleFlag(16)">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>
                                    <!-- Q17 -->
                                    <div id="question-17" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900">17.</span>
                                        <select v-model="answers.q17"
                                            class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F','G','H']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <span class="text-base text-gray-900 flex-1 select-text"
                                              data-segment-id="q17-text"
                                              v-html="seg('q17-text')"></span>
                                        <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                            :class="[ isQuestionFlagged(17) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===17||isQuestionFlagged(17))?'opacity-100':'opacity-0' ]"
                                            @click.stop="toggleFlag(17)">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>
                                    <!-- Q18 -->
                                    <div id="question-18" class="relative flex items-center gap-3 bg-white p-2"
                                        @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900">18.</span>
                                        <select v-model="answers.q18"
                                            class="w-20 border border-gray-900 bg-white px-2 py-1 text-center text-sm focus:border-black focus:outline-none">
                                            <option value="" disabled selected>Select</option>
                                            <option v-for="l in ['A','B','C','D','E','F','G','H']" :key="l" :value="l">{{ l }}</option>
                                        </select>
                                        <span class="text-base text-gray-900 flex-1 select-text"
                                              data-segment-id="q18-text"
                                              v-html="seg('q18-text')"></span>
                                        <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                            :class="[ isQuestionFlagged(18) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===18||isQuestionFlagged(18))?'opacity-100':'opacity-0' ]"
                                            @click.stop="toggleFlag(18)">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ Questions 19-22 ══ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <span class="text-gray-700 select-text"
                                              data-segment-id="q19-22-title"
                                              v-html="seg('q19-22-title')"></span>
                                    </h3>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700 select-text"
                                       data-segment-id="q19-22-inst"
                                       v-html="seg('q19-22-inst')"></p>
                                    <p class="mt-2 text-sm text-gray-700 select-text"
                                       data-segment-id="q19-22-write"
                                       v-html="seg('q19-22-write')"></p>
                                </div>

                                <div class="flex gap-6">
                                    <!-- Drop zones -->
                                    <div class="flex-1 space-y-4">
                                        <div id="question-19" class="relative flex items-center gap-3 bg-white p-2"
                                            @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">19.</span>
                                            <span class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion===19?'border-blue-500 bg-blue-50':answers.q19?'border-gray-900 bg-white font-medium':'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event,19)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event,19)" @click="clearAnswer(19)"
                                                :title="answers.q19?'Click to clear':'Drop answer here'">
                                                {{ answers.q19 ? getClassificationLabel(answers.q19) : '' }}
                                            </span>
                                            <span class="text-base text-gray-900 flex-1 select-text"
                                                  data-segment-id="q19-text" v-html="seg('q19-text')"></span>
                                            <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                                :class="[ isQuestionFlagged(19)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===19||isQuestionFlagged(19))?'opacity-100':'opacity-0' ]"
                                                @click.stop="toggleFlag(19)">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <div id="question-20" class="relative flex items-center gap-3 bg-white p-2"
                                            @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">20.</span>
                                            <span class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion===20?'border-blue-500 bg-blue-50':answers.q20?'border-gray-900 bg-white font-medium':'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event,20)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event,20)" @click="clearAnswer(20)"
                                                :title="answers.q20?'Click to clear':'Drop answer here'">
                                                {{ answers.q20 ? getClassificationLabel(answers.q20) : '' }}
                                            </span>
                                            <span class="text-base text-gray-900 flex-1 select-text"
                                                  data-segment-id="q20-text" v-html="seg('q20-text')"></span>
                                            <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                                :class="[ isQuestionFlagged(20)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===20||isQuestionFlagged(20))?'opacity-100':'opacity-0' ]"
                                                @click.stop="toggleFlag(20)">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <div id="question-21" class="relative flex items-center gap-3 bg-white p-2"
                                            @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">21.</span>
                                            <span class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion===21?'border-blue-500 bg-blue-50':answers.q21?'border-gray-900 bg-white font-medium':'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event,21)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event,21)" @click="clearAnswer(21)"
                                                :title="answers.q21?'Click to clear':'Drop answer here'">
                                                {{ answers.q21 ? getClassificationLabel(answers.q21) : '' }}
                                            </span>
                                            <span class="text-base text-gray-900 flex-1 select-text"
                                                  data-segment-id="q21-text" v-html="seg('q21-text')"></span>
                                            <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                                :class="[ isQuestionFlagged(21)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===21||isQuestionFlagged(21))?'opacity-100':'opacity-0' ]"
                                                @click.stop="toggleFlag(21)">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <div id="question-22" class="relative flex items-center gap-3 bg-white p-2"
                                            @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900">22.</span>
                                            <span class="inline-flex min-h-8 min-w-24 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion===22?'border-blue-500 bg-blue-50':answers.q22?'border-gray-900 bg-white font-medium':'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event,22)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event,22)" @click="clearAnswer(22)"
                                                :title="answers.q22?'Click to clear':'Drop answer here'">
                                                {{ answers.q22 ? getClassificationLabel(answers.q22) : '' }}
                                            </span>
                                            <span class="text-base text-gray-900 flex-1 select-text"
                                                  data-segment-id="q22-text" v-html="seg('q22-text')"></span>
                                            <button class="ml-1 flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0"
                                                :class="[ isQuestionFlagged(22)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===22||isQuestionFlagged(22))?'opacity-100':'opacity-0' ]"
                                                @click.stop="toggleFlag(22)">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Draggable options -->
                                    <div class="w-64 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div v-for="opt in classificationOptions" :key="opt.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1.5 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === opt.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, opt.value)"
                                                    @dragend="handleDragEnd">
                                                    <span class="font-bold text-gray-900 text-xs">{{ opt.value }}.</span>
                                                    <span class="text-gray-700 text-xs">{{ opt.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ Questions 23-26 ══ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <span class="text-gray-700 select-text"
                                              data-segment-id="q23-26-title"
                                              v-html="seg('q23-26-title')"></span>
                                    </h3>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700 select-text"
                                       data-segment-id="q23-26-inst1"
                                       v-html="seg('q23-26-inst1')"></p>
                                    <p class="mb-4 text-sm text-gray-700 select-text"
                                       data-segment-id="q23-26-inst2"
                                       v-html="seg('q23-26-inst2')"></p>
                                </div>

                                <div class="space-y-2">
                                    <!-- Q23 -->
                                    <div id="question-23" class="bg-white p-1"
                                        @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="text-base text-gray-900 select-text"
                                                  data-segment-id="q23-text" v-html="seg('q23-text')"></span>
                                            <span class="relative inline-flex items-center">
                                                <input v-model="answers.q23" type="text"
                                                    class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                    style="width: 90px" placeholder="23" maxlength="20"
                                                    spellcheck="false" autocomplete="off" />
                                                <button class="absolute -right-7 inline-flex h-5 w-5 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="[ isQuestionFlagged(23)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===23||isQuestionFlagged(23))?'opacity-100':'opacity-0' ]"
                                                    @click.stop="toggleFlag(23)">
                                                    <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Q24 -->
                                    <div id="question-24" class="bg-white p-1"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="text-base text-gray-900 select-text"
                                                  data-segment-id="q24-text" v-html="seg('q24-text')"></span>
                                            <span class="relative inline-flex items-center">
                                                <input v-model="answers.q24" type="text"
                                                    class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                    style="width: 90px" placeholder="24" maxlength="20"
                                                    spellcheck="false" autocomplete="off" />
                                                <button class="absolute -right-7 inline-flex h-5 w-5 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="[ isQuestionFlagged(24)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===24||isQuestionFlagged(24))?'opacity-100':'opacity-0' ]"
                                                    @click.stop="toggleFlag(24)">
                                                    <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Q25 -->
                                    <div id="question-25" class="bg-white p-1"
                                        @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="text-base text-gray-900 select-text"
                                                  data-segment-id="q25-text" v-html="seg('q25-text')"></span>
                                            <span class="relative inline-flex items-center">
                                                <input v-model="answers.q25" type="text"
                                                    class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                    style="width: 90px" placeholder="25" maxlength="20"
                                                    spellcheck="false" autocomplete="off" />
                                                <button class="absolute -right-7 inline-flex h-5 w-5 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="[ isQuestionFlagged(25)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===25||isQuestionFlagged(25))?'opacity-100':'opacity-0' ]"
                                                    @click.stop="toggleFlag(25)">
                                                    <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Q26 -->
                                    <div id="question-26" class="bg-white p-1"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="text-base text-gray-900 select-text"
                                                  data-segment-id="q26-text" v-html="seg('q26-text')"></span>
                                            <span class="relative inline-flex items-center">
                                                <input v-model="answers.q26" type="text"
                                                    class="question-input border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                    style="width: 90px" placeholder="26" maxlength="20"
                                                    spellcheck="false" autocomplete="off" />
                                                <button class="absolute -right-7 inline-flex h-5 w-5 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="[ isQuestionFlagged(26)?'border-gray-400 text-red-500 opacity-100':'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600', (hoveredQuestion===26||isQuestionFlagged(26))?'opacity-100':'opacity-0' ]"
                                                    @click.stop="toggleFlag(26)">
                                                    <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                                </button>
                                            </span>
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

    <Teleport to="body">
        <!-- Highlight context menu -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete highlight tooltip -->
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

        <!-- Note hover tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note input modal -->
        <div v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.select-text { user-select: text; -webkit-user-select: text; }
.select-none { user-select: none; -webkit-user-select: none; cursor: col-resize; }

.passage-panel { width: 100%; }
.answer-panel  { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel  { width: calc(100% - var(--panel-width) - 0.5rem); }
}

mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; cursor: pointer; color: inherit; }

.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question {
    background-color: rgba(0,0,0,0.1); border-radius: 4px;
    padding: 2px 4px; margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}
@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,0.2); transform: scale(1.05); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    border-left: 9px solid transparent; border-right: 9px solid transparent; border-top: 9px solid #d1d5db;
    z-index: -1;
}
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px;
    border-left: 9px solid transparent; border-right: 9px solid transparent; border-bottom: 9px solid #d1d5db;
    z-index: -1;
}
.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    border-left: 9px solid transparent; border-right: 9px solid transparent; border-top: 9px solid #d1d5db;
    z-index: -1;
}
.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
</style>

<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer; padding: 2px 0; border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147, 197, 253, 0.7) !important; }
.question-input::placeholder { font-weight: 700; color: #374151; }
</style>