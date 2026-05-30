ç
<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

// Reading Part 2 component

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts011-part2-panel-width';
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
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q24: '',
    q25: '',
    q26: '',
});

interface MultipleAnswersRecord {
    q20_21: string[];
    q22_23: string[];
}

const multipleAnswers = ref<MultipleAnswersRecord>({
    q20_21: [],
    q22_23: [],
});

const passageText = `A. It has been pointed out that learning mathematics and science is not so much learning facts as learning ways of thinking. It has also been emphasised that in order to learn science, people often have to change the way they think in ordinary situations. For example, in order to understand even simple concepts such as heat and temperature, ways of thinking of temperature as a measure of heat must be abandoned and a distinction between ‘temperature’ and ‘heat’ must be learned. These changes in ways of thinking are often referred to as conceptual changes. But how do conceptual changes happen? How do young people change their ways of thinking as they develop and as they learn in school?

B. Traditional instruction based on telling students how modern scientists think does not seem to be very successful. Students may learn the definitions, the formulae, the terminology, and yet still maintain their previous conceptions. This difficulty has been illustrated many times, for example, when instructed students are interviewed about heat and temperature. It is often identified by teachers as a difficulty in applying the concepts learned in the classroom; students may be able to repeat a formula but fail to use the concept represented by the formula when they explain observed events.

C. The psychologist Piaget suggested an interesting hypothesis relating to the process of cognitive change in children. Cognitive change was expected to result from the pupils’ own intellectual activity. When confronted with a result that challenges their thinking – that is, when faced with conflict – pupils realise that they need to think again about their own ways of solving problems, regardless of whether the problem is one in mathematics or in science. He hypothesised that conflict brings about disequilibrium, and then triggers equilibration processes that ultimately produce cognitive change. For this reason, according to Piaget and his colleagues, in order for pupils to progress in their thinking they need to be actively engaged in solving problems that will challenge their current mode of reasoning. However, Piaget also pointed out that young children do not always discard their ideas in the face of contradictory evidence. They may actually discard the evidence and keep their theory.

D. Piaget’s hypothesis about how cognitive change occurs was later translated into an educational approach which is now termed ‘discovery learning’. Discovery learning initially took what is now considered the Tone learner’ route. The role of the teacher was to select situations that challenged the pupils’ reasoning; and the pupils’ peers had no real role in this process. However, it was subsequently proposed that interpersonal conflict, especially with peers, might play an important role in promoting cognitive change. This hypothesis, originally advanced by Perret-Clermont (1980) and Doise and Mugny (1984), has been investigated in many recent studies of science teaching and learning.

E. Christine Howe and her colleagues, for example, have compared children’s progress in understanding several types of science concepts when they are given the opportunity to observe relevant events. In one study, Howe compared the progress of 8 to 12-year-old children in understanding what influences motion down a slope. In order to ascertain the role of conflict in group work, they created two kinds of groups according to a pre-test: one in which the children had dissimilar views, and a second in which the children had similar views. They found support for the idea that children in the groups with dissimilar views progressed more after their training sessions than those who had been placed in groups with similar views. However, they found no evidence to support the idea that the children worked out their new conceptions during their group discussions, because progress was not actually observed in a post-test immediately after the sessions of group work, but rather in a second test given around four weeks after the group work.

F. In another study, Howe set out to investigate whether the progress obtained through pair work could be a function of the exchange of ideas. They investigated the progress made by 12-15-year-old pupils in understanding the path of falling objects, a topic that usually involves conceptual difficulties. In order to create pairs of pupils with varying levels of dissimilarity in their initial conceptions, the pupils’ predictions and explanations of the path of falling objects were assessed before they were engaged in pair work. The work sessions involved solving computer-presented problems, again about predicting and explaining the paths of falling objects. A post-test, given to individuals, assessed the progress made by pupils in their conceptions of what influenced the path of falling objects.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 2', offset: 5543 },
    { text: 'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', offset: 5562 },
    { text: 'Questions 14-19', offset: 5658 },
    { text: 'Reading Passage 2 has SIX paragraphs, A-F.', offset: 5683 },
    { text: 'Choose the correct heading for paragraphs A-F from the list of headings below.', offset: 5727 },
    { text: 'Choose the correct number, i-ix', offset: 5809 },
    { text: 'List of Headings', offset: 5843 },
    { text: 'A suggested modification to a theory about learning.', offset: 5863 },
    { text: 'The problem of superficial understanding.', offset: 5919 },
    { text: 'The relationship between scientific understanding and age.', offset: 5963 },
    { text: 'The rejection of a widely held theory.', offset: 6025 },
    { text: 'The need to develop new concepts in daily life.', offset: 6066 },
    { text: 'The claim that a perceived contradiction can assist mental development.', offset: 6118 },
    { text: 'Implications for the training of science teachers.', offset: 6195 },
    { text: 'An experiment to assess the benefits of exchanging views with a partner.', offset: 6250 },
    { text: 'Evidence for the delayed benefits of disagreement between pupils.', offset: 6330 },
    { text: 'Paragraph A', offset: 6406 },
    { text: 'Paragraph B', offset: 6419 },
    { text: 'Paragraph C', offset: 6432 },
    { text: 'Paragraph D', offset: 6445 },
    { text: 'Paragraph E', offset: 6458 },
    { text: 'Paragraph F', offset: 6471 },
    { text: 'Questions 20-21', offset: 6484 },
    { text: 'Choose TWO letters, A-E.', offset: 6501 },
    { text: 'Which TWO of these statements are attributed to Piaget by the writer of the passage?', offset: 6596 },
    { text: 'A teachers can assist learning by explaining difficult concepts', offset: 6681 },
    { text: 'B mental challenge is a stimulus to learning', offset: 6745 },
    { text: 'C repetition and consistency of input aid cognitive development', offset: 6791 },
    { text: 'D children sometimes reject evidence that conflicts with their preconceptions', offset: 6861 },
    { text: 'E children can help each other make cognitive progress', offset: 6942 },
    { text: 'Questions 22-23', offset: 7004 },
    { text: 'Choose TWO letters, A-E.', offset: 7021 },
    { text: "Which TWO of these statements describe Howe's experiment with 8-12-year-olds?", offset: 7047 },
    { text: 'A the children were assessed on their ability to understand a scientific problem', offset: 7132 },
    { text: 'B all the children were working in mixed ability groups', offset: 7208 },
    { text: 'C the children who were the most talkative made the least progress', offset: 7265 },
    { text: 'D the teacher helped the children to understand a scientific problem', offset: 7332 },
    { text: 'E the children were given a total of three tests at different times', offset: 7404 },
    { text: 'Questions 24-26', offset: 7475 },
    { text: 'Complete the summary below. Choose NO MORE THAN TWO WORDS from the passage for each answer.', offset: 7492 },
    { text: 'How children learn', offset: 7586 },
    {
        text: "Piaget proposed that learning takes place when children encounter ideas that do not correspond to their current beliefs. The application of this theory gave rise to a teaching method known as 24............At first this approach only focused on the relationship between individual pupils and their 25............Later, researchers such as Perret-Clermont became interested in the role that interaction with 26............might also play in a pupil's development.",
        offset: 7605,
    },
]);

const handleMultipleChoice = (questionGroup: keyof MultipleAnswersRecord, option: string) => {
    const key = questionGroup;
    const currentAnswers = multipleAnswers.value[key];

    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        currentAnswers.splice(index, 1);
    } else {
        const maxAllowed = 2;
        if (currentAnswers.length < maxAllowed) {
            currentAnswers.push(option);
        }
    }
};

const isSelected = (questionGroup: keyof MultipleAnswersRecord, option: string) => {
    const arr = multipleAnswers.value[questionGroup];
    return Array.isArray(arr) && arr.includes(option);
};

const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    const sorted = [...overlappingHighlights].sort((a, b) => b.start_offset - a.start_offset);

    let result = segmentText;
    for (const highlight of sorted) {
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
    const combinedAnswers: Record<string, any> = { ...answers.value };

    const { q20_21, q22_23 } = multipleAnswers.value;

    combinedAnswers.q20 = q20_21[0] || '';
    combinedAnswers.q21 = q20_21[1] || '';
    combinedAnswers.q22 = q22_23[0] || '';
    combinedAnswers.q23 = q22_23[1] || '';

    return combinedAnswers;
};

// Watch for changes and auto-save
watch(
    [answers, multipleAnswers],
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600">
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
                                        :data-segment-text="'READING PASSAGE 2'"
                                        v-html="getHighlightedSegment('READING PASSAGE 2')"
                                    ></p>
                                </div>
                            </div>
                            <p
                                class="text-sm tracking-wide text-gray-700"
                                :data-segment-text="'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.'"
                                v-html="
                                    getHighlightedSegment(
                                        'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.',
                                    )
                                "
                            ></p>
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    <span
                                        :data-segment-text="'WATER TREATMENT: REED BED'"
                                        v-html="getHighlightedSegment('WATER TREATMENT: REED BED')"
                                    ></span>
                                </h2>
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
                                <div class="rounded-xl border border-purple-100 bg-gradient-to-br from-purple-50 to-pink-50 p-6 shadow-md">
                                    <div
                                        class="text-justify leading-relaxed font-medium whitespace-pre-wrap text-gray-700"
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600">
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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">QUESTIONS</p>
                                    <p class="text-xs text-gray-500">Answer all questions based on Reading Passage 2</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 14-19 -->
                                <div
                                    class="rounded-xl border border-pink-200 bg-gradient-to-r from-pink-50 to-purple-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-14-19" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 14-19'"
                                            v-html="getHighlightedSegment('Questions 14-19')"
                                        ></h3>
                                        <div class="rounded-xl border border-pink-300 bg-pink-50 p-3 shadow-inner sm:p-4">
                                            <p
                                                class="mb-2 text-sm font-medium text-gray-800 sm:text-base"
                                                :data-segment-text="'Reading Passage 2 has SIX paragraphs, A-F.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has SIX paragraphs, A-F.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm font-medium text-gray-800 sm:text-base"
                                                :data-segment-text="'Choose the correct heading for paragraphs A-F from the list of headings below.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Choose the correct heading for paragraphs A-F from the list of headings below.',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-600 sm:text-base"
                                                :data-segment-text="'Choose the correct number, i-ix'"
                                                v-html="getHighlightedSegment('Choose the correct number, i-ix')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                            <h4
                                                class="mb-3 text-center font-bold text-purple-700"
                                                :data-segment-text="'List of Headings'"
                                                v-html="getHighlightedSegment('List of Headings')"
                                            ></h4>
                                            <ul class="space-y-2 text-sm text-gray-700">
                                                <li>
                                                    <strong class="text-purple-600">i</strong>
                                                    <span
                                                        :data-segment-text="'A suggested modification to a theory about learning.'"
                                                        v-html="getHighlightedSegment('A suggested modification to a theory about learning.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">ii</strong>
                                                    <span
                                                        :data-segment-text="'The problem of superficial understanding.'"
                                                        v-html="getHighlightedSegment('The problem of superficial understanding.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">iii</strong>
                                                    <span
                                                        :data-segment-text="'The relationship between scientific understanding and age.'"
                                                        v-html="getHighlightedSegment('The relationship between scientific understanding and age.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">iv</strong>
                                                    <span
                                                        :data-segment-text="'The rejection of a widely held theory.'"
                                                        v-html="getHighlightedSegment('The rejection of a widely held theory.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">v</strong>
                                                    <span
                                                        :data-segment-text="'The need to develop new concepts in daily life.'"
                                                        v-html="getHighlightedSegment('The need to develop new concepts in daily life.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">vi</strong>
                                                    <span
                                                        :data-segment-text="'The claim that a perceived contradiction can assist mental development.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The claim that a perceived contradiction can assist mental development.',
                                                            )
                                                        "
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">vii</strong>
                                                    <span
                                                        :data-segment-text="'Implications for the training of science teachers.'"
                                                        v-html="getHighlightedSegment('Implications for the training of science teachers.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">viii</strong>
                                                    <span
                                                        :data-segment-text="'An experiment to assess the benefits of exchanging views with a partner.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'An experiment to assess the benefits of exchanging views with a partner.',
                                                            )
                                                        "
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="text-purple-600">ix</strong>
                                                    <span
                                                        :data-segment-text="'Evidence for the delayed benefits of disagreement between pupils.'"
                                                        v-html="
                                                            getHighlightedSegment('Evidence for the delayed benefits of disagreement between pupils.')
                                                        "
                                                    ></span>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="space-y-3 pt-4">
                                            <div
                                                v-for="n in 6"
                                                :key="n"
                                                class="flex items-center gap-3 rounded-lg border-l-4 border-pink-500 bg-white p-3 shadow-lg"
                                            >
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                                >
                                                    {{ 13 + n }}
                                                </div>
                                                <span
                                                    class="flex-1 font-semibold text-gray-700"
                                                    :data-segment-text="'Paragraph ' + String.fromCharCode(64 + n)"
                                                    v-html="getHighlightedSegment('Paragraph ' + String.fromCharCode(64 + n))"
                                                ></span>
                                                <select
                                                    v-model="answers['q' + (13 + n)]"
                                                    class="w-24 rounded-lg border border-pink-300 bg-pink-50 px-2 py-1.5 text-sm shadow-md focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="h in ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix']" :key="h" :value="h">
                                                        {{ h }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 20-26 -->
                                <div class="space-y-8">
                                    <!-- Questions 20-21 -->
                                    <div
                                        id="question-20-21"
                                        class="rounded-2xl border border-pink-200 bg-gradient-to-br from-pink-50 to-purple-100 p-6 shadow-lg"
                                    >
                                        <div class="mb-4 flex items-start gap-4">
                                            <div>
                                                <p
                                                    class="text-xl font-semibold text-pink-800"
                                                    :data-segment-text="'Questions 20-21'"
                                                    v-html="getHighlightedSegment('Questions 20-21')"
                                                ></p>
                                                <p
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'Choose TWO letters, A-E.'"
                                                    v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                                ></p>
                                            </div>
                                        </div>
                                        <p
                                            class="mb-4 text-lg font-semibold text-gray-800"
                                            :data-segment-text="'Which TWO of these statements are attributed to Piaget by the writer of the passage?'"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Which TWO of these statements are attributed to Piaget by the writer of the passage?',
                                                )
                                            "
                                        ></p>
                                        <div class="mt-4 space-y-3">
                                            <label
                                                v-for="(option, index) in ['A', 'B', 'C', 'D', 'E']"
                                                :key="'20-21-' + option"
                                                @click.prevent="handleMultipleChoice('q20_21', option)"
                                                :class="[
                                                    'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all sm:rounded-xl sm:p-4',
                                                    isSelected('q20_21', option)
                                                        ? 'border-pink-500 bg-pink-100 shadow-md'
                                                        : 'border-pink-200 bg-white hover:bg-pink-50',
                                                ]"
                                            >
                                                <div
                                                    class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded border-2"
                                                    :class="isSelected('q20_21', option) ? 'border-pink-600 bg-pink-500' : 'border-gray-400'"
                                                >
                                                    <svg
                                                        v-if="isSelected('q20_21', option)"
                                                        class="h-3 w-3 text-white"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </div>
                                                <span class="text-base font-medium text-gray-800 sm:text-lg">
                                                    <span
                                                        :data-segment-text="
                                                            [
                                                                'A teachers can assist learning by explaining difficult concepts',
                                                                'B mental challenge is a stimulus to learning',
                                                                'C repetition and consistency of input aid cognitive development',
                                                                'D children sometimes reject evidence that conflicts with their preconceptions',
                                                                'E children can help each other make cognitive progress',
                                                            ][index]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'A teachers can assist learning by explaining difficult concepts',
                                                                    'B mental challenge is a stimulus to learning',
                                                                    'C repetition and consistency of input aid cognitive development',
                                                                    'D children sometimes reject evidence that conflicts with their preconceptions',
                                                                    'E children can help each other make cognitive progress',
                                                                ][index],
                                                            )
                                                        "
                                                    ></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Questions 22-23 -->
                                    <div
                                        id="question-22-23"
                                        class="rounded-2xl border border-pink-200 bg-gradient-to-br from-pink-50 to-purple-100 p-6 shadow-lg"
                                    >
                                        <div class="mb-4 flex items-start gap-4">
                                            <div>
                                                <p
                                                    class="text-xl font-semibold text-pink-800"
                                                    :data-segment-text="'Questions 22-23'"
                                                    v-html="getHighlightedSegment('Questions 22-23')"
                                                ></p>
                                                <p
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'Choose TWO letters, A-E.'"
                                                    v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                                ></p>
                                            </div>
                                        </div>
                                        <p
                                            class="mb-4 text-lg font-semibold text-gray-800"
                                            :data-segment-text="'Which TWO of these statements describe Howe\'s experiment with 8-12-year-olds?'"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Which TWO of these statements describe Howe\'s experiment with 8-12-year-olds?',
                                                )
                                            "
                                        ></p>
                                        <div class="mt-4 space-y-3">
                                            <label
                                                v-for="(option, index) in ['A', 'B', 'C', 'D', 'E']"
                                                :key="'22-23-' + option"
                                                @click.prevent="handleMultipleChoice('q22_23', option)"
                                                :class="[
                                                    'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all sm:rounded-xl sm:p-4',
                                                    isSelected('q22_23', option)
                                                        ? 'border-pink-500 bg-pink-100 shadow-md'
                                                        : 'border-pink-200 bg-white hover:bg-pink-50',
                                                ]"
                                            >
                                                <div
                                                    class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded border-2"
                                                    :class="isSelected('q22_23', option) ? 'border-pink-600 bg-pink-500' : 'border-gray-400'"
                                                >
                                                    <svg
                                                        v-if="isSelected('q22_23', option)"
                                                        class="h-3 w-3 text-white"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </div>
                                                <span class="text-base font-medium text-gray-800 sm:text-lg">
                                                    <span
                                                        :data-segment-text="
                                                            [
                                                                'A the children were assessed on their ability to understand a scientific problem',
                                                                'B all the children were working in mixed ability groups',
                                                                'C the children who were the most talkative made the least progress',
                                                                'D the teacher helped the children to understand a scientific problem',
                                                                'E the children were given a total of three tests at different times',
                                                            ][index]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'A the children were assessed on their ability to understand a scientific problem',
                                                                    'B all the children were working in mixed ability groups',
                                                                    'C the children who were the most talkative made the least progress',
                                                                    'D the teacher helped the children to understand a scientific problem',
                                                                    'E the children were given a total of three tests at different times',
                                                                ][index],
                                                            )
                                                        "
                                                    ></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Questions 24-26 -->
                                    <div
                                        id="question-24-26"
                                        class="rounded-2xl border border-pink-200 bg-gradient-to-br from-pink-50 to-purple-100 p-6 shadow-lg"
                                    >
                                        <div class="mb-4 flex items-start gap-4">
                                            <div>
                                                <p
                                                    class="text-xl font-semibold text-pink-800"
                                                    :data-segment-text="'Questions 24-26'"
                                                    v-html="getHighlightedSegment('Questions 24-26')"
                                                ></p>
                                                <p
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'Complete the summary below. Choose NO MORE THAN TWO WORDS from the passage for each answer.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Complete the summary below. Choose NO MORE THAN TWO WORDS from the passage for each answer.',
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                        </div>
                                        <div class="mt-4 rounded-xl border border-pink-200 bg-white p-6 shadow-md">
                                            <h4
                                                class="mb-4 text-center text-lg font-bold text-pink-800"
                                                :data-segment-text="'How children learn'"
                                                v-html="getHighlightedSegment('How children learn')"
                                            ></h4>
                                            <div class="space-y-4 text-lg leading-relaxed text-gray-700">
                                                <p>
                                                    Piaget proposed that learning takes place when children encounter ideas that do not correspond to
                                                    their current beliefs. The application of this theory gave rise to a teaching method known as
                                                    <span class="inline-flex items-center gap-2">
                                                        <span
                                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-lg"
                                                            style="
                                                                box-shadow:
                                                                    0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                            "
                                                            >24</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q24"
                                                            class="inline-block w-48 rounded-md border-2 border-pink-300 bg-pink-50 px-3 py-1 text-base focus:border-pink-500 focus:bg-white focus:outline-none"
                                                        /> </span
                                                    >.
                                                </p>
                                                <p>
                                                    At first this approach only focused on the relationship between individual pupils and their
                                                    <span class="inline-flex items-center gap-2">
                                                        <span
                                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-lg"
                                                            style="
                                                                box-shadow:
                                                                    0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                            "
                                                            >25</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q25"
                                                            class="inline-block w-48 rounded-md border-2 border-pink-300 bg-pink-50 px-3 py-1 text-base focus:border-pink-500 focus:bg-white focus:outline-none"
                                                        /> </span
                                                    >.
                                                </p>
                                                <p>
                                                    Later, researchers such as Perret-Clermont became interested in the role that interaction with
                                                    <span class="inline-flex items-center gap-2">
                                                        <span
                                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-lg"
                                                            style="
                                                                box-shadow:
                                                                    0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                            "
                                                            >26</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q26"
                                                            class="inline-block w-48 rounded-md border-2 border-pink-300 bg-pink-50 px-3 py-1 text-base focus:border-pink-500 focus:bg-white focus:outline-none"
                                                        />
                                                    </span>
                                                    might also play in a pupil's development.
                                                </p>
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

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(168, 85, 247, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(168, 85, 247, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(168, 85, 247, 0.1);
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
    background: linear-gradient(to bottom, #a855f7, #ec4899);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #9333ea, #db2777);
}

/* Highlight functionality styles */
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
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
</style>
