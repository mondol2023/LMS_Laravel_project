<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Reading Part 1 component

interface Props {}

const props = withDefaults(defineProps<Props>(), {});

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

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-c19t1-part1-panel-width';
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

const passageText = `In 2016, the British professional tennis player Andy Murray was ranked as the world\u2019s number one. It was an incredible achievement by any standard \u2013 made even more remarkable by the fact that he did this during a period considered to be one of the strongest in the sport\u2019s history, competing against the likes of Rafael Nadal, Roger Federer and Novak Djokovic, to name just a few. Yet five years previously, he had been regarded as a talented outsider who entered but never won the major tournaments.

Of the changes that account for this transformation, one was visible and widely publicised: in 2011, Murray invited former number one player Ivan Lendl onto his coaching team \u2013 a valuable addition that had a visible impact on the player\u2019s playing style. Another change was so subtle as to pass more or less unnoticed. Like many players, Murray has long preferred a racket that consists of two types of string: one for the mains (verticals) and another for the crosses (horizontals). While he continued to use natural string in the crosses, in 2012 he switched to a synthetic string for the mains. A small change, perhaps, but its importance should not be underestimated.

The modification that Murray made is just one of a number of options available to players looking to tweak their rackets in order to improve their games. \u2018Touring professionals have their rackets customised to their specific needs,\u2019 says Colin Triplow, a UK-based professional racket stringer. \u2018It\u2019s a highly important part of performance maximisation.\u2019 Consequently, the specific rackets used by the world\u2019s elite are not actually readily available to the public; rather, each racket is individually made to suit the player who uses it. Take the US professional tennis players Mike and Bob Bryan, for example: \u2018We\u2019re very particular with our racket specifications,\u2019 they say. \u2018All our rackets are sent from our manufacturer to Tampa, Florida, where our frames go through a . . . thorough customisation process.\u2019 They explain how they have adjusted not only racket length, but even experimented with different kinds of paint. The rackets they use now weigh more than the average model and also have a denser string pattern (i.e. more crosses and mains).

The primary reason for these modifications is simple: as the line between winning and losing becomes thinner and thinner, even these slight changes become more and more important. As a result, players and their teams are becoming increasingly creative with the modifications to their rackets as they look to maximise their competitive advantage.

Racket modifications mainly date back to the 1970s, when the amateur German tennis player Werner Fischer started playing with the so-called spaghetti-strung racket. It created a string bed that generated so much topspin that it was quickly banned by the International Tennis Federation. However, within a decade or two, racket modification became a regularity. Today it is, in many ways, an aspect of the game that is equal in significance to nutrition or training.

Modifications can be divided into two categories: those to the string bed and those to the racket frame. The former is far more common than the latter: the choice of the strings and the tension with which they are installed is something that nearly all professional players experiment with. They will continually change it depending on various factors including the court surface, climatic conditions, and game styles. Some will even change it depending on how they feel at the time.

At one time, all tennis rackets were strung with natural gut made from the outer layer of sheep or cow intestines. This all changed in the early 1990s with the development of synthetic strings that were cheaper and more durable. They are made from three materials: nylon (relatively durable and affordable), Kevlar (too stiff to be used alone) or co-polyester (polyester combined with additives that enhance its performance). Even so, many professional players continue to use a \u2018hybrid set-up\u2019, where a combination of both synthetic and natural strings are used.

Of the synthetics, co-polyester is by far the most widely used. It\u2019s a perfect fit for the style of tennis now played, where players tend to battle it out from the back of the court rather than coming to the net. Studies indicate that the average spin from a co-polyester string is 25% greater than that from natural string or other synthetics. In a sense, the development of co-polyester strings has revolutionised the game.

However, many players go beyond these basic adjustments to the strings and make changes to the racket frame itself. For example, much of the serving power of US professional player Pete Sampras was attributed to the addition of four to five lead weights onto his rackets, and today many professionals have the weight adjusted during the manufacturing process.

Other changes to the frame involve the handle. Players have individual preferences for the shape of the handle and some will have the handle of one racket moulded onto the frame of a different racket. Other players make different changes. The professional Portuguese player Gon\u00e7alo Oliveira replaced the original grips of his rackets with something thinner because they had previously felt uncomfortable to hold.

Racket customisation and modification have pushed the standards of the game to greater levels that few could have anticipated in the days of natural strings and heavy, wooden frames, and it\u2019s exciting to see what further developments there will be in the future.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 1', offset: 4500 },
    { text: 'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.', offset: 4518 },
    { text: 'How tennis rackets have changed', offset: 4614 },
    { text: 'Questions 1-7', offset: 4645 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 4659 },
    { text: 'In boxes 1-7 on your answer sheet, write', offset: 4741 },
    { text: 'TRUE', offset: 4782 },
    { text: 'if the statement agrees with the information', offset: 4787 },
    { text: 'FALSE', offset: 4832 },
    { text: 'if the statement contradicts the information', offset: 4838 },
    { text: 'NOT GIVEN', offset: 4883 },
    { text: 'if there is no information on this', offset: 4893 },
    { text: "People had expected Andy Murray to become the world's top tennis player for at least five years before 2016.", offset: 4927 },
    { text: 'The change that Andy Murray made to his rackets attracted a lot of attention.', offset: 5036 },
    { text: "Most of the world's top players take a professional racket stringer on tour with them.", offset: 5113 },
    { text: 'Mike and Bob Bryan use rackets that are light in comparison to the majority of rackets.', offset: 5200 },
    { text: 'Werner Fischer played with a spaghetti-strung racket that he designed himself.', offset: 5288 },
    { text: 'The weather can affect how professional players adjust the strings on their rackets.', offset: 5367 },
    { text: 'It was believed that the change Pete Sampras made to his rackets contributed to his strong serve.', offset: 5451 },
    { text: 'Questions 8-13', offset: 5550 },
    { text: 'Complete the notes below.', offset: 5565 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 5591 },
    { text: 'Write your answers in boxes 8-13 on your answer sheet.', offset: 5647 },
    { text: 'The tennis racket and how it has changed', offset: 5702 },
    { text: 'Mike and Bob Bryan', offset: 5743 },
    { text: '\u2022 made changes to the types of ', offset: 5762 },
    { text: ' used on their racket frames.', offset: 5793 },
    { text: 'The spaghetti-strung racket', offset: 5822 },
    { text: '\u2022 Players were not allowed to use the spaghetti-strung racket because of the amount of ', offset: 5850 },
    { text: ' it created.', offset: 5937 },
    { text: 'Changes to rackets today', offset: 5950 },
    { text: '\u2022 Changes to rackets can be regarded as being as important as players\u2019 diets or the ', offset: 5975 },
    { text: ' they do.', offset: 6059 },
    { text: 'Types of string', offset: 6069 },
    { text: '\u2022 All rackets used to have natural strings made from the ', offset: 6084 },
    { text: ' of animals.', offset: 6141 },
    { text: 'Changes to the racket frame', offset: 6154 },
    { text: '\u2022 Pete Sampras had metal ', offset: 6183 },
    { text: ' put into the frames of his rackets.', offset: 6207 },
    { text: 'Changes to the racket handle', offset: 6243 },
    { text: '\u2022 Gon\u00e7alo Oliveira changed the ', offset: 6272 },
    { text: ' on his racket handles.', offset: 6303 },
]);

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

// Initialize answers and load drafts

// Save answers to drafts

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

// Watch for changes and auto-save
watch(answers, () => {}, { deep: true });

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

const handleContentTextSelect = (event: MouseEvent) => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        if (!range) return;

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

    // Position note input below the context menu
    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    // Focus the input after a small delay
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

    // Clear and close
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="min-h-screen overflow-y-auto">
        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div
                    class="passage-panel mb-4 max-h-screen overflow-y-auto rounded-r-lg bg-white shadow-lg lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
                        <div class="flex-shrink-0 border-b border-gray-200 bg-white p-6">
                            <div class="mb-4 flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
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
                                        class="text-sm font-semibold tracking-wide text-gray-700 uppercase"
                                        :data-segment-text="'READING PASSAGE 1'"
                                        v-html="getHighlightedSegment('READING PASSAGE 1')"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p
                                        class="mb-1 text-base sm:text-lg"
                                        :data-segment-text="'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.',
                                            )
                                        "
                                    ></p>
                                    <h2
                                        class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        <span
                                            :data-segment-text="'How tennis rackets have changed'"
                                            v-html="getHighlightedSegment('How tennis rackets have changed')"
                                        ></span>
                                    </h2>
                                </div>
                                <button
                                    class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200 sm:text-sm"
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
                            <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-lg">
                                <div class="rounded-lg border border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 shadow-md">
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
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
                                    <p
                                        class="text-sm font-semibold tracking-wide text-gray-700 uppercase"
                                        :data-segment-text="'QUESTIONS'"
                                        v-html="getHighlightedSegment('QUESTIONS')"
                                    ></p>
                                    <p
                                        class="text-xs text-gray-500"
                                        :data-segment-text="'Answer all questions based on Reading Passage 1'"
                                        v-html="getHighlightedSegment('Answer all questions based on Reading Passage 1')"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-7 -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-1-7" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 1-7'"
                                            v-html="getHighlightedSegment('Questions 1-7')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-50 p-4 shadow-inner">
                                            <p
                                                class="mb-3 text-sm text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the information given in Reading Passage 1?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the information given in Reading Passage 1?',
                                                    )
                                                "
                                            ></p>
                                            <div class="rounded-xl border border-purple-200 bg-white p-6 shadow-sm">
                                                <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                    <span
                                                        :data-segment-text="'In boxes 1-7 on your answer sheet, write'"
                                                        v-html="getHighlightedSegment('In boxes 1-7 on your answer sheet, write')"
                                                    ></span>
                                                </p>
                                                <div class="grid grid-cols-1 gap-2 pt-4 text-base sm:text-lg">
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-purple-100 px-2 py-1 font-bold text-purple-700"
                                                            :data-segment-text="'TRUE'"
                                                            v-html="getHighlightedSegment('TRUE')"
                                                        ></span>
                                                        <span
                                                            :data-segment-text="'if the statement agrees with the information'"
                                                            class="text-gray-700"
                                                            v-html="getHighlightedSegment('if the statement agrees with the information')"
                                                        ></span>
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-red-100 px-2 py-1 font-bold text-red-700"
                                                            :data-segment-text="'FALSE'"
                                                            v-html="getHighlightedSegment('FALSE')"
                                                        ></span>
                                                        <span
                                                            :data-segment-text="'if the statement contradicts the information'"
                                                            class="text-gray-700"
                                                            v-html="getHighlightedSegment('if the statement contradicts the information')"
                                                        ></span>
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-28 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                            :data-segment-text="'NOT GIVEN'"
                                                            v-html="getHighlightedSegment('NOT GIVEN')"
                                                        ></span>
                                                        <span
                                                            :data-segment-text="'if there is no information on this'"
                                                            class="text-gray-700"
                                                            v-html="getHighlightedSegment('if there is no information on this')"
                                                        ></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Q1 -->
                                            <div
                                                id="question-1"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >1</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'People had expected Andy Murray to become the world\'s top tennis player for at least five years before 2016.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'People had expected Andy Murray to become the world\'s top tennis player for at least five years before 2016.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q1"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q2 -->
                                            <div
                                                id="question-2"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >2</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'The change that Andy Murray made to his rackets attracted a lot of attention.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The change that Andy Murray made to his rackets attracted a lot of attention.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q2"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q3 -->
                                            <div
                                                id="question-3"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >3</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Most of the world\'s top players take a professional racket stringer on tour with them.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Most of the world\'s top players take a professional racket stringer on tour with them.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q3"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q4 -->
                                            <div
                                                id="question-4"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >4</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Mike and Bob Bryan use rackets that are light in comparison to the majority of rackets.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Mike and Bob Bryan use rackets that are light in comparison to the majority of rackets.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q4"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q5 -->
                                            <div
                                                id="question-5"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >5</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Werner Fischer played with a spaghetti-strung racket that he designed himself.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Werner Fischer played with a spaghetti-strung racket that he designed himself.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q5"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q6 -->
                                            <div
                                                id="question-6"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >6</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'The weather can affect how professional players adjust the strings on their rackets.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The weather can affect how professional players adjust the strings on their rackets.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q6"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q7 -->
                                            <div
                                                id="question-7"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >7</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'It was believed that the change Pete Sampras made to his rackets contributed to his strong serve.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'It was believed that the change Pete Sampras made to his rackets contributed to his strong serve.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q7"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 8-13 -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-8-13" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 8-13'"
                                            v-html="getHighlightedSegment('Questions 8-13')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm text-gray-800"
                                                :data-segment-text="'Complete the notes below.'"
                                                v-html="getHighlightedSegment('Complete the notes below.')"
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3 rounded-lg bg-white/50 p-4">
                                            <h4
                                                class="text-md font-bold text-blue-800"
                                                :data-segment-text="'The tennis racket and how it has changed'"
                                                v-html="getHighlightedSegment('The tennis racket and how it has changed')"
                                            ></h4>

                                            <h5
                                                class="font-semibold text-gray-700"
                                                :data-segment-text="'Mike and Bob Bryan'"
                                                v-html="getHighlightedSegment('Mike and Bob Bryan')"
                                            ></h5>

                                            <!-- Q8 -->
                                            <div id="question-8" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >8</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'\u2022 made changes to the types of '"
                                                        v-html="getHighlightedSegment('\u2022 made changes to the types of ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q8"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' used on their racket frames.'"
                                                        v-html="getHighlightedSegment(' used on their racket frames.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'The spaghetti-strung racket'"
                                                v-html="getHighlightedSegment('The spaghetti-strung racket')"
                                            ></h5>

                                            <!-- Q9 -->
                                            <div id="question-9" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >9</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'\u2022 Players were not allowed to use the spaghetti-strung racket because of the amount of '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                '\u2022 Players were not allowed to use the spaghetti-strung racket because of the amount of ',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q9"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span :data-segment-text="' it created.'" v-html="getHighlightedSegment(' it created.')"></span>
                                                </p>
                                            </div>

                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Changes to rackets today'"
                                                v-html="getHighlightedSegment('Changes to rackets today')"
                                            ></h5>

                                            <!-- Q10 -->
                                            <div id="question-10" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >10</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'\u2022 Changes to rackets can be regarded as being as important as players\u2019 diets or the '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                '\u2022 Changes to rackets can be regarded as being as important as players\u2019 diets or the ',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q10"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span :data-segment-text="' they do.'" v-html="getHighlightedSegment(' they do.')"></span>
                                                </p>
                                            </div>

                                            <h5 class="pt-2 font-semibold text-gray-700">Types of string</h5>

                                            <!-- Q11 -->
                                            <div id="question-11" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >11</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'\u2022 All rackets used to have natural strings made from the '"
                                                        v-html="
                                                            getHighlightedSegment('\u2022 All rackets used to have natural strings made from the ')
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q11"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span :data-segment-text="' of animals.'" v-html="getHighlightedSegment(' of animals.')"></span>
                                                </p>
                                            </div>

                                            <h5 class="pt-2 font-semibold text-gray-700">Changes to the racket frame</h5>

                                            <!-- Q12 -->
                                            <div id="question-12" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >12</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'\u2022 Pete Sampras had metal '"
                                                        v-html="getHighlightedSegment('\u2022 Pete Sampras had metal ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q12"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' put into the frames of his rackets.'"
                                                        v-html="getHighlightedSegment(' put into the frames of his rackets.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <h5 class="pt-2 font-semibold text-gray-700">Changes to the racket handle</h5>

                                            <!-- Q13 -->
                                            <div id="question-13" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >13</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'\u2022 Gon\u00e7alo Oliveira changed the '"
                                                        v-html="getHighlightedSegment('\u2022 Gon\u00e7alo Oliveira changed the ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q13"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' on his racket handles.'"
                                                        v-html="getHighlightedSegment(' on his racket handles.')"
                                                    ></span>
                                                </p>
                                            </div>
                                        </div>
                                    </section>
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
                    class="note-input-field w-full rounded-lg border-2 border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
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

.highlight-indigo {
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
        background-color: rgba(59, 130, 246, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(59, 130, 246, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(59, 130, 246, 0.1);
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
    background: linear-gradient(to bottom, #3b82f6, #6366f1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #4f46e5);
}
</style>
