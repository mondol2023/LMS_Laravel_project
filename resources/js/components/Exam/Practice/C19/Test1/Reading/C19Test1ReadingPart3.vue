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
const PANEL_WIDTH_KEY = 'reading-c19t1-part3-panel-width';
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

const passageText = `Misinformation \u2013 both deliberately promoted and accidentally shared \u2013 is perhaps an inevitable part of the world in which we live, but it is not a new problem. People likely have lied to one another for roughly as long as verbal communication has existed. Deceiving others can offer an apparent opportunity to gain strategic advantage, to motivate others to action, or even to protect interpersonal bonds. Moreover, people inadvertently have been sharing inaccurate information with one another for thousands of years.

However, we currently live in an era in which technology enables information to reach large audiences distributed across the globe, and thus the potential for immediate and widespread effects from misinformation now looms larger than in the past. Yet the means to correct misinformation might, over time, be found in those same patterns of mass communication and of the facilitated spread of information.

The main worry regarding misinformation is its potential to unduly influence attitudes and behavior, leading people to think and act differently than they would if they were correctly informed, as suggested by the research teams of Stephan Lewandowsky of the University of Bristol and Elizabeth Marsh of Duke University, among others. In other words, we worry that misinformation might lead people to hold misperceptions (or false beliefs) and that these misperceptions, especially when they occur among large groups of people, may have detrimental, downstream consequences for health, social harmony, and the political climate.

At least three observations related to misinformation in the contemporary mass-media environment warrant the attention of researchers, policy makers, and really everyone who watches television, listens to the radio, or reads information online. First of all, people who encounter misinformation tend to believe it, at least initially. Secondly, electronic and print media often do not block many types of misinformation before it appears in content available to large audiences. Thirdly, countering misinformation once it has enjoyed wide exposure can be a resource-intensive effort.

Knowing what happens when people initially encounter misinformation holds tremendous importance for estimating the potential for subsequent problems. Although it is fairly routine for individuals to come across information that is false, the question of exactly how \u2013 and when \u2013 we mentally label information as true or false has garnered philosophical debate. The dilemma is neatly summarized by a contrast between how the 17th-century philosophers Ren\u00e9 Descartes and Baruch Spinoza described human information engagement, with conflicting predictions that only recently have been empirically tested in robust ways. Descartes argued that a person only accepts or rejects information after considering its truth or falsehood; Spinoza argued that people accept all encountered information (or misinformation) by default and then subsequently verify or reject it through a separate cognitive process. In recent decades, empirical evidence from the research teams of Erik Asp of the University of Chicago and Daniel Gilbert at Harvard University, among others, has supported Spinoza\u2019s account: people appear to encode all new information as if it were true, even if only momentarily, and later tag the information as being either true or false, a pattern that seems consistent with the observation that mental resources for skepticism physically reside in a different part of the brain than the resources used in perceiving and encoding.

What about our second observation that misinformation often can appear in electronic or print media without being preemptively blocked? In support of this, one might consider the nature of regulatory structures in the United States: regulatory agencies here tend to focus on post hoc detection of broadcast information. Organizations such as the Food and Drug Administration (FDA) offer considerable monitoring and notification functions, but these roles typically do not involve preemptive censoring. The FDA oversees direct-to-consumer prescription drug advertising, for example, and has developed mechanisms such as the \u2018Bad Ad\u2019 program, through which people can report advertising in apparent violation of FDA guidelines on drug risks. Such programs, although laudable and useful, do not keep false advertising off the airwaves. In addition, even misinformation that is successfully corrected can continue to affect attitudes.

This leads us to our third observation: a campaign to correct misinformation, even if rhetorically compelling, requires resources and planning to accomplish necessary reach and frequency. For corrective campaigns to be persuasive, audiences need to be able to comprehend them, which requires either effort to frame messages in ways that are accessible or effort to educate and sensitize audiences to the possibility of misinformation. That some audiences might be unaware of the potential for misinformation also suggests the utility of media literacy efforts as early as elementary school. Even with journalists and scholars pointing to the phenomenon of \u2018fake news\u2019, people do not distinguish between demonstrably false stories and those based in fact when scanning and processing written information.

We live at a time when widespread misinformation is common. Yet at this time many people also are passionately developing potential solutions and remedies. The journey forward undoubtedly will be a long and arduous one. Future remedies will require not only continued theoretical consideration but also the development and maintenance of consistent monitoring tools \u2013 and a recognition among fellow members of society that claims which find prominence in the media that are insufficiently based in scientific consensus and social reality should be countered. Misinformation arises as a result of human fallibility and human information needs. To overcome the worst effects of the phenomenon, we will need coordinated efforts over time, rather than any singular one-time panacea we could hope to offer.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 3', offset: 5800 },
    { text: 'The persistence and peril of misinformation', offset: 5850 },
    // Q27-30
    { text: 'Questions 27\u201330', offset: 5950 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 5970 },
    { text: 'Write the correct letter in boxes 27\u201330 on your answer sheet.', offset: 6020 },
    { text: 'What point does the writer make about misinformation in the first paragraph?', offset: 6100 },
    { text: 'Misinformation is a relatively recent phenomenon.', offset: 6200 },
    { text: 'Some people find it easy to identify misinformation.', offset: 6260 },
    { text: 'Misinformation changes as it is passed from one person to another.', offset: 6320 },
    { text: 'There may be a number of reasons for the spread of misinformation.', offset: 6400 },
    { text: 'What does the writer say about the role of technology?', offset: 6480 },
    { text: 'It may at some point provide us with a solution to misinformation.', offset: 6550 },
    { text: 'It could fundamentally alter the way in which people regard information.', offset: 6630 },
    { text: 'It has changed the way in which organisations use misinformation.', offset: 6710 },
    { text: 'It has made it easier for people to check whether information is accurate.', offset: 6790 },
    { text: 'What is the writer doing in the fourth paragraph?', offset: 6870 },
    { text: 'comparing the different opinions people have of misinformation.', offset: 6930 },
    { text: 'explaining how the effects of misinformation have changed over time', offset: 7000 },
    { text: 'outlining which issues connected with misinformation are significant today', offset: 7080 },
    { text: 'describing the attitude of policy makers towards misinformation in the media', offset: 7160 },
    { text: 'What point does the writer make about regulation in the USA?', offset: 7250 },
    { text: 'The guidelines issued by the FDA need to be simplified.', offset: 7320 },
    { text: 'Regulation does not affect people\u2019s opinions of new prescription drugs.', offset: 7390 },
    { text: 'The USA has more regulatory bodies than most other countries.', offset: 7470 },
    { text: 'Regulation fails to prevent misinformation from appearing in the media.', offset: 7550 },
    // Q31-36
    { text: 'Questions 31\u201336', offset: 7650 },
    { text: 'Complete the summary using the list of phrases, A\u2013J, below', offset: 7670 },
    { text: 'Write the correct letter, A\u2013J, in boxes 31\u201336 on your answer sheet.', offset: 7740 },
    { text: 'What happens when people encounter misinformation?', offset: 7820 },
    { text: 'Although people have ', offset: 7880 },
    {
        text: ' to misinformation, there is debate about precisely how and when we label something as true or untrue. The philosophers Descartes and Spinoza had ',
        offset: 7910,
    },
    {
        text: ' about how people engage with information. While Descartes believed that people accept or reject information after considering whether it is true or not, Spinoza argued that people accepted all information they encountered (and by default misinformation) and did not verify or reject it until afterwards. Moreover, Spinoza believes that a distinct ',
        offset: 8080,
    },
    { text: ' is involved in these stages. Recent research has provided ', offset: 8450 },
    {
        text: ' for Spinoza\u2019s theory and it would appear that people accept all encountered information as if it were true, even if this is for an extremely ',
        offset: 8520,
    },
    {
        text: ', and do not label the information as true or false until later. This is consistent with the fact that the resources for scepticism and the resources for perceiving and encoding are in ',
        offset: 8670,
    },
    { text: ' in the brain.', offset: 8860 },
    { text: 'A constant conflict', offset: 8900 },
    { text: 'B additional evidence', offset: 8930 },
    { text: 'C different locations', offset: 8960 },
    { text: 'D experimental subjects', offset: 8990 },
    { text: 'E short period', offset: 9020 },
    { text: 'F extreme distrust', offset: 9050 },
    { text: 'G frequent exposure', offset: 9080 },
    { text: 'H mental operation', offset: 9110 },
    { text: 'I dubious reason', offset: 9140 },
    { text: 'J different ideas', offset: 9170 },
    // Q37-40
    { text: 'Questions 37\u201340', offset: 9220 },
    { text: 'Do the following statements agree with the claims of the writer in Reading Passage 3?', offset: 9240 },
    { text: 'In boxes 37\u201340 on your answer sheet, write', offset: 9330 },
    { text: 'if the statement agrees with the claims of the writer', offset: 9380 },
    { text: 'if the statement contradicts the claims of the writer', offset: 9440 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 9500 },
    {
        text: 'Campaigns designed to correct misinformation will fail to achieve their purpose if people are unable to understand them.',
        offset: 9570,
    },
    { text: 'Attempts to teach elementary school students about misinformation have been opposed.', offset: 9700 },
    { text: 'It may be possible to overcome the problem of misinformation in a relatively short period.', offset: 9800 },
    { text: 'The need to keep up with new information is hugely exaggerated in today\u2019s world.', offset: 9900 },
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
                                            :data-segment-text="'The persistence and peril of misinformation'"
                                            v-html="getHighlightedSegment('The persistence and peril of misinformation')"
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
                                <!-- Questions 27-30: Multiple Choice -->
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
                                                        :data-segment-text="'What point does the writer make about misinformation in the first paragraph?'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'What point does the writer make about misinformation in the first paragraph?',
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
                                                                :data-segment-text="'Misinformation is a relatively recent phenomenon.'"
                                                                v-html="getHighlightedSegment('Misinformation is a relatively recent phenomenon.')"
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
                                                                :data-segment-text="'Some people find it easy to identify misinformation.'"
                                                                v-html="getHighlightedSegment('Some people find it easy to identify misinformation.')"
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
                                                                :data-segment-text="'Misinformation changes as it is passed from one person to another.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Misinformation changes as it is passed from one person to another.',
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
                                                                :data-segment-text="'There may be a number of reasons for the spread of misinformation.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'There may be a number of reasons for the spread of misinformation.',
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
                                                        :data-segment-text="'What does the writer say about the role of technology?'"
                                                        v-html="getHighlightedSegment('What does the writer say about the role of technology?')"
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
                                                                :data-segment-text="'It may at some point provide us with a solution to misinformation.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'It may at some point provide us with a solution to misinformation.',
                                                                    )
                                                                "
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
                                                                :data-segment-text="'It could fundamentally alter the way in which people regard information.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'It could fundamentally alter the way in which people regard information.',
                                                                    )
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
                                                                :data-segment-text="'It has changed the way in which organisations use misinformation.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'It has changed the way in which organisations use misinformation.',
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
                                                                :data-segment-text="'It has made it easier for people to check whether information is accurate.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'It has made it easier for people to check whether information is accurate.',
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
                                                        :data-segment-text="'What is the writer doing in the fourth paragraph?'"
                                                        v-html="getHighlightedSegment('What is the writer doing in the fourth paragraph?')"
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
                                                                :data-segment-text="'comparing the different opinions people have of misinformation.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'comparing the different opinions people have of misinformation.',
                                                                    )
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
                                                                :data-segment-text="'explaining how the effects of misinformation have changed over time'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'explaining how the effects of misinformation have changed over time',
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
                                                                :data-segment-text="'outlining which issues connected with misinformation are significant today'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'outlining which issues connected with misinformation are significant today',
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
                                                                :data-segment-text="'describing the attitude of policy makers towards misinformation in the media'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'describing the attitude of policy makers towards misinformation in the media',
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
                                                        :data-segment-text="'What point does the writer make about regulation in the USA?'"
                                                        v-html="getHighlightedSegment('What point does the writer make about regulation in the USA?')"
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
                                                                :data-segment-text="'The guidelines issued by the FDA need to be simplified.'"
                                                                v-html="
                                                                    getHighlightedSegment('The guidelines issued by the FDA need to be simplified.')
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
                                                                :data-segment-text="'Regulation does not affect people\u2019s opinions of new prescription drugs.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Regulation does not affect people\u2019s opinions of new prescription drugs.',
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
                                                                :data-segment-text="'The USA has more regulatory bodies than most other countries.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'The USA has more regulatory bodies than most other countries.',
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
                                                                :data-segment-text="'Regulation fails to prevent misinformation from appearing in the media.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Regulation fails to prevent misinformation from appearing in the media.',
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

                                <!-- Questions 31-36: Summary Completion with Phrases -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-31-36" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 31\u201336'"
                                            v-html="getHighlightedSegment('Questions 31\u201336')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the summary using the list of phrases, A\u2013J, below'"
                                                v-html="getHighlightedSegment('Complete the summary using the list of phrases, A\u2013J, below')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013J, in boxes 31\u201336 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013J, in boxes 31\u201336 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <!-- Summary text with blanks -->
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-lg">
                                            <h4
                                                class="mb-4 text-center font-bold text-gray-800"
                                                :data-segment-text="'What happens when people encounter misinformation?'"
                                                v-html="getHighlightedSegment('What happens when people encounter misinformation?')"
                                            ></h4>
                                            <div class="leading-relaxed text-gray-700">
                                                <p>
                                                    <span
                                                        :data-segment-text="'Although people have '"
                                                        v-html="getHighlightedSegment('Although people have ')"
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
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']" :key="i" :value="i">
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' to misinformation, there is debate about precisely how and when we label something as true or untrue. The philosophers Descartes and Spinoza had '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' to misinformation, there is debate about precisely how and when we label something as true or untrue. The philosophers Descartes and Spinoza had ',
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
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']" :key="i" :value="i">
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' about how people engage with information. While Descartes believed that people accept or reject information after considering whether it is true or not, Spinoza argued that people accepted all information they encountered (and by default misinformation) and did not verify or reject it until afterwards. Moreover, Spinoza believes that a distinct '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' about how people engage with information. While Descartes believed that people accept or reject information after considering whether it is true or not, Spinoza argued that people accepted all information they encountered (and by default misinformation) and did not verify or reject it until afterwards. Moreover, Spinoza believes that a distinct ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-33"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >33</span
                                                    >
                                                    <select
                                                        v-model="answers.q33"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']" :key="i" :value="i">
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' is involved in these stages. Recent research has provided '"
                                                        v-html="getHighlightedSegment(' is involved in these stages. Recent research has provided ')"
                                                    ></span>
                                                    <span
                                                        id="question-34"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >34</span
                                                    >
                                                    <select
                                                        v-model="answers.q34"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']" :key="i" :value="i">
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' for Spinoza\u2019s theory and it would appear that people accept all encountered information as if it were true, even if this is for an extremely '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' for Spinoza\u2019s theory and it would appear that people accept all encountered information as if it were true, even if this is for an extremely ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-35"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >35</span
                                                    >
                                                    <select
                                                        v-model="answers.q35"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']" :key="i" :value="i">
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="', and do not label the information as true or false until later. This is consistent with the fact that the resources for scepticism and the resources for perceiving and encoding are in '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ', and do not label the information as true or false until later. This is consistent with the fact that the resources for scepticism and the resources for perceiving and encoding are in ',
                                                            )
                                                        "
                                                    ></span>
                                                    <span
                                                        id="question-36"
                                                        class="mx-1 inline-block h-8 w-8 flex-shrink-0 items-center rounded-full bg-green-600 pt-0.5 text-center font-bold text-white shadow-md"
                                                        >36</span
                                                    >
                                                    <select
                                                        v-model="answers.q36"
                                                        class="mx-1 w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']" :key="i" :value="i">
                                                            {{ i }}
                                                        </option>
                                                    </select>
                                                    <span
                                                        :data-segment-text="' in the brain.'"
                                                        v-html="getHighlightedSegment(' in the brain.')"
                                                    ></span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Options list -->
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <div class="grid grid-cols-2 gap-2 text-gray-700 md:grid-cols-3">
                                                <p
                                                    :data-segment-text="'A constant conflict'"
                                                    v-html="getHighlightedSegment('A constant conflict')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'B additional evidence'"
                                                    v-html="getHighlightedSegment('B additional evidence')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'C different locations'"
                                                    v-html="getHighlightedSegment('C different locations')"
                                                ></p>
                                                <p
                                                    :data-segment-text="'D experimental subjects'"
                                                    v-html="getHighlightedSegment('D experimental subjects')"
                                                ></p>
                                                <p :data-segment-text="'E short period'" v-html="getHighlightedSegment('E short period')"></p>
                                                <p :data-segment-text="'F extreme distrust'" v-html="getHighlightedSegment('F extreme distrust')"></p>
                                                <p
                                                    :data-segment-text="'G frequent exposure'"
                                                    v-html="getHighlightedSegment('G frequent exposure')"
                                                ></p>
                                                <p :data-segment-text="'H mental operation'" v-html="getHighlightedSegment('H mental operation')"></p>
                                                <p :data-segment-text="'I dubious reason'" v-html="getHighlightedSegment('I dubious reason')"></p>
                                                <p :data-segment-text="'J different ideas'" v-html="getHighlightedSegment('J different ideas')"></p>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 37-40: YES/NO/NOT GIVEN -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-37-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 37\u201340'"
                                            v-html="getHighlightedSegment('Questions 37\u201340')"
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
                                                :data-segment-text="'In boxes 37\u201340 on your answer sheet, write'"
                                                v-html="getHighlightedSegment('In boxes 37\u201340 on your answer sheet, write')"
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
                                                        :data-segment-text="'Campaigns designed to correct misinformation will fail to achieve their purpose if people are unable to understand them.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Campaigns designed to correct misinformation will fail to achieve their purpose if people are unable to understand them.',
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
                                                        :data-segment-text="'Attempts to teach elementary school students about misinformation have been opposed.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Attempts to teach elementary school students about misinformation have been opposed.',
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
                                                        :data-segment-text="'It may be possible to overcome the problem of misinformation in a relatively short period.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'It may be possible to overcome the problem of misinformation in a relatively short period.',
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
                                                        :data-segment-text="'The need to keep up with new information is hugely exaggerated in today\u2019s world.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The need to keep up with new information is hugely exaggerated in today\u2019s world.',
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
