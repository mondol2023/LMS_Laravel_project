<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Reading Part 2 component

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

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts015-part2-panel-width';
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

const passageText = `A. few years ago, in one of the most fascinating and disturbing experiments in behavioural psychology, Stanley Milgram of Yale University tested 40 subjects from all walks of life for their willingness to obey instructions given by a ‘leader’ in a situation in which the subjects might feel a personal distaste for the actions they were called upon to perform. Specifically, Milgram told each volunteer ‘teacher-subject’ that the experiment was in the noble cause of education, and was designed to test whether or not punishing pupils for their mistakes would have a positive effect on the pupils’ ability to learn.

B. Milgram’s experimental set-up involved placing the teacher-subject before a panel of thirty switches with labels ranging from ’15 volts of electricity (slight shock)’ to ‘450 volts (danger – severe shock)’ in steps of 15 volts each. The teacher-subject was told that whenever the pupil gave the wrong answer to a question, a shock was to be administered, beginning at the lowest level and increasing in severity with each successive wrong answer. The supposed ‘pupil’ was in reality an actor hired by Milgram to simulate receiving the shocks by emitting a spectrum of groans, screams and writhings together with an assortment of statements and expletives denouncing both the experiment and the experimenter. Milgram told the teacher-subject to ignore the reactions of the pupil, and to administer whatever level of shock was called for, as per the rule governing the experimental situation of the moment.

C. As the experiment unfolded, the pupil would deliberately give the wrong answers to questions posed by the teacher, thereby bringing on various electrical punishments, even up to the danger level of 300 volts and beyond. Many of the teacher-subjects balked at administering the higher levels of punishment, and turned to Milgram with questioning looks and/or complaints about continuing the experiment. In these situations, Milgram calmly explained that the teacher-subject was to ignore the pupil’s cries for mercy and carry on with the experiment. If the subject was still reluctant to proceed, Milgram said that it was important for the sake of the experiment that the procedure be followed through to the end. His final argument was, ‘You have no other choice. You must go on.’ What Milgram was trying to discover was the number of teacher-subjects who would be willing to administer the highest levels of shock, even in the face of strong personal and moral revulsion against the rules and conditions of the experiment.

D. Prior to carrying out the experiment, Milgram explained his idea to a group of 39 psychiatrists and asked them to predict the average percentage of people in an ordinary population who would be willing to administer the highest shock level of 450 volts. The overwhelming consensus was that virtually all the out teacher-subjects would refuse to obey the experimenter. The psychiatrists felt that ‘most subjects would not go beyond 150 volts’ and they further anticipated that only four per cent would go up to 300 volts. Furthermore, they thought that only a lunatic fringe of about one in 1,000 would give the highest shock of 450 volts. Furthermore, they thought that only a lunatic cringe of about one in 1,000 would give the highest shock of 450 volts.

E. What were the actual results? Well, over 60 per cent of the teacher-subjects continued to obey Milgram up to the 450-volt limit! In repetitions of the experiment in other countries, the percentage of obedient teacher-subjects was even higher, reaching 85 per cent in one country. How can we possibly account for this vast discrepancy between what calm, rational, knowledgeable people predict in the comfort of their study and what pressured, flustered, but cooperative teachers’ actually do in the laboratory of real life?

F. One’s first inclination might be to argue that there must be some sort of built-in animal aggression instinct that was activated by the experiment, and that Milgram’s teacher-subjects were just following a genetic need to discharge this pent-up primal urge onto the pupil by administering the electrical shock. A modern hard-core sociobiologist might even go so far as to claim that this aggressive instinct evolved as an advantageous trait, having been of survival value to our ancestors in their struggle against the hardships of life on the plains and in the caves, ultimately finding its way into our genetic make-up as a remnant of our ancient animal ways.

G. An alternative to this notion of genetic programming is to see the teacher-subjects’ actions as a result of the social environment under which the experiment was carried out. As Milgram himself pointed out, ‘Most subjects in the experiment see their behaviour in a larger context that is benevolent and useful to society – the pursuit of scientific truth. The psychological laboratory has a strong claim to legitimacy and evokes trust and confidence in those who perform there. An action such as shocking a victim, which in isolation appears evil, acquires a completely different meaning when placed in this setting.

H. Thus, in this explanation the subject merges his unique personality and personal and moral code with that of larger institutional structures, surrendering individual properties like loyalty, self-sacrifice and discipline to the service of malevolent systems of authority.

I. Here we have two radically different explanations for why so many teacher-subjects were willing to forgo their sense of personal responsibility for the sake of an institutional authority figure. The problem for biologists, psychologists and anthropologists is to sort which of these two polar explanations is more plausible. This, in essence, is the problem of modern sociobiology – to discover the degree to which hard-wired genetic programming dictates, or at least strongly biases, the interaction of animals and humans with their environment, that is, their behaviour. Put another way, sociobiology is concerned with elucidating the biological basis of all behaviour.`;

const passageOffset = passageText.length;
const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'Questions 14-19', offset: passageOffset + 1 },
    { text: 'Reading Passage 2 has nine paragraphs, A-I. Which paragraph contains the following information?', offset: passageOffset + 19 },
    { text: 'Write the correct letter A-I in boxes 14-19 on your answer sheet.', offset: passageOffset + 116 },
    { text: "a biological explanation of the teacher-subjects' behaviour", offset: passageOffset + 183 },
    { text: 'the explanation Milgram gave the teacher-subjects for the experiment', offset: passageOffset + 243 },
    { text: 'the identity of the pupils', offset: passageOffset + 313 },
    { text: 'the expected statistical outcome', offset: passageOffset + 340 },
    { text: 'the general aim of sociobiological study', offset: passageOffset + 373 },
    { text: 'the way Milgram persuaded the teacher-subjects to continue', offset: passageOffset + 416 },
    { text: 'Questions 20-22', offset: passageOffset + 482 },
    { text: 'Choose the correct letter, A, B, C or D. Write your answers in boxes 20-22 on your answer sheet.', offset: passageOffset + 500 },
    { text: 'The teacher-subjects were told that they were testing whether', offset: passageOffset + 596 },
    { text: 'A a 450-volt shock was dangerous', offset: passageOffset + 658 },
    { text: 'B punishment helps learning', offset: passageOffset + 692 },
    { text: 'C the pupils were honest', offset: passageOffset + 719 },
    { text: 'D they were suited to teaching', offset: passageOffset + 744 },
    { text: 'The teacher-subjects were instructed to', offset: passageOffset + 776 },
    { text: 'A stop when a pupil asked them to', offset: passageOffset + 816 },
    { text: 'B denounce pupils who made mistakes', offset: passageOffset + 851 },
    { text: 'C reduce the shock level after a correct answer', offset: passageOffset + 888 },
    { text: 'D give punishment according to a rule', offset: passageOffset + 937 },
    { text: 'Before the experiment took place the psychiatrists', offset: passageOffset + 975 },
    { text: 'A believed that a shock of 150 volts was too dangerous', offset: passageOffset + 1025 },
    { text: 'B failed to agree on how the teacher-subjects would respond to instructions', offset: passageOffset + 1081 },
    { text: "C underestimated the teacher-subjects' willingness to comply with experimental procedure", offset: passageOffset + 1160 },
    { text: 'D thought that many of the teacher-subjects would administer a shock of 450 volts', offset: passageOffset + 1251 },
    { text: 'Questions 23-26', offset: passageOffset + 1333 },
    { text: 'Do the following statements agree with the information given in Reading Passage 2?', offset: passageOffset + 1349 },
    { text: 'In boxes 23-26 on your answer sheet, write', offset: passageOffset + 1433 },
    { text: 'TRUE', offset: passageOffset + 1477 },
    { text: 'if the statement agrees with the information', offset: passageOffset + 1482 },
    { text: 'FALSE', offset: passageOffset + 1526 },
    { text: 'if the statement contradicts the information', offset: passageOffset + 1532 },
    { text: 'NOT GIVEN', offset: passageOffset + 1577 },
    { text: 'if there is no information on this', offset: passageOffset + 1587 },
    { text: 'Several of the subjects were psychology students at Yale University.', offset: passageOffset + 1622 },
    {
        text: "Some people may believe that the teacher-subjects' behaviour could be explained as a positive survival mechanism.",
        offset: passageOffset + 1689,
    },
    { text: 'In a sociological explanation, personal values are more powerful than authority.', offset: passageOffset + 1808 },
    { text: "Milgram's experiment solves an important question in sociobiology.", offset: passageOffset + 1889 },
]);

const questions14to19 = [
    "a biological explanation of the teacher-subjects' behaviour",
    'the explanation Milgram gave the teacher-subjects for the experiment',
    'the identity of the pupils',
    'the expected statistical outcome',
    'the general aim of sociobiological study',
    'the way Milgram persuaded the teacher-subjects to continue',
];

const questions20to22 = [
    {
        question: 'The teacher-subjects were told that they were testing whether',
        options: {
            A: 'a 450-volt shock was dangerous',
            B: 'punishment helps learning',
            C: 'the pupils were honest',
            D: 'they were suited to teaching',
        },
    },
    {
        question: 'The teacher-subjects were instructed to',
        options: {
            A: 'stop when a pupil asked them to',
            B: 'denounce pupils who made mistakes',
            C: 'reduce the shock level after a correct answer',
            D: 'give punishment according to a rule',
        },
    },
    {
        question: 'Before the experiment took place the psychiatrists',
        options: {
            A: 'believed that a shock of 150 volts was too dangerous',
            B: 'failed to agree on how the teacher-subjects would respond to instructions',
            C: "underestimated the teacher-subjects' willingness to comply with experimental procedure",
            D: 'thought that many of the teacher-subjects would administer a shock of 450 volts',
        },
    },
];

const questions23to26 = [
    'Several of the subjects were psychology students at Yale University.',
    "Some people may believe that the teacher-subjects' behaviour could be explained as a positive survival mechanism.",
    'In a sociological explanation, personal values are more powerful than authority.',
    "Milgram's experiment solves an important question in sociobiology.",
];

// Helper to get a highlighted version of a specific text segment
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

const getAnswers = () => answers.value;

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            if (!segmentEl) return null;
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

defineExpose({ getAnswers, scrollToQuestion, scrollToHighlight, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-pink-500 to-purple-600">
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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">READING PASSAGE 2</p>
                                </div>
                            </div>
                            <p class="text-sm tracking-wide text-gray-700">
                                You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.
                            </p>
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    Nature or Nurture?
                                </h2>
                            </div>
                        </div>

                        <div class="flex-1 space-y-6 overflow-y-auto p-6">
                            <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-lg">
                                <div class="rounded-xl border border-pink-100 bg-gradient-to-br from-pink-50 to-purple-50 p-6 shadow-md">
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-pink-500 to-purple-600">
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
                                <!-- Questions 14-19 -->
                                <section id="question-14-19" class="space-y-4 rounded-xl border border-purple-200 bg-purple-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-purple-800"
                                        :data-segment-text="'Questions 14-19'"
                                        v-html="getHighlightedSegment('Questions 14-19')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Reading Passage 2 has nine paragraphs, A-I. Which paragraph contains the following information?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Reading Passage 2 has nine paragraphs, A-I. Which paragraph contains the following information?',
                                            )
                                        "
                                    ></p>
                                    <p
                                        :data-segment-text="'Write the correct letter A-I in boxes 14-19 on your answer sheet.'"
                                        v-html="getHighlightedSegment('Write the correct letter A-I in boxes 14-19 on your answer sheet.')"
                                    ></p>

                                    <div class="space-y-4 pt-4">
                                        <div
                                            v-for="(question, index) in questions14to19"
                                            :key="index"
                                            class="group rounded-lg border-l-4 border-pink-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 transform items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-[0_4px_15px_rgba(59,130,246,0.4)] transition-transform group-hover:scale-110"
                                                    >{{ 14 + index }}</span
                                                >
                                                <p class="flex-1" :data-segment-text="question" v-html="getHighlightedSegment(question)"></p>
                                                <select
                                                    v-model="answers['q' + (14 + index)]"
                                                    class="w-28 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow focus:ring-2 focus:ring-pink-500"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="char in 'ABCDEFGHI'.split('')" :key="char" :value="char">{{ char }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Questions 20-22 -->
                                <section id="question-20-22" class="space-y-4 rounded-xl border border-pink-200 bg-pink-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-pink-800"
                                        :data-segment-text="'Questions 20-22'"
                                        v-html="getHighlightedSegment('Questions 20-22')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Choose the correct letter, A, B, C or D. Write your answers in boxes 20-22 on your answer sheet.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Choose the correct letter, A, B, C or D. Write your answers in boxes 20-22 on your answer sheet.',
                                            )
                                        "
                                    ></p>

                                    <div class="space-y-6">
                                        <div
                                            v-for="(q, index) in questions20to22"
                                            :key="index"
                                            class="group rounded-lg border-l-4 border-pink-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-start gap-4">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 transform items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-[0_4px_15px_rgba(59,130,246,0.4)] transition-transform group-hover:scale-110"
                                                    >{{ 20 + index }}</span
                                                >
                                                <p
                                                    class="flex-1 font-semibold"
                                                    :data-segment-text="q.question"
                                                    v-html="getHighlightedSegment(q.question)"
                                                ></p>
                                            </div>
                                            <div class="mt-4 space-y-3 pl-12">
                                                <label
                                                    v-for="(optionText, optionLabel) in q.options"
                                                    :key="optionLabel"
                                                    class="flex cursor-pointer items-center gap-3"
                                                >
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (20 + index)"
                                                        :value="optionLabel"
                                                        v-model="answers['q' + (20 + index)]"
                                                        class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                                    />
                                                    <span
                                                        class="font-medium"
                                                        :data-segment-text="`${optionLabel} ${optionText}`"
                                                        v-html="getHighlightedSegment(`${optionLabel} ${optionText}`)"
                                                    ></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Questions 23-26 -->
                                <section id="question-23-26" class="space-y-4 rounded-xl border border-pink-200 bg-pink-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-pink-800"
                                        :data-segment-text="'Questions 23-26'"
                                        v-html="getHighlightedSegment('Questions 23-26')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Do the following statements agree with the information given in Reading Passage 2?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Do the following statements agree with the information given in Reading Passage 2?',
                                            )
                                        "
                                    ></p>
                                    <p
                                        :data-segment-text="'In boxes 23-26 on your answer sheet, write'"
                                        v-html="getHighlightedSegment('In boxes 23-26 on your answer sheet, write')"
                                    ></p>
                                    <div class="space-y-8 rounded-lg border border-pink-200 bg-white p-8 shadow-lg">
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-green-100 p-2.5 text-green-700 shadow"
                                                :data-segment-text="'TRUE'"
                                                v-html="getHighlightedSegment('TRUE')"
                                            ></strong
                                            ><span
                                                :data-segment-text="' if the statement agrees with the information'"
                                                v-html="getHighlightedSegment(' if the statement agrees with the information')"
                                            ></span>
                                        </p>
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-red-100 p-2.5 text-red-700 shadow"
                                                :data-segment-text="'FALSE'"
                                                v-html="getHighlightedSegment('FALSE')"
                                            ></strong
                                            ><span
                                                :data-segment-text="' if the statement contradicts the information'"
                                                v-html="getHighlightedSegment(' if the statement contradicts the information')"
                                            ></span>
                                        </p>
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-gray-100 p-2.5 text-gray-700 shadow"
                                                :data-segment-text="'NOT GIVEN'"
                                                v-html="getHighlightedSegment('NOT GIVEN')"
                                            ></strong
                                            ><span
                                                :data-segment-text="' if there is no information on this'"
                                                v-html="getHighlightedSegment(' if there is no information on this')"
                                            ></span>
                                        </p>
                                    </div>
                                    <div class="space-y-4 pt-4">
                                        <div
                                            v-for="(question, index) in questions23to26"
                                            :key="index"
                                            class="group rounded-lg border-l-4 border-pink-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-start gap-4">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 transform items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-[0_4px_15px_rgba(236,72,153,0.4)] transition-transform group-hover:scale-110"
                                                    >{{ 23 + index }}</span
                                                >
                                                <p class="flex-1" :data-segment-text="question" v-html="getHighlightedSegment(question)"></p>
                                            </div>
                                            <div class="mt-3 flex space-x-4 pl-12">
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (23 + index)"
                                                        value="TRUE"
                                                        v-model="answers['q' + (23 + index)]"
                                                        class="form-radio text-pink-600 focus:ring-pink-500"
                                                    />
                                                    <span class="text-gray-700">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (23 + index)"
                                                        value="FALSE"
                                                        v-model="answers['q' + (23 + index)]"
                                                        class="form-radio text-pink-600 focus:ring-pink-500"
                                                    />
                                                    <span class="text-gray-700">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (23 + index)"
                                                        value="NOT GIVEN"
                                                        v-model="answers['q' + (23 + index)]"
                                                        class="form-radio text-pink-600 focus:ring-pink-500"
                                                    />
                                                    <span class="text-gray-700">NOT GIVEN</span>
                                                </label>
                                            </div>
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
                    <button
                        @click="openNoteInput"
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-pink-50 transition-all hover:scale-110 hover:bg-pink-100"
                        title="Add Note"
                    >
                        <svg class="h-4 w-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            class="absolute z-[9999] w-80 rounded-lg border-2 border-pink-300 bg-white p-4 shadow-2xl"
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
                    class="note-input-field w-full rounded-lg border-2 border-pink-200 px-3 py-2 text-sm focus:border-pink-500 focus:ring-2 focus:ring-pink-500 focus:outline-none"
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
                    class="rounded-md bg-pink-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-pink-700"
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

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #ec4899, #8b5cf6);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #d946ef, #7c3aed);
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

.highlight-pink {
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
