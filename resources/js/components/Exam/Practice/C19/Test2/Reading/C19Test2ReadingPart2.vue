<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
const PANEL_WIDTH_KEY = 'reading-c19t2-part2-panel-width';
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

const passageText = `A It isn\u2019t easy being a professional athlete. Not only are the physical demands greater than most people could handle, athletes also face intense psychological pressure during competition. This is something that British tennis player Emma Raducanu wrote about on social media following her withdrawal from the 2021 Wimbledon tournament. Though the young player had been doing well in the tournament, she began having difficulty regulating her breathing and heart rate during a match, which she later attributed to \u2018the accumulation of the excitement and the buzz\u2019.

B For athletes, some level of performance stress is almost unavoidable. But there are many different factors that dictate just how people\u2019s minds and bodies respond to stressful events. Typically, stress is the result of an exchange between two factors: demands and resources. An athlete may feel stressed about an event if they feel the demands on them are greater than they can handle. These demands include the high level of physical and mental effort required to succeed, and also the athlete\u2019s concerns about the difficulty of the event, their chance of succeeding, and any potential dangers such as injury. Resources, on the other hand, are a person\u2019s ability to cope with these demands. These include factors such as the competitor\u2019s degree of confidence, how much they believe they can control the situation\u2019s outcome, and whether they\u2019re looking forward to the event or not.

C Each new demand or change in circumstances affects whether a person responds positively or negatively to stress. Typically, the more resources a person feels they have in handling the situation, the more positive their stress response. This positive stress response is called a challenge state. But should the person feel there are too many demands placed on them, the more likely they are to experience a negative stress response \u2013 known as a threat state. Research shows that the challenge states lead to good performance, while threat states lead to poorer performance. So, in Emma Raducanu\u2019s case, a much larger audience, higher expectations and facing a more skilful opponent, may all have led her to feel there were greater demands being placed on her at Wimbledon \u2013 but she didn\u2019t have the resources to tackle them. This led to her experiencing a threat response.

D Our challenge and threat responses essentially influence how our body responds to stressful situations, as both affect the production of adrenaline and cortisol \u2013 also known as \u2018stress hormones\u2019. During a challenge state, adrenaline increases the amount of blood pumped from the heart and expands the blood vessels, which allows more energy to be delivered to the muscles and brain. This increase of blood and decrease of pressure in the blood vessels has been consistently related to superior sport performance in everything from cricket batting, to golf putting and football penalty taking. But during a threat state, cortisol inhibits the positive effect of adrenaline, resulting in tighter blood vessels, higher blood pressure, slower psychological responses, and a faster heart rate. In short, a threat state makes people more anxious \u2013 they make worse decisions and perform more poorly. In tennis players, cortisol has been associated with more unsuccessful serves and greater anxiety.

E That said, anxiety is also a common experience for athletes when they\u2019re under pressure. Anxiety can increase heart rate and perspiration, cause heart palpitations, muscle tremors and shortness of breath, as well as headaches, nausea, stomach pain, weakness and a desire to escape in more extreme cases. Anxiety can also reduce concentration and self-control and cause overthinking. The intensity with which a person experiences anxiety depends on the demands and resources they have. Anxiety may also manifest itself in the form of excitement or nervousness depending on the stress response. Negative stress responses can be damaging to both physical and mental health \u2013 and repeated episodes of anxiety coupled with negative responses can increase risk of heart disease and depression.

F But there are many ways athletes can ensure they respond positively under pressure. Positive stress responses can be promoted through the language that they and others \u2013 such as coaches or parents \u2014 use. Psychologists can also help athletes change how they see their physiological responses \u2013 such as helping them see a higher heart rate as excitement, rather than nerves. Developing psychological skills, such as visualisation, can also help decrease physiological responses to threat. Visualisation may involve the athlete recreating a mental picture of a time when they performed well, or picturing themselves doing well in the future. This can help create a feeling of control over the stressful event. Recreating competitive pressure during training can also help athletes learn how to deal with stress. An example of this might be scoring athletes against their peers to create a sense of competition. This would increase the demands which players experience compared to a normal training session, while still allowing them to practise coping with stress.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 2', offset: 5600 },
    { text: 'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', offset: 5650 },
    { text: 'Athletes and stress', offset: 5800 },
    { text: 'Questions 14\u201318', offset: 5900 },
    { text: 'Reading Passage 2 has six paragraphs, A\u2013F.', offset: 5950 },
    { text: 'Which paragraph contains the following information?', offset: 6050 },
    { text: 'Write the correct letter, A\u2013F, in boxes 14\u201318 on your answer sheet.', offset: 6150 },
    { text: 'NB You may use any letter more than once.', offset: 6250 },
    { text: 'reference to two chemical compounds which impact on performance', offset: 6350 },
    { text: 'examples of strategies for minimising the effects of stress', offset: 6450 },
    { text: 'how a sportsperson accounted for their own experience of stress', offset: 6550 },
    { text: 'study results indicating links between stress responses and performance', offset: 6650 },
    { text: 'mention of people who can influence how athletes perceive their stress responses', offset: 6750 },
    { text: 'Questions 19\u201322', offset: 6900 },
    { text: 'Complete the sentences below.', offset: 6950 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 7000 },
    { text: 'Write your answers in boxes 19\u201322 on your answer sheet.', offset: 7100 },
    { text: 'Performance stress involves many demands on the athlete, for example, coping with the possible risk of ', offset: 7200 },
    { text: 'Cortisol can cause tennis players to produce fewer good ', offset: 7400 },
    { text: 'Psychologists can help athletes to view their physiological responses as the effect of a positive feeling such as ', offset: 7550 },
    { text: ' is an example of a psychological technique which can reduce an athlete\u2019s stress responses.', offset: 7750 },
    { text: 'Questions 23\u201324', offset: 7950 },
    { text: 'Choose TWO letters, A\u2013E.', offset: 8000 },
    { text: 'Which TWO facts about Emma Raducanu\u2019s withdrawal from the Wimbledon tournament are mentioned in the text?', offset: 8050 },
    { text: 'A the stage at which she dropped out of the tournament', offset: 8200 },
    { text: 'B symptoms of her performance stress at the tournament', offset: 8300 },
    { text: 'C measures which she had taken to manage her stress levels', offset: 8400 },
    { text: 'D aspects of the Wimbledon tournament which increased her stress levels', offset: 8500 },
    { text: 'E reactions to her social media posts about her experience at Wimbledon', offset: 8650 },
    { text: 'Questions 25\u201326', offset: 8800 },
    { text: 'Which TWO facts about anxiety are mentioned in Paragraph E of the text?', offset: 8900 },
    { text: 'A the factors which determine how severe it may be', offset: 9050 },
    { text: 'B how long it takes for its effects to become apparent', offset: 9150 },
    { text: 'C which of its symptoms is most frequently encountered', offset: 9250 },
    { text: 'D the types of athletes who are most likely to suffer from it', offset: 9350 },
    { text: 'E the harm that can result if athletes experience it too often', offset: 9450 },
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
                            <div class="flex flex-col items-center justify-between gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    <span :data-segment-text="'Athletes and stress'" v-html="getHighlightedSegment('Athletes and stress')"></span>
                                </h2>
                                <button
                                    class="self-end rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200 sm:text-sm"
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
                                    <p class="text-xs text-gray-500">Answer all questions based on Reading Passage 2</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 14-18 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-14-18" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 14\u201318'"
                                            v-html="getHighlightedSegment('Questions 14\u201318')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Reading Passage 2 has six paragraphs, A\u2013F.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has six paragraphs, A\u2013F.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which paragraph contains the following information?'"
                                                v-html="getHighlightedSegment('Which paragraph contains the following information?')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013F, in boxes 14\u201318 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013F, in boxes 14\u201318 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'NB You may use any letter more than once.'"
                                                v-html="getHighlightedSegment('NB You may use any letter more than once.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="n in 5"
                                                :key="n"
                                                :id="'question-' + (13 + n)"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                    >{{ 13 + n }}</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="
                                                        textSegments.find((s) => s.offset === [6350, 6450, 6550, 6650, 6750][n - 1])?.text
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find((s) => s.offset === [6350, 6450, 6550, 6650, 6750][n - 1])?.text || '',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers['q' + (13 + n)]"
                                                    class="w-24 rounded-xl border border-pink-300 bg-pink-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 19-22 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-19-22" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 19\u201322'"
                                            v-html="getHighlightedSegment('Questions 19\u201322')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the sentences below.'"
                                                v-html="getHighlightedSegment('Complete the sentences below.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write your answers in boxes 19\u201322 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write your answers in boxes 19\u201322 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Question 19 -->
                                            <div
                                                id="question-19"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >19</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Performance stress involves many demands on the athlete, for example, coping with the possible risk of '"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'Performance stress involves many demands on the athlete, for example, coping with the possible risk of ',
                                                                )
                                                            "
                                                        ></span>
                                                        <input
                                                            v-model="answers.q19"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 20 -->
                                            <div
                                                id="question-20"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >20</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Cortisol can cause tennis players to produce fewer good '"
                                                            v-html="getHighlightedSegment('Cortisol can cause tennis players to produce fewer good ')"
                                                        ></span>
                                                        <input
                                                            v-model="answers.q20"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 21 -->
                                            <div
                                                id="question-21"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >21</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Psychologists can help athletes to view their physiological responses as the effect of a positive feeling such as '"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'Psychologists can help athletes to view their physiological responses as the effect of a positive feeling such as ',
                                                                )
                                                            "
                                                        ></span>
                                                        <input
                                                            v-model="answers.q21"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 22 -->
                                            <div
                                                id="question-22"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >22</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <input
                                                            v-model="answers.q22"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span
                                                            :data-segment-text="' is an example of a psychological technique which can reduce an athlete\u2019s stress responses.'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    ' is an example of a psychological technique which can reduce an athlete\u2019s stress responses.',
                                                                )
                                                            "
                                                        ></span>
                                                    </p>
                                                </div>
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
                                            :data-segment-text="'Questions 23\u201324'"
                                            v-html="getHighlightedSegment('Questions 23\u201324')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Choose TWO letters, A\u2013E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A\u2013E.')"
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Which TWO facts about Emma Raducanu\u2019s withdrawal from the Wimbledon tournament are mentioned in the text?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Which TWO facts about Emma Raducanu\u2019s withdrawal from the Wimbledon tournament are mentioned in the text?',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                :id="'question-23'"
                                                class="flex flex-col gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                        >23</span
                                                    >
                                                    <select
                                                        v-model="answers.q23"
                                                        class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div
                                                :id="'question-24'"
                                                class="flex flex-col gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                        >24</span
                                                    >
                                                    <select
                                                        v-model="answers.q24"
                                                        class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <ul class="space-y-2 text-gray-700">
                                                <li
                                                    :data-segment-text="'A the stage at which she dropped out of the tournament'"
                                                    v-html="getHighlightedSegment('A the stage at which she dropped out of the tournament')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'B symptoms of her performance stress at the tournament'"
                                                    v-html="getHighlightedSegment('B symptoms of her performance stress at the tournament')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'C measures which she had taken to manage her stress levels'"
                                                    v-html="getHighlightedSegment('C measures which she had taken to manage her stress levels')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'D aspects of the Wimbledon tournament which increased her stress levels'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'D aspects of the Wimbledon tournament which increased her stress levels',
                                                        )
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'E reactions to her social media posts about her experience at Wimbledon'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'E reactions to her social media posts about her experience at Wimbledon',
                                                        )
                                                    "
                                                ></li>
                                            </ul>
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
                                            :data-segment-text="'Questions 25\u201326'"
                                            v-html="getHighlightedSegment('Questions 25\u201326')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Choose TWO letters, A\u2013E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A\u2013E.')"
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Which TWO facts about anxiety are mentioned in Paragraph E of the text?'"
                                                v-html="
                                                    getHighlightedSegment('Which TWO facts about anxiety are mentioned in Paragraph E of the text?')
                                                "
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                :id="'question-25'"
                                                class="flex flex-col gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                        >25</span
                                                    >
                                                    <select
                                                        v-model="answers.q25"
                                                        class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div
                                                :id="'question-26'"
                                                class="flex flex-col gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                        >26</span
                                                    >
                                                    <select
                                                        v-model="answers.q26"
                                                        class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <ul class="space-y-2 text-gray-700">
                                                <li
                                                    :data-segment-text="'A the factors which determine how severe it may be'"
                                                    v-html="getHighlightedSegment('A the factors which determine how severe it may be')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'B how long it takes for its effects to become apparent'"
                                                    v-html="getHighlightedSegment('B how long it takes for its effects to become apparent')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'C which of its symptoms is most frequently encountered'"
                                                    v-html="getHighlightedSegment('C which of its symptoms is most frequently encountered')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'D the types of athletes who are most likely to suffer from it'"
                                                    v-html="getHighlightedSegment('D the types of athletes who are most likely to suffer from it')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'E the harm that can result if athletes experience it too often'"
                                                    v-html="getHighlightedSegment('E the harm that can result if athletes experience it too often')"
                                                ></li>
                                            </ul>
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
