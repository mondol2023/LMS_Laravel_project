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
const PANEL_WIDTH_KEY = 'reading-c19t2-part3-panel-width';
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

const passageText = `Let us start by looking at a modern \u2018genius\u2019, Maryam Mirzakhani, who died at the early age of 40. She was the only woman to win the Fields Medal \u2013 the mathematical equivalent of a Nobel prize. It would be easy to assume that someone as special as Mirzakhani must have been one of those \u2018gifted\u2019 children, those who have an extraordinary ability in a specific sphere of activity or knowledge. But look closer and a different story emerges. Mirzakhani was born in Tehran, Iran. She went to a highly selective girls\u2019 school but maths wasn\u2019t her interest \u2013 reading was. She loved novels and would read anything she could lay her hands on. As for maths, she did rather poorly at it for the first couple of years in her middle school, but became interested when her elder brother told her about what he\u2019d learned. He shared a famous maths problem from a magazine that fascinated her \u2013 and she was hooked.

In adult life it is clear that she was curious, excited by what she did and also resolute in the face of setbacks. One of her comments sums it up. \u2018Of course, the most rewarding part is the \u201cAha\u201d moment, the excitement of discovery and enjoyment of understanding something new \u2026 But most of the time, doing mathematics for me is like being on a long hike with no trail and no end in sight.\u2019 That trail took her to the heights of original research into mathematics.

Is her background unusual? Apparently not. Most Nobel prize winners were unexceptional in childhood. Einstein was slow to talk as a baby. He failed the general part of the entry test to Zurich Polytechnic \u2013 though they let him in because of high physics and maths scores. He struggled at work initially, but he kept plugging away and eventually rewrote the laws of Newtonian mechanics with his theory of relativity.

There has been a considerable amount of research on high performance over the last century that suggests it goes way beyond tested intelligence. On top of that, research is clear that brains are flexible, new neural pathways can be created, and IQ isn\u2019t fixed. For example, just because you can read stories with hundreds of pages at the age of five doesn\u2019t mean you will still be ahead of your contemporaries in your teens.

While the jury is out on giftedness being innate and other factors potentially making the difference, what is certain is that the behaviours associated with high levels of performance are replicable and most can be taught \u2013 even traits such as curiosity.

According to my colleague Prof Deborah Eyre, with whom I\u2019ve collaborated on the book Great Minds and How to Grow Them, the latest neuroscience and psychological research suggests most individuals can reach levels of performance associated in school with the gifted and talented. However, they must be taught the right attitudes and approaches to their learning and develop the attributes of high performers \u2013 curiosity, persistence and hard work, for example \u2013 an approach Eyre calls \u2018high performance learning\u2019. Critically, they need the right support in developing those approaches at home as well as at school.

Prof Anders Ericsson, an eminent education psychologist at Florida State University, US, is the co-author of Peak: Secrets from the New Science of Expertise. After research going back to 1980 into diverse achievements, from music to memory to sport, he doesn\u2019t think unique and innate talents are at the heart of performance. Deliberate practice, that stretches you every step of the way, and around 10,000 hours of it, is what produces the goods. It\u2019s not a magic number \u2013 the highest performers move on to doing a whole lot more, of course. Ericsson\u2019s memory research is particularly interesting because random students, trained in memory techniques for the study, went on to outperform others thought to have innately superior memories \u2013 those who you might call gifted.

But it is perhaps the work of Benjamin Bloom, another distinguished American educationist working in the 1980s, that gives the most pause for thought. Bloom\u2019s team looked at a group of extraordinarily high achieving people in disciplines as varied as ballet, swimming, piano, tennis, maths, sculpture and neurology. He found a pattern of parents encouraging and supporting their children, often in areas they enjoyed themselves. Bloom\u2019s outstanding people had worked very hard and consistently at something they had become hooked on when at a young age, and their parents all emerged as having strong work ethics themselves.

Eyre says we know how high performers learn. From that she has developed a high performing learning approach. She is working on this with a group of schools, both in Britain and abroad. Some spin-off research, which looked in detail at 24 of the 3,000 children being studied who were succeeding despite difficult circumstances, found something remarkable. Half were getting free school meals because of poverty, more than half were living with a single parent, and four in five were living in disadvantaged areas. Interviews uncovered strong evidence of an adult or adults in the child\u2019s life who valued and supported education, either in the immediate or extended family or in the child\u2019s wider community. Children talked about the need to work hard at school, to listen in class and keep trying.

Let us end with Einstein, the epitome of a genius. He clearly had curiosity, character and determination. He struggled against rejection in early life but was undeterred. Did he think he was a genius or even gifted? He once wrote: \u2018It\u2019s not that I\u2019m so smart, it\u2019s just that I stay with problems longer. Most people say it is the intellect which makes a great scientist. They are wrong: it is character.\u2019`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 3', offset: 5800 },
    { text: 'An inquiry into the existence of the gifted child', offset: 5850 },
    // Q27-32: Summary completion
    { text: 'Questions 27\u201332', offset: 5950 },
    { text: 'Complete the summary using the list of phrases, A\u2013K, below.', offset: 5970 },
    { text: 'Write the correct letter, A\u2013K, in boxes 27\u201332 on your answer sheet.', offset: 6040 },
    { text: 'Maryam Mirzakhani', offset: 6130 },
    { text: 'Maryam Mirzakhani is regarded as ', offset: 6160 },
    {
        text: ' in the field of mathematics because she was the only female holder of the prestigious Fields Medal \u2013 a record that she retained at the time of her death. However, maths held little ',
        offset: 6200,
    },
    { text: ' for her as a child and in fact her performance was below average until she was ', offset: 6400 },
    { text: ' by a difficult puzzle that one of her siblings showed her.', offset: 6490 },
    { text: 'Later, as a professional mathematician, she had an inquiring mind and proved herself to be ', offset: 6560 },
    { text: ' when things did not go smoothly. She said she got the greatest ', offset: 6660 },
    { text: ' from making ground-breaking discoveries and in fact she was responsible for some extremely ', offset: 6730 },
    { text: ' mathematical studies.', offset: 6830 },
    { text: 'A appeal', offset: 6870 },
    { text: 'B determined', offset: 6890 },
    { text: 'C intrigued', offset: 6910 },
    { text: 'D single', offset: 6930 },
    { text: 'E achievement', offset: 6950 },
    { text: 'F devoted', offset: 6970 },
    { text: 'G involved', offset: 6990 },
    { text: 'H unique', offset: 7010 },
    { text: 'I innovative', offset: 7030 },
    { text: 'J satisfaction', offset: 7050 },
    { text: 'K intent', offset: 7070 },
    // Q33-37: YES/NO/NOT GIVEN
    { text: 'Questions 33\u201337', offset: 7120 },
    { text: 'Do the following statements agree with the claims of the writer in Reading Passage 3?', offset: 7140 },
    { text: 'In boxes 33\u201337 on your answer sheet, write', offset: 7230 },
    { text: 'if the statement agrees with the claims of the writer', offset: 7280 },
    { text: 'if the statement contradicts the claims of the writer', offset: 7340 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 7400 },
    { text: 'Many people who ended up winning prestigious intellectual prizes only reached an average standard when young.', offset: 7470 },
    { text: 'Einstein\u2019s failures as a young man were due to his lack of confidence.', offset: 7590 },
    { text: 'It is difficult to reach agreement on whether some children are actually born gifted.', offset: 7670 },
    { text: 'Einstein was upset by the public\u2019s view of his life\u2019s work.', offset: 7770 },
    { text: 'Einstein put his success down to the speed at which he dealt with scientific questions.', offset: 7840 },
    // Q38-40: Multiple choice
    { text: 'Questions 38\u201340', offset: 7950 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 7970 },
    { text: 'Write the correct letter in boxes 38\u201340 on your answer sheet.', offset: 8020 },
    { text: 'What does Eyre believe is needed for children to equal \u2018gifted\u2019 standards?', offset: 8100 },
    { text: 'strict discipline from the teaching staff', offset: 8190 },
    { text: 'assistance from their peers in the classroom', offset: 8240 },
    { text: 'the development of a spirit of inquiry towards their studies', offset: 8300 },
    { text: 'the determination to surpass everyone else\u2019s achievements', offset: 8370 },
    { text: 'What is the result of Ericsson\u2019s research?', offset: 8440 },
    { text: 'Very gifted students do not need to work on improving memory skills.', offset: 8500 },
    { text: 'Being born with a special gift is not the key factor in becoming expert.', offset: 8580 },
    { text: 'Including time for physical exercise is crucial in raising performance.', offset: 8660 },
    { text: '10,000 hours of relevant and demanding work will create a genius.', offset: 8740 },
    { text: 'In the penultimate paragraph, it is stated the key to some deprived children\u2019s success is', offset: 8820 },
    { text: 'a regular and nourishing diet at home.', offset: 8920 },
    { text: 'the loving support of more than one parent.', offset: 8970 },
    { text: 'a community which has well-funded facilities for learning.', offset: 9030 },
    { text: 'the guidance of someone who recognises the benefits of learning.', offset: 9100 },
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
                                        <span
                                            :data-segment-text="'An inquiry into the existence of the gifted child'"
                                            v-html="getHighlightedSegment('An inquiry into the existence of the gifted child')"
                                        ></span>
                                    </h2>
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
                                <!-- Questions 27-32: Summary Completion with Phrases A-K -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-27-32" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 27\u201332'"
                                            v-html="getHighlightedSegment('Questions 27\u201332')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the summary using the list of phrases, A\u2013K, below.'"
                                                v-html="getHighlightedSegment('Complete the summary using the list of phrases, A\u2013K, below.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013K, in boxes 27\u201332 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013K, in boxes 27\u201332 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <!-- Summary text with blanks -->
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-lg">
                                            <h4
                                                class="mb-4 text-center font-bold text-gray-800"
                                                :data-segment-text="'Maryam Mirzakhani'"
                                                v-html="getHighlightedSegment('Maryam Mirzakhani')"
                                            ></h4>
                                            <div class="leading-relaxed text-gray-700">
                                                <p>
                                                    <span
                                                        :data-segment-text="'Maryam Mirzakhani is regarded as '"
                                                        v-html="getHighlightedSegment('Maryam Mirzakhani is regarded as ')"
                                                    ></span>
                                                    <span
                                                        id="question-27"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >27</span
                                                    >
                                                    <select
                                                        v-model="answers.q27"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option
                                                            v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']"
                                                            :key="i"
                                                            :value="i"
                                                        >
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' in the field of mathematics because she was the only female holder of the prestigious Fields Medal \u2013 a record that she retained at the time of her death. However, maths held little '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' in the field of mathematics because she was the only female holder of the prestigious Fields Medal \u2013 a record that she retained at the time of her death. However, maths held little ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-28"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >28</span
                                                    >
                                                    <select
                                                        v-model="answers.q28"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option
                                                            v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']"
                                                            :key="i"
                                                            :value="i"
                                                        >
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' for her as a child and in fact her performance was below average until she was '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' for her as a child and in fact her performance was below average until she was ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-29"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >29</span
                                                    >
                                                    <select
                                                        v-model="answers.q29"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option
                                                            v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']"
                                                            :key="i"
                                                            :value="i"
                                                        >
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' by a difficult puzzle that one of her siblings showed her.'"
                                                        v-html="getHighlightedSegment(' by a difficult puzzle that one of her siblings showed her.')"
                                                    ></span>
                                                </p>
                                                <p class="mt-3">
                                                    <span
                                                        :data-segment-text="'Later, as a professional mathematician, she had an inquiring mind and proved herself to be '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Later, as a professional mathematician, she had an inquiring mind and proved herself to be ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-30"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >30</span
                                                    >
                                                    <select
                                                        v-model="answers.q30"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option
                                                            v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']"
                                                            :key="i"
                                                            :value="i"
                                                        >
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' when things did not go smoothly. She said she got the greatest '"
                                                        v-html="
                                                            getHighlightedSegment(' when things did not go smoothly. She said she got the greatest ')
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-31"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >31</span
                                                    >
                                                    <select
                                                        v-model="answers.q31"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option
                                                            v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']"
                                                            :key="i"
                                                            :value="i"
                                                        >
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' from making ground-breaking discoveries and in fact she was responsible for some extremely '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' from making ground-breaking discoveries and in fact she was responsible for some extremely ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-32"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >32</span
                                                    >
                                                    <select
                                                        v-model="answers.q32"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option
                                                            v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']"
                                                            :key="i"
                                                            :value="i"
                                                        >
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' mathematical studies.'"
                                                        v-html="getHighlightedSegment(' mathematical studies.')"
                                                    ></span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Options list -->
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <div class="grid grid-cols-2 gap-2 text-gray-700 md:grid-cols-3">
                                                <p :data-segment-text="'A appeal'" v-html="getHighlightedSegment('A appeal')"></p>
                                                <p :data-segment-text="'B determined'" v-html="getHighlightedSegment('B determined')"></p>
                                                <p :data-segment-text="'C intrigued'" v-html="getHighlightedSegment('C intrigued')"></p>
                                                <p :data-segment-text="'D single'" v-html="getHighlightedSegment('D single')"></p>
                                                <p :data-segment-text="'E achievement'" v-html="getHighlightedSegment('E achievement')"></p>
                                                <p :data-segment-text="'F devoted'" v-html="getHighlightedSegment('F devoted')"></p>
                                                <p :data-segment-text="'G involved'" v-html="getHighlightedSegment('G involved')"></p>
                                                <p :data-segment-text="'H unique'" v-html="getHighlightedSegment('H unique')"></p>
                                                <p :data-segment-text="'I innovative'" v-html="getHighlightedSegment('I innovative')"></p>
                                                <p :data-segment-text="'J satisfaction'" v-html="getHighlightedSegment('J satisfaction')"></p>
                                                <p :data-segment-text="'K intent'" v-html="getHighlightedSegment('K intent')"></p>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 33-37: YES/NO/NOT GIVEN -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-33-37" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 33\u201337'"
                                            v-html="getHighlightedSegment('Questions 33\u201337')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the claims of the writer in Reading Passage 3?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the claims of the writer in Reading Passage 3?',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'In boxes 33\u201337 on your answer sheet, write'"
                                                v-html="getHighlightedSegment('In boxes 33\u201337 on your answer sheet, write')"
                                            ></p>
                                            <div class="mt-2 space-y-4 text-gray-700">
                                                <p>
                                                    <strong class="mx-2 rounded bg-green-100 px-6 py-1.5 font-semibold text-green-700 shadow-lg"
                                                        >YES</strong
                                                    >
                                                    <span
                                                        :data-segment-text="'if the statement agrees with the claims of the writer'"
                                                        v-html="getHighlightedSegment('if the statement agrees with the claims of the writer')"
                                                    ></span>
                                                </p>
                                                <p>
                                                    <strong class="mx-2 my-2 rounded bg-red-100 px-7 py-1.5 font-semibold text-red-700 shadow-lg"
                                                        >NO</strong
                                                    >
                                                    <span
                                                        :data-segment-text="'if the statement contradicts the claims of the writer'"
                                                        v-html="getHighlightedSegment('if the statement contradicts the claims of the writer')"
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
                                            <!-- Q33 -->
                                            <div
                                                id="question-33"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >33</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'Many people who ended up winning prestigious intellectual prizes only reached an average standard when young.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Many people who ended up winning prestigious intellectual prizes only reached an average standard when young.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q33"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q33"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q33"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q34 -->
                                            <div
                                                id="question-34"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >34</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'Einstein\u2019s failures as a young man were due to his lack of confidence.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Einstein\u2019s failures as a young man were due to his lack of confidence.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q34"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q34"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q34"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q35 -->
                                            <div
                                                id="question-35"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >35</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="'It is difficult to reach agreement on whether some children are actually born gifted.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'It is difficult to reach agreement on whether some children are actually born gifted.',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="YES"
                                                            v-model="answers.q35"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NO"
                                                            v-model="answers.q35"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            value="NOT GIVEN"
                                                            v-model="answers.q35"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>

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
                                                        :data-segment-text="'Einstein was upset by the public\u2019s view of his life\u2019s work.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Einstein was upset by the public\u2019s view of his life\u2019s work.',
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
                                                        :data-segment-text="'Einstein put his success down to the speed at which he dealt with scientific questions.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Einstein put his success down to the speed at which he dealt with scientific questions.',
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
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 38-40: Multiple Choice A-D -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-38-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 38\u201340'"
                                            v-html="getHighlightedSegment('Questions 38\u201340')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose the correct letter, A, B, C or D.'"
                                                v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter in boxes 38\u201340 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write the correct letter in boxes 38\u201340 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Q38 -->
                                            <div
                                                id="question-38"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >38</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'What does Eyre believe is needed for children to equal \u2018gifted\u2019 standards?'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'What does Eyre believe is needed for children to equal \u2018gifted\u2019 standards?',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q38"
                                                            value="A"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">A</strong>
                                                            <span
                                                                :data-segment-text="'strict discipline from the teaching staff'"
                                                                v-html="getHighlightedSegment('strict discipline from the teaching staff')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q38"
                                                            value="B"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">B</strong>
                                                            <span
                                                                :data-segment-text="'assistance from their peers in the classroom'"
                                                                v-html="getHighlightedSegment('assistance from their peers in the classroom')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q38"
                                                            value="C"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">C</strong>
                                                            <span
                                                                :data-segment-text="'the development of a spirit of inquiry towards their studies'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'the development of a spirit of inquiry towards their studies',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q38"
                                                            value="D"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">D</strong>
                                                            <span
                                                                :data-segment-text="'the determination to surpass everyone else\u2019s achievements'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'the determination to surpass everyone else\u2019s achievements',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q39 -->
                                            <div
                                                id="question-39"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >39</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'What is the result of Ericsson\u2019s research?'"
                                                        v-html="getHighlightedSegment('What is the result of Ericsson\u2019s research?')"
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q39"
                                                            value="A"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">A</strong>
                                                            <span
                                                                :data-segment-text="'Very gifted students do not need to work on improving memory skills.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Very gifted students do not need to work on improving memory skills.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q39"
                                                            value="B"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">B</strong>
                                                            <span
                                                                :data-segment-text="'Being born with a special gift is not the key factor in becoming expert.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Being born with a special gift is not the key factor in becoming expert.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q39"
                                                            value="C"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">C</strong>
                                                            <span
                                                                :data-segment-text="'Including time for physical exercise is crucial in raising performance.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Including time for physical exercise is crucial in raising performance.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q39"
                                                            value="D"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">D</strong>
                                                            <span
                                                                :data-segment-text="'10,000 hours of relevant and demanding work will create a genius.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        '10,000 hours of relevant and demanding work will create a genius.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q40 -->
                                            <div
                                                id="question-40"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >40</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'In the penultimate paragraph, it is stated the key to some deprived children\u2019s success is'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'In the penultimate paragraph, it is stated the key to some deprived children\u2019s success is',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q40"
                                                            value="A"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">A</strong>
                                                            <span
                                                                :data-segment-text="'a regular and nourishing diet at home.'"
                                                                v-html="getHighlightedSegment('a regular and nourishing diet at home.')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q40"
                                                            value="B"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">B</strong>
                                                            <span
                                                                :data-segment-text="'the loving support of more than one parent.'"
                                                                v-html="getHighlightedSegment('the loving support of more than one parent.')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q40"
                                                            value="C"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">C</strong>
                                                            <span
                                                                :data-segment-text="'a community which has well-funded facilities for learning.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'a community which has well-funded facilities for learning.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100">
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q40"
                                                            value="D"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">D</strong>
                                                            <span
                                                                :data-segment-text="'the guidance of someone who recognises the benefits of learning.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'the guidance of someone who recognises the benefits of learning.',
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
