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
const PANEL_WIDTH_KEY = 'reading-ielts009-part2-panel-width';
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

const passageText = `The debate surrounding literacy is one of the most charged in education. On the one hand, there is an army of people convinced that traditional skills of reading and writing are declining. On the other, a host of progressives protest that literacy is much more complicated than a simple technical mastery of reading and writing. This second position is supported by most of the relevant academic work over the past 20 years. These studies argue that literacy can only be understood in its social and technical context. In Renaissance England, for example, many more people could read than could write, and within reading there was a distinction between those who could read print and those who could manage the more difficult task of reading manuscript. An understanding of these earlier periods helps us understand today’s ‘crisis in literacy’ debate.

There does seem to be evidence that there has been an overall decline in some aspects of reading and writing - you only need to compare the tabloid newspapers of today with those of 50 years ago to see a clear decrease in vocabulary and simplification of syntax. But the picture is not uniform and doesn’t readily demonstrate the simple distinction between literate and illiterate which had been considered adequate since the middle of the 19th century.

While reading a certain amount of writing is as crucial as it has ever been in industrial societies, it is doubtful whether a fully extended grasp of either is as necessary as it was 30 or 40 years ago. While print retains much of its authority as a source of topical information, television has increasingly usurped this role. The ability to write fluent letters has been undermined by the telephone and research suggests that for many people the only use for writing, outside formal education, is the compilation of shopping lists.

The decision of some car manufacturers to issue their instructions to mechanics as a video pack rather than as a handbook might be taken to spell the end of any automatic link between industrialisation and literacy. On the other hand, it is also the case that ever-increasing numbers of people make their living out of writing, which is better rewarded than ever before. Schools are generally seen as institutions where the book rules - film, television and recorded sound have almost no place; but it is not clear that this opposition is appropriate. While you may not need to read and write to watch television, you certainly need to be able to read and write in order to make programmes.

Those who work in the new media are anything but illiterate. The traditional oppositions between old and new media are inadequate for understanding the world which a young child now encounters. The computer has re-established a central place for the written word on the screen, which used to be entirely devoted to the image. There is even anecdotal evidence that children are mastering reading and writing in order to get on to the Internet. There is no reason why the new and old media cannot be integrated in schools to provide the skills to become economically productive and politically enfranchised.

Nevertheless, there is a crisis in literacy and it would be foolish to ignore it. To understand that literacy may be declining because it is less central to some aspects of everyday life is not the same as acquiescing in this state of affairs. The production of school work with the new technologies could be a significant stimulus to literacy. How should these new technologies be introduced into the schools? It isn’t enough to call for computers, camcorders and edit suites in every classroom; unless they are properly integrated into the educational culture, they will stand unused. Evidence suggests that this is the fate of most information technology used in the classroom. Similarly, although media studies are now part of the national curriculum, and more and more students are now clamouring to take these course, teachers remain uncertain about both methods and aims in this area.

This is not the fault of the teachers. The entertainment and information industries must be drawn into a debate with the educational institutions to determine how best to blend these new technologies into the classroom.

Many people in our era are drawn to the pessimistic view that the new media are destroying old skills and eroding critical judgement. It may be true that past generations were more literate but - taking the pre-19th century meaning of the term - this was true of only a small section of the population. The word literacy is a 19th-century coinage to describe the divorce of reading and writing from a full knowledge of literature. The education reforms of the 19th century produced reading and writing as skills separable from full participation in the cultural heritage.

The new media now point not only to a futuristic cyber-economy, they also make our cultural past available to the whole nation. Most children’s access to these treasures is initially through television. It is doubtful whether our literary heritage has ever been available to or sought out by more than about 5 per cent of the population; it has certainly not been available to more than 10 per cent. But the new media joined to the old, through the public service tradition of British broadcasting, now makes our literary tradition available to all.`;

const textSegments = (() => {
    const segments: Array<{ text: string; offset: number }> = [];
    let offset = 0;

    function addSegment(text: string) {
        segments.push({ text, offset });
        offset += text.length;
    }

    addSegment(passageText);
    addSegment('READING PASSAGE 2');
    addSegment('You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.');
    addSegment('READING THE SCREEN');
    addSegment('QUESTIONS');
    addSegment('Answer all questions based on Reading Passage 2');
    addSegment('Questions 14-17');
    addSegment('When discussing the debate on literacy in education, the writer notes that');
    addSegment('A. children cannot read and write as well as they used to.');
    addSegment('B. academic work has improved over the last 20 years.');
    addSegment('C. there is evidence that literacy is related to external factors.');
    addSegment('D. there are opposing arguments that are equally convincing.');
    addSegment('In the 4th paragraph, the writer’s main point is that');
    addSegment('A. the printed word is both gaining and losing power.');
    addSegment('B. all inventions bring disadvantages as well as benefits.');
    addSegment('C. those who work in manual jobs no longer need to read.');
    addSegment('D. the media offers the best careers for those who like writing.');
    addSegment('According to the writer, the main problem that schools face today is');
    addSegment('A. how best to teach the skills of reading and writing.');
    addSegment('B. how best to incorporate technology into classroom teaching.');
    addSegment('C. finding the means to purchase technological equipment.');
    addSegment('D. managing the widely differing levels of literacy amongst pupils.');
    addSegment('At the end of the article, the writer is suggesting that');
    addSegment('A. literature and culture cannot be divorced.');
    addSegment('B. the term ‘literacy’ has not been very useful.');
    addSegment('C. 10 per cent of the population never read literature.');
    addSegment('D. our exposure to cultural information is likely to increase.');
    addSegment('Questions Questions 18-23');
    addSegment('Do the following statements agree with the views of the writer in Reading Passage 2?');
    addSegment('Match each opinion to the person credited with it.');
    addSegment('In boxes 18-23 on your answer sheet write');
    addSegment('Choose from the following options:');
    addSegment('Choose the appropriate letters A-D for questions 14 to 17.');
    addSegment('Write A-D in boxes 14-17 on your answer sheet.');
    addSegment('YES');
    addSegment('if the statement agrees with the writer');
    addSegment('NO');
    addSegment('if the statement contradicts the writer');
    addSegment('NOT GIVEN');
    addSegment('if it is impossible to say what the writer thinks about this');
    addSegment('It is not as easy to analyse literacy levels as it used to be.');
    addSegment('Our literacy skills need to be as highly developed as they were in the past.');
    addSegment('Illiteracy is on the increase.');
    addSegment('Professional writers earn relatively more than they used to.');
    addSegment('A good literacy level is important for those who work in television.');
    addSegment('Computers are having a negative impact on literacy in schools.');
    addSegment('Questions 24-26');
    addSegment('Complete the sentences below with words taken from Reading Passage 2.');
    addSegment('Write your answers in boxes 24-26 on your answer sheet.');
    addSegment('Use NO MORE THAN THREE WORDS for each answer.');
    addSegment('In Renaissance England, the best readers were those able to read');
    addSegment('The writer uses the example of');
    addSegment('to illustrate the general fall in certain areas of literacy.');
    addSegment('It has been shown that after leaving school, the only things that a lot of people write are');
    addSegment('.');

    return ref(segments);
})();

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
                            <div class="mt-4 flex items-center justify-center gap-2">
                                <h2
                                    class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-xl font-bold text-gray-900 text-transparent"
                                    :data-segment-text="'READING THE SCREEN'"
                                    v-html="getHighlightedSegment('READING THE SCREEN')"
                                ></h2>
                            </div>
                            <p
                                class="mt-4 text-center text-sm tracking-wide text-gray-700 italic"
                                :data-segment-text="'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.'"
                                v-html="
                                    getHighlightedSegment(
                                        'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.',
                                    )
                                "
                            ></p>
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-purple-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute h-full w-px bg-gray-300 transition-colors group-hover:bg-purple-400"></div>
                    <div class="absolute flex flex-col gap-1 rounded bg-white px-1 py-2 shadow-sm transition-all group-hover:bg-purple-100">
                        <div class="h-1 w-1 rounded-full bg-gray-400 group-hover:bg-purple-500"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 group-hover:bg-purple-500"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 group-hover:bg-purple-500"></div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
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
                                <p
                                    class="text-sm font-semibold tracking-wide text-gray-700 uppercase"
                                    :data-segment-text="'QUESTIONS'"
                                    v-html="getHighlightedSegment('QUESTIONS')"
                                ></p>
                                <p
                                    class="text-xs text-gray-500"
                                    :data-segment-text="'Answer all questions based on Reading Passage 2'"
                                    v-html="getHighlightedSegment('Answer all questions based on Reading Passage 2')"
                                ></p>
                            </div>
                        </div>
                    </div>

                    <!-- Scrollable Questions Content -->
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4">
                            <!-- Questions 15-19 -->
                            <div
                                class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3
                                            class="bg-gradient-to-r from-purple-500 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 14-17'"
                                            v-html="getHighlightedSegment('Questions 14-17')"
                                        ></h3>
                                    </div>
                                    <!--
                            </div>
                            <div class="space-y-6">

                                <!-- Q14 -->
                                    <div
                                        id="question-14"
                                        class="mb-4 rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">14</span>
                                            </div>

                                            <div class="col-span-11 text-base sm:text-lg">
                                                <p
                                                    class="mb-3 font-bold text-gray-700"
                                                    :data-segment-text="'When discussing the debate on literacy in education, the writer notes that'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'When discussing the debate on literacy in education, the writer notes that',
                                                        )
                                                    "
                                                ></p>

                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q14"
                                                            type="radio"
                                                            value="A"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'A. children cannot read and write as well as they used to.'"
                                                            v-html="
                                                                getHighlightedSegment('A. children cannot read and write as well as they used to.')
                                                            "
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q14"
                                                            type="radio"
                                                            value="B"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'B. academic work has improved over the last 20 years.'"
                                                            v-html="getHighlightedSegment('B. academic work has improved over the last 20 years.')"
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q14"
                                                            type="radio"
                                                            value="C"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'C. there is evidence that literacy is related to external factors.'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'C. there is evidence that literacy is related to external factors.',
                                                                )
                                                            "
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q14"
                                                            type="radio"
                                                            value="D"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'D. there are opposing arguments that are equally convincing.'"
                                                            v-html="
                                                                getHighlightedSegment('D. there are opposing arguments that are equally convincing.')
                                                            "
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Q15 -->
                                    <div
                                        id="question-15"
                                        class="mb-4 rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">15</span>
                                            </div>

                                            <div class="col-span-11 text-base sm:text-lg">
                                                <p
                                                    class="mb-3 font-bold text-gray-700"
                                                    :data-segment-text="'In the 4th paragraph, the writer’s main point is that'"
                                                    v-html="getHighlightedSegment('In the 4th paragraph, the writer’s main point is that')"
                                                ></p>

                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q15"
                                                            type="radio"
                                                            value="A"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'A. the printed word is both gaining and losing power.'"
                                                            v-html="getHighlightedSegment('A. the printed word is both gaining and losing power.')"
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q15"
                                                            type="radio"
                                                            value="B"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'B. all inventions bring disadvantages as well as benefits.'"
                                                            v-html="
                                                                getHighlightedSegment('B. all inventions bring disadvantages as well as benefits.')
                                                            "
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q15"
                                                            type="radio"
                                                            value="C"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'C. those who work in manual jobs no longer need to read.'"
                                                            v-html="getHighlightedSegment('C. those who work in manual jobs no longer need to read.')"
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q15"
                                                            type="radio"
                                                            value="D"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'D. the media offers the best careers for those who like writing.'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'D. the media offers the best careers for those who like writing.',
                                                                )
                                                            "
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Q16 -->
                                    <div
                                        id="question-16"
                                        class="mb-4 rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">16</span>
                                            </div>

                                            <div class="col-span-11 text-base sm:text-lg">
                                                <p
                                                    class="mb-3 font-bold text-gray-700"
                                                    :data-segment-text="'According to the writer, the main problem that schools face today is'"
                                                    v-html="
                                                        getHighlightedSegment('According to the writer, the main problem that schools face today is')
                                                    "
                                                ></p>

                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q16"
                                                            type="radio"
                                                            value="A"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'A. how best to teach the skills of reading and writing.'"
                                                            v-html="getHighlightedSegment('A. how best to teach the skills of reading and writing.')"
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q16"
                                                            type="radio"
                                                            value="B"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'B. how best to incorporate technology into classroom teaching.'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'B. how best to incorporate technology into classroom teaching.',
                                                                )
                                                            "
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q16"
                                                            type="radio"
                                                            value="C"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'C. finding the means to purchase technological equipment.'"
                                                            v-html="
                                                                getHighlightedSegment('C. finding the means to purchase technological equipment.')
                                                            "
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q16"
                                                            type="radio"
                                                            value="D"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'D. managing the widely differing levels of literacy amongst pupils.'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'D. managing the widely differing levels of literacy amongst pupils.',
                                                                )
                                                            "
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Q17 -->
                                    <div
                                        id="question-17"
                                        class="rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">17</span>
                                            </div>

                                            <div class="col-span-11 text-base sm:text-lg">
                                                <p
                                                    class="mb-3 font-bold text-gray-700"
                                                    :data-segment-text="'At the end of the article, the writer is suggesting that'"
                                                    v-html="getHighlightedSegment('At the end of the article, the writer is suggesting that')"
                                                ></p>

                                                <div class="space-y-3">
                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q17"
                                                            type="radio"
                                                            value="A"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'A. literature and culture cannot be divorced.'"
                                                            v-html="getHighlightedSegment('A. literature and culture cannot be divorced.')"
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q17"
                                                            type="radio"
                                                            value="B"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'B. the term ‘literacy’ has not been very useful.'"
                                                            v-html="getHighlightedSegment('B. the term ‘literacy’ has not been very useful.')"
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q17"
                                                            type="radio"
                                                            value="C"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'C. 10 per cent of the population never read literature.'"
                                                            v-html="getHighlightedSegment('C. 10 per cent of the population never read literature.')"
                                                        ></span>
                                                    </label>

                                                    <label class="flex items-center space-x-3">
                                                        <input
                                                            v-model="answers.q17"
                                                            type="radio"
                                                            value="D"
                                                            class="h-4 w-4 border-green-300 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span
                                                            :data-segment-text="'D. our exposure to cultural information is likely to increase.'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'D. our exposure to cultural information is likely to increase.',
                                                                )
                                                            "
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions Questions 18-23-->
                            <div class="rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 p-8 shadow-lg">
                                <div class="mb-8">
                                    <div class="mb-6 flex items-center space-x-4">
                                        <div>
                                            <h3
                                                class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-xl font-bold text-transparent"
                                                :data-segment-text="'Questions Questions 18-23'"
                                                v-html="getHighlightedSegment('Questions Questions 18-23')"
                                            ></h3>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-purple-200 bg-white p-6 shadow-sm">
                                        <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                            <span
                                                :data-segment-text="'Do the following statements agree with the views of the writer in Reading Passage 2?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the views of the writer in Reading Passage 2?',
                                                    )
                                                "
                                            ></span
                                            ><br />
                                            <span
                                                :data-segment-text="'Match each opinion to the person credited with it.'"
                                                v-html="getHighlightedSegment('Match each opinion to the person credited with it.')"
                                            ></span
                                            ><br />
                                            <span
                                                :data-segment-text="'In boxes 18-23 on your answer sheet write'"
                                                v-html="getHighlightedSegment('In boxes 18-23 on your answer sheet write')"
                                            ></span>
                                        </p>
                                        <div class="mt-4 rounded-lg border border-purple-200 bg-purple-50 p-4">
                                            <h4
                                                class="mb-3 text-base font-bold text-purple-800 sm:text-lg"
                                                :data-segment-text="'Choose from the following options:'"
                                                v-html="getHighlightedSegment('Choose from the following options:')"
                                            ></h4>
                                            <div class="rounded-xl border border-purple-200 bg-white p-6 shadow-sm">
                                                <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                    <span
                                                        :data-segment-text="'Choose the appropriate letters A-D for questions 14 to 17.'"
                                                        v-html="getHighlightedSegment('Choose the appropriate letters A-D for questions 14 to 17.')"
                                                    ></span
                                                    ><br />
                                                    <span
                                                        :data-segment-text="'Write A-D in boxes 14-17 on your answer sheet.'"
                                                        v-html="getHighlightedSegment('Write A-D in boxes 14-17 on your answer sheet.')"
                                                    ></span>
                                                </p>
                                                <div class="grid grid-cols-1 gap-2 pt-4 text-base sm:text-lg">
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-purple-100 px-2 py-1 font-bold text-purple-700"
                                                            :data-segment-text="'YES'"
                                                            v-html="getHighlightedSegment('YES')"
                                                        ></span>
                                                        <span
                                                            class="text-gray-700"
                                                            :data-segment-text="'if the statement agrees with the writer'"
                                                            v-html="getHighlightedSegment('if the statement agrees with the writer')"
                                                        ></span>
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-red-100 px-2 py-1 font-bold text-red-700"
                                                            :data-segment-text="'NO'"
                                                            v-html="getHighlightedSegment('NO')"
                                                        ></span>
                                                        <span
                                                            class="text-gray-700"
                                                            :data-segment-text="'if the statement contradicts the writer'"
                                                            v-html="getHighlightedSegment('if the statement contradicts the writer')"
                                                        ></span>
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                            :data-segment-text="'NOT GIVEN'"
                                                            v-html="getHighlightedSegment('NOT GIVEN')"
                                                        ></span>
                                                        <span
                                                            class="text-gray-700"
                                                            :data-segment-text="'if it is impossible to say what the writer thinks about this'"
                                                            v-html="
                                                                getHighlightedSegment('if it is impossible to say what the writer thinks about this')
                                                            "
                                                        ></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 18 -->
                                    <div
                                        id="question-18"
                                        class="rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">18</span>
                                            </div>

                                            <div class="grid grid-cols-12 gap-4">
                                                <div class="col-span-3">
                                                    <select
                                                        v-model="answers.q18"
                                                        class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-base transition-colors focus:border-purple-500 focus:bg-white focus:outline-none sm:text-lg"
                                                    >
                                                        <option value="">Select...</option>
                                                        <option value="YES">YES</option>
                                                        <option value="NO">NO</option>
                                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-9">
                                                    <p
                                                        class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                        :data-segment-text="'It is not as easy to analyse literacy levels as it used to be.'"
                                                        v-html="
                                                            getHighlightedSegment('It is not as easy to analyse literacy levels as it used to be.')
                                                        "
                                                    ></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 19 -->
                                    <div
                                        id="question-19"
                                        class="rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">19</span>
                                            </div>

                                            <div class="grid grid-cols-12 gap-4">
                                                <div class="col-span-3">
                                                    <select
                                                        v-model="answers.q19"
                                                        class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-base transition-colors focus:border-purple-500 focus:bg-white focus:outline-none sm:text-lg"
                                                    >
                                                        <option value="">Select...</option>
                                                        <option value="YES">YES</option>
                                                        <option value="NO">NO</option>
                                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-9">
                                                    <p
                                                        class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                        :data-segment-text="'Our literacy skills need to be as highly developed as they were in the past.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Our literacy skills need to be as highly developed as they were in the past.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 20 -->
                                    <div
                                        id="question-20"
                                        class="rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">20</span>
                                            </div>

                                            <div class="grid grid-cols-12 gap-4">
                                                <div class="col-span-3">
                                                    <select
                                                        v-model="answers.q20"
                                                        class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-base transition-colors focus:border-purple-500 focus:bg-white focus:outline-none sm:text-lg"
                                                    >
                                                        <option value="">Select...</option>
                                                        <option value="YES">YES</option>
                                                        <option value="NO">NO</option>
                                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-9">
                                                    <p
                                                        class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                        :data-segment-text="'Illiteracy is on the increase.'"
                                                        v-html="getHighlightedSegment('Illiteracy is on the increase.')"
                                                    ></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 21 -->
                                    <div
                                        id="question-21"
                                        class="rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">21</span>
                                            </div>

                                            <div class="grid grid-cols-12 gap-4">
                                                <div class="col-span-3">
                                                    <select
                                                        v-model="answers.q21"
                                                        class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-base transition-colors focus:border-purple-500 focus:bg-white focus:outline-none sm:text-lg"
                                                    >
                                                        <option value="">Select...</option>
                                                        <option value="YES">YES</option>
                                                        <option value="NO">NO</option>
                                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-9">
                                                    <p
                                                        class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                        :data-segment-text="'Professional writers earn relatively more than they used to.'"
                                                        v-html="getHighlightedSegment('Professional writers earn relatively more than they used to.')"
                                                    ></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 22 -->
                                    <div
                                        id="question-22"
                                        class="rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">22</span>
                                            </div>

                                            <div class="grid grid-cols-12 gap-4">
                                                <div class="col-span-3">
                                                    <select
                                                        v-model="answers.q22"
                                                        class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-base transition-colors focus:border-purple-500 focus:bg-white focus:outline-none sm:text-lg"
                                                    >
                                                        <option value="">Select...</option>
                                                        <option value="YES">YES</option>
                                                        <option value="NO">NO</option>
                                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-9">
                                                    <p
                                                        class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                        :data-segment-text="'A good literacy level is important for those who work in television.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'A good literacy level is important for those who work in television.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 23 -->
                                    <div
                                        id="question-23"
                                        class="rounded-xl border border-purple-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                            >
                                                <span class="text-sm font-bold text-white">23</span>
                                            </div>

                                            <div class="grid grid-cols-12 gap-4">
                                                <div class="col-span-3">
                                                    <select
                                                        v-model="answers.q23"
                                                        class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-base transition-colors focus:border-purple-500 focus:bg-white focus:outline-none sm:text-lg"
                                                    >
                                                        <option value="">Select...</option>
                                                        <option value="YES">YES</option>
                                                        <option value="NO">NO</option>
                                                        <option value="NOT GIVEN">NOT GIVEN</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-9">
                                                    <p
                                                        class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                        :data-segment-text="'Computers are having a negative impact on literacy in schools.'"
                                                        v-html="
                                                            getHighlightedSegment('Computers are having a negative impact on literacy in schools.')
                                                        "
                                                    ></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 24-27 -->
                            <div
                                class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3
                                            class="bg-gradient-to-r from-purple-500 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 24-26'"
                                            v-html="getHighlightedSegment('Questions 24-26')"
                                        ></h3>
                                    </div>
                                    <div class="rounded-xl border border-purple-200 bg-white p-6 shadow-sm">
                                        <p
                                            class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                            :data-segment-text="'Complete the sentences below with words taken from Reading Passage 2.'"
                                            v-html="getHighlightedSegment('Complete the sentences below with words taken from Reading Passage 2.')"
                                        ></p>
                                        <p
                                            class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                            :data-segment-text="'Write your answers in boxes 24-26 on your answer sheet.'"
                                            v-html="getHighlightedSegment('Write your answers in boxes 24-26 on your answer sheet.')"
                                        ></p>
                                        <p class="pt-4 text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                            <span
                                                :data-segment-text="'Use NO MORE THAN THREE WORDS for each answer.'"
                                                v-html="getHighlightedSegment('Use NO MORE THAN THREE WORDS for each answer.')"
                                            ></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="space-y-4 text-base leading-relaxed text-gray-800 sm:text-lg">
                                    <p id="reading-paragraph" class="space-y-4">
                                        <span
                                            :data-segment-text="'In Renaissance England, the best readers were those able to read'"
                                            v-html="getHighlightedSegment('In Renaissance England, the best readers were those able to read')"
                                        ></span>
                                        <span class="mx-1 inline-flex items-center space-x-2">
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 text-base font-bold text-white"
                                                >24</span
                                            >
                                            <input
                                                v-model="answers.q24"
                                                type="text"
                                                class="w-40 rounded-full border border-pink-300 bg-pink-50 px-3 py-1 text-center font-medium focus:border-pink-500 focus:bg-white focus:outline-none"
                                                placeholder="____"
                                            />
                                        </span>
                                        <span
                                            :data-segment-text="'The writer uses the example of'"
                                            v-html="getHighlightedSegment('The writer uses the example of')"
                                        ></span>
                                        <span class="mx-1 inline-flex items-center space-x-2">
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 text-base font-bold text-white"
                                                >25</span
                                            >
                                            <input
                                                v-model="answers.q25"
                                                type="text"
                                                class="w-40 rounded-full border border-pink-300 bg-pink-50 px-3 py-1 text-center font-medium focus:border-pink-500 focus:bg-white focus:outline-none"
                                                placeholder="____"
                                            />
                                        </span>
                                        <span
                                            :data-segment-text="'to illustrate the general fall in certain areas of literacy.'"
                                            v-html="getHighlightedSegment('to illustrate the general fall in certain areas of literacy.')"
                                        ></span>
                                        <span
                                            :data-segment-text="'It has been shown that after leaving school, the only things that a lot of people write are'"
                                            v-html="
                                                getHighlightedSegment(
                                                    'It has been shown that after leaving school, the only things that a lot of people write are',
                                                )
                                            "
                                        ></span>
                                        <span class="mx-1 inline-flex items-center space-x-2">
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 text-base font-bold text-white"
                                                >26</span
                                            >
                                            <input
                                                v-model="answers.q26"
                                                type="text"
                                                class="bg-pink-50px-3 w-40 rounded-full border border-pink-300 py-1 text-center font-medium focus:border-pink-500 focus:bg-white focus:outline-none"
                                                placeholder="____"
                                            />
                                        </span>
                                        <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                    </p>
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
    background: linear-gradient(to bottom, #3b82f6, #6366f1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #4f46e5);
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
