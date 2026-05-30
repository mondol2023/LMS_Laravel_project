<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

// Props for draft system
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

// Reading Part 2 component

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part2-panel-width';
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
    q20: '',
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

const multipleAnswers = reactive({
    q23_24: [],
    q25_26: [],
});

watch(multipleAnswers, (newVal) => {
    answers.value.q23 = newVal.q23_24[0] || '';
    answers.value.q24 = newVal.q23_24[1] || '';
    answers.value.q25 = newVal.q25_26[0] || '';
    answers.value.q26 = newVal.q25_26[1] || '';
});

const passageText = `A Procrastination is the habit of delaying a necessary task, usually by focusing on less urgent, more enjoyable, and easier activities instead. We all do it from time to time. We might be composing a message to a friend who we have to let down, or putting together an important report for college or work, we’re doing our best to avoid doing the job at hand, but deep down we know that we should just be getting on with it. Unfortunately, berating ourselves won’t stop us procrastinating again. In fact, it’s one of the worst things we can do. This matters because, as my research shows, procrastination doesn’t just waste time, but is actually linked to other problems, too.

B Contrary to popular belief, procrastination is not due to laziness or poor time management. Scientific studies suggest procrastination is, in fact, caused by poor mood management. This makes sense if we consider that people are more likely to put off starting or completing tasks that they are really not keen to do. If just thinking about the task threatens our sense of self-worth or makes us anxious, we will be more likely to put it off. Research involving brain imaging has found that areas of the brain linked to detection of threats and emotion regulation are actually different in people who chronically procrastinate compared to those who don’t procrastinate frequently.

C Tasks that are emotionally loaded or difficult, such as preparing for exams, are prime candidates for procrastination. People with low self-esteem are more likely to procrastinate. Another group of people who tend to procrastinate are perfectionists, who worry their work will be judged harshly by others. We know that if we don’t finish that report or complete those home repairs, then what we did can’t be evaluated. When we avoid such tasks, we also avoid the negative emotions associated with them. This is rewarding, and it conditions us to use procrastination to repair our mood. If we engage in more enjoyable tasks instead, we get another mood boost. In the long run, however, procrastination isn’t an effective way of managing emotions. The ‘mood repair’ we experience is temporary. Afterwards, people tend to be left with a sense of guilt that not only increases their negative mood, but also reinforces their tendency to procrastinate.

D So why is this such a problem? When most people think ofthe costs of procrastination, they think of the toll on productivity. For example, studies have shown that procrastination negatively impacts on student performance. But putting off reading textbooks and writing essays may affect other areas of students’ lives. In one study of over 3,000 German students over a six-month period, those who reported procrastinating over their university work were also more likely to engage in study-related misconduct, such as cheating and plagiarism. But the behaviour that procrastination was most closely linked with was using fraudulent excuses to get deadline extensions. Other research shows that employees on average spend almost a quarter oftheir workday procrastinating, and again this is linked with negative outcomes. In fact, in one US survey ofover 22,000 employees, participants who said they regularly procrastinated had less annual income and less employment stability. For every one- point increase on a measure of chronic procrastination, annual income decreased by US$l5,000.

E Procrastination also correlates with serious health and well-being problems. A tendency to procrastinate is linked to poor mental health, including higher levels of depression and anxiety. Across numerous studies, I’ve found people who regularly procrastinate report a greater number ofhealth issues, such as headaches, flu and colds, and digestive issues. They also experience higher levels of stress and poor sleep quality. They are less likely to practise healthy behaviours, such as eating a healthy diet and regularly exercising, and use destructive coping strategies to manage their stress. In one study of over 700 people, I found people prone to procrastination had a 63% greater risk ofpoor heart health after accounting for other personality traits and demographics.

F Finding better ways of managing our emotions is one route out of the vicious cycle of procrastination. An important first step is to manage our environment and how we view the task. There are a number of evidence-based strategies that can help us fend off distractions that can occupy our minds when we should be focusing on the thing we should be getting on with. For example, reminding ourselves about why the task is important and valuable can increase positive feelings towards it. Forgiving ourselves and feeling compassion when we procrastinate can help break the procrastination cycle. We should admit that we feel bad, but not be overly critical of ourselves. We should remind ourselves that we’re not the first person to procrastinate, nor the last. Doing this can take the edge off the negative feelings we have about ourselves when we procrastinate. This can all make it easier to get back on track.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'Questions 14–18', offset: 5684 },
    { text: 'Reading Passage 2 has seven sections, A–G.', offset: 5701 },
    { text: 'Which section contains the following information?', offset: 5747 },
    { text: 'Write the correct letter, A–G, in boxes 14–18 on your answer sheet.', offset: 5795 },
    { text: 'a mention of the potential for procrastination to cause currency loss', offset: 5905 },
    { text: 'an explanation of the typical sequence of feelings that procrastinators experience', offset: 5997 },
    { text: 'a description of a biological factor that may be linked to procrastination', offset: 6084 },
    { text: 'a reference to the fact that procrastination has been shown to be linked with academic dishonesty', offset: 6147 },
    { text: 'a description of the type of tasks which are often put off', offset: 6249 },
    { text: 'Questions 19–22', offset: 6338 },
    { text: 'Complete the sentences below.', offset: 6355 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 6415 },
    { text: 'Research has indicated that a person’s ', offset: 6474 },
    { text: ' can be negatively affected by procrastination.', offset: 6515 },
    {
        text: 'Procrastination is associated with a number of health issues, including a greater likelihood of experiencing problems with ',
        offset: 6598,
    },
    { text: 'Procrastinators may have poor ', offset: 6691 },
    { text: ' due to the stress they experience.', offset: 6763 },
    { text: 'One way to overcome procrastination is to develop better ', offset: 6852 },
    { text: ' control.', offset: 6965 },
    { text: 'Questions 23–24', offset: 7010 },
    { text: 'Choose TWO letters, A–E.', offset: 7024 },
    { text: 'Which TWO of the following statements about perfectionists are true?', offset: 7041 },
    { text: 'They are more likely to be satisfied with their work.', offset: 7068 },
    { text: 'They are more likely to procrastinate than other people.', offset: 7125 },
    { text: 'They believe that other people judge them.', offset: 7180 },
    { text: 'They get a mood boost from completing tasks.', offset: 7230 },
    { text: 'They are more likely to have low self-esteem.', offset: 7280 },
    { text: 'Questions 25–26', offset: 7350 },
    { text: 'Which TWO of the following are mentioned as strategies to overcome procrastination?', offset: 7380 },
    { text: 'A. a punishing schedule', offset: 7460 },
    { text: 'B. avoiding difficult tasks', offset: 7485 },
    { text: 'C. forgiving oneself', offset: 7515 },
    { text: 'D. focusing on the negative consequences', offset: 7540 },
    { text: 'E. understanding the value of the task', offset: 7585 },
]);

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

// Initialize answers and load drafts

// Save answers to drafts

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

// Watch for changes and auto-save

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
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

watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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
                            <div class="flex items-center justify-center gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    <span :data-segment-text="'Procrastination'" v-html="getHighlightedSegment('Procrastination')"></span>
                                </h2>
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-pink-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-pink-500"></div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-pink-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-pink-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-pink-600"></div>
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
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 14-16 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-14-16" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 14–16'"
                                            v-html="getHighlightedSegment('Questions 14–18')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Reading Passage 2 has seven sections, A–G.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has seven sections, A–G.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which section contains the following information?'"
                                                v-html="getHighlightedSegment('Which section contains the following information?')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="n in 3"
                                                :key="n"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                    >{{ 13 + n }}</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="
                                                        [
                                                            'a mention of the potential for procrastination to cause currency loss',
                                                            'an explanation of the typical sequence of feelings that procrastinators experience',
                                                            'a description of a biological factor that may be linked to procrastination',
                                                        ][n - 1]
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            [
                                                                'a mention of the potential for procrastination to cause currency loss',
                                                                'an explanation of the typical sequence of feelings that procrastinators experience',
                                                                'a description of a biological factor that may be linked to procrastination',
                                                            ][n - 1],
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers['q' + (13 + n)]"
                                                    class="w-24 rounded-xl border border-pink-300 bg-pink-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 17-22 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-17-22" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 17–22'"
                                            v-html="getHighlightedSegment('Questions 19–22')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the sentences below.'"
                                                v-html="getHighlightedSegment('Complete the sentences below.')"
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4 pt-4">
                                            <div class="flex items-center gap-3 rounded-xl p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >17</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'Research has indicated that a person’s '"
                                                        v-html="getHighlightedSegment('Research has indicated that a person’s ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q17"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' can be negatively affected by procrastination.'"
                                                        v-html="getHighlightedSegment(' can be negatively affected by procrastination.')"
                                                    ></span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 rounded-xl p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >18</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'Procrastination is associated with a number of health issues, including a greater likelihood of experiencing problems with '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Procrastination is associated with a number of health issues, including a greater likelihood of experiencing problems with ',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q18"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 rounded-xl p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >19</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'Procrastinators may have poor '"
                                                        v-html="getHighlightedSegment('Procrastinators may have poor ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q19"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' due to the stress they experience.'"
                                                        v-html="getHighlightedSegment(' due to the stress they experience.')"
                                                    ></span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 rounded-xl p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >20</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'One way to overcome procrastination is to develop better '"
                                                        v-html="getHighlightedSegment('One way to overcome procrastination is to develop better ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q20"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span :data-segment-text="' control.'" v-html="getHighlightedSegment(' control.')"></span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 rounded-xl p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >21</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'A person’s annual '"
                                                        v-html="getHighlightedSegment('A person’s annual ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q21"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' may be affected by procrastination.'"
                                                        v-html="getHighlightedSegment(' may be affected by procrastination.')"
                                                    ></span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 rounded-xl p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >22</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'Procrastinators are more likely to make '"
                                                        v-html="getHighlightedSegment('Procrastinators are more likely to make ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q22"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' for not meeting deadlines.'"
                                                        v-html="getHighlightedSegment(' for not meeting deadlines.')"
                                                    ></span>
                                                </p>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 23-24 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-23-24" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 23–24'"
                                            v-html="getHighlightedSegment('Questions 23–24')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose TWO letters, A–E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A–E.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which TWO of the following statements about perfectionists are true?'"
                                                v-html="getHighlightedSegment('Which TWO of the following statements about perfectionists are true?')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="A"
                                                    v-model="multipleAnswers.q23_24"
                                                    :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('A')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'They are more likely to be satisfied with their work.'"
                                                    v-html="getHighlightedSegment('They are more likely to be satisfied with their work.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="B"
                                                    v-model="multipleAnswers.q23_24"
                                                    :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('B')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'They are more likely to procrastinate than other people.'"
                                                    v-html="getHighlightedSegment('They are more likely to procrastinate than other people.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="C"
                                                    v-model="multipleAnswers.q23_24"
                                                    :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('C')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'They believe that other people judge them.'"
                                                    v-html="getHighlightedSegment('They believe that other people judge them.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="D"
                                                    v-model="multipleAnswers.q23_24"
                                                    :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('D')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'They get a mood boost from completing tasks.'"
                                                    v-html="getHighlightedSegment('They get a mood boost from completing tasks.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="E"
                                                    v-model="multipleAnswers.q23_24"
                                                    :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('E')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'They are more likely to have low self-esteem.'"
                                                    v-html="getHighlightedSegment('They are more likely to have low self-esteem.')"
                                                ></span>
                                            </label>
                                        </div>
                                        <div class="mt-4 rounded-lg border border-pink-200 bg-white p-3 shadow">
                                            <p class="font-medium text-pink-700">Selected: {{ multipleAnswers.q23_24.length }}/2 answers</p>
                                        </div>
                                    </section>
                                </div>
                                <!-- Questions 25-26 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-25-26" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 25–26'"
                                            v-html="getHighlightedSegment('Questions 25–26')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose TWO letters, A–E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A–E.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which TWO of the following are mentioned as strategies to overcome procrastination?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Which TWO of the following are mentioned as strategies to overcome procrastination?',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="A"
                                                    v-model="multipleAnswers.q25_26"
                                                    :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('A')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'A. a punishing schedule'"
                                                    v-html="getHighlightedSegment('A. a punishing schedule')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="B"
                                                    v-model="multipleAnswers.q25_26"
                                                    :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('B')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'B. avoiding difficult tasks'"
                                                    v-html="getHighlightedSegment('B. avoiding difficult tasks')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="C"
                                                    v-model="multipleAnswers.q25_26"
                                                    :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('C')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'C. forgiving oneself'"
                                                    v-html="getHighlightedSegment('C. forgiving oneself')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="D"
                                                    v-model="multipleAnswers.q25_26"
                                                    :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('D')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'D. focusing on the negative consequences'"
                                                    v-html="getHighlightedSegment('D. focusing on the negative consequences')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="E"
                                                    v-model="multipleAnswers.q25_26"
                                                    :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('E')"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span
                                                    class="font-medium text-gray-800"
                                                    :data-segment-text="'E. understanding the value of the task'"
                                                    v-html="getHighlightedSegment('E. understanding the value of the task')"
                                                ></span>
                                            </label>
                                        </div>
                                        <div class="mt-4 rounded-lg border border-pink-200 bg-white p-3 shadow">
                                            <p class="font-medium text-pink-700">Selected: {{ multipleAnswers.q25_26.length }}/2 answers</p>
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

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(219, 39, 119, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(219, 39, 119, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(219, 39, 119, 0.1);
        transform: scale(1);
    }
}

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #fdf2f8;
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
