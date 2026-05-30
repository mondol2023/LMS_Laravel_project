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

const passageText =
    ref(`A few years ago, Fred DeJesus from Brooklyn, New York became the first umpire in a minor league baseball game to use something called the Automated Ball-Strike System (ABS), often referred to as the ‘rob-umpire’. Instead of making any judgments himself about a strike*, DeJesus had decisions fed to him through an earpiece, connected to a modified missile-tracking system. The contraption looked like a large black pizza box with one glowing green eye, it was mounted above the press stand.

Major League Baseball (MLB), who had commissioned the system, wanted human umpires to announce the calls, just as they would have done in the past. When the first pitch came in, a recorded voice told DeJesus it was a strike. Previously, calling a strike was a judgment call on the part of the umpire. Even if the batter does not hit the ball, a pitch that passes through the ‘strike zone’ (an imaginary zone about seventeen inches wide, stretching from the batten’s knees to the middle of his chest) is considered a strike. During that first game, when DeJesus announced calls, there was no heckling and no shouted disagreement. Nobody said a word.

For a hundred and fifty years or so, the strike zone has been the game’s animating force-countless arguments between a team’s manager and the umpire have taken place over its boundaries and whether a ball had crossed through it. The rules of play have evolved in various stages. Today, everyone knows that you may scream your disagreement in an umpire’s face, but you must never shout personal abuse at them or touch them. That’s a no-no. When the robe-umpires came, however, the arguments stopped.

During the first robe-umpire season, players complained about some strange calls. In response, MLB decided to tweak the dimensions of the zone, and the following year the consensus was that ABS is profoundly consistent. MLB says the device is near-perfect, precise to within fractions of an inch. “It’ll reduce controversy in the game, and be good for the game,” says Rob Manfred, who is Commissioner for MLB. But the question is whether controversy is worth reducing, or whether it is the sign of a human hand.

A human, at least, yells back. When I spoke with Frank Viola, a coach for a North Carolina team, he said that ABS works as designed, but that it was also unforgiving and pedantic, almost legalistic. “Manfred is a lawyer,” Viola noted. Some pitchers have complained that, compared with a humans, the robot’s strike zone seems too precise. Viola was once a major-league player himself. When he was pitching, he explained, umpires rewarded skill. “Throw it where you aimed, and it would be a strike, even if it was an inch or two outside. There was a dialogue between pitcher and umpire.”

The executive tasked with running the experiment for MLB is Morgan Sword, who’s in charge of baseball operations. According to Sword, ABS was part of a larger project to make baseball more exciting since executives are terrified of losing younger fans, as has been the case with horse racing and boxing. He explains how they began the process by asking fans what version of baseball they found most exciting. The results showed that everyone wanted more action: more hits, more defense, more baserunning. This type of baseball essentially hasn’t existed since the 1960s, when the hundred-mile-an-hour fastball, which is difficult to hit and control, entered the game. It flattened the game into strikeouts, walks, and home runs-a type of play lacking much action.

Sword’s team brainstormed potential fixes. Any rule that existed, they talked about changing-from changing the bats to changing the geometry of the field. But while all of these were ruled out as potential fixes, ABS was seen as a perfect vehicle for change. According to Sword, once you get the technology right, you can load any strike zone you want into the system. “It might be a triangle, or a blob, or something shaped like Texas. Over time, as baseball evolves, ABS can allow the zone to change with it.”

“In the past twenty years, sports have moved away from judgment calls. Soccer has Video Assistant Referees (for offside decisions, for example). Tennis has Hawk-Eye (for line calls, for example). For almost a decade, baseball has used instant replay on the base paths. This is widely liked, even if the precision can sometimes cause problems. But these applications deal with something physical: bases, lines, goals. The boundaries of action are precise, delineated like the keys of a piano. This is not the case with ABS and the strike zone. Historically, a certain discretion has been appreciated.”

I decided to email Alva Noe, a professor at Berkeley University and a baseball fan, for his opinion. “Hardly a day goes by that I don’t wake up and run through the reasons that this [robe-umpires] is such a terrible idea,” he replied. He later told me, “This is part of a movement to use algorithms to take the hard choices of living out of life.” Perhaps he’s right. We watch baseball to kill time, not to maximize it. Some players I have met take a dissenting stance toward the robots too, believing that accuracy is not the answer. According to Joe Russo, who plays for a New Jersey team, “With technology, people just want everything to be perfect. That’s not reality. I think perfect would be weird. Your teams are always winning, work is always just great, there’s always money in your pocket, your car never breaks down. What is there to talk about?”

*strike: a strike is when the batter swings at a ball and misses or when the batter does not swing at a ball that passes through the strike zone.`);

const textSegments = ref([
    { text: passageText.value, offset: 0 },
    { text: 'Questions 27-32', offset: 5700 },
    { text: 'Do the following statements agree with the claims of the writer in Reading Passage 3?', offset: 5718 },
    { text: 'In boxes 27-32 on your answer sheet, write', offset: 5798 },
    { text: 'YES', offset: 5845 },
    { text: 'if the statement agrees with the claims of the writer', offset: 5850 },
    { text: 'NO', offset: 5905 },
    { text: 'if the statement contradicts the claims of the writer', offset: 5910 },
    { text: 'NOT GIVEN', offset: 5965 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 5978 },
    { text: 'When DeJesus first used ABS, he shared decision-making about strikes with it.', offset: 6050 },
    { text: 'MLB considered it necessary to amend the size of the strike zone when criticisms were received from players.', offset: 6140 },
    { text: "MLB is keen to justify the money spent on improving the accuracy of ABS's calculations.", offset: 6250 },
    { text: 'The hundred-mile-an-hour fastball led to a more exciting style of play.', offset: 6340 },
    { text: "The differing proposals for alterations to the baseball bat led to fierce debate on Sword's team.", offset: 6420 },
    { text: 'ABS makes changes to the shape of the strike zone feasible.', offset: 6510 },
    { text: 'Questions 33-37', offset: 6570 },
    { text: 'Complete the summary using the list of phrases, A-H, below.', offset: 6588 },
    { text: 'Write the correct letter, A-H, in boxes 33-37 on your answer sheet.', offset: 6655 },
    { text: 'Calls by the umpire', offset: 6730 },
    { text: 'Even after ABS was developed, MLB still wanted human umpires to shout out decisions as they had in their ', offset: 6752 },
    { text: ". The umpire's job had, at one time, required a ", offset: 6850 },
    {
        text: ' about whether a ball was a strike. A ball is considered a strike when the batter does not hit it and it crosses through a ',
        offset: 6920,
    },
    { text: " extending approximately from the batten's knee to his chest. In the past, ", offset: 7040 },
    {
        text: ' over strike calls were not uncommon, but today everyone accepts the complete ban on pushing or shoving the umpire. One difference, however, is that during the first game DeJesus used ABS, strike calls were met with ',
        offset: 7100,
    },
    { text: 'A pitch boundary', offset: 7280 },
    { text: 'B numerous disputes', offset: 7300 },
    { text: 'C team tactics', offset: 7325 },
    { text: 'D subjective assessment', offset: 7345 },
    { text: 'E widespread approval', offset: 7375 },
    { text: 'F former roles', offset: 7400 },
    { text: 'G total silence', offset: 7420 },
    { text: 'H perceived area', offset: 7440 },
    { text: 'Questions 38-40', offset: 7460 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 7478 },
    { text: 'What does the writer suggest about ABS in the fifth paragraph?', offset: 7525 },
    { text: 'A It is bound to make key decisions that are wrong.', offset: 7590 },
    { text: 'B It may reduce some of the appeal of the game.', offset: 7640 },
    { text: 'C It will lead to the disappearance of human umpires.', offset: 7690 },
    { text: 'D It may increase calls for the rules of baseball to be changed.', offset: 7745 },
    { text: 'Morgan Sword says that the introduction of ABS', offset: 7810 },
    { text: 'A was regarded as an experiment without a guaranteed outcome.', offset: 7865 },
    { text: 'B was intended to keep up with developments in other sports.', offset: 7925 },
    { text: 'C was a response to changing attitudes about the role of sport.', offset: 7985 },
    { text: 'D was an attempt to ensure baseball retained a young audience.', offset: 8045 },
    { text: 'Why does the writer include the views of Not and Russo?', offset: 8110 },
    { text: 'A to show that attitudes to technology vary widely', offset: 8170 },
    { text: 'B to argue that people have unrealistic expectations of sport', offset: 8225 },
    { text: 'C to indicate that accuracy is not the same thing as enjoyment', offset: 8285 },
    { text: 'D to suggest that the number of baseball fans needs to increase', offset: 8350 },
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
                            <div class="flex items-center justify-center">
                                <h2
                                    class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                >
                                    <span
                                        :data-segment-text="'Invasion of the Robot Umpires'"
                                        v-html="getHighlightedSegment('Invasion of the Robot Umpires')"
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
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 27-32 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-27-32" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 27-32'"
                                            v-html="getHighlightedSegment('Questions 27-32')"
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
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="n in 6"
                                                :key="n"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >{{ 26 + n }}</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="
                                                            [
                                                                'When DeJesus first used ABS, he shared decision-making about strikes with it.',
                                                                'MLB considered it necessary to amend the size of the strike zone when criticisms were received from players.',
                                                                'MLB is keen to justify the money spent on improving the accuracy of ABS\'s calculations.',
                                                                'The hundred-mile-an-hour fastball led to a more exciting style of play.',
                                                                'The differing proposals for alterations to the baseball bat led to fierce debate on Sword\'s team.',
                                                                'ABS makes changes to the shape of the strike zone feasible.',
                                                            ][n - 1]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'When DeJesus first used ABS, he shared decision-making about strikes with it.',
                                                                    'MLB considered it necessary to amend the size of the strike zone when criticisms were received from players.',
                                                                    'MLB is keen to justify the money spent on improving the accuracy of ABS\'s calculations.',
                                                                    'The hundred-mile-an-hour fastball led to a more exciting style of play.',
                                                                    'The differing proposals for alterations to the baseball bat led to fierce debate on Sword\'s team.',
                                                                    'ABS makes changes to the shape of the strike zone feasible.',
                                                                ][n - 1],
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            :value="'YES'"
                                                            v-model="answers['q' + (26 + n)]"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            :value="'NO'"
                                                            v-model="answers['q' + (26 + n)]"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            :value="'NOT GIVEN'"
                                                            v-model="answers['q' + (26 + n)]"
                                                            class="form-radio h-4 w-4 text-gray-600 focus:ring-gray-500"
                                                        />
                                                        <span class="font-medium text-gray-700">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 33-37 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-33-37" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 33-37'"
                                            v-html="getHighlightedSegment('Questions 33-37')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the summary using the list of phrases, A-H, below.'"
                                                v-html="getHighlightedSegment('Complete the summary using the list of phrases, A-H, below.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <ul class="grid grid-cols-2 gap-x-4 gap-y-2 text-gray-700">
                                                <li v-for="i in ['A', 'B', 'C', 'D']" :key="i">
                                                    <strong class="font-semibold">{{ i }}</strong>
                                                    <span
                                                        :data-segment-text="
                                                            {
                                                                A: 'pitch boundary',
                                                                B: 'numerous disputes',
                                                                C: 'team tactics',
                                                                D: 'subjective assessment',
                                                            }[i]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                {
                                                                    A: 'pitch boundary',
                                                                    B: 'numerous disputes',
                                                                    C: 'team tactics',
                                                                    D: 'subjective assessment',
                                                                }[i],
                                                            )
                                                        "
                                                    ></span>
                                                </li>
                                                <li v-for="i in ['E', 'F', 'G', 'H']" :key="i">
                                                    <strong class="font-semibold">{{ i }}</strong>
                                                    <span
                                                        :data-segment-text="
                                                            { E: 'widespread approval', F: 'former roles', G: 'total silence', H: 'perceived area' }[
                                                                i
                                                            ]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                {
                                                                    E: 'widespread approval',
                                                                    F: 'former roles',
                                                                    G: 'total silence',
                                                                    H: 'perceived area',
                                                                }[i],
                                                            )
                                                        "
                                                    ></span>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="space-y-4 rounded-lg bg-white/50 p-4">
                                            <h4
                                                class="text-center font-bold text-green-800"
                                                :data-segment-text="'Calls by the umpire'"
                                                v-html="getHighlightedSegment('Calls by the umpire')"
                                            ></h4>
                                            <p class="leading-relaxed text-gray-700">
                                                <span
                                                    :data-segment-text="'Even after ABS was developed, MLB still wanted human umpires to shout out decisions as they had in their '"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Even after ABS was developed, MLB still wanted human umpires to shout out decisions as they had in their ',
                                                        )
                                                    "
                                                ></span>
                                                <span
                                                    class="inline-block h-8 w-8 flex-shrink-0 rounded-full bg-green-600 pt-1 text-center font-semibold text-white shadow-lg"
                                                    >33</span
                                                >
                                                <select
                                                    v-model="answers.q33"
                                                    class="m-1 w-24 rounded-md border border-green-300 p-1 shadow-sm focus:border-green-500 focus:ring-green-500/50"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                                <span
                                                    :data-segment-text="'. The umpire\'s job had, at one time, required a '"
                                                    v-html="getHighlightedSegment('. The umpire\'s job had, at one time, required a ')"
                                                ></span>
                                                <span
                                                    class="inline-block h-8 w-8 flex-shrink-0 rounded-full bg-green-600 pt-1 text-center font-semibold text-white shadow-lg"
                                                    >34</span
                                                >
                                                <select
                                                    v-model="answers.q34"
                                                    class="mx-1 w-24 rounded-md border border-green-300 p-1 shadow-sm focus:border-green-500 focus:ring-green-500/50"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                                <span
                                                    :data-segment-text="' about whether a ball was a strike. A ball is considered a strike when the batter does not hit it and it crosses through a '"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            ' about whether a ball was a strike. A ball is considered a strike when the batter does not hit it and it crosses through a ',
                                                        )
                                                    "
                                                ></span>
                                                <span
                                                    class="inline-block h-8 w-8 flex-shrink-0 rounded-full bg-green-600 pt-1 text-center font-semibold text-white shadow-lg"
                                                    >35</span
                                                >
                                                <select
                                                    v-model="answers.q35"
                                                    class="mx-1 w-24 rounded-md border border-green-300 p-1 shadow-sm focus:border-green-500 focus:ring-green-500/50"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                                <span
                                                    :data-segment-text="' extending approximately from the batten\'s knee to his chest. In the past, '"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            ' extending approximately from the batten\'s knee to his chest. In the past, ',
                                                        )
                                                    "
                                                ></span>
                                                <span
                                                    class="inline-block h-8 w-8 flex-shrink-0 rounded-full bg-green-600 pt-1 text-center font-semibold text-white shadow-lg"
                                                    >36</span
                                                >
                                                <select
                                                    v-model="answers.q36"
                                                    class="mx-1 w-24 rounded-md border border-green-300 p-1 shadow-sm focus:border-green-500 focus:ring-green-500/50"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                                <span
                                                    :data-segment-text="' over strike calls were not uncommon, but today everyone accepts the complete ban on pushing or shoving the umpire. One difference, however, is that during the first game DeJesus used ABS, strike calls were met with '"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            ' over strike calls were not uncommon, but today everyone accepts the complete ban on pushing or shoving the umpire. One difference, however, is that during the first game DeJesus used ABS, strike calls were met with ',
                                                        )
                                                    "
                                                ></span>
                                                <span
                                                    class="inline-block h-8 w-8 flex-shrink-0 rounded-full bg-green-600 pt-1 text-center font-semibold text-white shadow-lg"
                                                    >37</span
                                                >
                                                <select
                                                    v-model="answers.q37"
                                                    class="mx-1 w-24 rounded-md border border-green-300 p-1 shadow-sm focus:border-green-500 focus:ring-green-500/50"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </p>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 38-40 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-38-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 38-40'"
                                            v-html="getHighlightedSegment('Questions 38-40')"
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
                                                v-for="(q, n) in [
                                                    [
                                                        'What does the writer suggest about ABS in the fifth paragraph?',
                                                        [
                                                            'A It is bound to make key decisions that are wrong.',
                                                            'B It may reduce some of the appeal of the game.',
                                                            'C It will lead to the disappearance of human umpires.',
                                                            'D It may increase calls for the rules of baseball to be changed.',
                                                        ],
                                                    ],
                                                    [
                                                        'Morgan Sword says that the introduction of ABS',
                                                        [
                                                            'A was regarded as an experiment without a guaranteed outcome.',
                                                            'B was intended to keep up with developments in other sports.',
                                                            'C was a response to changing attitudes about the role of sport.',
                                                            'D was an attempt to ensure baseball retained a young audience.',
                                                        ],
                                                    ],
                                                    [
                                                        'Why does the writer include the views of Not and Russo?',
                                                        [
                                                            'A to show that attitudes to technology vary widely',
                                                            'B to argue that people have unrealistic expectations of sport',
                                                            'C to indicate that accuracy is not the same thing as enjoyment',
                                                            'D to suggest that the number of baseball fans needs to increase',
                                                        ],
                                                    ],
                                                ]"
                                                :key="n"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >{{ 38 + n }}</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="q[0]"
                                                        v-html="getHighlightedSegment(q[0])"
                                                    ></p>
                                                </div>
                                                <div class="mt-4 space-y-2 pl-11">
                                                    <label
                                                        v-for="opt in q[1]"
                                                        :key="opt"
                                                        class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-green-100"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :value="opt[0]"
                                                            v-model="answers['q' + (38 + n)]"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="text-gray-700"
                                                            ><strong class="font-semibold">{{ opt[0] }}</strong>
                                                            <span
                                                                :data-segment-text="opt.substring(1)"
                                                                v-html="getHighlightedSegment(opt.substring(1))"
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
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #10b981, #059669);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #059669, #047857);
}
</style>
