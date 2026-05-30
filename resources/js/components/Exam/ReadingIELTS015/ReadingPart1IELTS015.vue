<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const PANEL_WIDTH_KEY = 'reading-ielts015-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight } = useHighlight();

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
});

const multipleAnswers = ref({
    q1_3: [] as string[],
});

const passageText = `For the century before Johnson’s Dictionary was published in 1775, there had been concern about the state of the English language. There was no standard way of speaking or writing and no agreement as to the best way of bringing some order to the chaos of English spelling. Dr Johnson provided the solution.

There had, of course, been dictionaries in the past, the first of these being a little book of some 120 pages, compiled by a certain Robert Cawdray, published in 1604 under the title A Table Alphabetical of hard usual English words. Like the various dictionaries that came after it during the seventeenth century, Cawdray’s tended to concentrate on ‘scholarly’ words; one function of the dictionary was to enable its student to convey an impression of fine learning.

Beyond the practical need to make order out of chaos, the rise of dictionaries is associated with the rise of the English middle class, who were anxious to define and circumscribe the various worlds to conquer -lexical as well as social and commercial. It is highly appropriate that Dr Samuel Johnson, the very model of an eighteenth-century literary man, as famous in his own time as in ours, should have published his Dictionary at the very beginning of the heyday of the middle class.

Johnson was a poet and critic who raised common sense to the heights of genius. His approach to the problems that had worried writers throughout the late seventeenth and early eighteenth centuries was intensely practical. Up until his time, the task of producing a dictionary on such a large scale had seemed impossible without the establishment of an academy to make decisions about right and wrong usage. Johnson decided he did not need an academy to settle arguments about language; he would write a dictionary himself; and he would do it single-handed. Johnson signed the contract for the Dictionary with the bookseller Robert Dosley at a breakfast held at the Golden Anchor Inn near Holborn Bar on 18 June 1764. He was to be paid £1,575 in instalments, and from this he took money to rent 17 Gough Square, in which he set up his ‘dictionary workshop’.

James Boswell, his biographer described the garret where Johnson worked as ‘fitted up like a counting house’ with a long desk running down the middle at which the copying clerks would work standing up. Johnson himself was stationed on a rickety chair at an ‘old crazy deal table’ surrounded by a chaos of borrowed books. He was also helped by six assistants, two of whom died whilst the Dictionary was still in preparation.

The work was immense; filing about eighty large notebooks (and without a library to hand), Johnson wrote the definitions of over 40,000 words, and illustrated their many meanings with some 114,000 quotations drawn from English writing on every subject, from the Elizabethans to his own time. He did not expel to achieve complete originality. Working to a deadline, he had to draw on the best of all previous dictionaries, and to make his work one of heroic synthesis. In fact, it was very much more. Unlike his predecessors, Johnson treated English very practically, as a living language, with many different shades of meaning. He adopted his definitions on the principle of English common law – according to precedent. After its publication, his Dictionary was not seriously rivalled for over a century.

After many vicissitudes the Dictionary was finally published on 15 April 1775. It was instantly recognised as a landmark throughout Europe. ‘This very noble work;’ wrote the leading Italian lexicographer, will be a perpetual monument of Fame to the Author, an Honour to his own Country in particular, and a general Benefit to the republic of Letters throughout Europe. The fact that Johnson had taken on the Academies of Europe and matched them (everyone knew that forty French academics had taken forty years to produce the first French national dictionary) was cause for much English celebration.

Johnson had worked for nine years, ‘with little assistance of the learned, and without any patronage of the great; not in the soft obscurities of retirement, or under the shelter of academic bowers, but amidst inconvenience and distraction, in sickness and in sorrow’. For all its faults and eccentricities his two-volume work is a masterpiece and a landmark, in his own words, ‘setting the orthography, displaying the analogy, regulating the structures, and ascertaining the significations of English words’. It is the cornerstone of Standard English, an achievement which, in James Boswell’s words, ‘conferred stability on the language of his country’.

The Dictionary, together with his other writing, made Johnson famous and so well esteemed that his friends were able to prevail upon King George III to offer him a pension. From then on, he was to become the Johnson of folklore.`;
const passageOffset = passageText.length;
const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'Questions 1-3', offset: passageOffset + 1 },
    { text: 'Choose THREE letters A-H.', offset: passageOffset + 17 },
    { text: 'Write your answers in boxes 1-3 on your answer sheet.', offset: passageOffset + 45 },
    { text: 'NB Your answers may be given in any order.', offset: passageOffset + 103 },
    { text: "Which THREE of the following statements are true of Johnson's Dictionary?", offset: passageOffset + 146 },
    { text: 'A It avoided all scholarly words.', offset: passageOffset + 221 },
    { text: 'B It was the only English dictionary in general use for 200 years.', offset: passageOffset + 254 },
    { text: 'C It was famous because of the large number of people involved.', offset: passageOffset + 322 },
    { text: 'D It focused mainly on language from contemporary texts.', offset: passageOffset + 386 },
    { text: 'E There was a time limit for its completion.', offset: passageOffset + 445 },
    { text: 'F It ignored work done by previous dictionary writers.', offset: passageOffset + 488 },
    { text: 'G It took into account subtleties of meaning.', offset: passageOffset + 543 },
    { text: 'H Its definitions were famous for their originality.', offset: passageOffset + 588 },
    { text: 'Questions 4-7', offset: passageOffset + 645 },
    { text: 'Complete the summary.', offset: passageOffset + 661 },
    { text: 'Choose NO MORE THAN TWO WORDS from the passage for each answer.', offset: passageOffset + 684 },
    {
        text: 'In 1764 Dr Johnson accepted the contract to produce a dictionary. Having rented a garret, he took on a number of',
        offset: passageOffset + 758,
    },
    { text: ', who stood at a long central desk. Johnson did not have a', offset: passageOffset + 888 },
    {
        text: " available to him, but eventually produced definitions of in excess of 40,000 words written down in 80 large notebooks. On publication, the Dictionary was immediately hailed in many European countries as a landmark. According to his biographer, James Boswell, Johnson's principal achievement was to bring",
        offset: passageOffset + 960,
    },
    { text: ' to the English language. As a reward for his hard work, he was granted a', offset: passageOffset + 1205 },
    { text: ' by the king.', offset: passageOffset + 1279 },
    { text: 'Questions 8-13', offset: passageOffset + 1292 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: passageOffset + 1307 },
    { text: 'In boxes 8-13 on your answer sheet, write', offset: passageOffset + 1391 },
    { text: 'TRUE', offset: passageOffset + 148 },
    { text: 'if the statement agrees with the information', offset: passageOffset + 153 },
    { text: 'FALSE', offset: passageOffset + 198 },
    { text: 'if the statement contradicts the information', offset: passageOffset + 204 },
    { text: 'NOT GIVEN', offset: passageOffset + 249 },
    { text: 'if there is no information on this', offset: passageOffset + 259 },
    { text: 'The growing importance of the middle classes led to an increased demand for dictionaries.', offset: passageOffset + 1604 },
    { text: 'Johnson has become more well known since his death.', offset: passageOffset + 1696 },
    { text: 'Johnson had been planning to write a dictionary for several years.', offset: passageOffset + 1748 },
    { text: 'Johnson set up an academy to help with the writing of his Dictionary.', offset: passageOffset + 1815 },
    { text: 'Johnson only received payment for his Dictionary on its completion.', offset: passageOffset + 1886 },
    { text: 'Not all of the assistants survived to see the publication of the Dictionary.', offset: passageOffset + 1955 },
]);

const handleMultipleChoice = (option: string) => {
    const key = 'q1_3';
    const currentAnswers = multipleAnswers.value[key];
    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        currentAnswers.splice(index, 1);
    } else {
        if (currentAnswers.length < 3) {
            currentAnswers.push(option);
        }
    }
};

const isSelected = (option: string) => {
    return multipleAnswers.value.q1_3.includes(option);
};

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

const getAnswers = () => {
    const allAnswers: Record<string, any> = { ...answers.value };
    if (Array.isArray(multipleAnswers.value.q1_3)) {
        const selected = multipleAnswers.value.q1_3.sort();
        allAnswers.q1 = selected[0] || '';
        allAnswers.q2 = selected[1] || '';
        allAnswers.q3 = selected[2] || '';
    }
    return allAnswers;
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

watch(notes, (newNotes) => emit('notesChange', newNotes), { deep: true });

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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">READING PASSAGE 1</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h2
                                        class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        Johnson’s Dictionary
                                    </h2>
                                </div>
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
                </div>

                <!-- Questions Section -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">QUESTIONS</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-3 -->
                                <section id="question-1-3" class="space-y-4 rounded-xl border border-blue-200 bg-blue-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-blue-800"
                                        :data-segment-text="'Questions 1-3'"
                                        v-html="getHighlightedSegment('Questions 1-3')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Choose THREE letters A-H.'"
                                        v-html="getHighlightedSegment('Choose THREE letters A-H.')"
                                    ></p>
                                    <p
                                        :data-segment-text="'Write your answers in boxes 1-3 on your answer sheet.'"
                                        v-html="getHighlightedSegment('Write your answers in boxes 1-3 on your answer sheet.')"
                                    ></p>
                                    <p>
                                        <strong
                                            class="text-red-400"
                                            :data-segment-text="'NB Your answers may be given in any order.'"
                                            v-html="getHighlightedSegment('NB Your answers may be given in any order.')"
                                        ></strong>
                                    </p>
                                    <p
                                        class="font-semibold"
                                        :data-segment-text="'Which THREE of the following statements are true of Johnsons Dictionary?'"
                                        v-html="getHighlightedSegment('Which THREE of the following statements are true of Johnsons Dictionary?')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label
                                            v-for="option in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']"
                                            :key="option"
                                            @click.prevent="handleMultipleChoice(option)"
                                            :class="[
                                                'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all',
                                                isSelected(option)
                                                    ? 'border-blue-500 bg-blue-100 shadow-md'
                                                    : 'border-gray-200 bg-white hover:bg-blue-50',
                                            ]"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="option"
                                                :checked="isSelected(option)"
                                                class="mt-0.5 h-5 w-5 flex-shrink-0 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span
                                                class="font-medium text-gray-700"
                                                :data-segment-text="textSegments.find((s) => s.text.startsWith(option + ' '))?.text"
                                                v-html="getHighlightedSegment(textSegments.find((s) => s.text.startsWith(option + ' '))?.text || '')"
                                            ></span>
                                        </label>
                                    </div>
                                </section>

                                <!-- Questions 4-7 -->
                                <section id="question-4-7" class="space-y-4 rounded-xl border border-blue-200 bg-blue-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-blue-800"
                                        :data-segment-text="'Questions 4-7'"
                                        v-html="getHighlightedSegment('Questions 4-7')"
                                    ></h3>
                                    <p :data-segment-text="'Complete the summary.'" v-html="getHighlightedSegment('Complete the summary.')"></p>
                                    <p
                                        :data-segment-text="'Choose NO MORE THAN TWO WORDS from the passage for each answer.'"
                                        v-html="getHighlightedSegment('Choose NO MORE THAN TWO WORDS from the passage for each answer.')"
                                    ></p>
                                    <div class="space-y-4 rounded-lg border border-blue-200 bg-white p-6 shadow-inner">
                                        <p class="leading-loose text-gray-700">
                                            <span
                                                :data-segment-text="'In 1764 Dr Johnson accepted the contract to produce a dictionary. Having rented a garret, he took on a number of'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'In 1764 Dr Johnson accepted the contract to produce a dictionary. Having rented a garret, he took on a number of',
                                                    )
                                                "
                                            ></span
                                            ><span class="mx-2 rounded-full bg-blue-600 px-3 py-2 font-bold text-white shadow-2xl">4</span>
                                            <input
                                                type="text"
                                                v-model="answers.q4"
                                                class="inline-block w-48 border-b-2 border-blue-300 bg-transparent text-center font-semibold text-blue-700 focus:border-blue-500 focus:outline-none"
                                            />
                                            <span
                                                :data-segment-text="', who stood at a long central desk. Johnson did not have a'"
                                                v-html="getHighlightedSegment(', who stood at a long central desk. Johnson did not have a')"
                                            ></span
                                            ><span class="mx-2 rounded-full bg-blue-600 px-3 py-2 font-bold text-white shadow-2xl">5</span>
                                            <input
                                                type="text"
                                                v-model="answers.q5"
                                                class="inline-block w-48 border-b-2 border-blue-300 bg-transparent text-center font-semibold text-blue-700 focus:border-blue-500 focus:outline-none"
                                            />
                                            <span
                                                :data-segment-text="' available to him, but eventually produced definitions of in excess of 40,000 words written down in 80 large notebooks. On publication, the Dictionary was immediately hailed in many European countries as a landmark. According to his biographer, James Boswell, Johnson\'s principal achievement was to bring'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        ' available to him, but eventually produced definitions of in excess of 40,000 words written down in 80 large notebooks. On publication, the Dictionary was immediately hailed in many European countries as a landmark. According to his biographer, James Boswell, Johnson\'s principal achievement was to bring',
                                                    )
                                                "
                                            ></span
                                            ><span class="mx-2 rounded-full bg-blue-600 px-3 py-2 font-bold text-white shadow-2xl">6</span>
                                            <input
                                                type="text"
                                                v-model="answers.q6"
                                                class="inline-block w-48 border-b-2 border-blue-300 bg-transparent text-center font-semibold text-blue-700 focus:border-blue-500 focus:outline-none"
                                            />
                                            <span
                                                :data-segment-text="' to the English language. As a reward for his hard work, he was granted a'"
                                                v-html="
                                                    getHighlightedSegment(' to the English language. As a reward for his hard work, he was granted a')
                                                "
                                            ></span
                                            ><span class="mx-2 rounded-full bg-blue-600 px-3 py-2 font-bold text-white shadow-2xl">7</span>
                                            <input
                                                type="text"
                                                v-model="answers.q7"
                                                class="inline-block w-48 border-b-2 border-blue-300 bg-transparent text-center font-semibold text-blue-700 focus:border-blue-500 focus:outline-none"
                                            />
                                            <span :data-segment-text="' by the king.'" v-html="getHighlightedSegment(' by the king.')"></span>
                                        </p>
                                    </div>
                                </section>

                                <!-- Questions 8-13 -->
                                <section id="question-8-13" class="space-y-4 rounded-xl border border-blue-200 bg-blue-50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-blue-800"
                                        :data-segment-text="'Questions 8-13'"
                                        v-html="getHighlightedSegment('Questions 8-13')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Do the following statements agree with the information given in Reading Passage 1?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Do the following statements agree with the information given in Reading Passage 1?',
                                            )
                                        "
                                    ></p>
                                    <p
                                        :data-segment-text="'In boxes 8-13 on your answer sheet, write'"
                                        v-html="getHighlightedSegment('In boxes 8-13 on your answer sheet, write')"
                                    ></p>
                                    <div class="space-y-8 rounded-lg border border-blue-200 bg-white p-8 shadow-lg">
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
                                            v-for="i in 6"
                                            :key="i"
                                            class="group rounded-lg border-l-4 border-blue-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-start gap-4">
                                                <span
                                                    class="flex h-8 w-8 transform items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                                    >{{ i + 7 }}</span
                                                >
                                                <p
                                                    class="flex-1"
                                                    :data-segment-text="
                                                        textSegments.find(
                                                            (s) => s.offset === passageOffset + [1604, 1696, 1748, 1815, 1886, 1955][i - 1],
                                                        )?.text
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find(
                                                                (s) => s.offset === passageOffset + [1604, 1696, 1748, 1815, 1886, 1955][i - 1],
                                                            )?.text || '',
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                            <div class="mt-3 flex space-x-4 pl-12">
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (i + 7)"
                                                        value="TRUE"
                                                        v-model="answers['q' + (i + 7)]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (i + 7)"
                                                        value="FALSE"
                                                        v-model="answers['q' + (i + 7)]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (i + 7)"
                                                        value="NOT GIVEN"
                                                        v-model="answers['q' + (i + 7)]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
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
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-blue-50 transition-all hover:scale-110 hover:bg-blue-100"
                        title="Add Note"
                    >
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            class="absolute z-[9999] w-80 rounded-lg border-2 border-blue-300 bg-white p-4 shadow-2xl"
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
                    class="note-input-field w-full rounded-lg border-2 border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
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
                    class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
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
