<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
const PANEL_WIDTH_KEY = 'reading-ielts011-part3-panel-width';
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
    ref(`Some of the most important decisions of our lives occur while we’re feeling stressed and anxious. From medical decisions to financial and professional ones, we are all sometimes required to weigh up information under stressful conditions. But do we become better or worse at processing and using information under such circumstances?

My colleague and I, both neuroscientists, wanted to investigate how the mind operates under stress, so we visited some local fire stations. Firefighters’ workdays vary quite a bit. Some are pretty relaxed; they’ll spend their time washing the truck, cleaning equipment, cooking meals and reading. Other days can be hectic, with numerous life- threatening incidents to attend to; they’ll enter burning homes to rescue trapped residents, and assist with medical emergencies. These ups and downs presented the perfect setting for an experiment on how people’s ability to use information changes when they feel under pressure.

We found that perceived threat acted as a trigger for a stress reaction that made the task of processing information easier for the firefighters – but only as long as it conveyed bad news.

This is how we arrived at these results. We asked the firefighters to estimate their likelihood of experiencing 40 different adverse events in their life, such as being involved in an accident or becoming a victim of card fraud. We then gave them either good news (that their likelihood of experiencing these events was lower than they’d thought) or bad news (that it was higher) and asked them to provide new estimates.

People are normally quite optimistic – they will ignore bad news and embrace the good. This is what happened when the firefighters were relaxed; but when they were under stress, a different pattern emerged. Under these conditions, they became hyper-vigilant to bad news, even when it had nothing to do with their job (such as learning that the likelihood of card fraud was higher than they’d thought), and altered their beliefs in response. In contrast, stress didn’t change how they responded to good news (such as learning that the likelihood of card fraud was lower than they’d thought).

Back in our lab, we observed the same pattern in students who were told they had to give a surprise public speech, which would be judged by a panel, recorded and posted online. Sure enough, their cortisol levels spiked, their heart rates went up and they suddenly became better at processing unrelated, yet alarming, information about rates of disease and violence.

When we experience stressful events, a physiological change is triggered that causes us to take in warnings and focus on what might go wrong. Brain imaging reveals that this ‘switch’ is related to a sudden boost in a neural signal important for learning, specifically in response to unexpected warning signs, such as faces expressing fear.

Such neural engineering could have helped prehistoric humans to survive. When our ancestors found themselves surrounded by hungry animals, they would have benefited from an increased ability to learn about hazards. In a safe environment, however, it would have been wasteful to be on high alert constantly. So, a neural switch that automatically increases or decreases our ability to process warnings in response to changes in our environment could have been useful. In fact, people with clinical depression and anxiety seem unable to switch away from a state in which they absorb all the negative messages around them.

It is also important to realise that stress travels rapidly from one person to the next. If a co-worker is stressed, we are more likely to tense up and feel stressed ourselves. We don’t even need to be in the same room with someone for their emotions to influence our behaviour. Studies show that if we observe positive feeds on social media, such as images of a pink sunset, we are more likely to post uplifting messages ourselves. If we observe negative posts, such as complaints about a long queue at the coffee shop, we will in turn create more negative posts.

In some ways, many of us now live as if we are in danger, constantly ready to tackle demanding emails and text messages, and respond to news alerts and comments on social media. Repeatedly checking your phone, according to a survey conducted by the American Psychological Association, is related to stress. In other words, a pre- programmed physiological reaction, which evolution has equipped us with to help us avoid famished predators, is now being triggered by an online post. Social media posting, according to one study, raises your pulse, makes you sweat, and enlarges your pupils more than most daily activities.

The fact that stress increases the likelihood that we will focus more on alarming messages, together with the fact that it spreads extremely rapidly, can create collective fear that is not always justified. After a stressful public event, such as a natural disaster or major financial crash, there is often a wave of alarming information in traditional and social media, which individuals become very aware of. But that has the effect of exaggerating existing danger. And so, a reliable pattern emerges – stress is triggered, spreading from one person to the next, which temporarily enhances the likelihood that people will take in negative reports, which increases stress further.

As a result, trips are cancelled, even if the disaster took place across the globe; stocks are sold, even when holding on is the best thing to do. The good news, however, is that positive emotions, such as hope, are contagious too, and are powerful in inducing people to act to find solutions. Being aware of the close relationship between people’s emotional state and how they process information can help us frame our messages more effectively and become conscientious agents of change.`);

const textSegments = ref([
    { text: passageText.value, offset: 0 },
    { text: 'READING PASSAGE 3', offset: 5693 },
    { text: 'How stress affects our judgement', offset: 5712 },
    { text: 'Questions 27–30', offset: 5747 },
    { text: 'Choose the correct letter, A, B, C or D.', offset: 5762 },
    { text: 'Write the correct letter in boxes 27–30 on your answer sheet.', offset: 5804 },
    { text: 'In the first paragraph, the writer introduces the topic of the text by', offset: 5869 },
    { text: 'defining some commonly used terms.', offset: 5937 },
    { text: 'questioning a widely held assumption.', offset: 5976 },
    { text: 'mentioning a challenge faced by everyone.', offset: 6017 },
    { text: 'specifying a situation which makes us most anxious.', offset: 6062 },
    { text: 'What point does the writer make about firefighters in the second paragraph?', offset: 6116 },
    { text: 'The regular changes of stress levels in their working lives make them ideal study subjects.', offset: 6195 },
    { text: 'The strategies they use to handle stress are of particular interest to researchers.', offset: 6289 },
    { text: 'The stressful nature of their job is typical of many public service professions.', offset: 6377 },
    { text: 'Their personalities make them especially well-suited to working under stress.', offset: 6458 },
    { text: 'What is the writer doing in the fourth paragraph?', offset: 6542 },
    { text: 'explaining their findings', offset: 6592 },
    { text: 'justifying their approach', offset: 6619 },
    { text: 'setting out their objectives', offset: 6647 },
    { text: 'describing their methodology', offset: 6678 },
    { text: 'In the seventh paragraph, the writer describes a mechanism in the brain which', offset: 6710 },
    { text: 'enables people to respond more quickly to stressful situations.', offset: 6788 },
    { text: 'results in increased ability to control our levels of anxiety.', offset: 6857 },
    { text: 'produces heightened sensitivity to indications of external threats.', offset: 6922 },
    { text: 'is activated when there is a need to communicate a sense of danger.', offset: 6994 },
    { text: 'Questions 31–35', offset: 7068 },
    { text: 'Complete each sentence with the correct ending, A–G, below.', offset: 7085 },
    { text: 'Write the correct letter, A–G, in boxes 31–35 on your answer sheet.', offset: 7149 },
    { text: 'made them feel optimistic.', offset: 7215 },
    { text: 'took relatively little notice of bad news.', offset: 7245 },
    { text: 'responded to negative and positive information in the same way.', offset: 7291 },
    { text: 'were feeling under stress.', offset: 7358 },
    { text: 'put them in a stressful situation.', offset: 7388 },
    { text: 'behaved in a similar manner, regardless of the circumstances.', offset: 7425 },
    { text: 'thought it more likely that they would experience something bad.', offset: 7490 },
    { text: 'At times when they were relaxed, the firefighters usually', offset: 7564 },
    { text: 'The researchers noted that when the firefighters were stressed, they', offset: 7621 },
    { text: 'When the firefighters were told good news, they always', offset: 7695 },
    { text: 'The students’ cortisol levels and heart rates were affected when the researchers', offset: 7752 },
    { text: 'In both experiments, negative information was processed better when the subjects', offset: 7842 },
    { text: 'Questions 36–40', offset: 7926 },
    { text: 'Do the following statements agree with the information given in Reading Passage 3?', offset: 7943 },
    { text: 'In boxes 36–40 on your answer sheet, write', offset: 8023 },
    { text: 'if the statement agrees with the claims of the writer', offset: 8092 },
    { text: 'if the statement contradicts the claims of the writer', offset: 8153 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 8213 },
    { text: 'The tone of the content we post on social media tends to reflect the nature of the posts in our feeds.', offset: 8282 },
    { text: 'Phones have a greater impact on our stress levels than other electronic media devices.', offset: 8387 },
    { text: 'The more we read about a stressful public event on social media, the less able we are to take the information in.', offset: 8479 },
    { text: 'Stress created by social media posts can lead us to take unnecessary precautions.', offset: 8593 },
    { text: 'Our tendency to be affected by other people’s moods can be used in a positive way.', offset: 8681 },
]);

const highlightedPassageText = computed(() => {
    return applyHighlightsToText(passageText.value);
});

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
                                            :data-segment-text="'How stress affects our judgement'"
                                            v-html="getHighlightedSegment('How stress affects our judgement')"
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
                                <!-- Questions 27–30 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-27-30" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 27–30'"
                                            v-html="getHighlightedSegment('Questions 27–30')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose the correct letter, A, B, C or D.'"
                                                v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter in boxes 27–30 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write the correct letter in boxes 27–30 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Q27 -->
                                            <div
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >27</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'In the first paragraph, the writer introduces the topic of the text by'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'In the first paragraph, the writer introduces the topic of the text by',
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
                                                                :data-segment-text="'defining some commonly used terms.'"
                                                                v-html="getHighlightedSegment('defining some commonly used terms.')"
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
                                                                :data-segment-text="'questioning a widely held assumption.'"
                                                                v-html="getHighlightedSegment('questioning a widely held assumption.')"
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
                                                                :data-segment-text="'mentioning a challenge faced by everyone.'"
                                                                v-html="getHighlightedSegment('mentioning a challenge faced by everyone.')"
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
                                                                :data-segment-text="'specifying a situation which makes us most anxious.'"
                                                                v-html="getHighlightedSegment('specifying a situation which makes us most anxious.')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q28 -->
                                            <div
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >28</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'What point does the writer make about firefighters in the second paragraph?'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'What point does the writer make about firefighters in the second paragraph?',
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
                                                                :data-segment-text="'The regular changes of stress levels in their working lives make them ideal study subjects.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'The regular changes of stress levels in their working lives make them ideal study subjects.',
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
                                                                :data-segment-text="'The strategies they use to handle stress are of particular interest to researchers.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'The strategies they use to handle stress are of particular interest to researchers.',
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
                                                                :data-segment-text="'The stressful nature of their job is typical of many public service professions.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'The stressful nature of their job is typical of many public service professions.',
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
                                                                :data-segment-text="'Their personalities make them especially well-suited to working under stress.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'Their personalities make them especially well-suited to working under stress.',
                                                                    )
                                                                "
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q29 -->
                                            <div
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
                                                                :data-segment-text="'explaining their findings'"
                                                                v-html="getHighlightedSegment('explaining their findings')"
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
                                                                :data-segment-text="'justifying their approach'"
                                                                v-html="getHighlightedSegment('justifying their approach')"
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
                                                                :data-segment-text="'setting out their objectives'"
                                                                v-html="getHighlightedSegment('setting out their objectives')"
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
                                                                :data-segment-text="'describing their methodology'"
                                                                v-html="getHighlightedSegment('describing their methodology')"
                                                            ></span
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Q30 -->
                                            <div
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                        >30</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-800"
                                                        :data-segment-text="'In the seventh paragraph, the writer describes a mechanism in the brain which'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'In the seventh paragraph, the writer describes a mechanism in the brain which',
                                                            )
                                                        "
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
                                                                :data-segment-text="'enables people to respond more quickly to stressful situations.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'enables people to respond more quickly to stressful situations.',
                                                                    )
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
                                                                :data-segment-text="'results in increased ability to control our levels of anxiety.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'results in increased ability to control our levels of anxiety.',
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
                                                                :data-segment-text="'produces heightened sensitivity to indications of external threats.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'produces heightened sensitivity to indications of external threats.',
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
                                                                :data-segment-text="'is activated when there is a need to communicate a sense of danger.'"
                                                                v-html="
                                                                    getHighlightedSegment(
                                                                        'is activated when there is a need to communicate a sense of danger.',
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
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-31-35" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 31–35'"
                                            v-html="getHighlightedSegment('Questions 31–35')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete each sentence with the correct ending, A–G, below.'"
                                                v-html="getHighlightedSegment('Complete each sentence with the correct ending, A–G, below.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A–G, in boxes 31–35 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write the correct letter, A–G, in boxes 31–35 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <ul class="space-y-1 text-gray-700">
                                                <li>
                                                    <strong class="font-semibold">A</strong>
                                                    <span
                                                        :data-segment-text="'made them feel optimistic.'"
                                                        v-html="getHighlightedSegment('made them feel optimistic.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="font-semibold">B</strong>
                                                    <span
                                                        :data-segment-text="'took relatively little notice of bad news.'"
                                                        v-html="getHighlightedSegment('took relatively little notice of bad news.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="font-semibold">C</strong>
                                                    <span
                                                        :data-segment-text="'responded to negative and positive information in the same way.'"
                                                        v-html="
                                                            getHighlightedSegment('responded to negative and positive information in the same way.')
                                                        "
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="font-semibold">D</strong>
                                                    <span
                                                        :data-segment-text="'were feeling under stress.'"
                                                        v-html="getHighlightedSegment('were feeling under stress.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="font-semibold">E</strong>
                                                    <span
                                                        :data-segment-text="'put them in a stressful situation.'"
                                                        v-html="getHighlightedSegment('put them in a stressful situation.')"
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="font-semibold">F</strong>
                                                    <span
                                                        :data-segment-text="'behaved in a similar manner, regardless of the circumstances.'"
                                                        v-html="
                                                            getHighlightedSegment('behaved in a similar manner, regardless of the circumstances.')
                                                        "
                                                    ></span>
                                                </li>
                                                <li>
                                                    <strong class="font-semibold">G</strong>
                                                    <span
                                                        :data-segment-text="'thought it more likely that they would experience something bad.'"
                                                        v-html="
                                                            getHighlightedSegment('thought it more likely that they would experience something bad.')
                                                        "
                                                    ></span>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="n in 5"
                                                :key="n"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-green-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-bold text-white shadow-md"
                                                    >{{ 30 + n }}</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="
                                                        textSegments.find((s) => s.offset === [7564, 7621, 7695, 7752, 7842][n - 1])?.text
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find((s) => s.offset === [7564, 7621, 7695, 7752, 7842][n - 1])?.text || '',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers['q' + (30 + n)]"
                                                    class="w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 36–40 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-36-40" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 36–40'"
                                            v-html="getHighlightedSegment('Questions 36–40')"
                                        ></h3>
                                        <div class="rounded-xl border border-green-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the information given in Reading Passage 3?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the information given in Reading Passage 3?',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'In boxes 36–40 on your answer sheet, write'"
                                                v-html="getHighlightedSegment('In boxes 36–40 on your answer sheet, write')"
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
                                            <div
                                                v-for="n in 5"
                                                :key="n"
                                                class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-md"
                                                        >{{ 35 + n }}</span
                                                    >
                                                    <p
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="
                                                            textSegments.find((s) => s.offset === [8282, 8387, 8479, 8593, 8681][n - 1])?.text
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                textSegments.find((s) => s.offset === [8282, 8387, 8479, 8593, 8681][n - 1])?.text ||
                                                                    '',
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-4 pl-11">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            :value="'YES'"
                                                            v-model="answers['q' + (35 + n)]"
                                                            class="form-radio h-4 w-4 text-green-600 focus:ring-green-500"
                                                        />
                                                        <span class="font-medium text-green-700">YES</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            :value="'NO'"
                                                            v-model="answers['q' + (35 + n)]"
                                                            class="form-radio h-4 w-4 text-red-600 focus:ring-red-500"
                                                        />
                                                        <span class="font-medium text-red-700">NO</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input
                                                            type="radio"
                                                            :value="'NOT GIVEN'"
                                                            v-model="answers['q' + (35 + n)]"
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
