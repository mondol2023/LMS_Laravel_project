<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

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

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts004-part3-panel-width';
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
    ref(`Livestock guard dogs, traditionally used to protect farm animals from predators, are now being used to protect the predators themselves

A For thousands of years, livestock guard dogs worked alongside shepherds to protect their sheep, goats and cattle from predators such as wolves and bears. But in the 19th and 20th centuries, when such predators were largely exterminated, most guard dogs lost their jobs. In recent years, however, as increased efforts have been made to protect wild animals, predators have become more widespread again. As a result, farmers once more need to protect their livestock, and guard dogs are enjoying an unexpected revival.

B Today there are around 50 breeds of guard dogs on duty in various parts of the world. These dogs are raised from an early age with the animals they will be watching and eventually these animals become the dog’s family. The dogs will place themselves between the livestock and any threat, barking loudly. If necessary, they will chase away predators, but often their mere presence is sufficient. ‘Their initial training is to make them understand that livestock is going to be their life,’ says Dan Macon, a shepherd with three guard dogs. ‘A fluffy white puppy is fun to be around, but too much human affection makes it a great dog for guarding the front porch, rather than a great livestock guard dog.’

C The evidence indicates that guard dogs are highly effective. For example, in Portugal, biologist Silvia Ribeiro has found that more than 90 per cent of the farmers participating in a programme to train and use guard dogs to protect their herds against attack from wolves rate the performance of the dogs as very good or excellent. In a study carried out in Australia by Linda van Bommel and Chris Johnson at the University of Tasmania, more than 65 per cent of herders reported that predation stopped completely after they got the dogs, and almost all the rest saw a decrease in attacks. ‘If they are managed and used properly, livestock guard dogs are the most efficient control method that we have in terms of the amount of livestock that they save from predation,’ says van Bommel.

D But today’s guard dogs also have a new role – to help preserve the predators. It is hoped that reductions in livestock losses can make farmers more tolerant of predators and less likely to kill them. In Namibia, more than 90 per cent of cheetahs live outside protected areas, close to humans raising livestock. As a result, the cheetahs are often held responsible for animal losses, and large numbers have been killed by farmers. When guard dogs were introduced, more than 90 per cent of farmers reported a dramatic reduction in livestock losses, and said that as a result they were less likely to kill predators. Julie Young, at Utah State University in the US, believes this result applies widely. “There is common ground from the livestock perspective and from the conservation perspective,’ she says. ‘If ranchers don’t have a dead cow, they will not make a call to apply for a permit to kill a wolf.”

E Looking at all the published evidence, Bethany Smith at Nottingham Trent University in the UK found that up to 88 per cent of farmers said they no longer killed predators after using dogs – but warned that such self-reported results must be taken with a pinch of salt. What’s more, it is possible that livestock guard dogs merely displace predators to unprotected neighbouring properties, where their fate isn’t recorded. ‘In some regions, we work with almost every farmer, but in others only one or two have dogs,’ says Ribeiro. ‘If we are not working with everybody, we are transferring the wolf pressure to the neighbour’s herd and he can use poison and kill an entire pack of wolves.’

F Another concern is whether there may be unintended ecological effects of using guard dogs. Studies suggest that reducing deaths of one type of predator may have a negative impact on other species. The extent of this problem isn’t known, but the consequences are clear in Namibia. Cheetahs aren’t the only species that cause sheep and goat losses there: other predators also attack livestock. In 2015, researchers reported that in spite of the impact farmers obtaining guard dogs had on cheetahs, the number of jackals killed by dogs and people actually increased. Guard dogs have other ecological impacts too. They have been found to spread diseases to wild animals, including endangered Ethiopian wolves. They may also compete with other carnivores for food. And by creating a ‘landscape of fear’, their mere presence can influence the behaviour of prey animals.

G The evidence so far, however, indicates that these consequences aren’t always negative. Guard dogs can deliver unexpected benefits by protecting vulnerable wildlife from predators. For example, their presence has been found to protect birds which build their nests on the ground in fields, where foxes would normally raid them. Indeed, Australian researchers are now using dogs to enhance biodiversity and create refuges for species threatened by predation. So if we can get this right, there may be a bright future for guard dogs in promoting harmonious coexistence between humans and wildlife.`);

const infoMatchingQuestions = [
    { num: 27, text: 'an example of how one predator has been protected by the introduction of livestock guard dogs' },
    { num: 28, text: 'an optimistic suggestion about the possible positive developments in the use of livestock guard dogs' },
    { num: 29, text: 'a description of how the methods used by livestock guard dogs help to keep predators away' },
    { num: 30, text: "claims by different academics that the use of livestock guard dogs is a successful way of protecting farmers' herds" },
    { num: 31, text: 'a reference to how livestock guard dogs gain their skills' },
];
const paragraphOptions = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

const peopleList = [
    { id: 'A', name: 'Dan Macon' },
    { id: 'B', name: 'Silvia Ribeiro' },
    { id: 'C', name: 'Linda van Bommel' },
    { id: 'D', name: 'Julie Young' },
    { id: 'E', name: 'Bethany Smith' },
];
const statementMatchingQuestions = [
    { num: 32, text: 'The use of guard dogs may save the lives of both livestock and wild animals.' },
    { num: 33, text: 'Claims of a change in behaviour from those using livestock guard dogs may not be totally accurate.' },
    { num: 34, text: 'There may be negative results if the use of livestock guard dogs is not sufficiently widespread.' },
    { num: 35, text: 'Livestock guard dogs are the best way of protecting farm animals, as long as the dogs are appropriately handled.' },
    { num: 36, text: 'Teaching a livestock guard dog how to do its work needs a different focus from teaching a house guard dog.' },
];

const summaryCompletion = {
    title: 'Unintended ecological effects of using guard dogs',
    parts: [
        {
            type: 'text',
            content:
                'In Namibia, livestock guard dogs have been used to protect domestic animals from attacks by cheetahs. This has led to a rise in the deaths of other predators, particularly ',
        },
        { type: 'input', num: 37 },
        { type: 'text', content: '. In addition, it has been suggested that the dogs could have ' },
        { type: 'input', num: 38 },
        { type: 'text', content: ' which may affect other species, and that they may reduce the amount of ' },
        { type: 'input', num: 39 },
        {
            type: 'text',
            content:
                ' available to certain wild animals. On the other hand, these dogs may help birds by protecting their nests. These might otherwise be threatened by predators such as ',
        },
        { type: 'input', num: 40 },
        { type: 'text', content: '.' },
    ],
};

const textSegments = ref([
    { text: passageText.value, offset: 0 },

    // Q 27-31
    { text: 'Questions 27-31', offset: 7000 },
    { text: 'Reading Passage 3 has seven paragraphs, A–G.', offset: 7050 },
    { text: 'Which paragraph contains the following information?', offset: 7100 },
    { text: 'NB You may use any letter more than once.', offset: 7150 },
    ...infoMatchingQuestions.map((s, i) => ({ text: s.text, offset: 7200 + i * 100 })),

    // Q 32-36
    { text: 'Questions 32-36', offset: 8000 },
    { text: 'Look at the following statements (Questions 32-36) and the list of people below.', offset: 8050 },
    { text: 'Match each statement with the correct person, A-E.', offset: 8100 },
    { text: 'List of People', offset: 8150 },
    ...peopleList.map((p, i) => ({ text: p.name, offset: 8200 + i * 50 })),
    ...statementMatchingQuestions.map((s, i) => ({ text: s.text, offset: 8500 + i * 100 })),

    // Q 37-40
    { text: 'Questions 37-40', offset: 9500 },
    { text: 'Complete the summary below.', offset: 9550 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 9600 },
    { text: summaryCompletion.title, offset: 9650 },
    ...summaryCompletion.parts.map((p, i) => ({ text: p.content || '', offset: 9700 + i * 100 })),
]);

const getHighlightedSegment = (segmentText: string) => {
    if (!segmentText) return '';
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
                    <div class="flex h-full flex-col overflow-hidden bg-white">
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
                                        class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-sm"
                                        :data-segment-text="'READING PASSAGE 3'"
                                        v-html="getHighlightedSegment('READING PASSAGE 3')"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-center">
                                <h2
                                    class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                >
                                    <span
                                        :data-segment-text="'A new role for livestock guard dogs'"
                                        v-html="getHighlightedSegment('A new role for livestock guard dogs')"
                                    ></span>
                                </h2>
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-green-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-green-500"></div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-full flex-col overflow-hidden bg-white">
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
                                    <p
                                        class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg"
                                        :data-segment-text="'QUESTIONS'"
                                        v-html="getHighlightedSegment('QUESTIONS')"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 27-31 -->
                                <div class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm">
                                    <section id="question-27-31" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 27-31'"
                                            v-html="getHighlightedSegment('Questions 27-31')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Reading Passage 3 has seven paragraphs, A–G.'"
                                                v-html="getHighlightedSegment('Reading Passage 3 has seven paragraphs, A–G.')"
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Which paragraph contains the following information?'"
                                                v-html="getHighlightedSegment('Which paragraph contains the following information?')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600"
                                                :data-segment-text="'NB You may use any letter more than once.'"
                                                v-html="getHighlightedSegment('NB You may use any letter more than once.')"
                                            ></p>
                                        </div>
                                        <div class="space-y-3 pt-2">
                                            <div
                                                v-for="q in infoMatchingQuestions"
                                                :key="q.num"
                                                :id="`question-${q.num}`"
                                                class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-xl hover:shadow-emerald-200/50"
                                            >
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border-b-4 border-emerald-700 bg-gradient-to-br from-green-500 to-emerald-500 text-base font-bold text-white shadow-lg"
                                                        >{{ q.num }}</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="q.text"
                                                        v-html="getHighlightedSegment(q.text)"
                                                    ></p>
                                                </div>
                                                <select
                                                    v-model="answers['q' + q.num]"
                                                    class="w-24 rounded-lg border border-green-300 bg-green-50 p-2 shadow-sm focus:border-green-500 focus:ring-green-500"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="p in paragraphOptions" :key="p" :value="p">{{ p }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 32-36 -->
                                <div class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm">
                                    <section id="question-32-36" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 32-36'"
                                            v-html="getHighlightedSegment('Questions 32-36')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Look at the following statements (Questions 32-36) and the list of people below.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Look at the following statements (Questions 32-36) and the list of people below.',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Match each statement with the correct person, A-E.'"
                                                v-html="getHighlightedSegment('Match each statement with the correct person, A-E.')"
                                            ></p>
                                        </div>
                                        <div class="my-4 rounded-lg border-2 border-dashed border-green-300 p-4">
                                            <h4
                                                class="mb-3 text-center font-bold text-green-800"
                                                :data-segment-text="'List of People'"
                                                v-html="getHighlightedSegment('List of People')"
                                            ></h4>
                                            <ul class="grid list-none grid-cols-1 space-y-1 gap-x-4 pl-2 sm:grid-cols-2">
                                                <li v-for="p in peopleList" :key="p.id" class="text-gray-700">
                                                    <strong class="text-green-600">{{ p.id }}</strong>
                                                    <span :data-segment-text="p.name" v-html="getHighlightedSegment(p.name)"></span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="space-y-3 pt-2">
                                            <div
                                                v-for="q in statementMatchingQuestions"
                                                :key="q.num"
                                                :id="`question-${q.num}`"
                                                class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-xl hover:shadow-emerald-200/50"
                                            >
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border-b-4 border-emerald-700 bg-gradient-to-br from-green-500 to-emerald-500 text-base font-bold text-white shadow-lg"
                                                        >{{ q.num }}</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="q.text"
                                                        v-html="getHighlightedSegment(q.text)"
                                                    ></p>
                                                </div>
                                                <select
                                                    v-model="answers['q' + q.num]"
                                                    class="w-24 rounded-lg border border-green-300 bg-green-50 p-2 shadow-sm focus:border-green-500 focus:ring-green-500"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="p in peopleList" :key="p.id" :value="p.id">{{ p.id }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 37-40 -->
                                <div class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm">
                                    <section id="question-37-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 37-40'"
                                            v-html="getHighlightedSegment('Questions 37-40')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the summary below.'"
                                                v-html="getHighlightedSegment('Complete the summary below.')"
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                        </div>
                                        <div class="my-4 rounded-lg border-t-4 border-green-500 bg-white p-6 shadow-lg">
                                            <h4
                                                class="mb-4 text-center font-bold text-green-800"
                                                :data-segment-text="summaryCompletion.title"
                                                v-html="getHighlightedSegment(summaryCompletion.title)"
                                            ></h4>
                                            <p class="leading-loose text-gray-700">
                                                <template v-for="(part, index) in summaryCompletion.parts" :key="index">
                                                    <span
                                                        v-if="part.type === 'text'"
                                                        :data-segment-text="part.content"
                                                        v-html="getHighlightedSegment(part.content)"
                                                    ></span>
                                                    <span v-else-if="part.type === 'input'" class="mx-1 inline-flex items-center gap-2">
                                                        <span
                                                            :id="`question-${part.num}`"
                                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-green-500 text-sm font-bold text-white shadow-md"
                                                            >{{ part.num }}</span
                                                        >
                                                        <input
                                                            v-model="answers['q' + part.num]"
                                                            type="text"
                                                            class="w-32 rounded-md border border-green-300 p-2 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 sm:text-sm"
                                                        />
                                                    </span>
                                                </template>
                                            </p>
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

    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="pointer-events-auto fixed z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
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
        <div
            v-if="showNoteInput"
            class="absolute z-[9999] w-80 rounded-lg border-2 border-indigo-300 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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
.passage-panel,
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
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: #f0fdf4;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #10b981, #059669);
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #059669, #047857);
}
</style>
