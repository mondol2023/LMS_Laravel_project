<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Reading Part 1 component

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
const PANEL_WIDTH_KEY = 'reading-ielts011-part1-panel-width';
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
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q7: '',
    q8: '',
    q9: '',
    q10: '',
    q11: '',
    q12: '',
    q13: '',
    q14: '',
});

const passageText = `The professional career paths available to graduates from courses relating to human movement and sport science are as diverse as the graduate’s imagination. However, undergraduate courses with this type of content, in Australia as well as in most other Western countries, were originally designed as preparation programmes for Physical Education (PE) teachers. 

The initial programmes commenced soon after the conclusion of World War II in the mid-1940s. One of the primary motives for these initiatives was the fact that, during the war effort, so many of the men who were assessed for military duty had been declared unfit. The government saw the solution in the providing of Physical Education programmes in schools, delivered by better prepared and specifically educated PE teachers. 

Later, in the 1970s and early 1980s, the surplus of Australians graduating with a PE degree obliged institutions delivering this qualification to identify new employment opportunities for their graduates, resulting in the first appearance of degrees catering for recreation professionals. In many instances, this diversity of programme delivery merely led to degrees, delivered by physical educators, as a sideline activity to the production of PE teachers. 

Whilst the need to produce Physical Education teachers remains a significant social need, and most developed societies demand the availability of quality leisure programmes for their citizens, the career options of graduates within this domain are still developing. The two most evident growth domains are in the area of the professional delivery of sport, and the role of a physical lifestyle for community health. 

The sports industry is developing at an unprecedented rate of growth. From a business perspective, sport is now seen as an area with the potential for high returns. It is quite significant that the businessman Rupert Murdoch broadened his business base from media to sport, having purchased an American baseball team and an Australian Rugby League competition, as well as seeking opportunities to invest in an English football club. No business person of such international stature would see fit to invest in sport unless he was satisfied that this was a sound business venture with ideal revenue-generating opportunities. 

These developments have confirmed sport as a business with professional management structures, marketing processes, and development strategies in place. They have indicated new and developing career paths for graduates of human movement science, sport science, exercise science and related degrees. Graduates can now visualise career paths extending into such diverse domains as sport management, sport marketing, event and facility management, government policy development pertaining to sport, sport journalism, sport psychology, and sport or athletic coaching. 

Business leaders will only continue their enthusiasm for sport if they receive returns for their money. Such returns will only be forthcoming if astute, enthusiastic and properly educated professionals are delivering the programs that earn appropriate financial returns. The successful universities of the 21st century will be those that have responded to this challenge by delivering such degrees. 

A second professional growth area for this group of graduates is associated with community health. The increasing demand for government expenditure within health budgets is reaching the stage where most governments are simply unable to function in a manner that is satisfying their constituents. One of the primary reasons for this problem is the unhelpful emphasis on treatment in medical care programmes. Governments have traditionally given their senior health official the title of ‘Minister for Health’, when in fact this officer has functioned as ‘Minister for Sickness and the Construction of Hospitals’. Government focus simply has to change. If the change is not brought about for philosophical reasons, it will occur naturally, because insufficient funding will be available to address the ever-increasing costs of medical support. 

Graduates of human movement, exercise science and sport science have the potential to become major players in this shift in policy focus. It is these graduates who already have the skills, knowledge and understanding to initiate community health education programmes to reduce cardio-vascular disease, to reduce medical dependency upon diabetes, to improve workplace health leading to increased productivity, to initiate and promote programmes of activity for the elderly that reduce medical dependency, and to maintain an active lifestyle for the unemployed and disadvantaged groups in society. This is the graduate that governments will be calling upon to shift the community focus from medical dependency to healthy lifestyles in the decades ahead. 

The career paths of these graduates are developing at a pace that is not evident in other professions. The contribution that these graduates can make to society, and the recognition of this contribution is at an unprecedented high, and all indications are that it will continue to grow.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 1', offset: 4825 },
    { text: 'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.', offset: 4844 },
    { text: 'Sport Science in Australia', offset: 4944 },
    { text: 'QUESTIONS', offset: 4967 },
    { text: 'Answer all questions based on Reading Passage 1', offset: 4976 },
    { text: 'Questions 1-5', offset: 5023 },
    { text: 'Complete the flow chart below.', offset: 5037 },
    { text: 'Choose NO MORE THAN TWO WORDS from the passage for each answer.', offset: 5068 },
    { text: 'The history of sports and physical science in Australia', offset: 5135 },
    { text: 'A lot of people identified as being', offset: 5191 },
    { text: 'Introduction of PE to', offset: 5225 },
    { text: 'Special training programmes for', offset: 5247 },
    { text: 'of PE graduates', offset: 5278 },
    { text: 'Identification of alternative', offset: 5294 },
    { text: 'Diversification of course delivery', offset: 5324 },
    { text: 'Questions 6-13', offset: 5439 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 5455 },
    { text: 'TRUE', offset: 5804 },
    { text: 'if the statement agrees with the information', offset: 5808 },
    { text: 'FALSE', offset: 5852 },
    { text: 'if the statement contradicts the information', offset: 5857 },
    { text: 'NOT GIVEN', offset: 5901 },
    { text: 'if there is no information on this', offset: 5910 },
    { text: 'Sport is generally regarded as a profitable area for investment.', offset: 5680 },
    { text: 'Rupert Murdoch has a personal as well as a business interest in sport.', offset: 5745 },
    { text: 'The range of career opportunities available to sport graduates is increasing.', offset: 5817 },
    { text: 'The interests of business and the interests of universities are linked.', offset: 5896 },
    { text: 'Governments have been focusing too much attention on preventative medicine.', offset: 5968 },
    { text: 'It is inevitable that government priorities for health spending will change.', offset: 6045 },
    { text: 'Existing degree courses are unsuitable for careers in community health.', offset: 6122 },
    { text: 'Funding for sport science and related degrees has been increased considerably.', offset: 6194 },
]);

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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
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
                                        :data-segment-text="'READING PASSAGE 1'"
                                        v-html="getHighlightedSegment('READING PASSAGE 1')"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p
                                        class="mb-1 text-base sm:text-lg"
                                        :data-segment-text="'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.',
                                            )
                                        "
                                    ></p>
                                    <h2
                                        class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        <span
                                            :data-segment-text="'Sport Science in Australia'"
                                            v-html="getHighlightedSegment('Sport Science in Australia')"
                                        ></span>
                                    </h2>
                                </div>
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
                                <div class="rounded-lg border border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 shadow-md">
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
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
                                        class="text-sm font-semibold tracking-wide text-gray-700 uppercase"
                                        :data-segment-text="'QUESTIONS'"
                                        v-html="getHighlightedSegment('QUESTIONS')"
                                    ></p>
                                    <p
                                        class="text-xs text-gray-500"
                                        :data-segment-text="'Answer all questions based on Reading Passage 1'"
                                        v-html="getHighlightedSegment('Answer all questions based on Reading Passage 1')"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-5 -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-1-5" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 1-5'"
                                            v-html="getHighlightedSegment('Questions 1-5')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-100 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm font-medium text-gray-800 sm:text-base"
                                                :data-segment-text="'Complete the flow chart below.'"
                                                v-html="getHighlightedSegment('Complete the flow chart below.')"
                                            ></p>
                                            <p class="text-sm font-medium text-gray-800 sm:text-base">
                                                <span
                                                    :data-segment-text="'Choose NO MORE THAN TWO WORDS from the passage for each answer.'"
                                                    v-html="getHighlightedSegment('Choose NO MORE THAN TWO WORDS from the passage for each answer.')"
                                                ></span>
                                            </p>
                                        </div>

                                        <div class="mt-6 space-y-4 rounded-lg border border-blue-200 bg-white p-6 shadow-xl">
                                            <h4
                                                class="text-center text-lg font-bold text-blue-800"
                                                :data-segment-text="'The history of sports and physical science in Australia'"
                                                v-html="getHighlightedSegment('The history of sports and physical science in Australia')"
                                            ></h4>
                                            <ul class="space-y-4">
                                                <li class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                                    >
                                                        1
                                                    </div>
                                                    <span
                                                        class="flex-shrink-0 text-gray-700"
                                                        :data-segment-text="'A lot of people identified as being'"
                                                        v-html="getHighlightedSegment('A lot of people identified as being')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q1"
                                                        type="text"
                                                        placeholder=""
                                                        class="focus:ring-opacity-50 w-full flex-grow rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                                    />
                                                </li>
                                                <li class="flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                                        ></path>
                                                    </svg>
                                                </li>
                                                <li class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                                    >
                                                        2
                                                    </div>
                                                    <span
                                                        class="flex-shrink-0 text-gray-700"
                                                        :data-segment-text="'Introduction of PE to'"
                                                        v-html="getHighlightedSegment('Introduction of PE to')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q2"
                                                        type="text"
                                                        placeholder=""
                                                        class="focus:ring-opacity-50 w-full flex-grow rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                                    />
                                                </li>
                                                <li class="flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                                        ></path>
                                                    </svg>
                                                </li>
                                                <li class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                                    >
                                                        3
                                                    </div>
                                                    <span
                                                        class="flex-shrink-0 text-gray-700"
                                                        :data-segment-text="'Special training programmes for'"
                                                        v-html="getHighlightedSegment('Special training programmes for')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q3"
                                                        type="text"
                                                        placeholder=""
                                                        class="focus:ring-opacity-50 w-full flex-grow rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                                    />
                                                </li>
                                                <li class="flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                                        ></path>
                                                    </svg>
                                                </li>
                                                <li class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                                    >
                                                        4
                                                    </div>
                                                    <input
                                                        v-model="answers.q4"
                                                        type="text"
                                                        placeholder=""
                                                        class="focus:ring-opacity-50 w-full flex-grow rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                                    />
                                                    <span
                                                        class="flex-shrink-0 text-gray-700"
                                                        :data-segment-text="'of PE graduates'"
                                                        v-html="getHighlightedSegment('of PE graduates')"
                                                    ></span>
                                                </li>
                                                <li class="flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                                        ></path>
                                                    </svg>
                                                </li>
                                                <li class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                                    >
                                                        5
                                                    </div>
                                                    <span
                                                        class="flex-shrink-0 text-gray-700"
                                                        :data-segment-text="'Identification of alternative'"
                                                        v-html="getHighlightedSegment('Identification of alternative')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q5"
                                                        type="text"
                                                        placeholder=""
                                                        class="focus:ring-opacity-50 w-full flex-grow rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                                    />
                                                </li>
                                                <li class="flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                                        ></path>
                                                    </svg>
                                                </li>
                                                <li class="flex items-center gap-3">
                                                    <span
                                                        class="flex-shrink-0 text-gray-700"
                                                        :data-segment-text="'Diversification of course delivery'"
                                                        v-html="getHighlightedSegment('Diversification of course delivery')"
                                                    ></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 6-13 -->
                                <section
                                    id="question-6-13"
                                    class="space-y-6 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm">
                                        <h3
                                            class="my-2 bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 6-13'"
                                            v-html="getHighlightedSegment('Questions 6-13')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-white p-6 shadow-lg">
                                            <p
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Do the following statements agree with the information given in Reading Passage 1?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the information given in Reading Passage 1?',
                                                    )
                                                "
                                            ></p>
                                            <div class="grid grid-cols-1 gap-2 pt-4 text-sm sm:text-lg">
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-24 rounded bg-blue-100 px-2 py-1 font-bold text-blue-700"
                                                        :data-segment-text="'TRUE'"
                                                        v-html="getHighlightedSegment('TRUE')"
                                                    ></span>
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'if the statement agrees with the information'"
                                                        v-html="getHighlightedSegment('if the statement agrees with the information')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-24 rounded bg-red-100 px-2 py-1 font-bold text-red-700"
                                                        :data-segment-text="'FALSE'"
                                                        v-html="getHighlightedSegment('FALSE')"
                                                    ></span>
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'if the statement contradicts the information'"
                                                        v-html="getHighlightedSegment('if the statement contradicts the information')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-28 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                        :data-segment-text="'NOT GIVEN'"
                                                        v-html="getHighlightedSegment('NOT GIVEN')"
                                                    ></span>
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'if there is no information on this'"
                                                        v-html="getHighlightedSegment('if there is no information on this')"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <!-- Q6 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-xl">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                6
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'Sport is generally regarded as a profitable area for investment.'"
                                                    v-html="getHighlightedSegment('Sport is generally regarded as a profitable area for investment.')"
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q6"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q6"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q6"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Q7 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                7
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'Rupert Murdoch has a personal as well as a business interest in sport.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Rupert Murdoch has a personal as well as a business interest in sport.',
                                                        )
                                                    "
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q7"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q7"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q7"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Q8 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                8
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'The range of career opportunities available to sport graduates is increasing.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'The range of career opportunities available to sport graduates is increasing.',
                                                        )
                                                    "
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q8"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q8"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q8"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Q9 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                9
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'The interests of business and the interests of universities are linked.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'The interests of business and the interests of universities are linked.',
                                                        )
                                                    "
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q9"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q9"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q9"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Q10 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                10
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'Governments have been focusing too much attention on preventative medicine.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Governments have been focusing too much attention on preventative medicine.',
                                                        )
                                                    "
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q10"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q10"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q10"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Q11 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                11
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'It is inevitable that government priorities for health spending will change.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'It is inevitable that government priorities for health spending will change.',
                                                        )
                                                    "
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q11"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q11"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q11"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Q12 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                12
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'Existing degree courses are unsuitable for careers in community health.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Existing degree courses are unsuitable for careers in community health.',
                                                        )
                                                    "
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q12"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q12"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q12"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Q13 -->
                                        <div class="flex items-start gap-4 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow"
                                            >
                                                13
                                            </div>
                                            <div class="flex-1">
                                                <p
                                                    class="text-gray-800"
                                                    :data-segment-text="'Funding for sport science and related degrees has been increased considerably.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Funding for sport science and related degrees has been increased considerably.',
                                                        )
                                                    "
                                                ></p>
                                                <div class="mt-3 flex space-x-4">
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q13"
                                                            value="TRUE"
                                                            class="form-radio text-blue-600"
                                                        /><span>TRUE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q13"
                                                            value="FALSE"
                                                            class="form-radio text-blue-600"
                                                        /><span>FALSE</span></label
                                                    >
                                                    <label class="flex cursor-pointer items-center space-x-2"
                                                        ><input
                                                            type="radio"
                                                            v-model="answers.q13"
                                                            value="NOT GIVEN"
                                                            class="form-radio text-blue-600"
                                                        /><span>NOT GIVEN</span></label
                                                    >
                                                </div>
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

.highlight-indigo {
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
    background: linear-gradient(to bottom, #3b82f6, #6366f1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #4f46e5);
}
</style>
