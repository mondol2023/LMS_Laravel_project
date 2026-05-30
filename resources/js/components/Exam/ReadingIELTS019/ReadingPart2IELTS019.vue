<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
    fontSize?: number;
    flaggedQuestions?: Set<number>;
}

const props = withDefaults(defineProps<Props>(), {
    fontSize: 16,
    flaggedQuestions: () => new Set<number>(),
});

const emit = defineEmits<{
    toggleFlag: [questionNumber: number];
}>();

// Hover state for showing bookmark buttons
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part2-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Delete highlight tooltip
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note tooltip (hover)
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

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

// ── Passage text ──────────────────────────────────────────────────────────────
const passageText =
    ref(`<b>A.</b> Everybody now knows that malaria is carried by mosquitoes. But in the 19th century, most experts believed that the disease was produced by "miasma" or "poisoning of the air". Others made a link between swamps, water and malaria, but did not make the future leap towards insects. The consequences of these theories were that little was done to combat the disease before the end of the century. Things became so bad that 11m Italians (from a total population of 25m) were "permanently at risk". In malarial zones the life expectancy of land workers was a terrifying 22.5 years. Those who escaped death were weakened or suffered from splenomegaly — a "painful enlargement of the spleen" and "a lifeless stare". The economic impact of the disease was immense. Epidemics were blamed on southern Italians, given the widespread belief that malaria was hereditary. In the 1880s, such theories began to collapse as the dreaded mosquito was identified as the real culprit.

<b>B.</b> Italian scientists, drawing on the pioneering work of French doctor Alphonse Laveran, were able to predict the cycles of fever but it was in Rome that further key discoveries were made. Giovanni Battista Grassi, a naturalist, found that a particular type of mosquito was the carrier of malaria. By experimenting on healthy volunteers (mosquitoes were released into rooms where they drank the blood of the human guinea pigs), Grassi was able to make the direct link between the insects (all females of a certain kind) and the disease. Soon, doctors and scientists made another startling discovery: the mosquitoes themselves were also infected and not mere carriers. Every year, during the mosquito season, malarial blood was moved around the population by the insects. Definitive proof of these new theories was obtained after an extraordinary series of experiments in Italy, where healthy people were introduced into malarial zones but kept free of mosquito bites — and remained well. The new Italian state had the necessary information to tackle the disease.

<b>C.</b> A complicated approach was adopted, which made use of quinine — a drug obtained from tree bark which had long been used to combat fever, but was now seen as a crucial part of the war on malaria. Italy introduced a quinine law and a quinine tax in 1904, and the drug was administered to large numbers of rural workers. Despite its often terrible side-effects (the headaches produced were known as the "quinine-buzz"), the drug was successful in limiting the spread of the disease, and in breaking cycles of infection. In addition, Italy set up rural health centres and invested heavily in education programmes. Malaria, as Snowden shows, was not just a medical problem, but a social and regional issue, and could only be defeated through multilayered strategies. Politics was itself transformed by the anti-malarial campaigns. It was originally decided to give quinine to all those in certain regions — even healthy people; peasants were often suspicious of medicine being forced upon them. Doctors were sometimes met with hostility and refusal, and many were dubbed "poisoners".

<b>D.</b> Despite these problems, the strategy was hugely successful. Deaths from malaria fell by some 80% in the first decade of the 20th century and some areas escaped altogether from the scourge of the disease. War, from 1915–18, delayed the campaign. Funds were diverted to the battlefields and the fight against malaria became a military issue, laying the way for the fascist approach to the problem. Mussolini's policies in the 20s and 30s are subjected to a serious cross-examination by Snowden. He shows how much of the regime's claims to have "eradicated" malaria through massive land reclamation, forced population removals and authoritarian clean-ups were pure propaganda. Mass draining was instituted — often at a great cost as Mussolini waged war not on the disease itself, but on the mosquitoes that carried it. The cleansing of Italy was also ethnic, as "carefully selected" Italians were chosen to inhabit the gleaming new towns of the former marshlands around Rome. The "successes" under fascism were extremely vulnerable, based as they were on a top-down concept of eradication. As war swept through the drained lands in the 40s, the disease returned with a vengeance.

<b>E.</b> In the most shocking part of the book, Snowden describes — passionately, but with the skill of a great historian — how the retreating Nazi armies in Italy in 1943–44 deliberately caused a massive malaria epidemic in Lazio. It was "the only known example of biological warfare in 20th century Europe". Shamefully, the Italian malaria expert Alberto Missiroli had a role to play in the disaster: he did not distribute quinine, despite being well aware of the epidemic to come. Snowden claims that Missiroli was already preparing a new strategy — with the support of the US Rockefeller Foundation — using a new pesticide, DDT. Missiroli allowed the epidemic to spread, in order to create the ideal conditions for a massive, lucrative, human experiment. Fifty-five thousand cases of malaria were recorded in the province of Littoria alone in 1944. It is estimated that more than a third of those in the affected area contracted the disease. Thousands, nobody knows how many, died. With the war over, the US government and the Rockefeller Foundation were free to experiment. DDT was sprayed from the air and 3m Italians had their bodies covered with the chemical. The effects were dramatic, and nobody really cared about the toxic effects of the chemical.

<b>F.</b> By 1962, malaria was more or less gone from the whole peninsula. The last cases were noted in a poor region of Sicily. One of the final victims to die of the disease in Italy was the popular cyclist, Fausto Coppi. He had contracted malaria in Africa in 1960, and the failure of doctors in the north of Italy to spot the disease was a sign of the times. A few decades earlier, they would have immediately noticed the tell-tale signs; it was later claimed that a small dose of quinine would have saved his life. As there are still more than 1m deaths every year from malaria worldwide, Snowden's book also has contemporary relevance. This is a disease that affects every level of the societies where it is rampant. It also provides us with "a message of hope for a world struggling with the great present-day medical emergency".`);

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts = [
    // ── Passage Panel ──────────────────────────────────────────────
    /* 0 */ 'Part 2',
    /* 1 */ 'Read the text and answer questions 14–26.',
    /* 2 */ 'The Conquest of Malaria in Italy, 1900–1962',
    /* 3 */ passageText.value,

    // ── Questions Section ───────────────────────────────────────────
    /* 4 */ 'QUESTIONS',
    /* 5 */ 'Answer all questions based on Reading Passage 2',

    // Questions 14-18 header texts
    /* 6 */ 'Questions 14–18',
    /* 7 */ 'Complete the summary below using NO MORE THAN TWO WORDS from the passage.',
    /* 8 */ 'Write your answers in boxes 14–18 on your answer sheet.',

    // Summary sentences (split around blanks)
    /* 9  */ 'Before the link between malaria and',
    /* 10 */ 'was established, there were many popular theories circulating among the public, one of which pointed to',
    /* 11 */ ', the unclean air. The lack of proper treatment affected the country so badly that rural people in malaria-infested places had extremely short',
    /* 12 */ '. The disease spread so quickly, especially in the south of Italy, thus giving rise to the idea that the disease was',
    /* 13 */ '. People believed in these theories until the mosquito was found to be the',
    /* 14 */ 'in the 1880s.',

    // Questions 19-21 header texts
    /* 15 */ 'Questions 19–21',
    /* 16 */ 'Do the following statements agree with the claims of the writer in Reading Passage 2?',
    /* 17 */ 'In boxes 19–21 on your answer sheet, write:',
    /* 18 */ 'TRUE if the statement agrees with the information',
    /* 19 */ 'FALSE if the statement contradicts the information',
    /* 20 */ 'NOT GIVEN if there is no information on this',

    // Q19-21 statement texts
    /* 21 */ 'The volunteers of the Italian experiments that provided assuring evidence were from all over Italy.',
    /* 22 */ 'It is possible to come out of malarial zones alive.',
    /* 23 */ 'The government successfully managed to give all people quinine medication.',

    // Questions 22-26 header texts
    /* 24 */ 'Questions 22–26',
    /* 25 */ 'Which paragraph contains the following information?',
    /* 26 */ 'Reading Passage 2 has six paragraphs, A–F.',
    /* 27 */ 'Write the correct letter A–F in boxes 22–26 on your answer sheet.',

    // Q22-26 statement texts
    /* 28 */ 'A breakthrough in the theory of the cause of malaria',
    /* 29 */ 'A story for today\'s readers',
    /* 30 */ 'A description of an expert who did nothing to restrict the spread of disease',
    /* 31 */ 'A setback in the battle against malaria due to government policies',
    /* 32 */ 'A description of how malaria affects the human body',
];

let currentOffset = 0;
const newTextSegments = allStaticTexts.map((text, index) => {
    const segment = {
        id: `segment_${index}`,
        text: text,
        offset: currentOffset,
    };
    currentOffset += text.length;
    return segment;
});

const textSegments = ref(newTextSegments);

// Convert plain text offset to HTML offset
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;

    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') {
                htmlIndex++;
            }
            htmlIndex++;
        } else {
            plainIndex++;
            htmlIndex++;
        }
    }

    return htmlIndex;
};

// Get plain text length of HTML string
const getPlainTextLength = (htmlText: string): number => {
    return htmlText.replace(/<[^>]*>/g, '').length;
};

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segmentText;
    }

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset,
            end: h.end_offset,
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start,
            end: n.end,
            type: 'note' as const,
            color: 'blue',
            id: n.id,
            noteText: n.note,
        })),
    ];

    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = segmentText;

    for (const annotation of sorted) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);

        if (plainStart < plainEnd) {
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd = plainToHtmlOffset(result, plainEnd);

            const before = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after = result.substring(htmlEnd);

            if (annotation.type === 'note') {
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }

    return result;
};

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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
            const segmentEl = (container as Element).closest('[data-segment-id]');

            if (!segmentEl) {
                return null;
            }

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) {
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
            const menuHeight = 70;
            const menuY = rect.top - menuHeight - 8;

            const viewportWidth = window.innerWidth;
            const menuWidth = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            selectedText.value = selection.toString();
            selectionRange.value = { start: startAbsOffset, end: endAbsOffset };
        } else {
            showContextMenu.value = false;
        }
    }, 10);
};

const passageTextRef = ref<HTMLDivElement | null>(null);

const handlePassageTextSelect = (event: MouseEvent) => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && passageTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70;
            const menuY = rect.top - menuHeight - 8;

            const viewportWidth = window.innerWidth;
            const menuWidth = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection) {
                const preSelectionRange = range!.cloneRange();
                const container = passageTextRef.value;
                preSelectionRange.selectNodeContents(container);
                preSelectionRange.setEnd(range!.startContainer, range!.startOffset);

                const plainTextBefore = preSelectionRange.toString();
                const startOffset = plainTextBefore.length;
                const endOffset = startOffset + selected.length;

                selectedText.value = selected;
                selectionRange.value = { start: startOffset, end: endOffset };
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    notes.value = notes.value.filter((n) => {
        return !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start);
    });

    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);

    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;

    const modalWidth = 320;
    const modalHeight = 180;
    const padding = 10;

    const selection = window.getSelection();
    let x: number;
    let y: number;

    if (selection && selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const rect = range.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    const halfWidth = modalWidth / 2;
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) {
            y = padding;
        }
    }

    noteInputPosition.value = { x, y };

    showNoteInput.value = true;
    showContextMenu.value = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    notes.value = notes.value.filter((n) => {
        const overlaps = n.start < newEnd && n.end > newStart;
        return !overlaps;
    });

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
        part: 'Part 2',
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

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = {
                x: rect.left + rect.width / 2,
                y: rect.bottom + 8,
            };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) {
            closeDeleteTooltip();
        }
        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(idToDelete);
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;

    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find((n) => n.id === noteIdNum);

            if (note) {
                const rect = noteMark.getBoundingClientRect();

                const tooltipHeight = 50;
                let y = rect.top - tooltipHeight - 8;

                if (y < 10) {
                    y = rect.bottom + 8;
                }

                noteTooltipPosition.value = {
                    x: rect.left + rect.width / 2,
                    y: y,
                };

                hoveredNoteId.value = noteIdNum;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    if (relatedTarget?.closest('.note-hover-tooltip')) {
        return;
    }

    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
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

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mouseover', handleNoteMouseEnter);
    document.removeEventListener('mouseout', handleNoteMouseLeave);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
});

defineExpose({
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 2 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="segment_0"
                v-html="getHighlightedSegment(allStaticTexts[0])"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="segment_1"
                v-html="getHighlightedSegment(allStaticTexts[1])"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-0.5">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="pt-6">
                        <h2 class="text-lg text-center font-bold text-gray-900">
                            <span data-segment-id="segment_2" v-html="getHighlightedSegment(allStaticTexts[2])"></span>
                        </h2>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto px-4" :style="contentZoom">
                        <div class="space-y-6 text-base leading-relaxed select-text sm:text-base">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span class="text-base text-gray-700 select-text" data-segment-id="segment_3"
                                        v-html="getHighlightedSegment(allStaticTexts[3])"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">

                    <!-- Scrollable Questions Content -->
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ── Questions 14–18 ─────────────────────────────────── -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="segment_6"
                                                v-html="getHighlightedSegment(allStaticTexts[6])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_7"
                                            v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_8"
                                            v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                                    </p>
                                </div>

                                <!-- Summary paragraph with inline inputs -->
<div class="space-y-3 text-base leading-relaxed text-gray-800 sm:text-base">
    <p class="leading-loose">
        <!-- Q14 -->
        <span class="select-text" data-segment-id="segment_9"
            v-html="getHighlightedSegment(allStaticTexts[9])"></span>
        <span id="question-14" class="inline-flex items-center mx-1"
            @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q14" type="text" placeholder="14"
                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
            <div class="relative w-7 h-7 ml-1 inline-flex items-center justify-center">
                <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                    @click.stop="toggleFlag(14)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                    :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                    :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </span>
        
        <!-- Q15 -->
        <span class="select-text" data-segment-id="segment_10"
            v-html="getHighlightedSegment(allStaticTexts[10])"></span>
        <span id="question-15" class="inline-flex items-center mx-1"
            @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q15" type="text" placeholder="15"
                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
            <div class="relative w-7 h-7 ml-1 inline-flex items-center justify-center">
                <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                    @click.stop="toggleFlag(15)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                    :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                    :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </span>
        
        <!-- Q16 -->
        <span class="select-text" data-segment-id="segment_11"
            v-html="getHighlightedSegment(allStaticTexts[11])"></span>
        <span id="question-16" class="inline-flex items-center mx-1"
            @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q16" type="text" placeholder="16"
                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
            <div class="relative w-7 h-7 ml-1 inline-flex items-center justify-center">
                <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                    @click.stop="toggleFlag(16)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                    :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                    :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </span>
        
        <!-- Q17 -->
        <span class="select-text" data-segment-id="segment_12"
            v-html="getHighlightedSegment(allStaticTexts[12])"></span>
        <span id="question-17" class="inline-flex items-center mx-1"
            @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q17" type="text" placeholder="17"
                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
            <div class="relative w-7 h-7 ml-1 inline-flex items-center justify-center">
                <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                    @click.stop="toggleFlag(17)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                    :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                    :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </span>
        
        <!-- Q18 -->
        <span class="select-text" data-segment-id="segment_13"
            v-html="getHighlightedSegment(allStaticTexts[13])"></span>
        <span id="question-18" class="inline-flex items-center mx-1"
            @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
            <input v-model="answers.q18" type="text" placeholder="18"
                class="w-36 border border-gray-900 px-3 py-0.5 text-center text-base font-medium transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base" />
            <div class="relative w-7 h-7 ml-1 inline-flex items-center justify-center">
                <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                    @click.stop="toggleFlag(18)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                    :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                    :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </span>
        
        <span class="select-text" data-segment-id="segment_14"
            v-html="getHighlightedSegment(allStaticTexts[14])"></span>
    </p>
</div>
                            </div>

                           <!-- ── Questions 19–21 ─────────────────────────────────── -->
<div class="p-6">
    <div class="mb-6">
        <div class="mb-4 flex items-center space-x-2">
            <h3 class="text-base font-bold text-gray-900">
                <span data-segment-id="segment_15"
                    v-html="getHighlightedSegment(allStaticTexts[15])"></span>
            </h3>
        </div>
        <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
            <span data-segment-id="segment_16"
                v-html="getHighlightedSegment(allStaticTexts[16])"></span>
        </p>
        <p class="mb-1 text-base leading-relaxed text-gray-700 sm:text-base">
            <span data-segment-id="segment_17"
                v-html="getHighlightedSegment(allStaticTexts[17])"></span>
        </p>
        <p class="mb-0 text-base leading-relaxed text-gray-700 sm:text-base font-semibold">
            <span data-segment-id="segment_18"
                v-html="getHighlightedSegment(allStaticTexts[18])"></span><br />
            <span data-segment-id="segment_19"
                v-html="getHighlightedSegment(allStaticTexts[19])"></span><br />
            <span data-segment-id="segment_20"
                v-html="getHighlightedSegment(allStaticTexts[20])"></span>
        </p>
    </div>

    <div class="space-y-4">
        <!-- Question 19 -->
        <div id="question-19" class="relative pr-10"
            @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
            <div class="flex items-start gap-3">
                <span class="font-bold text-gray-900 shrink-0">19.</span>
                <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                    <span class="select-text" data-segment-id="segment_21"
                        v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                </p>
            </div>
            <div class="ml-7 mt-2 flex gap-4 select-none flex-wrap">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q19" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">TRUE</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q19" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">FALSE</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q19" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">NOT GIVEN</span>
                </label>
            </div>
            <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                @click.stop="toggleFlag(19)"
                class="absolute top-0 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>

        <!-- Question 20 -->
        <div id="question-20" class="relative pr-10"
            @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
            <div class="flex items-start gap-3">
                <span class="font-bold text-gray-900 shrink-0">20.</span>
                <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                    <span class="select-text" data-segment-id="segment_22"
                        v-html="getHighlightedSegment(allStaticTexts[22])"></span>
                </p>
            </div>
            <div class="ml-7 mt-2 flex gap-4 select-none flex-wrap">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q20" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">TRUE</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q20" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">FALSE</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q20" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">NOT GIVEN</span>
                </label>
            </div>
            <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                @click.stop="toggleFlag(20)"
                class="absolute top-0 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>

        <!-- Question 21 -->
        <div id="question-21" class="relative pr-10"
            @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
            <div class="flex items-start gap-3">
                <span class="font-bold text-gray-900 shrink-0">21.</span>
                <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                    <span class="select-text" data-segment-id="segment_23"
                        v-html="getHighlightedSegment(allStaticTexts[23])"></span>
                </p>
            </div>
            <div class="ml-7 mt-2 flex gap-4 select-none flex-wrap">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q21" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">TRUE</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q21" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">FALSE</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" v-model="answers.q21" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                    <span class="text-sm text-gray-900">NOT GIVEN</span>
                </label>
            </div>
            <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                @click.stop="toggleFlag(21)"
                class="absolute top-0 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>
    </div>
</div>

                            <!-- ── Questions 22–26 ─────────────────────────────────── -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="segment_24"
                                                v-html="getHighlightedSegment(allStaticTexts[24])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_25"
                                            v-html="getHighlightedSegment(allStaticTexts[25])"></span>
                                    </p>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_26"
                                            v-html="getHighlightedSegment(allStaticTexts[26])"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_27"
                                            v-html="getHighlightedSegment(allStaticTexts[27])"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Question 22 -->
                                    <div id="question-22" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                                        <span class="font-bold text-gray-900 shrink-0">22.</span>
                                        <select v-model="answers.q22"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_28"
                                                v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                            @click.stop="toggleFlag(22)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 23 -->
                                    <div id="question-23" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                        <span class="font-bold text-gray-900 shrink-0">23.</span>
                                        <select v-model="answers.q23"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_29"
                                                v-html="getHighlightedSegment(allStaticTexts[29])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                            @click.stop="toggleFlag(23)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 24 -->
                                    <div id="question-24" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <span class="font-bold text-gray-900 shrink-0">24.</span>
                                        <select v-model="answers.q24"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_30"
                                                v-html="getHighlightedSegment(allStaticTexts[30])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                            @click.stop="toggleFlag(24)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 25 -->
                                    <div id="question-25" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                        <span class="font-bold text-gray-900 shrink-0">25.</span>
                                        <select v-model="answers.q25"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_31"
                                                v-html="getHighlightedSegment(allStaticTexts[31])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                            @click.stop="toggleFlag(25)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 26 -->
                                    <div id="question-26" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <span class="font-bold text-gray-900 shrink-0">26.</span>
                                        <select v-model="answers.q26"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_32"
                                                v-html="getHighlightedSegment(allStaticTexts[32])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                            @click.stop="toggleFlag(26)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu for Highlighting - Speech Bubble Style -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div class="highlight-tooltip pointer-events-auto fixed z-9999" :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                            title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                            title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                        </button>
                    </div>
                    <div class="tooltip-arrow"></div>
                </div>
            </div>

            <!-- Delete Highlight Tooltip -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
                <div class="delete-highlight-tooltip fixed z-9999" :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                        @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" />
                                <line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                            <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                            <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
                <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div
                        class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{
                            hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                            title="Delete note">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
                :style="{
                    left: `${noteInputPosition.x}px`,
                    top: `${noteInputPosition.y}px`,
                    transform: 'translateX(-50%)',
                }" @click.stop>
                <div class="mb-3">
                    <p
                        class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                        "{{ selectedText }}"
                    </p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                        class="border border-gray-900 bg-white px-3 py-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                        Cancel
                    </button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                        Save Note
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: col-resize;
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
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
}

.bg-clip-text mark[data-highlight-id] {
    -webkit-text-fill-color: initial !important;
    color: transparent;
    background-clip: padding-box !important;
    -webkit-background-clip: padding-box !important;
}

.highlight-nocolor {
    background-color: transparent;
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
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1);
    }

    50% {
        background-color: rgba(0, 0, 0, 0.2);
        transform: scale(1.05);
    }

    100% {
        background-color: rgba(0, 0, 0, 0.1);
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
    background: #374151;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #111827;
}

/* Highlight Tooltip Styles */
.highlight-tooltip .tooltip-arrow {
    position: absolute;
    left: 50%;
    bottom: -8px;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.highlight-tooltip .tooltip-arrow::before {
    content: '';
    position: absolute;
    left: -9px;
    bottom: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

/* Delete Highlight Tooltip - Arrow pointing UP */
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.1));
}

.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: '';
    position: absolute;
    left: -9px;
    top: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db;
    z-index: -1;
}

/* Note Hover Tooltip - Arrow pointing DOWN */
.note-hover-tooltip .tooltip-arrow-down {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.note-hover-tooltip .tooltip-arrow-down::before {
    content: '';
    position: absolute;
    left: -9px;
    bottom: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

.note-hover-tooltip .note-tooltip-content {
    max-width: 240px;
}

.note-hover-tooltip .note-tooltip-icon {
    color: #6b7280;
}

.note-hover-tooltip .note-tooltip-text {
    line-height: 1.4;
}

.note-hover-tooltip .note-delete-btn {
    color: #9ca3af;
}

.note-hover-tooltip .note-delete-btn:hover {
    color: #ef4444;
}
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
    padding: 2px 0;
    border-radius: 2px;
}

.note-highlight:hover {
    background-color: rgba(147, 197, 253, 0.7) !important;
}

/* Bold placeholder styling for question inputs */
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>