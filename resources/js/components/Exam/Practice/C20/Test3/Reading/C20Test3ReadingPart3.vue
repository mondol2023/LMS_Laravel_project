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
const PANEL_WIDTH_KEY = 'reading-ielts002-part3-panel-width';
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

const passageText = ref(`Three leaders in their fields answer questions about our relationships with robot

When asked ‘Should robots be used to colonise other planets?’, cosmology and astrophysics Professor Martin Rees said he believed the solar system would be mapped by robotic craft by the end of the century. ‘The next step would be mining of asteroids, enabling fabrication of large structures in space without having to bring all the raw materials from Earth…. I think this is more realistic and benign than the … “terraforming”* of planets.’ He maintains that colonised planets ‘should be preserved with a status that is analogous to Antarctica here on Earth.’

On the question of using robots to colonise other planets and exploit mineral resources, engineering Professor Daniel Wolpert replied, ‘I don’t see a pressing need to colonise other planets unless we can bring [these] resources back to Earth. The vast majority of Earth is currently inaccessible to us. Using robots to gather resources nearer to home would seem to be a better use of our robotic tools.’

Meanwhile, for anthropology Professor Kathleen Richardson, the idea of ‘colonisation’ of other planets seemed morally dubious: ‘I think whether we do something on Earth or on Mars we should always do it in the spirit of a genuine interest in “the Other”, not to impose a particular model, but to meet “the Other”.’

In response to the second question, ‘How soon will machine intelligence outstrip human intelligence?’, Rees mentions robots that are advanced enough to beat humans at chess, but then goes on to say, ‘Robots are still limited in their ability to sense their environment: they can’t yet recognise and move the pieces on a real chessboard as cleverly as a child can. Later this century, however, their more advanced successors may relate to their surroundings, and to people, as adeptly as we do. Moral questions then arise. … Should we feel guilty about exploiting [sophisticated robots]? Should we fret if they are underemployed, frustrated, or bored?’

Wolpert’s response to the question about machine intelligence outstripping human intelligence was this: ‘In a limited sense it already has. Machines can already navigate, remember and search for items with an ability that far outstrips humans. However, there is no machine that can identify visual objects or speech with the reliability and flexibility of humans…. Expecting a machine close to the creative intelligence of a human within the next 50 years would be highly ambitious.’

Richardson believes that our fear of machines becoming too advanced has more to do with human nature than anything intrinsic to the machines themselves. In her view, it stems from humans’ tendency to personify inanimate objects: we create machines based on representations of ourselves, imagine that machines think and behave as we do, and therefore see them as an autonomous threat. ‘One of the consequences of thinking that the problem lies with machines is that we tend to imagine they are greater and more powerful than they really are and subsequently they become so.’

This led on to the third question, ‘Should we be scared by advances in artificial intelligence?’ To this question, Rees replied, ‘Those who should be worried are the futurologists who believe in the so-called “singularity”.** … And another worry is that we are increasingly dependent on computer networks, and that these could behave like a single “brain” with a mind of its own, and with goals that may be contrary to human welfare. I think we should ensure that robots remain as no more than “idiot savants” lacking the capacity to outwit us, even though they may greatly surpass us in the ability to calculate and process information.’

Wolpert’s response was to say that we have already seen the damaging effects of artificial intelligence in the form of computer viruses. ‘But in this case,’ he says, ‘the real intelligence is the malicious designer. Critically, the benefits of computers outweigh the damage that computer viruses cause. Similarly, while there may be misuses of robotics in the near future, the benefits that they will bring are likely to outweigh these negative aspects.’

Richardson’s response to this question was this: ‘We need to ask why fears of artificial intelligence and robots persist; none have in fact risen up and challenged human supremacy.’ She believes that as robots have never shown themselves to be a threat to humans, it seems unlikely that they ever will. In fact, she went on, ‘Not all fear [robots]; many people welcome machine intelligence.’

In answer to the fourth question, What can science fiction tell us about robotics?’, Rees replied, ‘I sometimes advise students that it’s better to read first-rate science fiction than second-rate science more stimulating, and perhaps no more likely to be wrong.’

As his response, Wolpert commented, ‘Science fiction has often been remarkable at predicting the future. Science fiction has painted a vivid spectrum of possible futures, from cute and helpful robots to dystopian robotic societies. Interestingly, almost no science fiction envisages a future without robots.’

Finally, on the question of science fiction, Richardson pointed out that in modern society, people tend to think there is reality on the one hand, and fiction and fantasy on the other. She then explained that the division did not always exist, and that scientists and technologists made this separation because they wanted to carve out the sphere of their work. ‘But the divide is not so clear cut, and that is why the worlds seem to collide at times,’ she said. ‘In some cases, we need to bring these different understandings together to get a whole perspective. Perhaps then, we won’t be so frightened that something we create as a copy of ourselves will be a [threat] to us.’

*terraforming: modifying a planet’s atmosphere to suit human needs

** singularity: the point when robots will be able to start creating ever more sophisticated versions of themselves
`);

const experts = [
    { id: 'A', name: 'Martin Rees' },
    { id: 'B', name: 'Daniel Wolpert' },
    { id: 'C', name: 'Kathleen Richardson' },
];
const expertStatements = [
    { num: 27, text: 'For our own safety, humans will need to restrict the abilities of robots.' },
    { num: 28, text: 'The risk of robots harming us is less serious than humans believe it to be.' },
    { num: 29, text: 'It will take many decades for robot intelligence to be as imaginative as human intelligence.' },
    { num: 30, text: 'We may have to start considering whether we are treating robots fairly.' },
    { num: 31, text: 'Robots are probably of more help to us on Earth than in space.' },
    {
        num: 32,
        text: 'The ideas in high-quality science fiction may prove to be just as accurate as those found in the work of mediocre scientists.',
    },
    { num: 33, text: 'There are those who look forward to robots developing greater intelligence.' },
];
const sentenceEndings = [
    { id: 'A', text: 'robots to explore outer space.' },
    { id: 'B', text: 'advances made in machine intelligence so far.' },
    { id: 'C', text: 'changes made to other planets for our own benefit.' },
    { id: 'D', text: 'the harm already done by artificial intelligence.' },
];
const sentenceBeginnings = [
    { num: 34, text: 'Richardson and Rees express similar views regarding the ethical aspect of' },
    { num: 35, text: 'Rees and Wolpert share an opinion about the extent of' },
    { num: 36, text: 'Wolpert disagrees with Richardson on the question of' },
];
const mcqQuestions = [
    {
        num: 37,
        question: 'What point does Richardson make about fear of machines?',
        options: [
            'It has grown alongside the development of ever more advanced robots.',
            'It is the result of our inclination to attribute human characteristics to non-human entities.',
            'It has its origins in basic misunderstandings about how inanimate objects function.',
            'It demonstrates a key difference between human intelligence and machine intelligence.',
        ],
    },
    {
        num: 38,
        question: 'What potential advance does Rees see as a cause for concern?',
        options: [
            'robots outnumbering people',
            'robots having abilities which humans do not',
            'artificial intelligence developing independent thought',
            'artificial intelligence taking over every aspect of our lives',
        ],
    },
    {
        num: 39,
        question: 'What does Wolpert emphasise in his response to the question about science fiction?',
        options: [
            'how science fiction influences our attitudes to robots',
            'how fundamental robots are to the science fiction genre',
            'how the image of robots in science fiction has changed over time',
            'how reactions to similar portrayals of robots in science fiction may vary',
        ],
    },
    {
        num: 40,
        question: 'What is Richardson doing in her comment about reality and fantasy?',
        options: [
            'warning people not to confuse one with the other',
            'outlining ways in which one has impacted on the other',
            'recommending a change of approach in how people view them',
            'explaining why scientists have a different perspective on them from other people',
        ],
    },
];

const textSegments = ref([
    { text: passageText.value, offset: 0 },
    { text: 'Questions 27-33', offset: 6000 },
    ...expertStatements.map((s) => ({ text: s.text, offset: Math.random() * 1000 + 7000 })),
    { text: 'Questions 34-36', offset: 9000 },
    ...sentenceBeginnings.map((s) => ({ text: s.text, offset: Math.random() * 1000 + 10000 })),
    ...sentenceEndings.map((s) => ({ text: s.text, offset: Math.random() * 1000 + 11000 })),
    { text: 'Questions 37-40', offset: 12000 },
    ...mcqQuestions.flatMap((q) => [
        { text: q.question, offset: Math.random() * 1000 + 13000 },
        ...q.options.map((o) => ({ text: o, offset: Math.random() * 1000 + 14000 })),
    ]),
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
                                        :data-segment-text="'Three leaders in their fields answer questions about our relationships with robot'"
                                        v-html="
                                            getHighlightedSegment('Three leaders in their fields answer questions about our relationships with robot')
                                        "
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
                                <div><p class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg">QUESTIONS</p></div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 27-33 -->
                                <div class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm">
                                    <section id="question-27-33" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 27-33'"
                                            v-html="getHighlightedSegment('Questions 27-33')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Look at the following statements (27-33) and the list of experts below.'"
                                                v-html="
                                                    getHighlightedSegment('Look at the following statements (27-33) and the list of experts below.')
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Match each statement with the correct expert, A, B or C.'"
                                                v-html="getHighlightedSegment('Match each statement with the correct expert, A, B or C.')"
                                            ></p>
                                        </div>
                                        <div class="my-4 rounded-lg border-2 border-dashed border-green-300 p-4">
                                            <h4 class="mb-3 text-center font-bold text-green-800">List of Experts</h4>
                                            <ul class="list-none space-y-1 pl-2 text-center">
                                                <li v-for="e in experts" :key="e.id" class="text-gray-700">
                                                    <strong class="text-green-600">{{ e.id }}</strong> {{ e.name }}
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="space-y-3">
                                            <div
                                                v-for="q in expertStatements"
                                                :key="q.num"
                                                :id="`question-${q.num}`"
                                                class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-emerald-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-emerald-200/50"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 font-bold text-white shadow-md"
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
                                                    class="w-28 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="e in experts" :key="e.id" :value="e.id">{{ e.id }} - {{ e.name }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 34-36 -->
                                <div class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm">
                                    <section id="question-34-36" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 34-36'"
                                            v-html="getHighlightedSegment('Questions 34-36')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete each sentence with the correct ending, A-D, below.'"
                                                v-html="getHighlightedSegment('Complete each sentence with the correct ending, A-D, below.')"
                                            ></p>
                                        </div>
                                        <div class="my-4 rounded-lg border-2 border-dashed border-green-300 p-4">
                                            <ul class="grid list-none grid-cols-2 gap-2 space-y-1 pl-2">
                                                <li v-for="e in sentenceEndings" :key="e.id" class="text-gray-700">
                                                    <strong class="text-green-600">{{ e.id }}</strong>
                                                    <span :data-segment-text="e.text" v-html="getHighlightedSegment(e.text)"></span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="space-y-3">
                                            <div
                                                v-for="q in sentenceBeginnings"
                                                :key="q.num"
                                                :id="`question-${q.num}`"
                                                class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-emerald-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-emerald-200/50"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 font-bold text-white shadow-md"
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
                                                    class="w-28 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="e in sentenceEndings" :key="e.id" :value="e.id">{{ e.id }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 37-40 -->
                                <div class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm">
                                    <section id="question-37-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 37-40'"
                                            v-html="getHighlightedSegment('Questions 37-40')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose the correct letter, A, B, C or D.'"
                                                v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"
                                            ></p>
                                        </div>
                                        <div class="space-y-6">
                                            <div
                                                v-for="q in mcqQuestions"
                                                :key="q.num"
                                                :id="`question-${q.num}`"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >{{ q.num }}</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="q.question"
                                                        v-html="getHighlightedSegment(q.question)"
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label
                                                        v-for="(opt, i) in q.options"
                                                        :key="i"
                                                        class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :value="String.fromCharCode(65 + i)"
                                                            v-model="answers['q' + q.num]"
                                                            :name="`q${q.num}`"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">{{ String.fromCharCode(65 + i) }}</strong>
                                                            <span :data-segment-text="opt" v-html="getHighlightedSegment(opt)"></span
                                                        ></span>
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
    background: #f1f5f9;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #10b981, #059669);
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #059669, #047857);
}
</style>
