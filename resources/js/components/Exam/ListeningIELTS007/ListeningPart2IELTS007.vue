<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    fontSize?: number;
    flaggedQuestions?: Set<number>;
}

const props = withDefaults(defineProps<Props>(), {
    fontSize: 16,
    flaggedQuestions: () => new Set<number>(),
});

const emit = defineEmits<{ toggleFlag: [questionNumber: number] }>();

const hoveredQuestion = ref<number | null>(null);
const isQuestionFlagged = (n: number) => props.flaggedQuestions.has(n);
const toggleFlag = (n: number) => emit('toggleFlag', n);

const contentZoom = computed(() => ({ zoom: props.fontSize / 16 }));

const answers = ref({
    q11: '', q12: '', q13: '', q14: '', q15: '',
    q16: '', q17: '', q18: '', q19: '', q20: '',
});

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

const textSegments = ref([
    { id: 'part2-title',         text: 'Part 2',                                                                 offset: 0 },

    { id: 'part2-instruction',   text: 'Listen and answer Questions 11-20.',          offset: 23 },
    { id: 'section-title',       text: 'The British Library',                                                                    offset: 95 },
    { id: 'q11-label',           text: '11. The reading rooms are only open for group visits on',                                offset: 115 },
    { id: 'q12-label',           text: '12. The library was officially opened in',                                               offset: 194 },
    { id: 'q13-label-prefix',    text: '13. All the library rooms together cover',                                               offset: 252 },
    { id: 'q13-label-suffix',    text: 'meter square.',                                                                          offset: 315 },
    { id: 'q14-label',           text: '14. The library is financed by the',                                                     offset: 330 },
    { id: 'q15-label-prefix',    text: '15. The main function of the library is to provide resources for people doing',          offset: 386 },
    { id: 'q16-20-header',       text: 'Questions 16-20',                                                                        offset: 487 },
    { id: 'q16-20-instruction1', text: 'Label the plan below.',                                                                  offset: 503 },
    { id: 'q16-20-instruction2', text: 'Write NO MORE THAN THREE WORDS for each answer.',                                        offset: 526 },
    { id: 'plan-title',          text: 'Plan of the British Library',                                                            offset: 575 },
    { id: 'map-cafeteria',       text: 'Cafeteria',                                                                              offset: 603 },
    { id: 'map-lift',            text: 'Lift',                                                                                   offset: 613 },
    { id: 'map-toilets',         text: 'Toilets',                                                                                offset: 618 },
    { id: 'map-tower',           text: 'Tower',                                                                                  offset: 627 },
    { id: 'map-treasures-gallery', text: 'Treasures Gallery',                                                                   offset: 667 },
    { id: 'map-upper-ground',    text: 'Upper ground floor',                                                                     offset: 686 },
    { id: 'map-catalogues',      text: 'Catalogues',                                                                             offset: 706 },
    { id: 'map-to-cloakroom',    text: 'to cloakroom',                                                                          offset: 738 },
    { id: 'map-lower-ground',    text: 'Lower ground floor',                                                                     offset: 751 },
    { id: 'map-meeting-point',   text: 'Meeting point',                                                                          offset: 786 },
    { id: 'map-main-entrance',   text: 'Main Entrance',                                                                          offset: 801 },
    { id: 'map-piazza',          text: 'Piazza',                                                                                 offset: 816 },
    { id: 'map-statue',          text: 'Statue',                                                                                 offset: 827 },
]);

const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset,
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset,
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return plainText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset, end: h.end_offset,
            type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start, end: n.end,
            type: 'note' as const, color: 'blue', id: n.id, noteText: n.note,
        })),
    ].sort((a, b) => b.start - a.start);

    let result = plainText;
    for (const annotation of annotations) {
        const aStart = Math.max(0, annotation.start - segmentOffset);
        const aEnd = Math.min(plainText.length, annotation.end - segmentOffset);
        if (aStart < aEnd) {
            const before = result.substring(0, aStart);
            const annotated = result.substring(aStart, aEnd);
            const after = result.substring(aEnd);
            result = annotation.type === 'note'
                ? `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`
                : `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
        }
    }
    return result;
};

const getAnswers = () => ({ ...answers.value });

const getTextOffsetInElement = (element: Element, targetNode: Node, targetOffset: number): number => {
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null);
    let offset = 0;
    let node: Node | null;
    while ((node = walker.nextNode())) {
        if (node === targetNode) return offset + targetOffset;
        offset += (node.textContent || '').length;
    }
    return offset;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && contentTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 58;
            const vw = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), vw - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection && range) {
                let targetElement: Node | null = range.startContainer;
                while (targetElement && targetElement.nodeType !== Node.ELEMENT_NODE)
                    targetElement = targetElement.parentNode;
                while (targetElement && !(targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const parent = targetElement.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetElement = parent;
                }
                if (targetElement && (targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const attr = (targetElement as Element).getAttribute('data-segment-id') || '';
                    const segment = textSegments.value.find((s) => s.id === attr);
                    if (segment) {
                        const start = segment.offset + getTextOffsetInElement(targetElement as Element, range.startContainer, range.startOffset);
                        selectedText.value = selected;
                        selectionRange.value = { start, end: start + selected.length };
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
    const modalWidth = 320; const modalHeight = 180; const padding = 10;
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x;
    let y = contextMenuPosition.value.y + 70;
    if (selection && selection.rangeCount > 0) {
        const r = selection.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    }
    const vw = window.innerWidth; const vh = window.innerHeight;
    const hw = modalWidth / 2;
    if (x - hw < padding) x = hw + padding;
    else if (x + hw > vw - padding) x = vw - hw - padding;
    if (y + modalHeight > vh - padding) {
        if (selection && selection.rangeCount > 0)
            y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
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
    findOverlappingHighlights(newStart, newEnd).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const highlightMark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement;
    if (highlightMark) {
        const id = highlightMark.getAttribute('data-highlight-id');
        if (id) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(id, 10);
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
        deleteHighlight(highlightToDelete.value);
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const noteMark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find((n) => n.id === noteIdNum);
            if (note) {
                const rect = noteMark.getBoundingClientRect();
                let y = rect.top - 58;
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
    if ((event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
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

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mouseover', handleNoteMouseEnter);
    document.removeEventListener('mouseout', handleNoteMouseLeave);
});

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"></h3>
                <p class="text-base text-gray-700 select-text" data-segment-id="part2-instruction"
                    v-html="getHighlightedSegmentById('part2-instruction')"></p>
            </div>

            <div class="flex-1 pb-32">
                <div class="p-2 sm:p-3 md:p-4">
                    <div class="space-y-4">

                        <!-- Section Title -->
                        <p class="text-base font-bold text-gray-900 select-text" data-segment-id="section-title"
                            v-html="getHighlightedSegmentById('section-title')"></p>

                        <!-- Q11 -->
                        <div id="question-11" class="relative flex flex-wrap items-baseline gap-1"
                            @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                @click.stop="toggleFlag(11)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(11) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q11-label"
                                v-html="getHighlightedSegmentById('q11-label')"></span>
                            <input v-model="answers.q11" type="text" placeholder="11"
                                class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                spellcheck="false" autocomplete="off"
                                @focus="hoveredQuestion = 11" @blur="hoveredQuestion = null" />
                            <span class="text-base text-gray-800">.</span>
                        </div>

                        <!-- Q12 -->
                        <div id="question-12" class="relative flex flex-wrap items-baseline gap-1"
                            @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                @click.stop="toggleFlag(12)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(12) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q12-label"
                                v-html="getHighlightedSegmentById('q12-label')"></span>
                            <input v-model="answers.q12" type="text" placeholder="12"
                                class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                spellcheck="false" autocomplete="off"
                                @focus="hoveredQuestion = 12" @blur="hoveredQuestion = null" />
                            <span class="text-base text-gray-800">.</span>
                        </div>

                        <!-- Q13 -->
                        <div id="question-13" class="relative flex flex-wrap items-baseline gap-1"
                            @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                @click.stop="toggleFlag(13)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(13) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q13-label-prefix"
                                v-html="getHighlightedSegmentById('q13-label-prefix')"></span>
                            <input v-model="answers.q13" type="text" placeholder="13"
                                class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                spellcheck="false" autocomplete="off"
                                @focus="hoveredQuestion = 13" @blur="hoveredQuestion = null" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q13-label-suffix"
                                v-html="getHighlightedSegmentById('q13-label-suffix')"></span>
                        </div>

                        <!-- Q14 -->
                        <div id="question-14" class="relative flex flex-wrap items-baseline gap-1"
                            @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                @click.stop="toggleFlag(14)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q14-label"
                                v-html="getHighlightedSegmentById('q14-label')"></span>
                            <input v-model="answers.q14" type="text" placeholder="14"
                                class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                spellcheck="false" autocomplete="off"
                                @focus="hoveredQuestion = 14" @blur="hoveredQuestion = null" />
                            <span class="text-base text-gray-800">.</span>
                        </div>

                        <!-- Q15 -->
                        <div id="question-15" class="relative flex flex-wrap items-baseline gap-1"
                            @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                @click.stop="toggleFlag(15)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q15-label-prefix"
                                v-html="getHighlightedSegmentById('q15-label-prefix')"></span>
                            <input v-model="answers.q15" type="text" placeholder="15"
                                class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                spellcheck="false" autocomplete="off"
                                @focus="hoveredQuestion = 15" @blur="hoveredQuestion = null" />
                            <span class="text-base text-gray-800">.</span>
                        </div>

                        <!-- ───── Q16–20 Map Section ───── -->
                        <div class="mt-6">
                            <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q16-20-header"
                                v-html="getHighlightedSegmentById('q16-20-header')"></h3>
                            <p class="text-base text-gray-800 select-text" data-segment-id="q16-20-instruction1"
                                v-html="getHighlightedSegmentById('q16-20-instruction1')"></p>
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q16-20-instruction2"
                                v-html="getHighlightedSegmentById('q16-20-instruction2')"></p>
                        </div>

                        <!-- Map Title -->
                        <p class="text-center text-base font-bold text-gray-900 select-text" data-segment-id="plan-title"
                            v-html="getHighlightedSegmentById('plan-title')"></p>

                        <!-- ───── British Library Floor Plan (SVG-based layout) ───── -->
                        <div class="mx-auto w-full max-w-2xl border border-gray-800 p-4 text-xs select-none">

  <!-- 🗺️ Map Image -->
  <div class="mb-6">
    <img
      src="/images/listening/IELTS007.png"
      alt="Museum floor plan map"
      class="w-full h-auto border border-gray-800 select-none"
      draggable="false"
    />

  </div>

  <!-- ❓ Questions List (Inputs Below Image) -->
  <div class="space-y-3 border-t border-gray-200 pt-4">

    <!-- Question 16 -->
    <div class="flex items-center  gap-3 rounded-lg b p-3 hover:bg-gray-100 transition-colors"
         @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
      <div class="flex items-center gap-2 min-w-0">
        <span class="font-bold text-gray-700">16.</span>

      </div>
      <div class="relative flex-shrink-0">
        <input
          v-model="answers.q16"
          type="text"
          placeholder="Answer"
          class="w-60 border border-gray-500 bg-transparent px-2 py-1 text-center text-xs placeholder:font-bold placeholder:text-gray-400 focus:border-gray-800 focus:outline-none rounded"
          spellcheck="false"
          autocomplete="off"
          @focus="hoveredQuestion = 16"
          @blur="hoveredQuestion = null"
        />
        <button
          v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
          @click.stop="toggleFlag(16)"
          class="absolute -top-2 -right-7 flex h-5 w-5 items-center justify-center rounded border transition-all"
          :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'">
          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Question 17 -->
    <div class="flex items-center  gap-3  p-3 hover:bg-gray-100 transition-colors"
         @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
      <div class="flex items-center gap-2 min-w-0">
        <span class="font-bold text-gray-700">17.</span>

      </div>
      <div class="relative flex-shrink-0">
        <input
          v-model="answers.q17"
          type="text"
          placeholder="Answer"
          class="w-60 border border-gray-500 bg-transparent px-2 py-1 text-center text-xs placeholder:font-bold placeholder:text-gray-400 focus:border-gray-800 focus:outline-none rounded"
          spellcheck="false"
          autocomplete="off"
          @focus="hoveredQuestion = 17"
          @blur="hoveredQuestion = null"
        />
        <button
          v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
          @click.stop="toggleFlag(17)"
          class="absolute -top-2 -right-7 flex h-5 w-5 items-center justify-center rounded border transition-all"
          :class="isQuestionFlagged(17) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'">
          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Question 18 -->
    <div class="flex items-center  gap-3  p-3 hover:bg-gray-100 transition-colors"
         @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
      <div class="flex items-center gap-2 min-w-0">
        <span class="font-bold text-gray-700">18.</span>

      </div>
      <div class="relative flex-shrink-0">
        <input
          v-model="answers.q18"
          type="text"
          placeholder="Answer"
          class="w-60 border border-gray-500 bg-transparent px-2 py-1 text-center text-xs placeholder:font-bold placeholder:text-gray-400 focus:border-gray-800 focus:outline-none rounded"
          spellcheck="false"
          autocomplete="off"
          @focus="hoveredQuestion = 18"
          @blur="hoveredQuestion = null"
        />
        <button
          v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
          @click.stop="toggleFlag(18)"
          class="absolute -top-2 -right-7 flex h-5 w-5 items-center justify-center rounded border transition-all"
          :class="isQuestionFlagged(18) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'">
          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Question 19 -->
    <div class="flex items-center  gap-3  p-3 hover:bg-gray-100 transition-colors"
         @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
      <div class="flex items-center gap-2 min-w-0">
        <span class="font-bold text-gray-700">19.</span>

      </div>
      <div class="relative flex-shrink-0">
        <input
          v-model="answers.q19"
          type="text"
          placeholder="Answer"
          class="w-60 border border-gray-500 bg-transparent px-2 py-1 text-center text-xs placeholder:font-bold placeholder:text-gray-400 focus:border-gray-800 focus:outline-none rounded"
          spellcheck="false"
          autocomplete="off"
          @focus="hoveredQuestion = 19"
          @blur="hoveredQuestion = null"
        />
        <button
          v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
          @click.stop="toggleFlag(19)"
          class="absolute -top-2 -right-7 flex h-5 w-5 items-center justify-center rounded border transition-all"
          :class="isQuestionFlagged(19) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'">
          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Question 20 -->
    <div class="flex items-center gap-3  p-3 hover:bg-gray-100 transition-colors"
         @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
      <div class="flex items-center gap-2 min-w-0">
        <span class="font-bold text-gray-700">20.</span>

      </div>
      <div class="relative flex-shrink-0">
        <input
          v-model="answers.q20"
          type="text"
          placeholder="Answer"
          class="w-60 border border-gray-500 bg-transparent px-2 py-1 text-center text-xs placeholder:font-bold placeholder:text-gray-400 focus:border-gray-800 focus:outline-none rounded"
          spellcheck="false"
          autocomplete="off"
          @focus="hoveredQuestion = 20"
          @blur="hoveredQuestion = null"
        />
        <button
          v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
          @click.stop="toggleFlag(20)"
          class="absolute -top-2 -right-7 flex h-5 w-5 items-center justify-center rounded border transition-all"
          :class="isQuestionFlagged(20) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'">
          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
          </svg>
        </button>
      </div>
    </div>

  </div>
</div>
                        <!-- End map -->

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

        <!-- Delete highlight tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-[9999]"
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

        <!-- Note hover tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="max-w-[180px] text-base break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-base focus:borderlack focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100">Cancel</button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
            </div>
        </div>

    </Teleport>
</template>

<style scoped>
.part-header-box {
    background-color: #F1F2EC;
}

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0%   { background-color: rgba(0, 0, 0, 0.1); }
    50%  { background-color: rgba(0, 0, 0, 0.25); }
    100% { background-color: rgba(0, 0, 0, 0.1); }
}

.select-text {
    user-select: text;
    -webkit-user-select: text;
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
}

.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

.highlight-tooltip { position: fixed; z-index: 9999; }

.tooltip-arrow {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}

.tooltip-arrow-up {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    borderottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}

.tooltip-arrow-down {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}

input:focus { background-color: rgba(0,0,0,0.02); }

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #000; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #374151; }
</style>

<style>
.note-highlight {
    borderottom: 2px solid rgba(0, 0, 0, 0.4);
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
.note-highlight:hover { borderottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>
