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

// Reading Part 3 component

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-c19t4-part3-panel-width';
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

const passageText = `There has long been a general assumption that human beings are essentially selfish. We\u2019re apparently ruthless, with strong impulses to compete against each other for resources and to accumulate power and possessions. If we are kind to one another, it\u2019s usually because we have ulterior motives. If we are good, it\u2019s only because we have managed to control and transcend our innate selfishness and brutality.

This bleak view of human nature is closely associated with the science writer Richard Dawkins, whose 1976 book The Selfish Gene became popular because it fitted so well with \u2013 and helped to justify \u2013 the competitive and individualistic ethos that was so prevalent in late 20th-century societies. Like many others, Dawkins justifies his views with reference to the field of evolutionary psychology. Evolutionary psychology theorises that present-day human traits developed in prehistoric times, during what is termed the \u2018environment of evolutionary adaptedness\u2019.

Prehistory is usually seen as a period of intense competition, when life was such a brutal battle that only those with traits such as selfishness, aggression and ruthlessness survived. And because survival depended on access to resources \u2013 such as rivers, forests and animals \u2013 there was bound to be conflict between rival groups, which led to the development of traits such as racism and warfare. This seems logical. But, in fact, the assumption on which this all rests \u2013 that prehistoric life was a desperate struggle for survival \u2013 is false.

It\u2019s important to remember that in the prehistoric era, the world was very sparsely populated. According to some estimates, around 15,000 years ago, the population of Europe was only 29,000, and the population of the whole world was less than half a million. Humans at that time were hunter-gatherers: people who lived by hunting wild animals and collecting wild plants. With such small population densities, it seems unlikely that prehistoric hunter-gatherer groups had to compete against each other for resources or had any need to develop ruthlessness and competitiveness, or to go to war.

There is significant evidence to back this notion from contemporary hunter-gatherer groups, who live in the same way as prehistoric humans did. As the anthropologist Bruce Knauft has remarked, hunter-gatherers are characterized by \u2018extreme political and sexual egalitarianism\u2019. Knauft has observed that individuals in such groups don\u2019t accumulate property or possessions and have an ethical obligation to share everything. They also have methods of preserving egalitarianism by ensuring that disparities of status don\u2019t arise.

The !Kung people of southern Africa, for example, swap arrows before going hunting and when an animal is killed, the acclaim does not go to the person who fired the arrow, but to the person the arrow belongs to. And if a person becomes too domineering, the other members of the group ostracise them, exiling the offender from society. Typically in such groups, men do not dictate what women do. Women in hunter-gatherer groups worldwide often benefit from a high level of autonomy, being able to select their own marriage partners, decide what work they do and work whenever they choose to. And if a marriage breaks down, they have custody rights over their children.

Many anthropologists believe that societies such as the !Kung were normal until a few thousand years ago, when population growth led to the development of agriculture and a settled lifestyle. In view of the above, there seems little reason to assume that traits such as racism, warfare and male domination should have been selected by evolution \u2013 as they would have been of little benefit in the prehistoric era. Individuals who behaved selfishly and ruthlessly would be less likely to survive, since they would have been ostracised from their groups.

It makes more sense, then, to see traits such as cooperation, egalitarianism, altruism and peacefulness as innate characteristics of human beings. These were the traits that were prevalent in human life for tens of thousands of years. So presumably these traits are still strong in us now.

But if prehistoric life wasn\u2019t really as brutal as has often been assumed, why do modern humans behave so selfishly and ruthlessly? Perhaps these negative traits should be seen as a later development, the result of environmental and psychological factors. Research has shown repeatedly that when the natural habitats of primates such as apes and gorillas are disrupted, they tend to become more violent and hierarchical.

So, it could well be that the same thing has happened to us. I believe that the end of the hunter-gatherer lifestyle and the advent of farming was connected to a psychological change that occurred in some groups of people. There was a new sense of individuality and separateness, which led to a new selfishness, and ultimately to hierarchical societies, patriarchy and warfare. At any rate, these negative traits appear to have developed so recently that it doesn\u2019t seem feasible to explain them in adaptive or evolutionary terms.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 3', offset: 5800 },
    { text: 'The Unselfish Gene', offset: 5850 },
    { text: 'A psychologist gives his view on how humans became self-centred', offset: 5920 },
    // Q27-30: Multiple choice
    { text: 'Questions 27\u201330', offset: 6100 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 6120 },
    { text: 'Write the correct letter in boxes 27\u201330 on your answer sheet.', offset: 6170 },
    { text: 'What is the writer doing in the first paragraph?', offset: 6250 },
    { text: 'setting out two opposing views about human nature', offset: 6340 },
    { text: 'justifying his opinion about our tendency to be greedy', offset: 6400 },
    { text: 'describing a commonly held belief about people\u2019s behaviour', offset: 6470 },
    { text: 'explaining why he thinks that humans act in a selfish manner', offset: 6540 },
    { text: 'What point is made about Richard Dawkins\u2019 book The Selfish Gene?', offset: 6610 },
    { text: 'Its appeal lay in the radical nature of its ideas.', offset: 6710 },
    { text: 'Its success was due to the scientific support it offered.', offset: 6750 },
    { text: 'It presented a view that was in line with the attitudes of its time.', offset: 6820 },
    { text: 'It took an innovative approach to the analysis of human psychology.', offset: 6890 },
    { text: 'What does the writer suggest about the prehistoric era in the fourth paragraph?', offset: 6960 },
    { text: 'Societies were more complex than many people believe.', offset: 7060 },
    { text: 'Supplies of natural resources were probably relatively plentiful.', offset: 7120 },
    { text: 'Most estimates about population sizes are likely to be inaccurate.', offset: 7190 },
    { text: 'Humans moved across continents more than was previously thought.', offset: 7260 },
    { text: 'The writer refers to Bruce Knauft\u2019s work as support for the idea that', offset: 7340 },
    { text: 'selfishness is a relatively recent development in human societies.', offset: 7430 },
    { text: 'only people in isolated communities can live in an unselfish manner.', offset: 7500 },
    { text: 'very few lifestyles have survived unchanged since prehistoric times.', offset: 7570 },
    { text: 'hunter-gatherer cultures worldwide are declining in number.', offset: 7640 },
    // Q31-35: Summary completion
    { text: 'Questions 31\u201335', offset: 7720 },
    { text: 'Complete the summary below.', offset: 7740 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 7780 },
    { text: 'Write your answers in boxes 31\u201335 on your answer sheet.', offset: 7850 },
    { text: 'Contemporary hunter-gatherer societies', offset: 7920 },
    { text: 'Bruce Knauft\u2019s research shows that contemporary hunter-gatherer societies tend to exhibit a high level of', offset: 7970 },
    {
        text: 'in all areas of life. In these cultures, distributing resources fairly among all members is a moral obligation. These societies also employ strategies to prevent differences in',
        offset: 8080,
    },
    { text: 'occurring: for example, the !Kung follow a custom whereby the credit for one person\u2019s success at', offset: 8260 },
    { text: 'is given to another member of the group. Individuals who behave in a', offset: 8370 },
    { text: 'manner are punished by being excluded from the group, and women have a considerable amount of', offset: 8450 },
    { text: 'in choices regarding work and marriage.', offset: 8550 },
    // Q36-40: YES/NO/NOT GIVEN
    { text: 'Questions 36\u201340', offset: 8620 },
    { text: 'Do the following statements agree with the views of the writer in Reading Passage 3?', offset: 8640 },
    { text: 'In boxes 36\u201340 on your answer sheet, write', offset: 8740 },
    { text: 'if the statement agrees with the views of the writer', offset: 8790 },
    { text: 'if the statement contradicts the views of the writer', offset: 8850 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 8920 },
    { text: 'Some anthropologists are mistaken about the point when the number of societies such as the !Kung began to decline.', offset: 9000 },
    { text: 'Humans who developed warlike traits in prehistory would have had an advantage over those who did not.', offset: 9120 },
    { text: 'Being peaceful and cooperative is a natural way for people to behave.', offset: 9230 },
    { text: 'Negative traits are more apparent in some modern cultures than in others.', offset: 9310 },
    {
        text: 'Animal research has failed to reveal a link between changes in the environment and the emergence of aggressive tendencies.',
        offset: 9390,
    },
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
                                        <span :data-segment-text="'The Unselfish Gene'" v-html="getHighlightedSegment('The Unselfish Gene')"></span>
                                    </h2>
                                    <p
                                        class="mt-1 text-center text-sm text-gray-500 italic"
                                        :data-segment-text="'A psychologist gives his view on how humans became self-centred'"
                                        v-html="getHighlightedSegment('A psychologist gives his view on how humans became self-centred')"
                                    ></p>
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-green-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-green-500"></div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-green-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-green-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-green-600"></div>
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
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 27-30: Multiple Choice A-D -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-27-30" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 27\u201330'"
                                            v-html="getHighlightedSegment('Questions 27\u201330')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose the correct letter, A, B, C or D.'"
                                                v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter in boxes 27\u201330 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write the correct letter in boxes 27\u201330 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Q27 -->
                                            <div
                                                id="question-27"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >27</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'What is the writer doing in the first paragraph?'"
                                                        v-html="getHighlightedSegment('What is the writer doing in the first paragraph?')"
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q27"
                                                            value="A"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">A</strong>
                                                            <span
                                                                :data-segment-text="'setting out two opposing views about human nature'"
                                                                v-html="getHighlightedSegment('setting out two opposing views about human nature')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q27"
                                                            value="B"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">B</strong>
                                                            <span
                                                                :data-segment-text="'justifying his opinion about our tendency to be greedy'"
                                                                v-html="
                                                                    getHighlightedSegment('justifying his opinion about our tendency to be greedy')
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q27"
                                                            value="C"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">C</strong>
                                                            <span
                                                                :data-segment-text="'describing a commonly held belief about people\u2019s behaviour'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'describing a commonly held belief about people\u2019s behaviour',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q27"
                                                            value="D"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">D</strong>
                                                            <span
                                                                :data-segment-text="'explaining why he thinks that humans act in a selfish manner'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'explaining why he thinks that humans act in a selfish manner',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q28 -->
                                            <div
                                                id="question-28"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >28</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'What point is made about Richard Dawkins\u2019 book The Selfish Gene?'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'What point is made about Richard Dawkins\u2019 book The Selfish Gene?',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q28"
                                                            value="A"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">A</strong>
                                                            <span
                                                                :data-segment-text="'Its appeal lay in the radical nature of its ideas.'"
                                                                v-html="getHighlightedSegment('Its appeal lay in the radical nature of its ideas.')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q28"
                                                            value="B"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">B</strong>
                                                            <span
                                                                :data-segment-text="'Its success was due to the scientific support it offered.'"
                                                                v-html="
                                                                    getHighlightedSegment('Its success was due to the scientific support it offered.')
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q28"
                                                            value="C"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">C</strong>
                                                            <span
                                                                :data-segment-text="'It presented a view that was in line with the attitudes of its time.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'It presented a view that was in line with the attitudes of its time.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q28"
                                                            value="D"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">D</strong>
                                                            <span
                                                                :data-segment-text="'It took an innovative approach to the analysis of human psychology.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'It took an innovative approach to the analysis of human psychology.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q29 -->
                                            <div
                                                id="question-29"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >29</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'What does the writer suggest about the prehistoric era in the fourth paragraph?'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'What does the writer suggest about the prehistoric era in the fourth paragraph?',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q29"
                                                            value="A"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">A</strong>
                                                            <span
                                                                :data-segment-text="'Societies were more complex than many people believe.'"
                                                                v-html="
                                                                    getHighlightedSegment('Societies were more complex than many people believe.')
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q29"
                                                            value="B"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">B</strong>
                                                            <span
                                                                :data-segment-text="'Supplies of natural resources were probably relatively plentiful.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Supplies of natural resources were probably relatively plentiful.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q29"
                                                            value="C"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">C</strong>
                                                            <span
                                                                :data-segment-text="'Most estimates about population sizes are likely to be inaccurate.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Most estimates about population sizes are likely to be inaccurate.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q29"
                                                            value="D"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">D</strong>
                                                            <span
                                                                :data-segment-text="'Humans moved across continents more than was previously thought.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Humans moved across continents more than was previously thought.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q30 -->
                                            <div
                                                id="question-30"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >30</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'The writer refers to Bruce Knauft\u2019s work as support for the idea that'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The writer refers to Bruce Knauft\u2019s work as support for the idea that',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q30"
                                                            value="A"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">A</strong>
                                                            <span
                                                                :data-segment-text="'selfishness is a relatively recent development in human societies.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'selfishness is a relatively recent development in human societies.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q30"
                                                            value="B"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">B</strong>
                                                            <span
                                                                :data-segment-text="'only people in isolated communities can live in an unselfish manner.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'only people in isolated communities can live in an unselfish manner.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q30"
                                                            value="C"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">C</strong>
                                                            <span
                                                                :data-segment-text="'very few lifestyles have survived unchanged since prehistoric times.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'very few lifestyles have survived unchanged since prehistoric times.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q30"
                                                            value="D"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">D</strong>
                                                            <span
                                                                :data-segment-text="'hunter-gatherer cultures worldwide are declining in number.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'hunter-gatherer cultures worldwide are declining in number.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 31-35: Summary Completion (ONE WORD ONLY) -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-31-35" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 31\u201335'"
                                            v-html="getHighlightedSegment('Questions 31\u201335')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the summary below.'"
                                                v-html="getHighlightedSegment('Complete the summary below.')"
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write your answers in boxes 31\u201335 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write your answers in boxes 31\u201335 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <h4
                                                class="mb-4 text-lg font-bold text-green-700"
                                                :data-segment-text="'Contemporary hunter-gatherer societies'"
                                                v-html="getHighlightedSegment('Contemporary hunter-gatherer societies')"
                                            ></h4>

                                            <div class="space-y-4 leading-relaxed text-gray-700">
                                                <p>
                                                    <span
                                                        :data-segment-text="'Bruce Knauft\u2019s research shows that contemporary hunter-gatherer societies tend to exhibit a high level of'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Bruce Knauft\u2019s research shows that contemporary hunter-gatherer societies tend to exhibit a high level of',
                                                            )
                                                        "
                                                    ></span>
                                                    <span id="question-31" class="mx-1 inline-flex items-center">
                                                        <span
                                                            class="mr-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-xs font-semibold text-white shadow-md"
                                                            >31</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q31"
                                                            class="w-32 rounded border-b-2 border-green-400 bg-green-50 px-2 py-1 text-sm focus:border-green-600 focus:outline-none"
                                                            placeholder="ONE WORD ONLY"
                                                        />
                                                    </span>
                                                    <span
                                                        :data-segment-text="'in all areas of life. In these cultures, distributing resources fairly among all members is a moral obligation. These societies also employ strategies to prevent differences in'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'in all areas of life. In these cultures, distributing resources fairly among all members is a moral obligation. These societies also employ strategies to prevent differences in',
                                                            )
                                                        "
                                                    ></span>
                                                    <span id="question-32" class="mx-1 inline-flex items-center">
                                                        <span
                                                            class="mr-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-xs font-semibold text-white shadow-md"
                                                            >32</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q32"
                                                            class="w-32 rounded border-b-2 border-green-400 bg-green-50 px-2 py-1 text-sm focus:border-green-600 focus:outline-none"
                                                            placeholder="ONE WORD ONLY"
                                                        />
                                                    </span>
                                                    <span
                                                        :data-segment-text="'occurring: for example, the !Kung follow a custom whereby the credit for one person\u2019s success at'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'occurring: for example, the !Kung follow a custom whereby the credit for one person\u2019s success at',
                                                            )
                                                        "
                                                    ></span>
                                                    <span id="question-33" class="mx-1 inline-flex items-center">
                                                        <span
                                                            class="mr-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-xs font-semibold text-white shadow-md"
                                                            >33</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q33"
                                                            class="w-32 rounded border-b-2 border-green-400 bg-green-50 px-2 py-1 text-sm focus:border-green-600 focus:outline-none"
                                                            placeholder="ONE WORD ONLY"
                                                        />
                                                    </span>
                                                    <span
                                                        :data-segment-text="'is given to another member of the group. Individuals who behave in a'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'is given to another member of the group. Individuals who behave in a',
                                                            )
                                                        "
                                                    ></span>
                                                    <span id="question-34" class="mx-1 inline-flex items-center">
                                                        <span
                                                            class="mr-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-xs font-semibold text-white shadow-md"
                                                            >34</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q34"
                                                            class="w-32 rounded border-b-2 border-green-400 bg-green-50 px-2 py-1 text-sm focus:border-green-600 focus:outline-none"
                                                            placeholder="ONE WORD ONLY"
                                                        />
                                                    </span>
                                                    <span
                                                        :data-segment-text="'manner are punished by being excluded from the group, and women have a considerable amount of'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'manner are punished by being excluded from the group, and women have a considerable amount of',
                                                            )
                                                        "
                                                    ></span>
                                                    <span id="question-35" class="mx-1 inline-flex items-center">
                                                        <span
                                                            class="mr-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-xs font-semibold text-white shadow-md"
                                                            >35</span
                                                        >
                                                        <input
                                                            type="text"
                                                            v-model="answers.q35"
                                                            class="w-32 rounded border-b-2 border-green-400 bg-green-50 px-2 py-1 text-sm focus:border-green-600 focus:outline-none"
                                                            placeholder="ONE WORD ONLY"
                                                        />
                                                    </span>
                                                    <span
                                                        :data-segment-text="'in choices regarding work and marriage.'"
                                                        v-html="getHighlightedSegment('in choices regarding work and marriage.')"
                                                    ></span>
                                                </p>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 36-40: YES/NO/NOT GIVEN -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-36-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 36\u201340'"
                                            v-html="getHighlightedSegment('Questions 36\u201340')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the views of the writer in Reading Passage 3?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the views of the writer in Reading Passage 3?',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'In boxes 36\u201340 on your answer sheet, write'"
                                                v-html="getHighlightedSegment('In boxes 36\u201340 on your answer sheet, write')"
                                            ></p>
                                            <div class="mt-2 space-y-4 text-gray-700">
                                                <p>
                                                    <strong class="mx-2 rounded bg-green-100 px-6 py-1.5 font-semibold text-green-700 shadow-lg"
                                                        >YES</strong
                                                    >
                                                    <span
                                                        :data-segment-text="'if the statement agrees with the views of the writer'"
                                                        v-html="getHighlightedSegment('if the statement agrees with the views of the writer')"
                                                    ></span>
                                                </p>
                                                <p>
                                                    <strong class="mx-2 my-2 rounded bg-red-100 px-7 py-1.5 font-semibold text-red-700 shadow-lg"
                                                        >NO</strong
                                                    >
                                                    <span
                                                        :data-segment-text="'if the statement contradicts the views of the writer'"
                                                        v-html="getHighlightedSegment('if the statement contradicts the views of the writer')"
                                                    ></span>
                                                </p>
                                                <p>
                                                    <strong class="mx-1 my-2 rounded bg-gray-100 px-1 py-2 font-semibold text-gray-700 shadow-lg"
                                                        >NOT GIVEN</strong
                                                    >
                                                    <span
                                                        :data-segment-text="'if it is impossible to say what the writer thinks about this'"
                                                        v-html="getHighlightedSegment('if it is impossible to say what the writer thinks about this')"
                                                    ></span>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <!-- Q36 -->
                                            <div
                                                id="question-36"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >36</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'Some anthropologists are mistaken about the point when the number of societies such as the !Kung began to decline.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Some anthropologists are mistaken about the point when the number of societies such as the !Kung began to decline.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q36"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q36"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q36"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q37 -->
                                            <div
                                                id="question-37"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >37</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'Humans who developed warlike traits in prehistory would have had an advantage over those who did not.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Humans who developed warlike traits in prehistory would have had an advantage over those who did not.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q37"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q37"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q37"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q38 -->
                                            <div
                                                id="question-38"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >38</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'Being peaceful and cooperative is a natural way for people to behave.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Being peaceful and cooperative is a natural way for people to behave.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q38"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q38"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q38"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q39 -->
                                            <div
                                                id="question-39"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >39</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'Negative traits are more apparent in some modern cultures than in others.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Negative traits are more apparent in some modern cultures than in others.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q39"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q39"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q39"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q40 -->
                                            <div
                                                id="question-40"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >40</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'Animal research has failed to reveal a link between changes in the environment and the emergence of aggressive tendencies.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Animal research has failed to reveal a link between changes in the environment and the emergence of aggressive tendencies.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q40"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q40"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q40"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
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
