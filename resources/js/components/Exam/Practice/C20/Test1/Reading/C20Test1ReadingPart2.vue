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
    q20: '',
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

const passageText = `A Around 25 million elms, accounting for 90% of all elm trees in the UK, died during the 1960s and ’70s of Dutch elm disease. In the aftermath, the elm, once so dominant in the British landscape, was largely forgotten. However, there’s now hope the elm may be reintroduced to the countryside of central and southern England. Any reintroduction will start from a very low base. ‘The impact of the disease is difficult to picture if you hadn’t seen what was there before,’ says Matt Elliot of the Woodland Trust. ‘You look at old photographs from the 1960s and it’s only then that you realise the impact [elms had] ... They were significant, large trees... then they were gone.’

B The disease is caused by a fungus that blocks the elms’ vascular (water, nutrient and food transport) system, causing branches to wilt and die. A first epidemic, which occurred in the 1920s, gradually died down, but in the ’70s a second epidemic was triggered by shipments of elm from Canada. The wood came in the form of logs destined for boat building and its intact bark was perfect for the elm bark beetles that spread the deadly fungus. This time, the beetles carried a much more virulent strain that destroyed the vast majority of British elms.

C Today, elms still exist in the southern English countryside but mostly only in low hedgerows between fields. ‘We have millions of small elms in hedgerows but they get targeted by the beetle as soon as they reach a certain size,’ says Karen Russell, co-author of the report ‘Where we are with elm’. Once the trunk of the elm reaches 10-15 centimetres or so in diameter, it becomes a perfect size for beetles to lay eggs and for the fungus to take hold. Yet mature specimens have been identified, in counties such as Cambridgeshire, that are hundreds of years old, and have mysteriously escaped the epidemic. The key, Russell says, is to identify and study those trees that have survived and work out why they stood tall when millions of others succumbed. Nevertheless, opportunities are limited as the number of these mature survivors is relatively small. ‘What are the reasons for their survival?’ asks Russell. ‘Avoidance, tolerance, resistance? We don’t know where the balance lies between the three. I don’t see how it can be entirely down to luck.’

D For centuries, elm ran a close second to oak as the hardwood tree of choice in Britain and was in many instances the most prominent tree in the landscape. Not only was elm common in European forests, it became a key component of birch, ash and hazel woodlands. The use of elm is thought to go back to the Bronze Age, when it was widely used for tools. Elm was also the preferred material for shields and early swords. In the 18th century, it was planted more widely and its wood was used for items such as storage crates and flooring. It was also suitable for items that experienced high levels of impact and was used to build the keel of the 19th-century sailing ship Cutty Sark as well as mining equipment.

E Given how ingrained elm is in British culture, it’s unsurprising the tree has many advocates. Amongst them is Peter Bourne of the National Elm Collection in Brighton. ‘I saw Dutch elm disease unfold as a small boy,’ he says. ‘The elm seemed to be part of rural England, but I remember watching trees just lose their leaves and that really stayed with me.’ Today, the city of Brighton’s elms total about 17,000. Local factors appear to have contributed to their survival. Strong winds from the sea make it difficult for the determined elm bark beetle to attack this coastal city’s elm population. However, the situation is precarious. ‘The beetles can just march in if we’re not careful, as the threat is right on our doorstep,’ says Bourne.

F Any prospect of the elm returning relies heavily on trees being either resistant to, or tolerant of, the disease. This means a widespread reintroduction would involve existing or new hybrid strains derived from resistant, generally non-native elm species. A new generation of seedlings have been bred and tested to see if they can withstand the fungus by cutting a small slit on the bark and injecting a tiny amount of the pathogen. ‘The effects are very quick,’ says Russell. ‘You return in four to six weeks and trees that are resistant show no symptoms, whereas those that are susceptible show leaf loss and may even have died completely.’

G All of this raises questions of social acceptance, acknowledges Russell. ‘If we’re putting elm back into the landscape, a small element of it is not native – are we bothered about that?’ For her, the environmental case for reintroducing elm is strong. ‘They will host wildlife, which is a good thing. Others are more wary. ‘On the face of it, it seems like a good idea,’ says Elliot. The problem, he suggests, is that, ‘You’re replacing a native species with a horticultural analogue*. You’re effectively cloning.’ There’s also the risk of introducing new diseases. Rather than plant new elms, the Woodland Trust emphasises providing space to those elms that have survived independently. ‘Sometimes the best thing you can do is just give nature time to recover over time, you might get resistance,’ says Elliot.

* horticultural analogue: a cultivated plant species that is genetically similar to an existing species`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 2', offset: 5543 },
    { text: 'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', offset: 5562 },
    { text: 'The Return of the Elm', offset: 5662 },
    { text: 'Questions 14–18', offset: 5684 },
    { text: 'Reading Passage 2 has seven sections, A–G.', offset: 5701 },
    { text: 'Which section contains the following information?', offset: 5747 },
    { text: 'Write the correct letter, A–G, in boxes 14–18 on your answer sheet.', offset: 5795 },
    { text: 'NB You may use any letter more than once.', offset: 5864 },
    { text: 'reference to the research problems that arise from there being only a few surviving large elms', offset: 5905 },
    { text: 'details of a difference of opinion about the value of reintroducing elms to Britain', offset: 5997 },
    { text: 'reference to how Dutch elm disease was brought into Britain', offset: 6084 },
    { text: 'a description of the conditions that have enabled a location in Britain to escape Dutch elm disease', offset: 6147 },
    { text: 'reference to the stage at which young elms become vulnerable to Dutch elm disease', offset: 6249 },
    { text: 'Questions 19–23', offset: 6338 },
    { text: 'Match each statement with the correct person, A, B, or C.', offset: 6355 },
    { text: 'Write your answers in boxes 19–23 on your answer sheet.', offset: 6415 },
    { text: 'NB You may use any letter more than once.', offset: 6474 },
    { text: 'If a tree gets infected with Dutch elm disease, the damage rapidly becomes visible.', offset: 6515 },
    { text: 'It may be better to wait and see if the mature elms that have survived continue to flourish.', offset: 6598 },
    { text: 'There must be an explanation for the survival of some mature elms.', offset: 6691 },
    { text: 'We need to be aware that insects carrying Dutch elm disease are not very far away.', offset: 6763 },
    { text: 'You understand the effect Dutch elm disease has had when you see evidence of how prominent the tree once was.', offset: 6852 },
    { text: 'List of People', offset: 6965 },
    { text: 'A Matt Elliot', offset: 6980 },
    { text: 'B Karen Russell', offset: 6994 },
    { text: 'C Peter Bourne', offset: 7010 },
    { text: 'Questions 24–26', offset: 7024 },
    { text: 'Complete the summary below.', offset: 7041 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 7068 },
    { text: 'Write your answers in boxes 24–26 on your answer sheet.', offset: 7125 },
    { text: 'Uses of a popular tree', offset: 7180 },
    { text: 'For hundreds of years, the only tree that was more popular in Britain than elm was ', offset: 7202 },
    {
        text: '. Starting in the Bronze Age, many tools were made from elm and people also used it to make weapons. In the 18th century, it was grown to provide wood for boxes and ',
        offset: 7284,
    },
    { text: '. Due to its strength, elm was often used for mining equipment and the Cutty Sark’s ', offset: 7484 },
    { text: ' was also constructed from elm.', offset: 7574 },
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
                                :data-segment-text="'You should spend about 20 minutes on Questions 14-23 which are based on Reading Passage 2 below.'"
                                v-html="
                                    getHighlightedSegment(
                                        'You should spend about 20 minutes on Questions 14-23 which are based on Reading Passage 2 below.',
                                    )
                                "
                            ></p>
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    <span :data-segment-text="'The Return of the Elm'" v-html="getHighlightedSegment('The Return of the Elm')"></span>
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
                                            :data-segment-text="'Questions 14–18'"
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
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A–G, in boxes 14–18 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write the correct letter, A–G, in boxes 14–18 on your answer sheet.')"
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
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                    >{{ 13 + n }}</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="
                                                        textSegments.find((s) => s.offset === [5905, 5997, 6084, 6147, 6249][n - 1])?.text
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find((s) => s.offset === [5905, 5997, 6084, 6147, 6249][n - 1])?.text || '',
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

                                <!-- Questions 19-23 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-19-23" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 19–23'"
                                            v-html="getHighlightedSegment('Questions 19–23')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Match each statement with the correct person, A, B, or C.'"
                                                v-html="getHighlightedSegment('Match each statement with the correct person, A, B, or C.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write your answers in boxes 19–23 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write your answers in boxes 19–23 on your answer sheet.')"
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'NB You may use any letter more than once.'"
                                                v-html="getHighlightedSegment('NB You may use any letter more than once.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <h4
                                                class="font-bold text-gray-800"
                                                :data-segment-text="'List of People'"
                                                v-html="getHighlightedSegment('List of People')"
                                            ></h4>
                                            <ul class="mt-2 space-y-1 text-gray-700">
                                                <li :data-segment-text="'A Matt Elliot'" v-html="getHighlightedSegment('A Matt Elliot')"></li>
                                                <li :data-segment-text="'B Karen Russell'" v-html="getHighlightedSegment('B Karen Russell')"></li>
                                                <li :data-segment-text="'C Peter Bourne'" v-html="getHighlightedSegment('C Peter Bourne')"></li>
                                            </ul>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="n in 5"
                                                :key="n"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >{{ 18 + n }}</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="
                                                        textSegments.find((s) => s.offset === [6515, 6598, 6691, 6763, 6852][n - 1])?.text
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find((s) => s.offset === [6515, 6598, 6691, 6763, 6852][n - 1])?.text || '',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers['q' + (18 + n)]"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                                <!-- Questions 24-26 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-24-26" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 24–26'"
                                            v-html="getHighlightedSegment('Questions 24–26')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
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
                                            <p
                                                class="mt-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write your answers in boxes 24–26 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write your answers in boxes 24–26 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-lg">
                                            <h4
                                                class="text-center font-bold text-gray-800"
                                                :data-segment-text="'Uses of a popular tree'"
                                                v-html="getHighlightedSegment('Uses of a popular tree')"
                                            ></h4>
                                            <div class="mt-4 space-y-4 leading-relaxed text-gray-700">
                                                <p>
                                                    <span
                                                        :data-segment-text="'For hundreds of years, the only tree that was more popular in Britain than elm was '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'For hundreds of years, the only tree that was more popular in Britain than elm was ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        class="mx-2 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-pink-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >24</span
                                                    >
                                                    <input
                                                        v-model="answers.q24"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="'. Starting in the Bronze Age, many tools were made from elm and people also used it to make weapons. In the 18th century, it was grown to provide wood for boxes and '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                '. Starting in the Bronze Age, many tools were made from elm and people also used it to make weapons. In the 18th century, it was grown to provide wood for boxes and ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        class="mx-2 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-pink-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >25</span
                                                    >
                                                    <input
                                                        v-model="answers.q25"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="'. Due to its strength, elm was often used for mining equipment and the Cutty Sark’s '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                '. Due to its strength, elm was often used for mining equipment and the Cutty Sark’s ',
                                                            )
                                                        "
                                                    ></span>

                                                    <span
                                                        class="mx-2 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-pink-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >26</span
                                                    >
                                                    <input
                                                        v-model="answers.q26"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' was also constructed from elm.'"
                                                        v-html="getHighlightedSegment(' was also constructed from elm.')"
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
