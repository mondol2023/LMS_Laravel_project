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
const PANEL_WIDTH_KEY = 'reading-ielts009-part1-panel-width';
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
});

const passageText = `Despite the specialization of scientific research, amateurs still have an important role to play. During the scientific revolution of the 17th century, scientists were largely men of private means who pursued their interest in natural philosophy for their own edification. Only in the past century or two has it become possible to make a living from investigating the workings of nature. Modern science was, in other words, built on the work of amateurs. Today, science is an increasingly specialized and compartmentalized subject, the domain of experts who know more and more about less and less. Perhaps surprisingly, however, amateurs – even those without private means – are still important.

A recent poll carried out at a meeting of the American Association for the Advancement of Science by astronomer Dr Richard Fienberg found that, in addition to his field of astronomy, amateurs are actively involved in such field as acoustics, horticulture, ornithology, meteorology, hydrology and palaeontology. Far from being crackpots, amateur scientists are often in close touch with professionals, some of whom rely heavily on their co-operation.

Admittedly, some fields are more open to amateurs than others. Anything that requires expensive equipment is clearly a no-go area. And some kinds of research can be dangerous; most amateur chemists, jokes Dr Fienberg, are either locked up or have blown themselves to bits. But amateurs can make valuable contributions in fields from rocketry to palaeontology and the rise of the Internet has made it easier than before to collect data and distribute results.

Exactly which field of study has benefited most from the contributions of amateurs is a matter of some dispute. Dr Fienberg makes a strong case for astronomy. There is, he points out, a long tradition of collaboration between amateur and professional sky watchers. Numerous comets, asteroids and even the planet Uranus were discovered by amateurs. Today, in addition to comet and asteroid spotting, amateurs continue to do valuable work observing the brightness of variable stars and detecting novae-‘new’ stars in the Milky Way and supernovae in other galaxies. Amateur observers are helpful, says Dr Fienberg, because there are so many of them (they far outnumber professionals) and because they are distributed all over the world. This makes special kinds of observations possible:’ if several observers around the world accurately record the time when a star is eclipsed by an asteroid, for example, it is possible to derive useful information about the asteroid’s shape.

Another field in which amateurs have traditionally played an important role is palaeontology. Adrian Hunt, a palaeontologist at Mesa Technical College in New Mexico, insists that this is the field in which amateurs have made the biggest contribution. Despite the development of high-tech equipment, he says, the best sensors for finding fossils are human eyes – lots of them.

Finding volunteers to look for fossils is not difficult, he says, because of the near universal interest in anything to do with dinosaurs. As well as helping with this research, volunteers learn about science, a process he calls ‘recreational education’.

Rick Bonney of the Cornell Laboratory of Ornithology in Ithaca, New York, contends that amateurs have contributed the most in his field. There are, he notes, thought to be as many as 60 million birdwatchers in America alone. Given their huge numbers and the wide geographical coverage they provide, Mr Bonney has enlisted thousands of amateurs in a number of research projects. Over the past few years, their observations have uncovered previously unknown trends and cycles in bird migrations and revealed declines in the breeding populations of several species of migratory birds, prompting a habitat conservation programme. Despite the successes and whatever the field of study, collaboration between amateurs and professionals is not without its difficulties. Not everyone, for example, is happy with the term ‘amateur’. Mr Bonney has coined the term ‘citizen scientist’ because he felt that other words, such as ‘volunteer’ sounded disparaging. A more serious problem is the question of how professionals can best acknowledge the contributions made by amateurs. Dr Fienberg says that some amateur astronomers are happy to provide their observations but grumble about not being reimbursed for out-of-pocket expenses. Others feel let down when their observations are used in scientific papers, but they are not listed as co-authors. Dr Hunt says some amateur palaeontologists are disappointed when told that they cannot take finds home with them.

These are legitimate concerns but none seems insurmountable. Provided amateurs and professionals agree the terms on which they will work together beforehand, there is no reason why co-operation between the two groups should not flourish. Last year Dr S. Carlson, founder of the Society for Amateur Scientists won an award worth $290,000 for his work in promoting such co-operation. He says that one of the main benefits of the prize is the endorsement it has given to the contributions of amateur scientists, which has done much to silence critics among those professionals who believe science should remain their exclusive preserve.

At the moment, says Dr Carlson, the society is involved in several schemes including an innovative rocket- design project and the setting up of a network of observers who will search for evidence of a link between low- frequency radiation and earthquakes. The amateurs, he says, provide enthusiasm and talent, while the professionals provide guidance ‘so that anything they do discover will be taken seriously’. Having laid the foundations of science, amateurs will have much to contribute to its ever – expanding edifice.`;

const textSegments = (() => {
    const segments: Array<{ text: string; offset: number }> = [];
    let offset = 0;

    function addSegment(text: string) {
        segments.push({ text, offset });
        offset += text.length;
    }

    addSegment(passageText);
    addSegment('READING PASSAGE 1');
    addSegment('You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.');
    addSegment('In Praise of Amateurs');
    addSegment('QUESTIONS');
    addSegment('Answer all questions based on Reading Passage 1');
    addSegment('Questions 1-8');
    addSegment(
        'Complete the summary below. Choose ONE /TWO WORDS from the passage for each answer. Write your answers in boxes 1-8 on your answer sheet.',
    );
    addSegment('Prior to the 19th century, professional');
    addSegment('did not exist and scientific research was largely carried out by amateurs. However, while');
    addSegment(
        'today is mostly the domain of professionals, a recent US survey highlighted the fact that amateurs play an important role in at least seven',
    );
    addSegment('and indeed many professionals are reliant on their');
    addSegment('. In areas such as astronomy, amateurs can be invaluable when making specific');
    addSegment(
        'on a global basis. Similarly, in the area of palaeontology their involvement is invaluable and helpers are easy to recruit because of the popularity of',
    );
    addSegment('. Amateur bird watchers also play an active role and their work has led to the establishment of a');
    addSegment(
        '. Occasionally the term amateur has been the source of disagreement and alternative names have been suggested but generally speaking, as long as the professional scientists',
    );
    addSegment('the work of the non-professionals, the two groups can work productively together.');
    addSegment('Questions 9-13');
    addSegment('Reading Passage 1 contains a number of opinions provided by four different scientists.');
    addSegment('Match each opinion (Questions 9-13) with the scientists A-D.');
    addSegment('NB. You may use any of the scientists A-D more than once.');
    addSegment('Name of Scientists');
    addSegment('A. Dr Fienberg');
    addSegment('B. Adrian Hunt');
    addSegment('C. Rick Bonney');
    addSegment('D. Dr Carlson');
    addSegment('Amateur involvement can also be an instructive pastime.');
    addSegment('Amateur scientists are prone to accidents.');
    addSegment('Science does not belong to professional scientists alone.');
    addSegment('In certain areas of my work, people are a more valuable resource than technology.');
    addSegment('It is important to give amateurs a name which reflects the value of their work.');

    return ref(segments);
})();

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
                                            :data-segment-text="'In Praise of Amateurs'"
                                            v-html="getHighlightedSegment('In Praise of Amateurs')"
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
                                <!-- Questions 1-10 -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3
                                                class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-lg font-bold text-transparent"
                                                :data-segment-text="'Questions 1-8'"
                                                v-html="getHighlightedSegment('Questions 1-8')"
                                            ></h3>
                                        </div>
                                        <p
                                            class="mb-4 text-base leading-relaxed text-gray-700 sm:text-lg"
                                            :data-segment-text="'Complete the summary below. Choose ONE /TWO WORDS from the passage for each answer. Write your answers in boxes 1-8 on your answer sheet.'"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Complete the summary below. Choose ONE /TWO WORDS from the passage for each answer. Write your answers in boxes 1-8 on your answer sheet.',
                                                )
                                            "
                                        ></p>

                                        <div class="space-y-4 text-base leading-relaxed text-gray-800 sm:text-lg">
                                            <p id="reading-paragraph" class="space-y-4">
                                                <span
                                                    :data-segment-text="'Prior to the 19th century, professional'"
                                                    v-html="getHighlightedSegment('Prior to the 19th century, professional')"
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <spans
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >1</spans
                                                    >
                                                    <input
                                                        v-model="answers.q1"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    />
                                                </span>
                                                <span
                                                    :data-segment-text="'did not exist and scientific research was largely carried out by amateurs. However, while'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'did not exist and scientific research was largely carried out by amateurs. However, while',
                                                        )
                                                    "
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <span
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >2</span
                                                    >
                                                    <input
                                                        v-model="answers.q2"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    />
                                                </span>
                                                <span
                                                    :data-segment-text="'today is mostly the domain of professionals, a recent US survey highlighted the fact that amateurs play an important role in at least seven'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'today is mostly the domain of professionals, a recent US survey highlighted the fact that amateurs play an important role in at least seven',
                                                        )
                                                    "
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <span
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >3</span
                                                    >
                                                    <input
                                                        v-model="answers.q3"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    />
                                                </span>
                                                <span
                                                    :data-segment-text="'and indeed many professionals are reliant on their'"
                                                    v-html="getHighlightedSegment('and indeed many professionals are reliant on their')"
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <span
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >4</span
                                                    >
                                                    <input
                                                        v-model="answers.q4"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    /> </span
                                                ><span
                                                    :data-segment-text="'. In areas such as astronomy, amateurs can be invaluable when making specific'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            '. In areas such as astronomy, amateurs can be invaluable when making specific',
                                                        )
                                                    "
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <span
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >5</span
                                                    >
                                                    <input
                                                        v-model="answers.q5"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    />
                                                </span>
                                                <span
                                                    :data-segment-text="'on a global basis. Similarly, in the area of palaeontology their involvement is invaluable and helpers are easy to recruit because of the popularity of'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'on a global basis. Similarly, in the area of palaeontology their involvement is invaluable and helpers are easy to recruit because of the popularity of',
                                                        )
                                                    "
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <span
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >6</span
                                                    >
                                                    <input
                                                        v-model="answers.q6"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    /> </span
                                                ><span
                                                    :data-segment-text="'. Amateur bird watchers also play an active role and their work has led to the establishment of a'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            '. Amateur bird watchers also play an active role and their work has led to the establishment of a',
                                                        )
                                                    "
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <span
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >7</span
                                                    >
                                                    <input
                                                        v-model="answers.q7"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    /> </span
                                                ><span
                                                    :data-segment-text="'. Occasionally the term amateur has been the source of disagreement and alternative names have been suggested but generally speaking, as long as the professional scientists'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            '. Occasionally the term amateur has been the source of disagreement and alternative names have been suggested but generally speaking, as long as the professional scientists',
                                                        )
                                                    "
                                                ></span>
                                                <span class="mx-1 inline-flex items-center space-x-2">
                                                    <span
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-base font-bold text-white shadow-md"
                                                        >8</span
                                                    >
                                                    <input
                                                        v-model="answers.q8"
                                                        type="text"
                                                        class="w-32 rounded-full border border-blue-300 bg-blue-50 px-3 py-1 text-center font-medium focus:border-indigo-500 focus:bg-white focus:outline-none"
                                                        placeholder="____"
                                                    />
                                                </span>
                                                <span
                                                    :data-segment-text="'the work of the non-professionals, the two groups can work productively together.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'the work of the non-professionals, the two groups can work productively together.',
                                                        )
                                                    "
                                                ></span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Questions 11-14 -->
                                    <div
                                        class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                    >
                                        <div>
                                            <div class="mb-4 flex items-center space-x-2">
                                                <h3
                                                    class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-lg font-bold text-transparent"
                                                    :data-segment-text="'Questions 9-13'"
                                                    v-html="getHighlightedSegment('Questions 9-13')"
                                                ></h3>
                                            </div>
                                            <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-lg">
                                                <span
                                                    :data-segment-text="'Reading Passage 1 contains a number of opinions provided by four different scientists.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Reading Passage 1 contains a number of opinions provided by four different scientists.',
                                                        )
                                                    "
                                                ></span
                                                ><br />
                                                <span
                                                    :data-segment-text="'Match each opinion (Questions 9-13) with the scientists A-D.'"
                                                    v-html="getHighlightedSegment('Match each opinion (Questions 9-13) with the scientists A-D.')"
                                                ></span
                                                ><br />
                                                <strong
                                                    :data-segment-text="'NB. You may use any of the scientists A-D more than once.'"
                                                    v-html="getHighlightedSegment('NB. You may use any of the scientists A-D more than once.')"
                                                ></strong>
                                            </p>
                                        </div>
                                        <!-- Scientist Names Reference -->
                                        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                            <h2
                                                class="mb-2 text-lg font-bold text-blue-700"
                                                :data-segment-text="'Name of Scientists'"
                                                v-html="getHighlightedSegment('Name of Scientists')"
                                            ></h2>
                                            <ul class="space-y-1 font-medium text-gray-700">
                                                <li :data-segment-text="'A. Dr Fienberg'" v-html="getHighlightedSegment('A. Dr Fienberg')"></li>
                                                <li :data-segment-text="'B. Adrian Hunt'" v-html="getHighlightedSegment('B. Adrian Hunt')"></li>
                                                <li :data-segment-text="'C. Rick Bonney'" v-html="getHighlightedSegment('C. Rick Bonney')"></li>
                                                <li :data-segment-text="'D. Dr Carlson'" v-html="getHighlightedSegment('D. Dr Carlson')"></li>
                                            </ul>
                                        </div>

                                        <!-- Questions 9–13 -->
                                        <div class="space-y-4">
                                            <!-- Q9 -->
                                            <div id="question-9" class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-sm">
                                                <div class="flex items-start gap-4">
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 px-3"
                                                    >
                                                        <span class="text-base font-bold text-white">9</span>
                                                    </div>

                                                    <div class="grid w-full grid-cols-12 items-center gap-2">
                                                        <div class="col-span-3">
                                                            <select
                                                                v-model="answers.q9"
                                                                class="w-full rounded-full border-2 border-blue-200 bg-blue-50 px-3 py-1 text-base transition-colors focus:border-blue-500 focus:bg-white focus:outline-none sm:text-lg"
                                                            >
                                                                <option value="">Select...</option>
                                                                <option value="A">A</option>
                                                                <option value="B">B</option>
                                                                <option value="C">C</option>
                                                                <option value="D">D</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-span-9">
                                                            <p
                                                                class="text-base leading-relaxed font-medium text-gray-700 sm:text-lg"
                                                                :data-segment-text="'Amateur involvement can also be an instructive pastime.'"
                                                                v-html="
                                                                    getHighlightedSegment('Amateur involvement can also be an instructive pastime.')
                                                                "
                                                            ></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Q10 -->
                                            <div id="question-10" class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-sm">
                                                <div class="flex items-start gap-4">
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 px-3"
                                                    >
                                                        <span class="text-sm font-bold text-white">10</span>
                                                    </div>

                                                    <div class="grid w-full grid-cols-12 items-center gap-2">
                                                        <div class="col-span-3">
                                                            <select
                                                                v-model="answers.q10"
                                                                class="w-full rounded-full border-2 border-blue-200 bg-blue-50 px-3 py-1 text-base transition-colors focus:border-blue-500 focus:bg-white focus:outline-none sm:text-lg"
                                                            >
                                                                <option value="">Select...</option>
                                                                <option value="A">A</option>
                                                                <option value="B">B</option>
                                                                <option value="C">C</option>
                                                                <option value="D">D</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-span-9">
                                                            <p
                                                                class="text-base leading-relaxed font-medium text-gray-700 sm:text-lg"
                                                                :data-segment-text="'Amateur scientists are prone to accidents.'"
                                                                v-html="getHighlightedSegment('Amateur scientists are prone to accidents.')"
                                                            ></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Q11 -->
                                            <div id="question-11" class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-sm">
                                                <div class="flex items-start gap-4">
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 px-3 shadow-md"
                                                    >
                                                        <span class="text-sm font-bold text-white">11</span>
                                                    </div>

                                                    <div class="grid w-full grid-cols-12 items-center gap-2">
                                                        <div class="col-span-3">
                                                            <select
                                                                v-model="answers.q11"
                                                                class="w-full rounded-full border-2 border-blue-200 bg-blue-50 px-3 py-1 text-base transition-colors focus:border-blue-500 focus:bg-white focus:outline-none sm:text-lg"
                                                            >
                                                                <option value="">Select...</option>
                                                                <option value="A">A</option>
                                                                <option value="B">B</option>
                                                                <option value="C">C</option>
                                                                <option value="D">D</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-span-9">
                                                            <p
                                                                class="text-base leading-relaxed font-medium text-gray-700 sm:text-lg"
                                                                :data-segment-text="'Science does not belong to professional scientists alone.'"
                                                                v-html="
                                                                    getHighlightedSegment('Science does not belong to professional scientists alone.')
                                                                "
                                                            ></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Q12 -->
                                            <div id="question-12" class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-sm">
                                                <div class="flex items-start gap-4">
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 px-3 shadow-md"
                                                    >
                                                        <span class="text-sm font-bold text-white">12</span>
                                                    </div>

                                                    <div class="grid w-full grid-cols-12 items-center gap-2">
                                                        <div class="col-span-3">
                                                            <select
                                                                v-model="answers.q12"
                                                                class="w-full rounded-full border-2 border-blue-200 bg-blue-50 px-3 py-1 text-base transition-colors focus:border-blue-500 focus:bg-white focus:outline-none sm:text-lg"
                                                            >
                                                                <option value="">Select...</option>
                                                                <option value="A">A</option>
                                                                <option value="B">B</option>
                                                                <option value="C">C</option>
                                                                <option value="D">D</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-span-9">
                                                            <p
                                                                class="text-base leading-relaxed font-medium text-gray-700 sm:text-lg"
                                                                :data-segment-text="'In certain areas of my work, people are a more valuable resource than technology.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'In certain areas of my work, people are a more valuable resource than technology.',
                                                                    )
                                                                "
                                                            ></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Q13 -->
                                            <div id="question-13" class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-sm">
                                                <div class="flex items-start gap-4">
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 px-3 shadow-md"
                                                    >
                                                        <span class="text-sm font-bold text-white">13</span>
                                                    </div>

                                                    <div class="grid w-full grid-cols-12 items-center gap-2">
                                                        <div class="col-span-3">
                                                            <select
                                                                v-model="answers.q13"
                                                                class="w-full rounded-full border-2 border-blue-200 bg-blue-50 px-3 py-1 text-base transition-colors focus:border-blue-500 focus:bg-white focus:outline-none sm:text-lg"
                                                            >
                                                                <option value="">Select...</option>
                                                                <option value="A">A</option>
                                                                <option value="B">B</option>
                                                                <option value="C">C</option>
                                                                <option value="D">D</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-span-9">
                                                            <p
                                                                class="text-base leading-relaxed font-medium text-gray-700 sm:text-lg"
                                                                :data-segment-text="'It is important to give amateurs a name which reflects the value of their work.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'It is important to give amateurs a name which reflects the value of their work.',
                                                                    )
                                                                "
                                                            ></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
