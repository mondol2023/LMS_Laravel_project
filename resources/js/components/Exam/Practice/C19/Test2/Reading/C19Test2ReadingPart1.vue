<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Reading Part 1 component

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

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-c19t2-part1-panel-width';
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

const passageText = `The Industrial Revolution began in Britain in the mid-1700s and by the 1830s and 1840s has spread to many other parts of the world, including the United States. In Britain, it was a period when a largely rural, agrarian* society was transformed into an industrialised, urban one. Goods that had once been crafted by hand started to be produced in mass quantities by machines in factories, thanks to the invention of steam power and the introduction of new machines and manufacturing techniques in textiles, iron-making and other industries.

The foundations of the Industrial Revolution date back to the early 1700s, when the English inventor Thomas Newcomen designed the first modern steam engine. Called the \u2018atmospheric steam engine\u2019, Newcomen\u2019s invention was originally used to power machines that pumped water out of mines. In the 1760s, the Scottish engineer James Watt started to adapt one of Newcomen\u2019s models, and succeeded in making it far more efficient. Watt later worked with the English manufacturer Matthew Boulton to invent a new steam engine driven by both the forward and backward strokes of the piston, while the gear mechanism it was connected to produced rotary motion. It was a key innovation that would allow steam power to spread across British industries.

The demand for coal, which was a relatively cheap energy source, grew rapidly during the Industrial Revolution, as it was needed to run not only the factories used to produce manufactured goods, but also steam-powered transportation. In the early 1800s, the English engineer Richard Trevithick built a steam-powered locomotive, and by 1830 goods and passengers were being transported between the industrial centres of Manchester and Liverpool. In addition, steam-powered boats and ships were widely used to carry goods along Britain\u2019s canals as well as across the Atlantic.

Britain had produced textiles like wool, linen and cotton, for hundreds of years, but prior to the Industrial Revolution, the British textile business was a true \u2018cottage industry\u2019, with the work performed in small workshops or even homes by individual spinners, weavers and dyers. Starting in the mid-1700s, innovations like the spinning jenny and the power loom made weaving cloth and spinning yarn and thread much easier. With these machines, relatively little labour was required to produce cloth, and the new, mechanised textile factories that opened around the country were quickly able to meet customer demand for cloth both at home and abroad.

The British iron industry also underwent major change as it adopted new innovations. Chief among the new techniques was the smelting of iron ore with coke (a material made by heating coal) instead of the traditional charcoal. This method was cheaper and produced metals that were of a higher quality, enabling Britain\u2019s iron and steel production to expand in response to demand created by the Napoleonic Wars (1803-15) and the expansion of the railways from the 1830s.

The latter part of the Industrial Revolution also saw key advances in communication methods, as people increasingly saw the need to communicate efficiently over long distances. In 1837, British inventors William Cooke and Charles Wheatstone patented the first commercial telegraphy system. In the 1830s and 1840s, Samuel Morse and other inventors worked on their own versions in the United States. Cooke and Wheatstone\u2019s system was soon used for railway signalling in the UK. As the speed of the new locomotives increased, it was essential to have a fast and effective means of avoiding collisions.

The impact of the Industrial Revolution on people\u2019s lives was immense. Although many people in Britain had begun moving to the cities from rural areas before the Industrial Revolution, this accelerated dramatically with industrialisation, as the rise of large factories turned smaller towns into major cities in just a few decades. This rapid urbanisation brought significant challenges, as overcrowded cities suffered from pollution and inadequate sanitation.

Although industrialisation increased the country\u2019s economic output overall and improved the standard of living for the middle and upper classes, many poor people continued to struggle. Factory workers had to work long hours in dangerous conditions for extremely low wages. These conditions along with the rapid pace of change fuelled opposition to industrialisation. A group of British workers who became known as \u2018Luddites\u2019 were British weavers and textile workers who objected to the increased use of mechanised looms and knitting frames. Many had spent years learning their craft, and they feared that unskilled machine operators were robbing them of their livelihood. A few desperate weavers began breaking into factories and smashing textile machines. They called themselves Luddites after Ned Ludd, a young apprentice who was rumoured to have wrecked a textile machine in 1779.

The first major instances of machine breaking took place in 1811 in the city of Nottingham, and the practice soon spread across the country. Machine-breaking Luddites attacked and burned factories, and in some cases they even exchanged gunfire with company guards and soldiers. The workers wanted employers to stop installing new machinery, but the British government responded to the uprisings by making machine-breaking punishable by death. The unrest finally reached its peak in April 1812, when a few Luddites were shot during an attack on a mill near Huddersfield. In the days that followed, other Luddites were arrested, and dozens were hanged or transported to Australia. By 1813, the Luddite resistance had all but vanished.

* agrarian: relating to the land, especially the use of land for farming`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 1', offset: 5000 },
    { text: 'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.', offset: 5018 },
    { text: 'The Industrial Revolution in Britain', offset: 5114 },
    { text: 'Questions 1-7', offset: 5150 },
    { text: 'Complete the notes below.', offset: 5164 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 5190 },
    { text: 'Write your answers in boxes 1-7 on your answer sheet.', offset: 5245 },
    { text: "Britain's Industrial Revolution", offset: 5299 },
    { text: 'Steam power', offset: 5330 },
    { text: "Newcomen's steam engine was used in mines to remove water.", offset: 5342 },
    { text: "In Watt and Boulton's steam engine, the movement of the ", offset: 5401 },
    { text: ' was linked to a gear system.', offset: 5458 },
    { text: 'A greater supply of ', offset: 5487 },
    { text: ' was required to power steam engines.', offset: 5508 },
    { text: 'Textile industry', offset: 5544 },
    { text: 'Before the Industrial Revolution, spinners and weavers worked at home and in ', offset: 5561 },
    { text: 'Not as much ', offset: 5639 },
    { text: ' was needed to produce cloth once the spinning jenny and power loom were invented.', offset: 5652 },
    { text: 'Iron industry', offset: 5734 },
    { text: 'Smelting of iron ore with coke resulted in material that was better ', offset: 5748 },
    { text: 'Demand for iron increased with the growth of the ', offset: 5817 },
    { text: 'Communications', offset: 5869 },
    { text: 'Cooke and Wheatstone patented the first telegraphy system.', offset: 5884 },
    { text: 'The telegraphy system was used to prevent locomotives colliding.', offset: 5943 },
    { text: 'Urbanisation', offset: 6007 },
    { text: 'Small towns turned into cities very quickly.', offset: 6020 },
    { text: 'The new cities were dirty, crowded and lacked sufficient ', offset: 6065 },
    { text: 'Questions 8-13', offset: 6124 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 6139 },
    { text: 'In boxes 8-13 on your answer sheet, write', offset: 6221 },
    { text: 'TRUE', offset: 6263 },
    { text: 'if the statement agrees with the information', offset: 6268 },
    { text: 'FALSE', offset: 6313 },
    { text: 'if the statement contradicts the information', offset: 6319 },
    { text: 'NOT GIVEN', offset: 6364 },
    { text: 'if there is no information on this', offset: 6374 },
    { text: "Britain's canal network grew rapidly so that more goods could be transported around the country.", offset: 6408 },
    { text: 'Costs in the iron industry rose when the technique of smelting iron ore with coke was introduced.', offset: 6504 },
    { text: "Samuel Morse's communication system was more reliable than that developed by William Cooke and Charles Wheatstone.", offset: 6601 },
    { text: 'The economic benefits of industrialisation were limited to certain sectors of society.', offset: 6716 },
    { text: 'Some skilled weavers believed that the introduction of the new textile machines would lead to job losses.', offset: 6802 },
    { text: 'There was some sympathy among local people for the Luddites who were arrested near Huddersfield.', offset: 6907 },
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
                                            :data-segment-text="'The Industrial Revolution in Britain'"
                                            v-html="getHighlightedSegment('The Industrial Revolution in Britain')"
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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">QUESTIONS</p>
                                    <p class="text-xs text-gray-500">Answer all questions based on Reading Passage 1</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-7: Note Completion -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-1-7" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 1-7'"
                                            v-html="getHighlightedSegment('Questions 1-7')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm text-gray-800"
                                                :data-segment-text="'Complete the notes below.'"
                                                v-html="getHighlightedSegment('Complete the notes below.')"
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3 rounded-lg bg-white/50 p-4">
                                            <h4
                                                class="text-md font-bold text-blue-800"
                                                :data-segment-text="'Britain\'s Industrial Revolution'"
                                                v-html="getHighlightedSegment('Britain\'s Industrial Revolution')"
                                            ></h4>

                                            <!-- Steam power section -->
                                            <h5
                                                class="font-semibold text-gray-700"
                                                :data-segment-text="'Steam power'"
                                                v-html="getHighlightedSegment('Steam power')"
                                            ></h5>

                                            <div class="rounded-lg p-3">
                                                <p class="text-gray-700">
                                                    <span
                                                        :data-segment-text="'Newcomen\'s steam engine was used in mines to remove water.'"
                                                        v-html="getHighlightedSegment('Newcomen\'s steam engine was used in mines to remove water.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q1 -->
                                            <div id="question-1" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >1</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'In Watt and Boulton\'s steam engine, the movement of the '"
                                                        v-html="getHighlightedSegment('In Watt and Boulton\'s steam engine, the movement of the ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q1"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' was linked to a gear system.'"
                                                        v-html="getHighlightedSegment(' was linked to a gear system.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q2 -->
                                            <div id="question-2" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >2</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'A greater supply of '"
                                                        v-html="getHighlightedSegment('A greater supply of ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q2"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' was required to power steam engines.'"
                                                        v-html="getHighlightedSegment(' was required to power steam engines.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Textile industry section -->
                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Textile industry'"
                                                v-html="getHighlightedSegment('Textile industry')"
                                            ></h5>

                                            <!-- Q3 -->
                                            <div id="question-3" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >3</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'Before the Industrial Revolution, spinners and weavers worked at home and in '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Before the Industrial Revolution, spinners and weavers worked at home and in ',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q3"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span>.</span>
                                                </p>
                                            </div>

                                            <!-- Q4 -->
                                            <div id="question-4" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >4</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span :data-segment-text="'Not as much '" v-html="getHighlightedSegment('Not as much ')"></span>
                                                    <input
                                                        v-model="answers.q4"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' was needed to produce cloth once the spinning jenny and power loom were invented.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' was needed to produce cloth once the spinning jenny and power loom were invented.',
                                                            )
                                                        "
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Iron industry section -->
                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Iron industry'"
                                                v-html="getHighlightedSegment('Iron industry')"
                                            ></h5>

                                            <!-- Q5 -->
                                            <div id="question-5" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >5</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'Smelting of iron ore with coke resulted in material that was better '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Smelting of iron ore with coke resulted in material that was better ',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q5"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span>.</span>
                                                </p>
                                            </div>

                                            <!-- Q6 -->
                                            <div id="question-6" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >6</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'Demand for iron increased with the growth of the '"
                                                        v-html="getHighlightedSegment('Demand for iron increased with the growth of the ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q6"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span>.</span>
                                                </p>
                                            </div>

                                            <!-- Communications section -->
                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Communications'"
                                                v-html="getHighlightedSegment('Communications')"
                                            ></h5>

                                            <div class="rounded-lg p-3">
                                                <p class="text-gray-700">
                                                    <span
                                                        :data-segment-text="'Cooke and Wheatstone patented the first telegraphy system.'"
                                                        v-html="getHighlightedSegment('Cooke and Wheatstone patented the first telegraphy system.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <div class="rounded-lg p-3">
                                                <p class="text-gray-700">
                                                    <span
                                                        :data-segment-text="'The telegraphy system was used to prevent locomotives colliding.'"
                                                        v-html="
                                                            getHighlightedSegment('The telegraphy system was used to prevent locomotives colliding.')
                                                        "
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Urbanisation section -->
                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Urbanisation'"
                                                v-html="getHighlightedSegment('Urbanisation')"
                                            ></h5>

                                            <div class="rounded-lg p-3">
                                                <p class="text-gray-700">
                                                    <span
                                                        :data-segment-text="'Small towns turned into cities very quickly.'"
                                                        v-html="getHighlightedSegment('Small towns turned into cities very quickly.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q7 -->
                                            <div id="question-7" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >7</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'The new cities were dirty, crowded and lacked sufficient '"
                                                        v-html="getHighlightedSegment('The new cities were dirty, crowded and lacked sufficient ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q7"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span>.</span>
                                                </p>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 8-13: TRUE/FALSE/NOT GIVEN -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-8-13" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 8-13'"
                                            v-html="getHighlightedSegment('Questions 8-13')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-50 p-4 shadow-inner">
                                            <p
                                                class="mb-3 text-sm text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the information given in Reading Passage 1?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the information given in Reading Passage 1?',
                                                    )
                                                "
                                            ></p>
                                            <div class="rounded-xl border border-purple-200 bg-white p-6 shadow-sm">
                                                <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                    <span
                                                        :data-segment-text="'In boxes 8-13 on your answer sheet, write'"
                                                        v-html="getHighlightedSegment('In boxes 8-13 on your answer sheet, write')"
                                                    ></span>
                                                </p>
                                                <div class="grid grid-cols-1 gap-2 pt-4 text-base sm:text-lg">
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-purple-100 px-2 py-1 font-bold text-purple-700"
                                                            :data-segment-text="'TRUE'"
                                                            v-html="getHighlightedSegment('TRUE')"
                                                        ></span>
                                                        <span
                                                            :data-segment-text="'if the statement agrees with the information'"
                                                            class="text-gray-700"
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
                                                            :data-segment-text="'if the statement contradicts the information'"
                                                            class="text-gray-700"
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
                                                            :data-segment-text="'if there is no information on this'"
                                                            class="text-gray-700"
                                                            v-html="getHighlightedSegment('if there is no information on this')"
                                                        ></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Q8 -->
                                            <div
                                                id="question-8"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >8</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Britain\'s canal network grew rapidly so that more goods could be transported around the country.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Britain\'s canal network grew rapidly so that more goods could be transported around the country.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q8"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q9 -->
                                            <div
                                                id="question-9"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >9</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Costs in the iron industry rose when the technique of smelting iron ore with coke was introduced.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Costs in the iron industry rose when the technique of smelting iron ore with coke was introduced.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q9"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q10 -->
                                            <div
                                                id="question-10"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >10</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Samuel Morse\'s communication system was more reliable than that developed by William Cooke and Charles Wheatstone.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Samuel Morse\'s communication system was more reliable than that developed by William Cooke and Charles Wheatstone.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q10"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q11 -->
                                            <div
                                                id="question-11"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >11</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'The economic benefits of industrialisation were limited to certain sectors of society.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The economic benefits of industrialisation were limited to certain sectors of society.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q11"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q12 -->
                                            <div
                                                id="question-12"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >12</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Some skilled weavers believed that the introduction of the new textile machines would lead to job losses.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Some skilled weavers believed that the introduction of the new textile machines would lead to job losses.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q12"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q13 -->
                                            <div
                                                id="question-13"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >13</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'There was some sympathy among local people for the Luddites who were arrested near Huddersfield.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'There was some sympathy among local people for the Luddites who were arrested near Huddersfield.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q13"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
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
