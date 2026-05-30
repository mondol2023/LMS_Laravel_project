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

const passageText = ref(`<b>A</b>  They play hard, they play often, and they play to win. Australian sports teams win more than their fair share of titles, demolishing rivals with seeming ease. How do they do it? A big part of the secret is an extensive and expensive network of sporting academies underpinned by science and medicine. At the Australian Institute of Sport (AIS), hundreds of youngsters and pros live and train under the eyes of coaches. Another body, the Australian Sports Commission (ASC), finances programmes of excellence in a total of 96 sports for thousands of sportsmen and women. Both provide intensive coaching, training facilities and nutritional advice.

<b>B</b>  Inside the academies, science takes centre stage. The AIS employs more than 100 sports scientists and doctors, and collaborates with scores of others in universities and research centres. AIS scientists work across a number of sports, applying skills learned in one - such as building muscle strength in golfers - to others, such as swimming and squash. They are backed up by technicians who design instruments to collect data from athletes. They all focus on one aim: winning. 'We can't waste our time looking at ethereal scientific questions that don't help the coach work with an athlete and improve performance.' says Peter Fricker, chief of science at AIS.

<b>C</b>  A lot of their work comes down to measurement - everything from the exact angle of a swimmer's dive to the second-by-second power output of a cyclist. This data is used to wring improvements out of athletes. The focus is on individuals, tweaking performances to squeeze an extra hundredth of a second here, an extra millimetre there. No gain is too slight to bother with. It's the tiny, gradual improvements that add up to world-beating results. To demonstrate how the system works, Bruce Mason at AIS shows off the prototype of a 3D analysis tool for studying swimmers. A wire-frame model of a champion swimmer slices through the water, her arms moving in slow motion. Looking side-on, Mason measures the distance between strokes. From above, he analyses how her spine swivels. When fully developed, this system will enable him to build a biomechanical profile for coaches to use to help budding swimmers. Mason's contribution to sport also includes the development of the SWAN (SWimming ANalysis) system now used in Australian national competitions. It collects images from digital cameras running at 50 frames a second and breaks down each part of a swimmer's performance into factors that can be analysed individually - stroke length, stroke frequency, the average duration of each stroke, velocity, start, lap and finish times, and so on. At the end of each race, SWAN spits out data on each swimmer.

<b>D</b>  'Take a look.' says Mason, pulling out a sheet of data. He points out the data on the swimmers in second and third place, which shows that the one who finished third actually swam faster. So why did he finish 35 hundredths of a second down? 'His turn times were 44 hundredths of a second behind the other guy.' says Mason. 'If he can improve on his turns, he can do much better.' This is the kind of accuracy that AIS scientists' research is bringing to a range of sports. With the Cooperative Research Centre for Micro Technology in Melbourne, they are developing unobtrusive sensors that will be embedded in an athlete's clothes or running shoes to monitor heart rate, sweating, heat production or any other factor that might have an impact on an athlete's ability to run. There's more to it than simply measuring performance. Fricker gives the example of athletes who may be down with coughs and colds 11 or 12 times a year. After years of experimentation, AIS and the University of Newcastle in New South Wales developed a test that measures how much of the immune-system protein immunoglobulin A is present in athletes' saliva. If IgA levels suddenly fall below a certain level, training is eased or dropped altogether. Soon, IgA levels start rising again, and the danger passes. Since the tests were introduced, AIS athletes in all sports have been remarkably successful at staying healthy.

<b>E</b>  Using data is a complex business. Well before a championship, sports scientists and coaches start to prepare the athlete by developing a 'competition model', based on what they expect will be the winning times. 'You design the model to make that time.' says Mason. 'A start of this much, each free-swimming period has to be this fast, with a certain stroke frequency and stroke length, with turns done in these times'. All the training is then geared towards making the athlete hit those targets, both overall and for each segment of the race. Techniques like these have transformed Australia into arguably the world's most successful sporting nation.

<b>F</b>  Of course, there's nothing to stop other countries copying - and many have tried. Some years ago, the AIS unveiled coolant-lined jackets for endurance athletes. At the Atlanta Olympic Games in 1996, these sliced as much as two per cent off cyclists' and rowers' times. Now everyone uses them. The same has happened to the 'altitude tent', developed by AIS to replicate the effect of altitude training at sea level. But Australia's success story is about more than easily copied technological fixes, and up to now no nation has replicated its all-encompassing system.`);

// All static texts in the component, in order of appearance, for offset calculation
const allStaticTexts = [
    // Passage Panel
    'Part 2',                                                           // 0
    'Read the text and answer questions 1–13.',                         // 1
    "AUSTRALIA'S SPORTING SUCCESS",                                     // 2
    '',                                                                 // 3 (no subtitle)
    passageText.value,                                                  // 4

    // Questions Section
    'QUESTIONS',                                                        // 5
    'Answer all questions based on Reading Passage 2',                  // 6
    'Questions 1-7',                                                    // 7
    'Reading Passage 2 has six paragraphs, A-F.',                       // 8
    'Which paragraph contains the following information?',              // 9
    'Write the correct letter A-F in boxes 1-7 on your answer sheet. NB You may use any letter more than once.', // 10

    // Q1-7 descriptions
    'a reference to the exchange of expertise between different sports',                    // 11
    'an explanation of how visual imaging is employed in investigations',                   // 12
    'a reason for narrowing the scope of research activity',                                // 13
    'how some AIS ideas have been reproduced',                                              // 14
    'how obstacles to optimum achievement can be investigated',                             // 15
    'an overview of the funded support of athletes',                                        // 16
    'how performance requirements are calculated before an event',                          // 17

    // Q8-11
    'Questions 8-11',                                                   // 18
    'Classify the following techniques according to whether the writer states they -',      // 19
    'In boxes 8-11 on your answer sheet, write -',                      // 20
    'A  are currently exclusively used by Australians',                 // 21
    'B  will be used in the future by Australians',                     // 22
    'C  are currently used by both Australians and their rivals',       // 23

    // Q8-11 statements
    'cameras',                                                          // 24
    'sensors',                                                          // 25
    'protein tests',                                                    // 26
    'altitude tents',                                                   // 27

    // Q12-13
    'Questions 12 and 13',                                              // 28
    'Choose NO MORE THAN THREE WORDS AND/OR A NUMBER from the Reading Passage for each answer.', // 29
    'Write your answers in boxes 12 and 13 on your answer sheet.',      // 30
    'What is produced to help an athlete plan their performance in an event?',              // 31
    'By how much did some cyclists\' performance improve at the 1996 Olympic Games?',      // 32

    // Question number labels
    '<b>1.</b>',   // 33
    '<b>2.</b>',   // 34
    '<b>3.</b>',   // 35
    '<b>4.</b>',   // 36
    '<b>5.</b>',   // 37
    '<b>6.</b>',   // 38
    '<b>7.</b>',   // 39
    '<b>8.</b>',   // 40
    '<b>9.</b>',   // 41
    '<b>10.</b>',  // 42
    '<b>11.</b>',  // 43
    '<b>12.</b>',  // 44
    '<b>13.</b>',  // 45
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
                                    <span class="text-base text-gray-700 select-text" data-segment-id="segment_4"
                                        v-html="getHighlightedSegment(allStaticTexts[4])"></span>
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

                            <!-- Questions 1-7 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="segment_7"
                                                v-html="getHighlightedSegment(allStaticTexts[7])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_8"
                                            v-html="getHighlightedSegment(allStaticTexts[8])"></span>
                                    </p>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_9"
                                            v-html="getHighlightedSegment(allStaticTexts[9])"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_10"
                                            v-html="getHighlightedSegment(allStaticTexts[10])"></span>
                                    </p>
                                </div>
                                <div class="space-y-4">
                                    <!-- Question 1 -->
                                    <div id="question-1" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_33"
                                            v-html="getHighlightedSegment(allStaticTexts[33])"></span>
                                        <select v-model="answers.q1"
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
                                            <span class="select-text" data-segment-id="segment_11"
                                                v-html="getHighlightedSegment(allStaticTexts[11])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                            @click.stop="toggleFlag(1)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 2 -->
                                    <div id="question-2" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_34"
                                            v-html="getHighlightedSegment(allStaticTexts[34])"></span>
                                        <select v-model="answers.q2"
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
                                            <span class="select-text" data-segment-id="segment_12"
                                                v-html="getHighlightedSegment(allStaticTexts[12])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                            @click.stop="toggleFlag(2)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 3 -->
                                    <div id="question-3" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_35"
                                            v-html="getHighlightedSegment(allStaticTexts[35])"></span>
                                        <select v-model="answers.q3"
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
                                            <span class="select-text" data-segment-id="segment_13"
                                                v-html="getHighlightedSegment(allStaticTexts[13])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                            @click.stop="toggleFlag(3)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 4 -->
                                    <div id="question-4" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_36"
                                            v-html="getHighlightedSegment(allStaticTexts[36])"></span>
                                        <select v-model="answers.q4"
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
                                            <span class="select-text" data-segment-id="segment_14"
                                                v-html="getHighlightedSegment(allStaticTexts[14])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                            @click.stop="toggleFlag(4)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 5 -->
                                    <div id="question-5" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_37"
                                            v-html="getHighlightedSegment(allStaticTexts[37])"></span>
                                        <select v-model="answers.q5"
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
                                            <span class="select-text" data-segment-id="segment_15"
                                                v-html="getHighlightedSegment(allStaticTexts[15])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                            @click.stop="toggleFlag(5)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 6 -->
                                    <div id="question-6" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 6" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_38"
                                            v-html="getHighlightedSegment(allStaticTexts[38])"></span>
                                        <select v-model="answers.q6"
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
                                            <span class="select-text" data-segment-id="segment_16"
                                                v-html="getHighlightedSegment(allStaticTexts[16])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                            @click.stop="toggleFlag(6)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 7 -->
                                    <div id="question-7" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_39"
                                            v-html="getHighlightedSegment(allStaticTexts[39])"></span>
                                        <select v-model="answers.q7"
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
                                            <span class="select-text" data-segment-id="segment_17"
                                                v-html="getHighlightedSegment(allStaticTexts[17])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                            @click.stop="toggleFlag(7)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 8-11 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="segment_18"
                                                v-html="getHighlightedSegment(allStaticTexts[18])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_19"
                                            v-html="getHighlightedSegment(allStaticTexts[19])"></span>
                                    </p>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_20"
                                            v-html="getHighlightedSegment(allStaticTexts[20])"></span>
                                    </p>
                                    <p class="text-base text-gray-700 sm:text-base pl-4">
                                        <span class="font-semibold" data-segment-id="segment_21"
                                            v-html="getHighlightedSegment(allStaticTexts[21])"></span>
                                    </p>
                                    <p class="text-base text-gray-700 sm:text-base pl-4">
                                        <span class="font-semibold" data-segment-id="segment_22"
                                            v-html="getHighlightedSegment(allStaticTexts[22])"></span>
                                    </p>
                                    <p class="mb-4 text-base text-gray-700 sm:text-base pl-4">
                                        <span class="font-semibold" data-segment-id="segment_23"
                                            v-html="getHighlightedSegment(allStaticTexts[23])"></span>
                                    </p>
                                </div>
                                <div class="space-y-4">
                                    <!-- Question 8 -->
                                    <div id="question-8" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_40"
                                            v-html="getHighlightedSegment(allStaticTexts[40])"></span>
                                        <select v-model="answers.q8"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_24"
                                                v-html="getHighlightedSegment(allStaticTexts[24])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                            @click.stop="toggleFlag(8)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 9 -->
                                    <div id="question-9" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_41"
                                            v-html="getHighlightedSegment(allStaticTexts[41])"></span>
                                        <select v-model="answers.q9"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_25"
                                                v-html="getHighlightedSegment(allStaticTexts[25])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="toggleFlag(9)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 10 -->
                                    <div id="question-10" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_42"
                                            v-html="getHighlightedSegment(allStaticTexts[42])"></span>
                                        <select v-model="answers.q10"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_26"
                                                v-html="getHighlightedSegment(allStaticTexts[26])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 11 -->
                                    <div id="question-11" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <span class="text-gray-900 shrink-0" data-segment-id="segment_43"
                                            v-html="getHighlightedSegment(allStaticTexts[43])"></span>
                                        <select v-model="answers.q11"
                                            class="w-24 flex-shrink-0 border border-gray-900 px-2 py-0.5 text-base transition-colors focus:border-black focus:outline-none sm:text-base">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                        </select>
                                        <p class="flex-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                            <span class="select-text" data-segment-id="segment_27"
                                                v-html="getHighlightedSegment(allStaticTexts[27])"></span>
                                        </p>
                                        <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 12-13 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="segment_28"
                                                v-html="getHighlightedSegment(allStaticTexts[28])"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_29"
                                            v-html="getHighlightedSegment(allStaticTexts[29])"></span>
                                    </p>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="segment_30"
                                            v-html="getHighlightedSegment(allStaticTexts[30])"></span>
                                    </p>
                                </div>
                                <div class="space-y-4">
                                    <!-- Question 12 -->
                                    <div id="question-12" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <!-- <span class="text-gray-900 shrink-0 font-medium" data-segment-id="segment_44"
                                            v-html="getHighlightedSegment(allStaticTexts[44])"></span> -->
                                        <div class="flex flex-col gap-2 flex-1">
                                            <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                <span class="select-text" data-segment-id="segment_31"
                                                    v-html="getHighlightedSegment(allStaticTexts[31])"></span>
                                            </p>
                                            <input
                                                v-model="answers.q12"
                                                type="text"
                                                placeholder="12"
                                                class="w-1/2 text-center border border-gray-900 px-3 py-1.5 text-base transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                                                spellcheck="false"
                                                autocomplete="off"
                                            />
                                        </div>
                                        <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 13 -->
                                    <div id="question-13" class="relative flex items-start gap-3 pr-10"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <!-- <span class="text-gray-900 shrink-0 font-medium" data-segment-id="segment_45"
                                            v-html="getHighlightedSegment(allStaticTexts[45])"></span> -->
                                        <div class="flex flex-col gap-2 flex-1">
                                            <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                <span class="select-text" data-segment-id="segment_32"
                                                    v-html="getHighlightedSegment(allStaticTexts[32])"></span>
                                            </p>
                                            <input
                                                v-model="answers.q13"
                                                type="text"
                                                placeholder="13"
                                                class="w-1/2 border text-center border-gray-900 px-3 py-1.5 text-base transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                                                spellcheck="false"
                                                autocomplete="off"
                                            />
                                        </div>
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
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

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>