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
const PANEL_WIDTH_KEY = 'reading-c19t3-part3-panel-width';
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

const passageText = `Noise, Alex Waibel tells me, is one of the major challenges that artificial speech translation has to meet. A device may be able to recognize speech in a laboratory, or a meeting room, but will struggle to cope with the kind of background noise I can hear in my office surrounding Professor Waibel as he speaks to me from Kyoto station in Japan. I\u2019m struggling to follow him in English, on a scratchy line that reminds me we are nearly 10,000 kilometers apart \u2013 and that distance is still an obstacle to communication even if you\u2019re speaking the same language, as we are. We haven\u2019t reached the future yet. If we had, Waibel would have been able to speak more comfortably in his native German and I would have been able to hear his words in English.

At Karlsruhe Institute of Technology, where he is a professor of computer science, Waibel and his colleagues already give lectures in German that their students can follow in English via an electronic translator. The system generates text that students can read on their laptops or phones, so the process is somewhat similar to subtitling. It helps that lecturers speak clearly, don\u2019t have to compete with background chatter, and say much the same thing each year.

The idea of artificial speech translation has been around for a long time. Douglas Adams\u2019 science fiction novel, The Hitchhiker\u2019s Guide to the Galaxy, published in 1979, featured a life form called the \u2018Babel fish\u2019 which, when placed in the ear, enabled a listener to understand any language in the universe. It came to represent one of those devices that technology enthusiasts dream of long before they become practically realizable, like TVs flat enough to hang on walls: objects that we once could only dream of having but that are now commonplace. Now devices that look like prototype Babel fish have started to appear, riding a wave of advances in artificial translation and voice recognition.

At this stage, however, they seem to be regarded as eye-catching novelties rather than steps towards what Waibel calls \u2018making a language-transparent society.\u2019 They tend to be domestic devices or applications suitable for hotel check-ins, for example, providing a practical alternative to speaking traveler\u2019s English. The efficiency of the translator is less important than the social function. However, \u2018Professionals are less inclined to be patient in a conversation,\u2019 founder and CEO at Waverly Labs, Andrew Ochoa, observes. To redress this, Waverly is now preparing a new model for professional applications, which entails performance improvements in speech recognition, translation accuracy and the time it takes to deliver the translated speech.

For a conversation, both speakers need to have devices called Pilots (translator earpieces) in their ears. \u2018We find that there\u2019s a barrier with sharing one of the earphones with a stranger,\u2019 says Ochoa. That can\u2019t have been totally unexpected. The problem would be solved if earpiece translators became sufficiently prevalent that strangers would be likely to already have their own in their ears. Whether that happens, and how quickly, will probably depend not so much on the earpieces themselves, but on the prevalence of voice-controlled devices and artificial translation in general.

Waibel highlights the significance of certain Asian nations, noting that voice translation has really taken off in countries such as Japan with a range of systems. There is still a long way to go, though. A translation system needs to be simultaneous, like the translator\u2019s voice speaking over the foreign politician being interviewed on the TV, rather than in sections that oblige speakers to pause after every few remarks and wait for the translation to be delivered. It needs to work offline, for situations where internet access isn\u2019t possible, and to address apprehensions about the amount of private speech data accumulating in the cloud, having been sent to servers for processing.

Systems not only need to cope with physical challenges such as noise, they will also need to be socially aware by addressing people in the right way. Some cultural traditions demand solemn respect for academic status, for example, and it is only polite to respect this. Etiquette-sensitive artificial translators could relieve people of the need to know these differing cultural norms. At the same time, they might help to preserve local customs, slowing the spread of habits associated with international English, such as its readiness to get on first-name terms.

Professors and other professionals will not outsource language awareness to software, though. If the technology matures into seamless, ubiquitous artificial speech translation, it will actually add value to language skills. Whether it will help people conduct their family lives or relationships is open to question\u2014though one noteworthy possibility is that it could overcome the language barriers that often arise between generations after migration, leaving children and their grandparents without a shared language.

Whatever uses it is put to, though, it will never be as good as the real thing. Even if voice-morphing technology simulates the speaker\u2019s voice, their lip movements won\u2019t match, and they will look like they are in a dubbed movie. The contrast will underline the value of shared languages, and the value of learning them. Sharing a language can promote a sense of belonging and community, as with the international scientists who use English as a lingua franca, where their predecessors used Latin. Though the practical need for a common language will diminish, the social value of sharing one will persist. And software will never be a substitute for the subtle but vital understanding that comes with knowledge of a language.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 3', offset: 5800 },
    { text: 'Is the era of artificial speech translation upon us?', offset: 5850 },
    {
        text: 'Once the stuff of science fiction, technology that enables people to talk using different languages is now here. But how effective is it?',
        offset: 5920,
    },
    // Q27-30: Multiple choice
    { text: 'Questions 27\u201330', offset: 6100 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 6120 },
    { text: 'Write the correct letter in boxes 27\u201330 on your answer sheet.', offset: 6170 },
    { text: 'What does the reader learn about the conversation in the first paragraph?', offset: 6250 },
    { text: 'The speakers are communicating in different languages.', offset: 6340 },
    { text: 'Neither of the speakers is familiar with their environment.', offset: 6400 },
    { text: 'The topic of the conversation is difficult for both speakers.', offset: 6470 },
    { text: 'Aspects of the conversation are challenging for both speakers.', offset: 6540 },
    { text: 'What assists the electronic translator during lectures at Karlsruhe Institute of Technology?', offset: 6610 },
    { text: 'the repeated content of lectures', offset: 6710 },
    { text: 'the students\u2019 reading skills', offset: 6750 },
    { text: 'the languages used', offset: 6790 },
    { text: 'the lecturers\u2019 technical ability', offset: 6820 },
    { text: 'When referring to The Hitchhiker\u2019s Guide to the Galaxy, the writer suggests that', offset: 6860 },
    { text: 'the Babel fish was considered undesirable at the time.', offset: 6950 },
    { text: 'this book was not seriously intending to predict the future.', offset: 7010 },
    { text: 'artificial speech translation was not a surprising development.', offset: 7080 },
    { text: 'some speech translation techniques are better than others.', offset: 7150 },
    { text: 'What does the writer say about sharing earpieces?', offset: 7220 },
    { text: 'It is something people will get used to doing.', offset: 7280 },
    { text: 'The reluctance to do this is understandable.', offset: 7340 },
    { text: 'The equipment will be unnecessary in the future.', offset: 7390 },
    { text: 'It is something few people need to worry about.', offset: 7450 },
    // Q31-34: Sentence completion
    { text: 'Questions 31\u201334', offset: 7520 },
    { text: 'Complete each sentence with the correct ending, A\u2013F, below.', offset: 7540 },
    { text: 'Write the correct letter, A\u2013F, in boxes 31\u201334 on your answer sheet.', offset: 7610 },
    { text: 'Speech translation methods are developing fast in Japan', offset: 7700 },
    { text: 'TV interviews that use translation voiceover methods are successful', offset: 7770 },
    { text: 'Future translation systems should address people appropriately', offset: 7850 },
    { text: 'Users may be able to maintain their local customs', offset: 7920 },
    { text: 'A but there are concerns about this.', offset: 7990 },
    { text: 'B as systems do not need to conform to standard practices.', offset: 8030 },
    { text: 'C but they are far from perfect.', offset: 8100 },
    { text: 'D despite the noise issues.', offset: 8140 },
    { text: 'E because translation is immediate.', offset: 8180 },
    { text: 'F and have an awareness of good manners.', offset: 8220 },
    // Q35-40: YES/NO/NOT GIVEN
    { text: 'Questions 35\u201340', offset: 8280 },
    { text: 'Do the following statements agree with the claims of the writer in Reading Passage 3?', offset: 8300 },
    { text: 'In boxes 35\u201340 on your answer sheet, write', offset: 8390 },
    { text: 'if the statement agrees with the claims of the writer', offset: 8440 },
    { text: 'if the statement contradicts the claims of the writer', offset: 8500 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 8560 },
    { text: 'Language translation systems will be seen as very useful throughout the academic and professional worlds.', offset: 8640 },
    { text: 'The overall value of automated translation to family life is yet to be shown.', offset: 8760 },
    { text: 'Automated translation could make life more difficult for immigrant families.', offset: 8850 },
    { text: 'Visual aspects of language translation are being considered by scientists.', offset: 8940 },
    { text: 'International scientists have found English easier to translate into other languages than Latin.', offset: 9020 },
    { text: 'As far as language is concerned, there is a difference between people\u2019s social and practical needs.', offset: 9130 },
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
                                            :data-segment-text="'Is the era of artificial speech translation upon us?'"
                                            v-html="getHighlightedSegment('Is the era of artificial speech translation upon us?')"
                                        ></span>
                                    </h2>
                                    <p
                                        class="mt-1 text-center text-sm text-gray-500 italic"
                                        :data-segment-text="'Once the stuff of science fiction, technology that enables people to talk using different languages is now here. But how effective is it?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Once the stuff of science fiction, technology that enables people to talk using different languages is now here. But how effective is it?',
                                            )
                                        "
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
                                                        :data-segment-text="'What does the reader learn about the conversation in the first paragraph?'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'What does the reader learn about the conversation in the first paragraph?',
                                                            )
                                                        "
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
                                                                :data-segment-text="'The speakers are communicating in different languages.'"
                                                                v-html="
                                                                    getHighlightedSegment('The speakers are communicating in different languages.')
                                                                "
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
                                                                :data-segment-text="'Neither of the speakers is familiar with their environment.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Neither of the speakers is familiar with their environment.',
                                                                    )
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
                                                                :data-segment-text="'The topic of the conversation is difficult for both speakers.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'The topic of the conversation is difficult for both speakers.',
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
                                                                :data-segment-text="'Aspects of the conversation are challenging for both speakers.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Aspects of the conversation are challenging for both speakers.',
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
                                                        :data-segment-text="'What assists the electronic translator during lectures at Karlsruhe Institute of Technology?'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'What assists the electronic translator during lectures at Karlsruhe Institute of Technology?',
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
                                                                :data-segment-text="'the repeated content of lectures'"
                                                                v-html="getHighlightedSegment('the repeated content of lectures')"
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
                                                                :data-segment-text="'the students\u2019 reading skills'"
                                                                v-html="getHighlightedSegment('the students\u2019 reading skills')"
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
                                                                :data-segment-text="'the languages used'"
                                                                v-html="getHighlightedSegment('the languages used')"
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
                                                                :data-segment-text="'the lecturers\u2019 technical ability'"
                                                                v-html="getHighlightedSegment('the lecturers\u2019 technical ability')"
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
                                                        :data-segment-text="'When referring to The Hitchhiker\u2019s Guide to the Galaxy, the writer suggests that'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'When referring to The Hitchhiker\u2019s Guide to the Galaxy, the writer suggests that',
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
                                                                :data-segment-text="'the Babel fish was considered undesirable at the time.'"
                                                                v-html="
                                                                    getHighlightedSegment('the Babel fish was considered undesirable at the time.')
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
                                                                :data-segment-text="'this book was not seriously intending to predict the future.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'this book was not seriously intending to predict the future.',
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
                                                                :data-segment-text="'artificial speech translation was not a surprising development.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'artificial speech translation was not a surprising development.',
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
                                                                :data-segment-text="'some speech translation techniques are better than others.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'some speech translation techniques are better than others.',
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
                                                        :data-segment-text="'What does the writer say about sharing earpieces?'"
                                                        v-html="getHighlightedSegment('What does the writer say about sharing earpieces?')"
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
                                                                :data-segment-text="'It is something people will get used to doing.'"
                                                                v-html="getHighlightedSegment('It is something people will get used to doing.')"
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
                                                                :data-segment-text="'The reluctance to do this is understandable.'"
                                                                v-html="getHighlightedSegment('The reluctance to do this is understandable.')"
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
                                                                :data-segment-text="'The equipment will be unnecessary in the future.'"
                                                                v-html="getHighlightedSegment('The equipment will be unnecessary in the future.')"
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
                                                                :data-segment-text="'It is something few people need to worry about.'"
                                                                v-html="getHighlightedSegment('It is something few people need to worry about.')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 31-34: Sentence Completion A-F -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-31-34" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 31\u201334'"
                                            v-html="getHighlightedSegment('Questions 31\u201334')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete each sentence with the correct ending, A\u2013F, below.'"
                                                v-html="getHighlightedSegment('Complete each sentence with the correct ending, A\u2013F, below.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013F, in boxes 31\u201334 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013F, in boxes 31\u201334 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <!-- Sentence beginnings with dropdowns -->
                                        <div class="space-y-3">
                                            <!-- Q31 -->
                                            <div
                                                id="question-31"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >31</span
                                                    >
                                                    <div class="flex-1">
                                                        <p
                                                            class="text-gray-700"
                                                            :data-segment-text="'Speech translation methods are developing fast in Japan'"
                                                            v-html="getHighlightedSegment('Speech translation methods are developing fast in Japan')"
                                                        ></p>
                                                        <select
                                                            v-model="answers.q31"
                                                            class="mt-2 w-full rounded-xl border border-green-300 bg-green-50 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                        >
                                                            <option disabled value="">Select ending</option>
                                                            <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F']" :key="i" :value="i">{{ i }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Q32 -->
                                            <div
                                                id="question-32"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >32</span
                                                    >
                                                    <div class="flex-1">
                                                        <p
                                                            class="text-gray-700"
                                                            :data-segment-text="'TV interviews that use translation voiceover methods are successful'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'TV interviews that use translation voiceover methods are successful',
                                                                )
                                                            "
                                                        ></p>
                                                        <select
                                                            v-model="answers.q32"
                                                            class="mt-2 w-full rounded-xl border border-green-300 bg-green-50 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                        >
                                                            <option disabled value="">Select ending</option>
                                                            <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F']" :key="i" :value="i">{{ i }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

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
                                                    <div class="flex-1">
                                                        <p
                                                            class="text-gray-700"
                                                            :data-segment-text="'Future translation systems should address people appropriately'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'Future translation systems should address people appropriately',
                                                                )
                                                            "
                                                        ></p>
                                                        <select
                                                            v-model="answers.q33"
                                                            class="mt-2 w-full rounded-xl border border-green-300 bg-green-50 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                        >
                                                            <option disabled value="">Select ending</option>
                                                            <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F']" :key="i" :value="i">{{ i }}</option>
                                                        </select>
                                                    </div>
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
                                                    <div class="flex-1">
                                                        <p
                                                            class="text-gray-700"
                                                            :data-segment-text="'Users may be able to maintain their local customs'"
                                                            v-html="getHighlightedSegment('Users may be able to maintain their local customs')"
                                                        ></p>
                                                        <select
                                                            v-model="answers.q34"
                                                            class="mt-2 w-full rounded-xl border border-green-300 bg-green-50 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                        >
                                                            <option disabled value="">Select ending</option>
                                                            <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F']" :key="i" :value="i">{{ i }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Endings list -->
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <div class="space-y-2 text-gray-700">
                                                <p
                                                    :data-segment-text="'A but there are concerns about this.'"
                                                    v-html="getHighlightedSegment('A but there are concerns about this.')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'B as systems do not need to conform to standard practices.'"
                                                    v-html="getHighlightedSegment('B as systems do not need to conform to standard practices.')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'C but they are far from perfect.'"
                                                    v-html="getHighlightedSegment('C but they are far from perfect.')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'D despite the noise issues.'"
                                                    v-html="getHighlightedSegment('D despite the noise issues.')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'E because translation is immediate.'"
                                                    v-html="getHighlightedSegment('E because translation is immediate.')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'F and have an awareness of good manners.'"
                                                    v-html="getHighlightedSegment('F and have an awareness of good manners.')"
                                                ></p>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 35-40: YES/NO/NOT GIVEN -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-35-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 35\u201340'"
                                            v-html="getHighlightedSegment('Questions 35\u201340')"
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
                                                :data-segment-text="'In boxes 35\u201340 on your answer sheet, write'"
                                                v-html="getHighlightedSegment('In boxes 35\u201340 on your answer sheet, write')"
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
                                                        :data-segment-text="'Language translation systems will be seen as very useful throughout the academic and professional worlds.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Language translation systems will be seen as very useful throughout the academic and professional worlds.',
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
                                                        :data-segment-text="'The overall value of automated translation to family life is yet to be shown.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The overall value of automated translation to family life is yet to be shown.',
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
                                                        :data-segment-text="'Automated translation could make life more difficult for immigrant families.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Automated translation could make life more difficult for immigrant families.',
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
                                                        :data-segment-text="'Visual aspects of language translation are being considered by scientists.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Visual aspects of language translation are being considered by scientists.',
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
                                                        :data-segment-text="'International scientists have found English easier to translate into other languages than Latin.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'International scientists have found English easier to translate into other languages than Latin.',
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
                                                        :data-segment-text="'As far as language is concerned, there is a difference between people\u2019s social and practical needs.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'As far as language is concerned, there is a difference between people\u2019s social and practical needs.',
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
