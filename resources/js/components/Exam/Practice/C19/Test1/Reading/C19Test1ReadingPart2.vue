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
const PANEL_WIDTH_KEY = 'reading-c19t1-part2-panel-width';
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

const passageText = `A When one mentions pirates, an image springs to most people\u2019s minds of a crew of misfits, daredevils and adventurers in command of a tall sailing ship in the Caribbean Sea. Yet from the first to the third millennium BCE, thousands of years before these swashbucklers began spreading fear across the Caribbean, pirates prowled the Mediterranean, raiding merchant ships and threatening vital trade routes. However, despite all efforts and the might of various ancient states, piracy could not be stopped. The situation remained unchanged for thousands of years. Only when the pirates directly threatened the interests of ancient Rome did the Roman Republic organise a massive fleet to eliminate piracy. Under the command of the Roman general Pompey, Rome eradicated piracy, transforming the Mediterranean into \u2018Mare Nostrum\u2019 (Our Sea).

B Although piracy in the Mediterranean is first recorded in ancient Egypt during the reign of Pharaoh Amenhotep III (c 1390\u20131353 BCE), it is reasonable to assume it predated this powerful civilisation. This is partly due to the great importance the Mediterranean held at this time, and partly due to its geography. While the Mediterranean region is predominantly fertile, some parts are rugged and hilly, even mountainous. In the ancient times, the inhabitants of these areas relied heavily on marine resources, including fish and salt. Most had their own boats, possessed good seafaring skills, and unsurpassed knowledge of the local coastline and sailing routes. Thus, it is not surprising that during hardships, these men turned to piracy. Geography itself further benefited the pirates, with the numerous coves along the coast providing places for them to hide their boats and strike undetected. Before the invention of ocean-going caravels* in the 15th century, ships could not easily cross long distances over open water. Thus, in the ancient world most were restricted to a few well-known navigable routes that followed the coastline. Caught in a trap, a slow merchant ship laden with goods had no other option but to surrender. In addition, knowledge of the local area helped the pirates to avoid retaliation once a state fleet arrived.
* caravel: a small, highly manoeuvrable sailing ship developed by the Portuguese

C One should also add that it was not unknown in the first and second millennia BCE for governments to resort to pirates\u2019 services, especially during wartime, employing their skills and numbers against their opponents. A pirate fleet would serve in the first wave of attack, preparing the way for the navy. Some of the regions were known for providing safe harbours to pirates, who, in return, boosted the local economy.

D The first known record of a named group of Mediterranean pirates, made during the rule of ancient Egyptian Pharaoh Akhenaten (c 1353\u20131336 BCE), was in the Amarna Letters. These were extracts of diplomatic correspondence between the pharaoh and his allies, and covered many pressing issues, including piracy. It seems the pharaoh was troubled by two distinct pirate groups, the Lukka and the Sherden. Despite the Egyptian fleet\u2019s best efforts, the pirates continued to cause substantial disruption to regional commerce. In the letters, the king of Alashiya (modern Cyprus) rejected Akhenaten\u2019s claims of a connection with the Lukka (based in modern-day Turkey). The king assured Akhenaten he was prepared to punish any of his subjects involved in piracy.

E The ancient Greek world\u2019s experience of piracy was different from that of Egyptian rulers. While Egypt\u2019s power was land-based, the ancient Greeks relied on the Mediterranean in almost all aspects of life, from trade to warfare. Interestingly, in his works the Iliad and the Odyssey, the ancient Greek writer Homer not only condones, but praises the lifestyle and actions of pirates. The opinion remained unchanged in the following centuries. The ancient Greek historian Thucydides, for instance, glorified pirates\u2019 daring attacks on ships or even cities. For Greeks, piracy was a part of everyday life. Even high-ranking members of the state were not beyond engaging in such activities. According to the Greek orator Demosthenes, in 355 BCE, Athenian ambassadors made a detour from their official travel to capture a ship sailing from Egypt, taking the wealth found onboard for themselves! The Greeks\u2019 liberal approach towards piracy does not mean they always tolerated it, but attempts to curtail piracy were hampered by the large number of pirates operating in the Mediterranean.

F The rising power of ancient Rome required the Roman Republic to deal with piracy in the Mediterranean. While piracy was a serious issue for the Republic, Rome profited greatly from its existence. Pirate raids provided a steady source of slaves, essential for Rome\u2019s agriculture and mining industries. But this arrangement could work only while the pirates left Roman interests alone. Pirate attacks on grain ships, which were essential to Roman citizens, led to angry voices in the Senate, demanding punishment of the culprits. Rome, however, did nothing, further encouraging piracy. By the 1st century BCE, emboldened pirates kidnapped prominent Roman dignitaries, asking for a large ransom to be paid. Their most famous hostage was none other than Julius Caesar, captured in 75 BCE.

G By now, Rome was well aware that pirates had outlived their usefulness. The time had come for concerted action. In 67 BCE, a new law granted Pompey vast funds to combat the Mediterranean menace. Taking personal command, Pompey divided the entire Mediterranean into 13 districts, assigning a fleet and commander to each. After cleansing one district of pirates, the fleet would join another in the next district. The process continued until the entire Mediterranean was free of pirates. Although thousands of pirates died at the hands of Pompey\u2019s troops, as a long-term solution to the problem, many more were offered land in fertile areas located far from the sea. Instead of a maritime menace, Rome got productive farmers that further boosted its economy.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 2', offset: 5600 },
    { text: 'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', offset: 5650 },
    { text: 'The pirates of the ancient Mediterranean', offset: 5800 },
    { text: 'In the first and second millennia BCE, pirates sailed around the Mediterranean, attacking ships and avoiding pursuers', offset: 5900 },
    { text: 'Questions 14\u201319', offset: 6100 },
    { text: 'Reading Passage 2 has seven paragraphs, A\u2013G.', offset: 6150 },
    { text: 'Which paragraph contains the following information?', offset: 6250 },
    { text: 'Write the correct letter, A\u2013G, in boxes 14\u201319 on your answer sheet.', offset: 6350 },
    { text: 'NB You may use any letter more than once.', offset: 6450 },
    { text: 'a reference to a denial of involvement in piracy', offset: 6550 },
    { text: 'details of how a campaign to eradicate piracy was carried out', offset: 6650 },
    { text: 'a mention of the circumstances in which states in the ancient world would make use of pirates', offset: 6750 },
    { text: 'a reference to how people today commonly view pirates', offset: 6900 },
    { text: 'an explanation of how some people were encouraged not to return to piracy', offset: 7000 },
    { text: 'a mention of the need for many sailing vessels to stay relatively close to land', offset: 7100 },
    { text: 'Questions 20\u201321', offset: 7250 },
    { text: 'Choose TWO letters, A\u2013E.', offset: 7300 },
    {
        text: 'Which TWO of the following statements does the writer make about inhabitants of the Mediterranean region in the ancient world?',
        offset: 7350,
    },
    { text: 'A. They often used stolen vessels to carry out pirate attacks.', offset: 7550 },
    { text: 'B. They managed to escape capture by the authorities because they knew the area so well.', offset: 7650 },
    { text: 'C. They paid for information about the routes merchant ships would take.', offset: 7800 },
    { text: 'D. They depended more on the sea for their livelihood than on farming.', offset: 7900 },
    { text: 'E. They stored many of the goods taken in pirate attacks in coves along the coastline.', offset: 8000 },
    { text: 'Questions 22\u201323', offset: 8150 },
    { text: 'Which TWO of the following statements does the writer make about piracy and ancient Greece?', offset: 8250 },
    { text: 'A. The state estimated that very few people were involved in piracy.', offset: 8400 },
    { text: 'B. Attitudes towards piracy changed shortly after the Iliad and the Odyssey were written.', offset: 8500 },
    { text: 'C. Important officials were known to occasionally take part in piracy.', offset: 8650 },
    { text: 'D. Every citizen regarded pirate attacks on cities as unacceptable.', offset: 8750 },
    { text: 'E. A favourable view of piracy is evident in certain ancient Greek texts.', offset: 8850 },
    { text: 'Questions 24\u201326', offset: 9000 },
    { text: 'Complete the summary below.', offset: 9050 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 9100 },
    { text: 'Write your answers in boxes 24\u201326 on your answer sheet.', offset: 9200 },
    { text: 'Ancient Rome and piracy', offset: 9300 },
    {
        text: 'Piracy was an issue ancient Rome had to deal with, but it also brought some benefits for Rome. For example, pirates supplied slaves that were important for Rome\u2019s industries. However, attacks on vessels transporting ',
        offset: 9350,
    },
    { text: ' to Rome resulted in calls for ', offset: 9600 },
    { text: ' for the pirates responsible. Nevertheless, piracy continued, with some pirates demanding a ', offset: 9700 },
    { text: ' for the return of the Roman officials they captured.', offset: 9850 },
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
                                    <span
                                        :data-segment-text="'The pirates of the ancient Mediterranean'"
                                        v-html="getHighlightedSegment('The pirates of the ancient Mediterranean')"
                                    ></span>
                                </h2>
                                <p
                                    class="text-center text-sm text-gray-600 italic"
                                    :data-segment-text="'In the first and second millennia BCE, pirates sailed around the Mediterranean, attacking ships and avoiding pursuers'"
                                    v-html="
                                        getHighlightedSegment(
                                            'In the first and second millennia BCE, pirates sailed around the Mediterranean, attacking ships and avoiding pursuers',
                                        )
                                    "
                                ></p>
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
                                <!-- Questions 14-19 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-14-19" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 14\u201319'"
                                            v-html="getHighlightedSegment('Questions 14\u201319')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Reading Passage 2 has seven paragraphs, A\u2013G.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has seven paragraphs, A\u2013G.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which paragraph contains the following information?'"
                                                v-html="getHighlightedSegment('Which paragraph contains the following information?')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013G, in boxes 14\u201319 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013G, in boxes 14\u201319 on your answer sheet.',
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
                                                v-for="n in 6"
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
                                                        textSegments.find((s) => s.offset === [6550, 6650, 6750, 6900, 7000, 7100][n - 1])?.text
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find((s) => s.offset === [6550, 6650, 6750, 6900, 7000, 7100][n - 1])
                                                                ?.text || '',
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

                                <!-- Questions 20-21 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-20-21" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 20\u201321'"
                                            v-html="getHighlightedSegment('Questions 20\u201321')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Choose TWO letters, A\u2013E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A\u2013E.')"
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Which TWO of the following statements does the writer make about inhabitants of the Mediterranean region in the ancient world?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Which TWO of the following statements does the writer make about inhabitants of the Mediterranean region in the ancient world?',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                :id="'question-20'"
                                                class="flex flex-col gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                        >20</span
                                                    >
                                                    <select
                                                        v-model="answers.q20"
                                                        class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div
                                                :id="'question-21'"
                                                class="flex flex-col gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                        >21</span
                                                    >
                                                    <select
                                                        v-model="answers.q21"
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
                                                    :data-segment-text="'A. They often used stolen vessels to carry out pirate attacks.'"
                                                    v-html="getHighlightedSegment('A. They often used stolen vessels to carry out pirate attacks.')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'B. They managed to escape capture by the authorities because they knew the area so well.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'B. They managed to escape capture by the authorities because they knew the area so well.',
                                                        )
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'C. They paid for information about the routes merchant ships would take.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'C. They paid for information about the routes merchant ships would take.',
                                                        )
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'D. They depended more on the sea for their livelihood than on farming.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'D. They depended more on the sea for their livelihood than on farming.',
                                                        )
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'E. They stored many of the goods taken in pirate attacks in coves along the coastline.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'E. They stored many of the goods taken in pirate attacks in coves along the coastline.',
                                                        )
                                                    "
                                                ></li>
                                            </ul>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 22-23 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-22-23" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 22\u201323'"
                                            v-html="getHighlightedSegment('Questions 22\u201323')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Choose TWO letters, A\u2013E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A\u2013E.')"
                                            ></p>
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'Which TWO of the following statements does the writer make about piracy and ancient Greece?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Which TWO of the following statements does the writer make about piracy and ancient Greece?',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                :id="'question-22'"
                                                class="flex flex-col gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                        >22</span
                                                    >
                                                    <select
                                                        v-model="answers.q22"
                                                        class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                    </select>
                                                </div>
                                            </div>
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
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <ul class="space-y-2 text-gray-700">
                                                <li
                                                    :data-segment-text="'A. The state estimated that very few people were involved in piracy.'"
                                                    v-html="
                                                        getHighlightedSegment('A. The state estimated that very few people were involved in piracy.')
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'B. Attitudes towards piracy changed shortly after the Iliad and the Odyssey were written.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'B. Attitudes towards piracy changed shortly after the Iliad and the Odyssey were written.',
                                                        )
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'C. Important officials were known to occasionally take part in piracy.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'C. Important officials were known to occasionally take part in piracy.',
                                                        )
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'D. Every citizen regarded pirate attacks on cities as unacceptable.'"
                                                    v-html="
                                                        getHighlightedSegment('D. Every citizen regarded pirate attacks on cities as unacceptable.')
                                                    "
                                                ></li>
                                                <li
                                                    :data-segment-text="'E. A favourable view of piracy is evident in certain ancient Greek texts.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'E. A favourable view of piracy is evident in certain ancient Greek texts.',
                                                        )
                                                    "
                                                ></li>
                                            </ul>
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
                                            :data-segment-text="'Questions 24\u201326'"
                                            v-html="getHighlightedSegment('Questions 24\u201326')"
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
                                                :data-segment-text="'Write your answers in boxes 24\u201326 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write your answers in boxes 24\u201326 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-lg">
                                            <h4
                                                class="text-center font-bold text-gray-800"
                                                :data-segment-text="'Ancient Rome and piracy'"
                                                v-html="getHighlightedSegment('Ancient Rome and piracy')"
                                            ></h4>
                                            <div class="mt-4 space-y-4 leading-relaxed text-gray-700">
                                                <p>
                                                    <span
                                                        :data-segment-text="'Piracy was an issue ancient Rome had to deal with, but it also brought some benefits for Rome. For example, pirates supplied slaves that were important for Rome\u2019s industries. However, attacks on vessels transporting '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Piracy was an issue ancient Rome had to deal with, but it also brought some benefits for Rome. For example, pirates supplied slaves that were important for Rome\u2019s industries. However, attacks on vessels transporting ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-24"
                                                        class="mx-2 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-pink-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >24</span
                                                    >
                                                    <input
                                                        v-model="answers.q24"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' to Rome resulted in calls for '"
                                                        v-html="getHighlightedSegment(' to Rome resulted in calls for ')"
                                                    ></span>
                                                    <span
                                                        id="question-25"
                                                        class="mx-2 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-pink-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >25</span
                                                    >
                                                    <input
                                                        v-model="answers.q25"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' for the pirates responsible. Nevertheless, piracy continued, with some pirates demanding a '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' for the pirates responsible. Nevertheless, piracy continued, with some pirates demanding a ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-26"
                                                        class="mx-2 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-pink-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >26</span
                                                    >
                                                    <input
                                                        v-model="answers.q26"
                                                        type="text"
                                                        class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' for the return of the Roman officials they captured.'"
                                                        v-html="getHighlightedSegment(' for the return of the Roman officials they captured.')"
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
