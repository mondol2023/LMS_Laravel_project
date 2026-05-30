<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
}>();

// Reading Part 3 component

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts011-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight } = useHighlight();

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

const passageText =
    ref(`Many past societies collapsed or vanished, leaving behind monumental ruins such as those that the poet Shelley imagined in his sonnet, Ozymandias. By collapse, I mean a drastic decrease in human population size and/or political/economic/social complexity, over a considerable area, for an extended time. By those standards, most people would consider the following past societies to have been famous victims of full-fledged collapses rather than of just minor declines: the Anasazi and Cahokia within the boundaries of the modern US, the Maya cities in Central America, Moche and Tiwanaku societies in South America, Norse Greenland, Mycenean Greece and Minoan Crete in Europe, Great Zimbabwe in Africa, Angkor Wat and the Harappan Indus Valley cities in Asia, and Easter Island in the Pacific Ocean. The monumental ruins left behind by those past societies hold a fascination for all of us. We marvel at them when as children we first learn of them through pictures. When we grow up, many of us plan vacations in order to experience them at first hand. We feel drawn to their often spectacular and haunting beauty, and also to the mysteries that they pose. The scales of the ruins testify to the former wealth and power of their builders. Yet these builders vanished, abandoning the great structures that they had created at such effort. How could a society that was once so mighty end up collapsing? It has long been suspected that many of those mysterious abandonments were at least partly triggered by ecological problems: people inadvertently destroying the environmental resources on which their societies depended. This suspicion of unintended ecological suicide (ecocide) has been confirmed by discoveries made in recent decades by archaeologists, climatologists, historians, paleontologists, and palynologists (pollen scientists). The processes through which past societies have undermined themselves by damaging their environments fall into eight categories, whose relative importance differs from case to case: deforestation and habitat destruction, soil problems, water management problems, overhunting, overfishing, effects of introduced species on native species, human population growth, and increased impact of people. 

Those past collapses tended to follow somewhat similar courses constituting variations on a theme. Writers find it tempting to draw analogies between the course of human societies and the course of individual human lives – to talk of a society’s birth, growth, peak, old age and eventual death. But that metaphor proves erroneous for many past societies: they declined rapidly after reaching peak numbers and power, and those rapid declines must have come as a surprise and shock to their citizens. Obviously, too, this trajectory is not one that all past societies followed unvaryingly to completion: different societies collapsed to different degrees and in somewhat different ways, while many societies did not collapse at all. 

Today many people feel that environmental problems overshadow all the other threats to global civilisation. These environmental problems include the same eight that undermined past societies, plus four new ones: human-caused climate change, build up of toxic chemicals in the environment, energy shortages, and full human utilisation of the Earth’s photosynthetic capacity. But the seriousness of these current environmental problems is vigorously debated. Are the risks greatly exaggerated, or conversely are they underestimated? Will modern technology solve our problems, or is it creating new problems faster than it solves old ones? When we deplete one resource (e.g. wood, oil, or ocean fish), can we count on being able to substitute some new resource (e.g. plastics, wind and solar energy, or farmed fish)? Isn’t the rate of human population growth declining, such that we’re already on course for the world’s population to level off at some manageable number of people? 

Questions like this illustrate why those famous collapses of past civilisations have taken on more meaning than just that of a romantic mystery. Perhaps there are some practical lessons that we could learn from all those past collapses. But there are also differences between the modern world and its problems, and those past societies and their problems. We shouldn’t be so naive as to think that study of the past will yield simple solutions, directly transferable to our societies today. We differ from past societies in some respects that put us at lower risk than them; some of those respects often mentioned include our powerful technology (i.e. its beneficial effects), globalisation, modern medicine, and greater knowledge of past societies and of distant modern societies. We also differ from past societies in some respects that put us at greater risk than them: again, our potent technology (i.e., its unintended destructive effects), globalisation (such that now a problem in one part of the world affects all the rest), the dependence of millions of us on modern medicine for our survival, and our much larger human population. Perhaps we can still learn from the past, but only if we think carefully about its lessons.`);

const textSegments = ref([
    { text: passageText.value, offset: 0 },
    { text: 'READING PASSAGE 3', offset: 5693 },
    { text: 'Questions 27-29', offset: 5712 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 5729 },
    { text: 'When the writer describes the impact of monumental ruins today, he emphasises', offset: 5774 },
    { text: 'A. the income they generate from tourism.', offset: 5855 },
    { text: 'B. the area of land they occupy.', offset: 5895 },
    { text: 'C. their archaeological value.', offset: 5928 },
    { text: 'D. their romantic appeal.', offset: 5958 },
    { text: 'Recent findings concerning vanished civilisations have', offset: 5984 },
    { text: 'A. overturned long-held beliefs.', offset: 6043 },
    { text: 'B. caused controversy amongst scientists.', offset: 6075 },
    { text: 'C. come from a variety of disciplines.', offset: 6116 },
    { text: 'D. identified one main cause of environmental damage.', offset: 6157 },
    { text: 'What does the writer say about ways in which former societies collapsed?', offset: 6216 },
    { text: 'A. The pace of decline was usually similar.', offset: 6288 },
    { text: 'B. The likelihood of collapse would have been foreseeable.', offset: 6331 },
    { text: 'C. Deterioration invariably led to total collapse.', offset: 6390 },
    { text: 'D. Individual citizens could sometimes influence the course of events.', offset: 6441 },
    { text: 'Questions 30-34', offset: 6519 },
    { text: 'Do the following statements agree with the views of the writer in Reading Passage 3?', offset: 6536 },
    { text: 'YES', offset: 6620 },
    { text: 'if the statement agrees with the views of the writer', offset: 6623 },
    { text: 'NO', offset: 6677 },
    { text: 'if the statement contradicts the views of the writer', offset: 6679 },
    { text: 'NOT GIVEN', offset: 6735 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 6746 },
    { text: 'It is widely believed that environmental problems represent the main danger faced by the modern world.', offset: 6825 },
    { text: 'The accumulation of poisonous substances is a relatively modern problem.', offset: 6928 },
    { text: 'There is general agreement that the threats posed by environmental problems are very serious.', offset: 7005 },
    { text: 'Some past societies resembled present-day societies more closely than others.', offset: 7098 },
    { text: 'We should be careful when drawing comparisons between past and present.', offset: 7183 },
    { text: 'Questions 35-39', offset: 7268 },
    { text: 'Complete each sentence with the correct ending, A-F, below.', offset: 7285 },
    { text: 'Evidence of the greatness of some former civilisations', offset: 7351 },
    { text: 'The parallel between an individual’s life and the life of a society', offset: 7409 },
    { text: 'The number of environmental problems that societies face', offset: 7484 },
    { text: 'The power of technology', offset: 7546 },
    { text: 'A consideration of historical events and trends', offset: 7572 },
    { text: 'is not necessarily valid.', offset: 7622 },
    { text: 'provides grounds for an optimistic outlook.', offset: 7650 },
    { text: 'exists in the form of physical structures.', offset: 7697 },
    { text: 'is potentially both positive and negative.', offset: 7741 },
    { text: 'will not provide direct solutions for present problems.', offset: 7786 },
    { text: 'is greater now than in the past.', offset: 7848 },
    { text: 'Question 40', offset: 7884 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 7897 },
    { text: 'What is the main argument of Reading Passage 3?', offset: 7942 },
    { text: 'A. There are differences as well as similarities between past and present societies.', offset: 7995 },
    { text: 'B. More should be done to preserve the physical remains of earlier civilisations.', offset: 8080 },
    { text: 'C. Some historical accounts of great civilisations are inaccurate.', offset: 8165 },
    { text: 'D. Modern societies are dependent on each other for their continuing survival.', offset: 8234 },
]);

const highlightedPassageText = computed(() => {
    return applyHighlightsToText(passageText.value);
});

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentText: string) => {
    // Find this segment's offset
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    // Apply highlights to this segment
    // Sort by start offset descending
    const sorted = [...overlappingHighlights].sort((a, b) => b.start_offset - a.start_offset);

    let result = segmentText;
    for (const highlight of sorted) {
        // Calculate the position within this segment
        const highlightStart = Math.max(0, highlight.start_offset - segmentOffset);
        const highlightEnd = Math.min(segmentText.length, highlight.end_offset - segmentOffset);

        if (highlightStart < highlightEnd) {
            const before = result.substring(0, highlightStart);
            const highlighted = result.substring(highlightStart, highlightEnd);
            const after = result.substring(highlightEnd);

            result = `${before}<mark class="highlight-${highlight.color}" data-highlight-id="${highlight.id}">${highlighted}</mark>${after}`;
        }
    }

    return result;
};

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};
// Watch for changes and auto-save
watch(
    answers,
    () => {
        saveAnswers();
    },
    { deep: true },
);

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

const scrollToHighlight = async (highlightId: number) => {
    await nextTick();
    const element = document.querySelector(`mark[data-highlight-id="${highlightId}"]`);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Optional: Add a temporary visual effect to the scrolled highlight
        element.classList.add('highlight-flash');
        setTimeout(() => {
            element.classList.remove('highlight-flash');
        }, 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-text]');

            if (!segmentEl) {
                return null;
            }

            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) {
                console.error('Segment not found for text:', segmentText);
                return null;
            }

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) {
                    offsetInSegment += offsetInNode;
                    break;
                } else {
                    offsetInSegment += currentNode.textContent?.length || 0;
                }
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

        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.bottom + 5;

            const viewportWidth = window.innerWidth;
            const menuWidth = 250;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2), viewportWidth - menuWidth / 2),
                y: menuY,
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

    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);

    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;

    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: selectionRange.value.start,
        end: selectionRange.value.end,
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

const closeContextMenu = () => {
    showContextMenu.value = false;
};

const handleDeleteHighlight = (id: number) => {
    deleteHighlight(id);
};

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) {
        showContextMenu.value = false;
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

// Watch notes and emit changes
watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

// Load saved answers when component mounts
onMounted(async () => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
});

defineExpose({
    getAnswers,
    scrollToQuestion,
    scrollToHighlight,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <div class="container mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div
                    class="passage-panel mb-4 max-h-screen overflow-y-auto rounded-r-lg bg-white shadow-lg lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
                        <div class="flex-shrink-0 border-b border-gray-200 bg-white p-6">
                            <div class="mb-4 flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p
                                        class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg"
                                        :data-segment-text="'READING PASSAGE 3'"
                                        v-html="getHighlightedSegment('READING PASSAGE 3')"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h2
                                        class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        <span
                                            :data-segment-text="'History of telegraph in communication'"
                                            v-html="getHighlightedSegment('History of telegraph in communication')"
                                        ></span>
                                    </h2>
                                </div>
                                <button
                                    class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200 sm:text-base"
                                    title="Select text to highlight"
                                >
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 space-y-6 overflow-y-auto p-6">
                            <div class="space-y-6 text-base leading-relaxed select-text sm:text-lg">
                                <div class="rounded-lg border border-green-100 bg-gradient-to-r from-green-50 to-emerald-50 p-4 shadow-md">
                                    <div
                                        class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700"
                                        :data-segment-text="passageText"
                                        v-html="getHighlightedSegment(passageText)"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-blue-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-blue-500"></div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
                        <!-- Questions Header (Fixed) -->
                        <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg">QUESTIONS</p>
                                    <p class="text-xs text-gray-500">Answer all questions based on Reading Passage 3</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto bg-green-50 pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 27–29 -->
                                <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-100 p-6 shadow-lg">
                                    <div class="mb-4 flex items-start gap-4">
                                        <div>
                                            <p
                                                class="text-xl font-semibold text-green-800"
                                                :data-segment-text="'Questions 27-29'"
                                                v-html="getHighlightedSegment('Questions 27-29')"
                                            ></p>
                                            <p
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'Choose the correct letter, A, B, C or D.'"
                                                v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"
                                            ></p>
                                        </div>
                                    </div>
                                    <div class="space-y-6">
                                        <!-- Question 27 -->
                                        <div class="rounded-xl border border-green-200 bg-white p-5 shadow-md">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 text-base font-bold text-white shadow-lg"
                                                >
                                                    27
                                                </div>
                                                <p
                                                    class="flex-1 font-semibold text-gray-800"
                                                    :data-segment-text="'When the writer describes the impact of monumental ruins today, he emphasises'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'When the writer describes the impact of monumental ruins today, he emphasises',
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                            <div class="mt-4 space-y-1 pl-2">
                                                <label
                                                    v-for="(option, index) in ['A', 'B', 'C', 'D']"
                                                    :key="'27-' + option"
                                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-green-50"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="q27"
                                                        :value="option"
                                                        v-model="answers.q27"
                                                        class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                    />
                                                    <span
                                                        class="font-medium text-gray-700"
                                                        :data-segment-text="
                                                            [
                                                                'A. the income they generate from tourism.',
                                                                'B. the area of land they occupy.',
                                                                'C. their archaeological value.',
                                                                'D. their romantic appeal.',
                                                            ][index]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'A. the income they generate from tourism.',
                                                                    'B. the area of land they occupy.',
                                                                    'C. their archaeological value.',
                                                                    'D. their romantic appeal.',
                                                                ][index],
                                                            )
                                                        "
                                                    ></span>
                                                </label>
                                            </div>
                                        </div>
                                        <!-- Question 28 -->
                                        <div class="rounded-xl border border-green-200 bg-white p-5 shadow-md">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 text-base font-bold text-white shadow-lg"
                                                >
                                                    28
                                                </div>
                                                <p
                                                    class="flex-1 font-semibold text-gray-800"
                                                    :data-segment-text="'Recent findings concerning vanished civilisations have'"
                                                    v-html="getHighlightedSegment('Recent findings concerning vanished civilisations have')"
                                                ></p>
                                            </div>
                                            <div class="mt-4 space-y-1 pl-2">
                                                <label
                                                    v-for="(option, index) in ['A', 'B', 'C', 'D']"
                                                    :key="'28-' + option"
                                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-green-50"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="q28"
                                                        :value="option"
                                                        v-model="answers.q28"
                                                        class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                    />
                                                    <span
                                                        class="font-medium text-gray-700"
                                                        :data-segment-text="
                                                            [
                                                                'A. overturned long-held beliefs.',
                                                                'B. caused controversy amongst scientists.',
                                                                'C. come from a variety of disciplines.',
                                                                'D. identified one main cause of environmental damage.',
                                                            ][index]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'A. overturned long-held beliefs.',
                                                                    'B. caused controversy amongst scientists.',
                                                                    'C. come from a variety of disciplines.',
                                                                    'D. identified one main cause of environmental damage.',
                                                                ][index],
                                                            )
                                                        "
                                                    ></span>
                                                </label>
                                            </div>
                                        </div>
                                        <!-- Question 29 -->
                                        <div class="rounded-xl border border-green-200 bg-white p-5 shadow-md">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 text-base font-bold text-white shadow-lg"
                                                >
                                                    29
                                                </div>
                                                <p
                                                    class="flex-1 font-semibold text-gray-800"
                                                    :data-segment-text="'What does the writer say about ways in which former societies collapsed?'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'What does the writer say about ways in which former societies collapsed?',
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                            <div class="mt-4 space-y-1 pl-2">
                                                <label
                                                    v-for="(option, index) in ['A', 'B', 'C', 'D']"
                                                    :key="'29-' + option"
                                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-green-50"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="q29"
                                                        :value="option"
                                                        v-model="answers.q29"
                                                        class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                    />
                                                    <span
                                                        class="font-medium text-gray-700"
                                                        :data-segment-text="
                                                            [
                                                                'A. The pace of decline was usually similar.',
                                                                'B. The likelihood of collapse would have been foreseeable.',
                                                                'C. Deterioration invariably led to total collapse.',
                                                                'D. Individual citizens could sometimes influence the course of events.',
                                                            ][index]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'A. The pace of decline was usually similar.',
                                                                    'B. The likelihood of collapse would have been foreseeable.',
                                                                    'C. Deterioration invariably led to total collapse.',
                                                                    'D. Individual citizens could sometimes influence the course of events.',
                                                                ][index],
                                                            )
                                                        "
                                                    ></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions 30–34 -->
                                <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-100 p-6 shadow-lg">
                                    <div class="mb-4 flex items-start gap-4">
                                        <div>
                                            <p
                                                class="text-xl font-semibold text-green-800"
                                                :data-segment-text="'Questions 30-34'"
                                                v-html="getHighlightedSegment('Questions 30-34')"
                                            ></p>
                                            <p
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the views of the writer in Reading Passage 3?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the views of the writer in Reading Passage 3?',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                    </div>
                                    <div class="mb-6 rounded-xl border border-green-200 bg-white p-4 shadow-md">
                                        <div class="grid grid-cols-1 gap-2 text-sm sm:text-base">
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-28 rounded-md bg-green-100 p-2 text-center font-bold text-green-700 shadow-lg"
                                                    :data-segment-text="'YES'"
                                                    >YES</span
                                                >
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'if the statement agrees with the views of the writer'"
                                                    v-html="getHighlightedSegment('if the statement agrees with the views of the writer')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-28 rounded-md bg-red-100 p-2 text-center font-bold text-red-700 shadow-lg"
                                                    :data-segment-text="'NO'"
                                                    >NO</span
                                                >
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'if the statement contradicts the views of the writer'"
                                                    v-html="getHighlightedSegment('if the statement contradicts the views of the writer')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-28 rounded-md bg-gray-200 p-2 text-center font-bold text-gray-700 shadow-lg"
                                                    :data-segment-text="'NOT GIVEN'"
                                                    >NOT GIVEN</span
                                                >
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'if it is impossible to say what the writer thinks about this'"
                                                    v-html="getHighlightedSegment('if it is impossible to say what the writer thinks about this')"
                                                ></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div v-for="i in 5" :key="i" class="rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 text-base font-bold text-white shadow-lg"
                                                >
                                                    {{ 29 + i }}
                                                </div>
                                                <p
                                                    class="flex-1 font-semibold text-gray-800"
                                                    :data-segment-text="
                                                        [
                                                            'It is widely believed that environmental problems represent the main danger faced by the modern world.',
                                                            'The accumulation of poisonous substances is a relatively modern problem.',
                                                            'There is general agreement that the threats posed by environmental problems are very serious.',
                                                            'Some past societies resembled present-day societies more closely than others.',
                                                            'We should be careful when drawing comparisons between past and present.',
                                                        ][i - 1]
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            [
                                                                'It is widely believed that environmental problems represent the main danger faced by the modern world.',
                                                                'The accumulation of poisonous substances is a relatively modern problem.',
                                                                'There is general agreement that the threats posed by environmental problems are very serious.',
                                                                'Some past societies resembled present-day societies more closely than others.',
                                                                'We should be careful when drawing comparisons between past and present.',
                                                            ][i - 1],
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                            <div class="mt-3 ml-4 flex gap-4 pr-4">
                                                <label class="flex cursor-pointer items-center gap-2">
                                                    <input
                                                        type="radio"
                                                        :name="`q${29 + i}`"
                                                        value="YES"
                                                        v-model="answers[`q${29 + i}`]"
                                                        class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                    />
                                                    <span class="font-semibold text-green-700">YES</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-2">
                                                    <input
                                                        type="radio"
                                                        :name="`q${29 + i}`"
                                                        value="NO"
                                                        v-model="answers[`q${29 + i}`]"
                                                        class="h-5 w-5 border-gray-300 text-red-600 focus:ring-red-500"
                                                    />
                                                    <span class="font-semibold text-red-700">NO</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-2">
                                                    <input
                                                        type="radio"
                                                        :name="`q${29 + i}`"
                                                        value="NOT GIVEN"
                                                        v-model="answers[`q${29 + i}`]"
                                                        class="h-5 w-5 border-gray-300 text-gray-600 focus:ring-gray-500"
                                                    />
                                                    <span class="font-semibold text-gray-600">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Questions 35–39 -->
                                <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-100 p-6 shadow-lg">
                                    <div class="mb-4 flex items-start gap-4">
                                        <div>
                                            <p
                                                class="text-xl font-semibold text-green-800"
                                                :data-segment-text="'Questions 35-39'"
                                                v-html="getHighlightedSegment('Questions 35-39')"
                                            ></p>
                                            <p
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'Complete each sentence with the correct ending, A-F, below.'"
                                                v-html="getHighlightedSegment('Complete each sentence with the correct ending, A-F, below.')"
                                            ></p>
                                        </div>
                                    </div>
                                    <div class="mb-6 rounded-xl border border-green-200 bg-white p-4 shadow-md">
                                        <ul class="space-y-2 text-gray-700">
                                            <li
                                                v-for="(ending, index) in [
                                                    'is not necessarily valid.',
                                                    'provides grounds for an optimistic outlook.',
                                                    'exists in the form of physical structures.',
                                                    'is potentially both positive and negative.',
                                                    'will not provide direct solutions for present problems.',
                                                    'is greater now than in the past.',
                                                ]"
                                                :key="index"
                                            >
                                                <span class="font-bold text-green-700">{{ String.fromCharCode(65 + index) }}</span>
                                                <span :data-segment-text="ending" v-html="getHighlightedSegment(ending)"></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="space-y-4">
                                        <div
                                            v-for="i in 5"
                                            :key="i"
                                            class="flex items-center gap-4 rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-sm"
                                        >
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                >{{ 34 + i }}</span
                                            >
                                            <label
                                                :for="`q${34 + i}`"
                                                class="flex-1 font-medium text-gray-800"
                                                :data-segment-text="
                                                    [
                                                        'Evidence of the greatness of some former civilisations',
                                                        'The parallel between an individual’s life and the life of a society',
                                                        'The number of environmental problems that societies face',
                                                        'The power of technology',
                                                        'A consideration of historical events and trends',
                                                    ][i - 1]
                                                "
                                                v-html="
                                                    getHighlightedSegment(
                                                        [
                                                            'Evidence of the greatness of some former civilisations',
                                                            'The parallel between an individual’s life and the life of a society',
                                                            'The number of environmental problems that societies face',
                                                            'The power of technology',
                                                            'A consideration of historical events and trends',
                                                        ][i - 1],
                                                    )
                                                "
                                            ></label>
                                            <select
                                                :id="`q${34 + i}`"
                                                v-model="answers[`q${34 + i}`]"
                                                class="w-24 rounded-md border-2 border-green-300 bg-green-50 px-3 py-1 text-sm focus:border-green-500 focus:bg-white focus:outline-none"
                                            >
                                                <option disabled value="">Select</option>
                                                <option v-for="char in ['A', 'B', 'C', 'D', 'E', 'F']" :key="char" :value="char">{{ char }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question 40 -->
                                <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-100 p-6 shadow-lg">
                                    <div class="mb-4 flex items-start gap-4">
                                        <div
                                            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-600 text-lg font-bold text-white shadow-md"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                        >
                                            40
                                        </div>
                                        <div>
                                            <p
                                                class="text-xl font-semibold text-green-800"
                                                :data-segment-text="'Question 40'"
                                                v-html="getHighlightedSegment('Question 40')"
                                            ></p>
                                            <p
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'Choose the correct letter, A, B, C or D.'"
                                                v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"
                                            ></p>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-green-200 bg-white p-5 shadow-md">
                                        <p
                                            class="mb-4 font-semibold text-gray-800"
                                            :data-segment-text="'What is the main argument of Reading Passage 3?'"
                                            v-html="getHighlightedSegment('What is the main argument of Reading Passage 3?')"
                                        ></p>
                                        <div class="space-y-3">
                                            <label
                                                v-for="(option, index) in ['A', 'B', 'C', 'D']"
                                                :key="'40-' + option"
                                                class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-green-50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q40"
                                                    :value="option"
                                                    v-model="answers.q40"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-700"
                                                    :data-segment-text="
                                                        [
                                                            'A. There are differences as well as similarities between past and present societies.',
                                                            'B. More should be done to preserve the physical remains of earlier civilisations.',
                                                            'C. Some historical accounts of great civilisations are inaccurate.',
                                                            'D. Modern societies are dependent on each other for their continuing survival.',
                                                        ][index]
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            [
                                                                'A. There are differences as well as similarities between past and present societies.',
                                                                'B. More should be done to preserve the physical remains of earlier civilisations.',
                                                                'C. Some historical accounts of great civilisations are inaccurate.',
                                                                'D. Modern societies are dependent on each other for their continuing survival.',
                                                            ][index],
                                                        )
                                                    "
                                                ></span>
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

    <!-- Context Menu for Highlighting -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="pointer-events-auto fixed z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
                :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <div class="flex items-center gap-2">
                    <button
                        v-for="color in colors"
                        :key="color.name"
                        @click="applyHighlight(color.name)"
                        :class="[color.class, 'h-8 w-8 rounded-md border border-gray-300 transition-transform hover:scale-110']"
                        :title="`Highlight ${color.name}`"
                    ></button>
                    <!-- Note Button -->
                    <button
                        @click="openNoteInput"
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-indigo-50 transition-all hover:scale-110 hover:bg-indigo-100"
                        title="Add Note"
                    >
                        <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                    </button>
                    <button
                        @click="closeContextMenu"
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 hover:bg-gray-100"
                        title="Cancel"
                    >
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="absolute z-[9999] w-80 rounded-lg border-2 border-indigo-300 bg-white p-4 shadow-2xl"
            :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }"
            @click.stop
        >
            <div class="mb-3">
                <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                <input
                    v-model="noteInputText"
                    type="text"
                    placeholder="Write your note here..."
                    class="note-input-field w-full rounded-lg border-2 border-indigo-200 px-3 py-2 text-base focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none sm:text-lg"
                    @keyup.enter="saveNote"
                    @keyup.escape="cancelNote"
                />
            </div>
            <div class="flex justify-end gap-2">
                <button
                    @click="cancelNote"
                    class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200"
                >
                    Cancel
                </button>
                <button
                    @click="saveNote"
                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-indigo-700"
                >
                    Save Note
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: col-resize;
}

.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
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
        background-color: rgba(34, 197, 94, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(34, 197, 94, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(34, 197, 94, 0.1);
        transform: scale(1);
    }
}

.highlight-flash {
    animation: flashHighlight 1.5s ease-out;
}

@keyframes flashHighlight {
    0% {
        background-color: yellow;
    }
    100% {
        background-color: transparent;
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
    background: linear-gradient(to bottom, #10b981, #059669);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #059669, #047857);
}
</style>
