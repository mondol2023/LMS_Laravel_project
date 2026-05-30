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
    ref(`In general, it is plausible to suppose that we should prefer peace and quiet to noise. And yet most of us have had the experience of having to adjust to sleeping in the mountains or the countryside because it was initially 'too quiet', an experience that suggests that humans are capable of adapting to a wide range of noise levels. Research supports this view. For example, Glass and Singer (1972) exposed people to short bursts of very loud noise and then measured their ability to work out problems and their physiological reactions to the noise. The noise was quite disruptive at first, but after about four minutes the subjects were doing just as well on their tasks as control subjects who were not exposed to noise. Their physiological arousal also declined quickly to the same levels as those of the control subjects. 

But there are limits to adaptation and loud noise becomes more troublesome if the person is required to concentrate on more than one task. For example, high noise levels interfered with the performance of subjects who were required to monitor three dials at a time, a task not unlike that of an aeroplane pilot or an air-traffic controller (Broadbent, 1957). Similarly, noise did not affect a subject's ability to track a moving line with a steering wheel, but it did interfere with the subject's ability to repeat numbers while tracking (Finke man and Glass 1970). 

Probably the most significant finding from research on noise is that its predictability is more important than how loud it is. We are much more able to 'tune out' chronic, background noise, even if it is quite loud than to work under circumstances with unexpected intrusions of noise. In the Glass and Singer study, in which subjects were exposed to bursts of noise as they worked on a task, some subjects heard loud bursts and others heard soft bursts. For some subjects, the bursts were spaced exactly one minute apart (predictable noise); others heard the same amount of noise overall, but the bursts occurred at random intervals (unpredictable noise).

<div class="bg-green-50 to-lime-50 flex items-center justify-center p-4">

  <div class="bg-white/80  rounded-xl shadow-md border border-green-200">
    <table class="text-sm text-center text-gray-700">
      <thead>
        <tr class="bg-gradient-to-r from-emerald-500 to-green-500 text-white">
          <th class="px-3 py-2 font-medium"></th>
          <th class="px-3 py-2 font-medium">Unpredictable Noise</th>
          <th class="px-3 py-2 font-medium">Predictable Noise</th>
          <th class="px-3 py-2 font-medium">Average</th>
        </tr>
      </thead>
      <tbody>
        <tr class="hover:bg-green-50 transition">
          <td class="px-3 py-2 font-medium text-emerald-700">Loud noise</td>
          <td class="px-3 py-2">40.1</td>
          <td class="px-3 py-2">31.8</td>
          <td class="px-3 py-2 font-semibold text-green-600">35.9</td>
        </tr>
        <tr class="bg-green-50 hover:bg-green-100 transition">
          <td class="px-3 py-2 font-medium text-emerald-700">Soft noise</td>
          <td class="px-3 py-2">36.7</td>
          <td class="px-3 py-2">27.4</td>
          <td class="px-3 py-2 font-semibold text-green-600">32.1</td>
        </tr>
        <tr class="border-t border-green-200 bg-emerald-50">
          <td class="px-3 py-2 font-semibold text-emerald-800">Average</td>
          <td class="px-3 py-2 font-semibold">38.4</td>
          <td class="px-3 py-2 font-semibold">29.6</td>
          <td class="px-3 py-2"></td>
        </tr>
      </tbody>
    </table>
  </div>

</div>
<h2 class="my-2 text-xl font-bold text-emerald-700">Table 1: Proofreading Errors and Noise</h2>
Subjects reported finding the predictable and unpredictable noise equally annoying, and all subjects performed at about the same level during the noise portion of the experiment- But the different noise conditions had quite different after-effects when the subjects were required to proofread written material under conditions of no noise. As shown in Table 1 the unpredictable noise produced more errors in the later proofreading task than predictable noise; and soft, unpredictable noise actually produced slightly more errors on this task than the loud, predictable noise.Apparently, unpredictable noise produces more fatigue than predictable noise, but it takes a while for this fatigue to take its toll on performance. 

Predictability is not the only variable that reduces or eliminates the negative effects of noise. Another is "control". If the individual knows that he or she can control the noise, this seems to eliminate both its negative effects at the time and its after-effects. This is true even if the individual never actually exercises his or her option to turn the noise off (Glass and Singer, 1972). Just the knowledge that one has control is sufficient. 
 The studies discussed so far exposed people lo noise for only short periods and only transient effects were studied. But the major worry about noisy environments is that living day after day with chronic noise may produce serious, lasting effects. One study, suggesting that this worry is a realistic one, compared elementary school pupils who attended schools near Los Angeles's busiest airport with students who attended schools in quiet neighborhoods (Cohen et al., 1980). It was found that children from the noisy schools had higher blood pressure and were more easily distracted than those who attended the quiet schools. Moreover, there was no evidence of adaptability to the noise. In fact, the longer the children had attended the noisy schools, the more distractible they became. The effects also seem to be long-lasting. A follow-up study showed that children who were moved to less noisy classrooms still showed greater distractibility one year later than students who had always been in the quiet schools (Cohen et al, 1981). It should be noted that the two groups of children had been carefully matched by the investigators so that they were comparable in age, ethnicity, race, and social class.
`);

const initialTextSegments = [
    { text: passageText.value, offset: 0 },
    { text: 'READING PASSAGE 3' },
    { text: 'History of telegraph in communication' },

    { text: 'Questions 27-29' },
    { text: 'Choose the correct letter, A, B, C or D.' },

    { text: 'The writer suggests that people may have difficulty sleeping in the mountains because' },
    { text: 'A. humans do not prefer peace and quiet to noise.' },
    { text: 'B. they may be exposed to short bursts of very strange sounds.' },
    { text: 'C. humans prefer to hear a certain amount of noise while they sleep.' },
    { text: 'D. they may have adapted to a higher noise level in the city.' },

    { text: 'In noise experiments, Glass and Singer found that' },
    { text: 'A. problem-solving is much easier under quiet conditions.' },
    { text: 'B. physiological arousal prevents the ability to work.' },
    { text: 'C. bursts of noise do not seriously disrupt problem-solving in the long term.' },
    { text: 'D. the physiological arousal of control subjects declined quickly.' },

    { text: 'Researchers discovered that high noise levels are not likely to interfere with the' },
    { text: 'A. successful performance of a single task.' },
    { text: 'B. tasks of pilots or air traffic controllers.' },
    { text: 'C. ability to repeat numbers while tracking moving lines.' },
    { text: 'D. ability to monitor three dials at once.' },

    { text: 'Questions 30-34' },
    {
        text: 'Complete the summary using the list of words and phrases, A-J, below.\nWrite the correct letter A-J in boxes 30-34 on your answer sheet.\nNB You may use any letter more than once.',
    },

    {
        text: 'Glass and Singer (1972) showed that situations in which there is intense noise have less effect on performance than circumstances in which ',
    },
    {
        text: ' noise occurs. Subjects were divided into groups to perform a task. Some heard loud bursts of noise, others sort. For some subjects, the noise was predictable, while for others its occurrence was random. All groups were exposed to ',
    },
    { text: ' noise. The predictable noise group ' },
    {
        text: ' the unpredictable noise group on this task. In the second part of the experiment, the four groups were given a proofreading task to complete under conditions of no noise. They were required to check written material for errors. The group which had been exposed to unpredictable noise ',
    },
    { text: ' the group which had been exposed to predictable noise. The results suggest that ' },
    { text: ' noise produces fatigue but that this manifests itself later.' },

    { text: 'A. no control over' },
    { text: 'B. unexpected' },
    { text: 'C. intense' },
    { text: 'D. the same amount of' },
    { text: 'E. performed better than' },
    { text: 'F. performed at about the same level as' },
    { text: 'G. no' },
    { text: 'H. showed more irritation than' },
    { text: 'I. made more mistakes than' },
    { text: 'J. different types of' },

    { text: 'Questions 35-40' },
    {
        text: 'Look at the following statements (Questions 35-40) and the list of researchers below.\nMatch each statement with the correct researcher(s), A-E.\nNB You may use any letter more than once.',
    },
    { text: 'Subjects exposed to noise find it difficult at first to concentrate on problem-solving tasks.' },
    { text: 'Long-term exposure to noise can produce changes in behavior which can still be observed a year later.' },
    { text: 'The problems associated with exposure to noise do not arise if the subject knows they can make it stop.' },
    { text: 'Exposure to high-pitched noise results in more errors than exposure to low-pitched noise' },
    { text: 'Subjects find it difficult to perform three tasks at the same time when exposed to noise' },
    { text: "Noise affects a subject's capacity to repeat numbers while carrying out another task." },
    { text: 'List of Researchers' },
    { text: 'A. Glass and Singer' },
    { text: 'B. Broadbent' },
    { text: 'C. Finke man and Glass' },
    { text: 'D. Cohen et al.' },
    { text: 'E. None of the above' },
];

const textSegments = ref(
    initialTextSegments.reduce(
        (acc, segment, index) => {
            const previous = index > 0 ? acc[index - 1] : null;
            const offset = previous ? (previous.offset || 0) + previous.text.length + 1 : 0;

            return [...acc, { ...segment, offset }];
        },
        [] as Array<{ text: string; offset?: number }>,
    ),
);

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
                                        :data-segment-text="textSegments[1].text"
                                        v-html="getHighlightedSegment(textSegments[1].text)"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h2
                                        class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        <span :data-segment-text="textSegments[2].text" v-html="getHighlightedSegment(textSegments[2].text)"></span>
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
                                <div class="rounded-lg border border-green-100 bg-gradient-to-r from-green-50 to-green-100 p-4 shadow-md">
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-emerald-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-emerald-500"></div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-emerald-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-emerald-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-emerald-600"></div>
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-green-600">
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
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 27-29 Multiple Choice -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="textSegments[3].text"
                                            v-html="getHighlightedSegment(textSegments[3].text)"
                                        ></h3>
                                        <p
                                            class="text-gray-700"
                                            :data-segment-text="textSegments[4].text"
                                            v-html="getHighlightedSegment(textSegments[4].text)"
                                        ></p>
                                        <div class="mt-4 space-y-3">
                                            <div
                                                v-for="(qNum, index) in [27, 28, 29]"
                                                :key="qNum"
                                                :id="`question-${qNum}`"
                                                class="rounded-xl border-l-4 border-emerald-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 text-lg font-bold text-white shadow-md"
                                                        style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2)"
                                                        >{{ qNum }}</span
                                                    >
                                                    <p
                                                        class="font-semibold text-gray-800"
                                                        :data-segment-text="textSegments[5 + index * 5].text"
                                                        v-html="getHighlightedSegment(textSegments[5 + index * 5].text)"
                                                    ></p>
                                                </div>
                                                <div class="mt-2 space-y-2 pl-11">
                                                    <label
                                                        v-for="optionIndex in [0, 1, 2, 3]"
                                                        :key="optionIndex"
                                                        class="flex cursor-pointer items-center gap-2"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :name="`q${qNum}`"
                                                            :value="['A', 'B', 'C', 'D'][optionIndex]"
                                                            v-model="answers[`q${qNum}`]"
                                                            class="h-4 w-4 accent-green-500"
                                                        />
                                                        <span
                                                            class="font-medium text-gray-700"
                                                            :data-segment-text="textSegments[6 + index * 5 + optionIndex].text"
                                                            v-html="getHighlightedSegment(textSegments[6 + index * 5 + optionIndex].text)"
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions 30-34 Summary Completion -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="textSegments[20].text"
                                            v-html="getHighlightedSegment(textSegments[20].text)"
                                        ></h3>
                                        <p
                                            class="whitespace-pre-wrap text-gray-700"
                                            :data-segment-text="textSegments[21].text"
                                            v-html="getHighlightedSegment(textSegments[21].text)"
                                        ></p>

                                        <div class="mt-4 space-y-1 rounded-lg border border-green-400 bg-green-100 p-4 leading-relaxed text-gray-700">
                                            <strong>List of Words and Phrases:</strong>
                                            <div class="mt-2 space-y-1">
                                                <div
                                                    v-for="i in 10"
                                                    :key="i"
                                                    class="text-gray-700"
                                                    :data-segment-text="textSegments[27 + i].text"
                                                    v-html="getHighlightedSegment(textSegments[27 + i].text)"
                                                ></div>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 leading-relaxed text-gray-700">
                                            <span
                                                :data-segment-text="textSegments[22].text"
                                                v-html="getHighlightedSegment(textSegments[22].text)"
                                            ></span>
                                            <div class="inline-flex items-center gap-2">
                                                <span
                                                    class="flex h-6 w-6 transform cursor-pointer items-center justify-center rounded-full bg-emerald-600 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105"
                                                    >30</span
                                                >
                                                <select
                                                    v-model="answers.q30"
                                                    class="my-2 inline-block w-32 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option
                                                        v-for="option in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']"
                                                        :key="option"
                                                        :value="option"
                                                    >
                                                        {{ option }}
                                                    </option>
                                                </select>
                                            </div>
                                            <span
                                                :data-segment-text="textSegments[23].text"
                                                v-html="getHighlightedSegment(textSegments[23].text)"
                                            ></span>
                                            <div class="inline-flex items-center gap-2">
                                                <span
                                                    class="flex h-6 w-6 transform cursor-pointer items-center justify-center rounded-full bg-emerald-600 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105"
                                                    >31</span
                                                >
                                                <select
                                                    v-model="answers.q31"
                                                    class="my-2 inline-block w-32 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option
                                                        v-for="option in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']"
                                                        :key="option"
                                                        :value="option"
                                                    >
                                                        {{ option }}
                                                    </option>
                                                </select>
                                            </div>
                                            <span
                                                :data-segment-text="textSegments[24].text"
                                                v-html="getHighlightedSegment(textSegments[24].text)"
                                            ></span>
                                            <div class="inline-flex items-center gap-2">
                                                <span
                                                    class="flex h-6 w-6 transform cursor-pointer items-center justify-center rounded-full bg-emerald-600 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105"
                                                    >32</span
                                                >
                                                <select
                                                    v-model="answers.q32"
                                                    class="my-2 inline-block w-32 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option
                                                        v-for="option in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']"
                                                        :key="option"
                                                        :value="option"
                                                    >
                                                        {{ option }}
                                                    </option>
                                                </select>
                                            </div>
                                            <span
                                                :data-segment-text="textSegments[25].text"
                                                v-html="getHighlightedSegment(textSegments[25].text)"
                                            ></span>
                                            <div class="inline-flex items-center gap-2">
                                                <span
                                                    class="flex h-6 w-6 transform cursor-pointer items-center justify-center rounded-full bg-emerald-600 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105"
                                                    >33</span
                                                >
                                                <select
                                                    v-model="answers.q33"
                                                    class="my-2 inline-block w-32 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option
                                                        v-for="option in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']"
                                                        :key="option"
                                                        :value="option"
                                                    >
                                                        {{ option }}
                                                    </option>
                                                </select>
                                            </div>
                                            <span
                                                :data-segment-text="textSegments[26].text"
                                                v-html="getHighlightedSegment(textSegments[26].text)"
                                            ></span>
                                            <div class="inline-flex items-center gap-2">
                                                <span
                                                    class="flex h-6 w-6 transform cursor-pointer items-center justify-center rounded-full bg-emerald-600 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105"
                                                    >34</span
                                                >
                                                <select
                                                    v-model="answers.q34"
                                                    class="my-2 inline-block w-32 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option value="">Select</option>
                                                    <option
                                                        v-for="option in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']"
                                                        :key="option"
                                                        :value="option"
                                                    >
                                                        {{ option }}
                                                    </option>
                                                </select>
                                            </div>
                                            <span
                                                :data-segment-text="textSegments[27].text"
                                                v-html="getHighlightedSegment(textSegments[27].text)"
                                            ></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions 35-40 Matching -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="textSegments[38].text"
                                            v-html="getHighlightedSegment(textSegments[38].text)"
                                        ></h3>
                                        <p
                                            class="whitespace-pre-wrap text-gray-700"
                                            :data-segment-text="textSegments[39].text"
                                            v-html="getHighlightedSegment(textSegments[39].text)"
                                        ></p>
                                        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4">
                                            <h4
                                                class="font-bold text-gray-800"
                                                :data-segment-text="textSegments[46].text"
                                                v-html="getHighlightedSegment(textSegments[46].text)"
                                            ></h4>
                                            <div class="mt-2 grid grid-cols-1 gap-1">
                                                <div
                                                    v-for="i in 5"
                                                    :key="i"
                                                    :data-segment-text="textSegments[46 + i].text"
                                                    v-html="getHighlightedSegment(textSegments[46 + i].text)"
                                                ></div>
                                            </div>
                                        </div>
                                        <div class="mt-4 space-y-3">
                                            <div
                                                v-for="qNum in [35, 36, 37, 38, 39, 40]"
                                                :key="qNum"
                                                :id="`question-${qNum}`"
                                                class="rounded-xl border-l-4 border-emerald-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-600 font-semibold text-white shadow-lg"
                                                        >{{ qNum }}</span
                                                    >
                                                    <div class="flex-1">
                                                        <p
                                                            class="text-gray-700"
                                                            :data-segment-text="textSegments[40 + (qNum - 35)].text"
                                                            v-html="getHighlightedSegment(textSegments[40 + (qNum - 35)].text)"
                                                        ></p>
                                                        <select
                                                            v-model="answers[`q${qNum}`]"
                                                            class="mt-2 w-full rounded-lg border-2 border-gray-200 px-3 py-2 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                        >
                                                            <option value="">Select</option>
                                                            <option v-for="option in ['A', 'B', 'C', 'D', 'E']" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
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
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-emerald-50 transition-all hover:scale-110 hover:bg-emerald-100"
                        title="Add Note"
                    >
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            class="absolute z-[9999] w-80 rounded-lg border-2 border-emerald-300 bg-white p-4 shadow-2xl"
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
                    class="note-input-field w-full rounded-lg border-2 border-emerald-200 px-3 py-2 text-base focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:outline-none sm:text-lg"
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
                    class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-emerald-700"
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
