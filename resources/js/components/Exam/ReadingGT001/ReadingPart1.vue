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

// Track hovered question for showing flag icon
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

// Reading Part 1 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Delete highlight tooltip
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note tooltip (hover)
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

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
    q14: '',

});

// Text segments with their cumulative offsets in the full text
const passageText = `

<b>1.</b> Warning of an emergency evacuation will be marked by a number of short bell rings. (In the event of a power failure, this may be a hand-held bell or siren.) <br/>
<b>2.</b> All class work will cease immediately.<br/>
<b>3.</b> Students will leave their bags, books and other possessions where they are.<br/>
<b>4.</b> Teachers will take the class rolls.<br/>
<b>5.</b> Classes will vacate the premises using the nearest staircase. If these stairs are inaccessible, use the nearest alternative staircase. Do not use the lifts. Do not run.<br/>
<b>6.</b> Each class, under the teacher's supervision, will move in a brisk, orderly fashion to the paved quadrangle area adjacent to the car park.<br/>
<b>7.</b> All support staff will do the same.<br/>
<b>8.</b> The Marshalling Supervisor, Ms Randall, will be wearing a red cap and she will be waiting there with the master timetable and staff list in her possession.<br/>
<b>9.</b> Students assemble in the quad with their teacher at the time of evacuation. The teacher will do a head count and check the roll.<br/>
<b>10.</b> Each teacher sends a student to the Supervisor to report whether all students have been accounted for. After checking, students will sit down (in the event of rain or wet pavement they may remain standing).<br/>
<b>11.</b> The Supervisor will inform the Office when all staff and students have been accounted for.<br/>
<b>12.</b> All students, teaching staff and support personnel remain in the evacuation area until the All Clear signal is given.<br/>
<b>13.</b> The All Clear will be a long bell ring or three blasts on the siren.<br/>
<b>14.</b> Students will return to class in an orderly manner under teacher guidance.<br/>
<b>15.</b> In the event of an emergency occurring during lunch or breaks, students are to assemble in their home-room groups in the quad and await their home-room teacher.<br/>


`;

const communityEducationText = `Community Education

<b>Short Courses: Business</b>

Business Basics
Gain foundation knowledge for employment in an accounts position with bookkeeping and business basics through to intermediate level; suitable for anyone requiring knowledge from the ground up.
Code B/ED011
16th or 24th April
9am–4pm
Cost $420

<b>Bookkeeping</b>
This course will provide students with a comprehensive understanding of bookkeeping and a great deal of hands-on experience.
Code B/ED020
19th April
9am–2.30pm (one session only so advance bookings essential)
Cost $250

<b>New Enterprise Module</b>
Understand company structures, tax rates, deductions, employer obligations, profit and loss statements, GST and budgeting for tax.
Code B/ED030
15th or 27th May
6pm–9pm
Cost $105

<b>Social Networking – the Latest Marketing Tool</b>
This broad overview gives you the opportunity to analyse what web technologies are available and how they can benefit your organisation.
Code B/ED033
1st or 8th or 15th June
6pm–9pm
Cost $95

<b>Communication</b>
Take the fear out of talking to large gatherings of people. Gain the public-speaking experience that will empower you with better communication skills and confidence.
Code B/ED401
12th or 13th or 14th July
6pm–9pm
Cost $90`;


// Convert plain text offset to HTML offset
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;

    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') {
                htmlIndex++;
            }
            htmlIndex++; // Skip closing '>'
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

const rawSegments = [
    { id: 'section1', text: 'Part 1' },
    { id: 'questions-1-14', text: 'Read and answerQuestions 1–14' },

    { id: 'read-text-1-8', text: 'Read the text below and answer Questions 1–8' },

    { id: 'emergency-title', text: 'EMERGENCY PROCEDURES' },
    { id: 'revised-date', text: 'Revised July 2011' },
    { id: 'applies-to-all', text: 'THIS APPLIES TO ALL PERSONS ON THE SCHOOL CAMPUS' },

    {
        id: 'emergency-intro',
        text: 'In cases of emergency (e.g. fire), find the nearest teacher who will : send a messenger at full speed to the Office OR inform the Office via phone ext. 99.',
    },

    { id: 'evacuation-title', text: 'PROCEDURE FOR EVACUATION' },
    { id: 'passage-text', text: passageText },

    { id: 'read-text-9-14', text: 'Read the text below and answer Questions 9–14' },
    { id: 'community-education', text: communityEducationText },

    { id: 'q1-8-title', text: 'Questions 1–8' },
    { id: 'complete-sentences', text: 'Complete the sentences below.' },
    { id: 'choose-three-words', text: 'Choose NO MORE THAN THREE WORDS from the text for each answer.' },

    { id: 'q1-text-before', text: 'In an emergency, a teacher will either phone the office or' },
    { id: 'q2-text-before', text: 'The signal for evacuation will normally be several' },
    { id: 'q3-text-before', text: 'If possible, students should leave the building by the' },
    { id: 'q4-text-before', text: 'They then walk quickly to the' },
    { id: 'q5-text-after', text: 'will join the teachers and students in the quad.' },
    { id: 'q6-text-before', text: 'Each class teacher will count up his or her students and mark' },
    { id: 'q7-text-before', text: 'After the' },
    { id: 'q7-text-after', text: ', everyone may return to class.' },
    { id: 'q8-text-before', text: 'If there is an emergency at lunchtime, students gather in the quad in' },
    { id: 'q8-text-after', text: 'and wait for their teacher.' },



    { id: 'q9-14-title', text: 'Questions 9-14' },
    { id: 'statements-agree', text: 'Do the following statements agree with the information given in the text?' },
    { id: 'write-boxes', text: 'In boxes 9-14 on your answer sheet, write' },

    { id: 'true-label', text: 'TRUE' },
    { id: 'true-desc', text: 'if the statement agrees with the information' },

    { id: 'false-label', text: 'FALSE' },
    { id: 'false-desc', text: 'if the statement contradicts the information' },

    { id: 'not-given-label', text: 'NOT GIVEN' },
    { id: 'not-given-desc', text: 'if there is no information on this' },

    { id: 'q9-statement', text: '9. Business Basics is appropriate for beginners.' },
    { id: 'q10-statement', text: '10. Bookkeeping has no practical component.' },
    { id: 'q11-statement', text: '11. Bookkeeping is intended for advanced students only.' },
    { id: 'q12-statement', text: '12. The New Enterprise Module can help your business become more profitable.' },
    { id: 'q13-statement', text: '13. Social Networking focuses on a specific website to help your business succeed.' },
    { id: 'q14-statement', text: '14. The Communication class involves speaking in front of an audience.' },
];

const textSegments = ref(
    (() => {
        let runningOffset = 0;

        return rawSegments.map((segment) => {
            const currentOffset = runningOffset;
            runningOffset += getPlainTextLength(segment.text);

            return {
                ...segment,
                offset: currentOffset,
            };
        });
    })()
);

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentText: string) => {
    // Find this segment's offset
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    // Use plain text length for comparison
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    // Check if any highlights overlap with this segment (using plain text offsets)
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segmentText;
    }

    // Combine highlights and notes into a single list of annotations
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

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = segmentText;

    for (const annotation of sorted) {
        // Calculate the plain text position within this segment
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);

        if (plainStart < plainEnd) {
            // Convert plain text offsets to HTML offsets
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd = plainToHtmlOffset(result, plainEnd);

            const before = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after = result.substring(htmlEnd);

            if (annotation.type === 'note') {
                // Simple mark tag - tooltip is rendered via Teleport on hover
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }

    return result;
};

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(plainText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
    }

    // Combine highlights and notes into a single list of annotations
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

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = plainText;

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
        // Add highlight effect
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected || !selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);
        const rect = range.getBoundingClientRect();

        if (rect && contentTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50;
            const menuY = rect.top - menuHeight - 8;

            const viewportWidth = window.innerWidth;
            const menuWidth = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10),
            };

            showContextMenu.value = true;
        }

        // Find nearest ancestor with data-segment-id
        let targetElement: HTMLElement | null = null;
        const startContainer = range.startContainer;

        if (startContainer.nodeType === Node.TEXT_NODE) {
            targetElement = startContainer.parentElement?.closest('[data-segment-id]') as HTMLElement | null;
        } else {
            targetElement = (startContainer as HTMLElement).closest?.('[data-segment-id]') as HTMLElement | null;
        }

        if (!targetElement) {
            showContextMenu.value = false;
            return;
        }

        const segmentId = targetElement.getAttribute('data-segment-id');
        if (!segmentId) {
            showContextMenu.value = false;
            return;
        }

        const segment = textSegments.value.find((s) => s.id === segmentId);
        if (!segment) {
            showContextMenu.value = false;
            return;
        }

        const preSelectionRange = document.createRange();
        preSelectionRange.selectNodeContents(targetElement);
        preSelectionRange.setEnd(range.startContainer, range.startOffset);

        const plainTextOffset = preSelectionRange.toString().length;

        const normalizedSelected = selected.trim();
        const startOffset = segment.offset + plainTextOffset;
        const endOffset = startOffset + normalizedSelected.length;

        selectedText.value = normalizedSelected;
        selectionRange.value = { start: startOffset, end: endOffset };

        console.log('Selection:', {
            segmentId,
            selected: normalizedSelected,
            startOffset,
            endOffset,
            plainTextOffset,
        });
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    // Remove overlapping notes (last action wins - highlight overwrites note)
    notes.value = notes.value.filter((n) => {
        return !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start);
    });

    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);

    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;

    const modalWidth = 320; // w-80 = 20rem = 320px
    const modalHeight = 180; // Approximate height of the modal
    const padding = 10; // Minimum distance from viewport edges

    // Get fresh position based on current selection
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

    // Viewport boundary checking
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Check horizontal bounds (modal is centered, so check half-width on each side)
    const halfWidth = modalWidth / 2;
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    // Check vertical bounds - if modal goes below viewport, show above selection
    if (y + modalHeight > viewportHeight - padding) {
        // Try to position above the selection
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        // If still outside viewport (too close to top), position at top with padding
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

    // Remove overlapping highlights (last action wins - note overwrites highlight)
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    // Remove any existing notes that overlap with this range
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

// Close delete tooltip
const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

// Handle keyboard events
const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
};

// Handle click on content area - check if clicking on a highlight mark
const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        // Clicked on a highlight - show delete tooltip
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
        // Clicked outside highlight - close tooltips
        if (showDeleteTooltip.value) {
            closeDeleteTooltip();
        }
        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
    }
};

// Delete highlight - simple and direct
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        // Close tooltip and clear state first
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        // Then delete
        deleteHighlight(idToDelete);
    }
};

// Handle mouse enter on noted text to show tooltip
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

                // Position tooltip ABOVE the noted text with arrow pointing DOWN
                const tooltipHeight = 50; // Approximate tooltip height
                let y = rect.top - tooltipHeight - 8; // 8px gap above text

                // If not enough space above, show below
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

// Handle mouse leave from noted text
const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    // Check if we're moving to the tooltip itself
    if (relatedTarget?.closest('.note-hover-tooltip')) {
        return;
    }

    // Check if leaving a note mark
    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

// Handle mouse leave from the tooltip itself
const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

// Delete note from hover tooltip
const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
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

    // Set minimum and maximum widths (20% to 80%)
    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

// Load saved answers when component mounts
onMounted(async () => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);

    // Add resize event listeners
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});

onBeforeUnmount(() => {
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="section1" v-html="getHighlightedSegmentById('section1')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="questions-1-14"
                    v-html="getHighlightedSegmentById('questions-1-14')"
                ></p>
            </div>
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage Panel ── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">

                        <div>
                            <p class="tracking-wide select-text">
                                <span data-segment-id="read-text-1-8" v-html="getHighlightedSegmentById('read-text-1-8')"></span>
                            </p>
                        </div>
                        <div class="bg-gray-300 text-center text-xl font-bold">
                            <p class="select-text">
                                <span data-segment-id="emergency-title" v-html="getHighlightedSegmentById('emergency-title')"></span>
                            </p>
                        </div>
                        <div>
                            <p class="text-center text-sm select-text">
                                <span data-segment-id="revised-date" v-html="getHighlightedSegmentById('revised-date')"></span>
                            </p>
                        </div>
                        <div>
                            <p class="text-center font-bold select-text">
                                <span data-segment-id="applies-to-all" v-html="getHighlightedSegmentById('applies-to-all')"></span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm select-text">
                                <span data-segment-id="emergency-intro" v-html="getHighlightedSegmentById('emergency-intro')"></span>
                            </p>
                        </div>
                        <div>
                            <p class="mt-6 bg-gray-300 text-center select-text">
                                <span data-segment-id="evacuation-title" v-html="getHighlightedSegmentById('evacuation-title')"></span>
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6 px-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span data-segment-id="passage-text" class="text-lg text-gray-700" v-html="getHighlightedSegmentById('passage-text')"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text 2: Community Education -->
                    <div class="mt-8 border-t-2 border-gray-300 px-6 pt-6">
                        <div class="mb-4">
                            <p class="text-sm font-bold tracking-wide uppercase select-text">
                                <span data-segment-id="read-text-9-14" v-html="getHighlightedSegmentById('read-text-9-14')"></span>
                            </p>
                        </div>
                        <div class="space-y-4 select-text" :style="contentZoom">
                            <div class="text-justify leading-relaxed text-gray-700">
                                <span data-segment-id="community-education" class="text-lg whitespace-pre-line text-gray-700" v-html="getHighlightedSegmentById('community-education')"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Resize Handle ── -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Panel ── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 1–8: Sentence Completion -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q1-8-title" class="text-gray-700" v-html="getHighlightedSegmentById('q1-8-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="complete-sentences" v-html="getHighlightedSegmentById('complete-sentences')"></span><br />
                                        <span data-segment-id="choose-three-words" v-html="getHighlightedSegmentById('choose-three-words')"></span>
                                    </p>
                                </div>

                                <div class="space-y-3">

                                    <!-- Q1 -->
                                    <div
                                        id="question-1"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 1"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <span data-segment-id="q1-text-before" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q1-text-before')"></span>
                                        <input
                                            v-model="answers.q1"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="1"
                                            @focus="hoveredQuestion = 1"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span class="text-gray-700">.</span>
                                        <button
                                            v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                            @click.stop="toggleFlag(1)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q2 -->
                                    <div
                                        id="question-2"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 2"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <span data-segment-id="q2-text-before" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q2-text-before')"></span>
                                        <input
                                            v-model="answers.q2"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="2"
                                            @focus="hoveredQuestion = 2"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span class="text-gray-700">.</span>
                                        <button
                                            v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                            @click.stop="toggleFlag(2)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q3 -->
                                    <div
                                        id="question-3"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 3"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <span data-segment-id="q3-text-before" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q3-text-before')"></span>
                                        <input
                                            v-model="answers.q3"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="3"
                                            @focus="hoveredQuestion = 3"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span class="text-gray-700">.</span>
                                        <button
                                            v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                            @click.stop="toggleFlag(3)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q4 -->
                                    <div
                                        id="question-4"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 4"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <span data-segment-id="q4-text-before" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q4-text-before')"></span>
                                        <input
                                            v-model="answers.q4"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="4"
                                            @focus="hoveredQuestion = 4"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span class="text-gray-700">.</span>
                                        <button
                                            v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                            @click.stop="toggleFlag(4)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q5 (input FIRST, then text after) -->
                                    <div
                                        id="question-5"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 5"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <input
                                            v-model="answers.q5"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="5"
                                            @focus="hoveredQuestion = 5"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span data-segment-id="q5-text-after" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q5-text-after')"></span>
                                        <button
                                            v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                            @click.stop="toggleFlag(5)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q6 -->
                                    <div
                                        id="question-6"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 6"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <span data-segment-id="q6-text-before" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q6-text-before')"></span>
                                        <input
                                            v-model="answers.q6"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="6"
                                            @focus="hoveredQuestion = 6"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span class="text-gray-700">.</span>
                                        <button
                                            v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                            @click.stop="toggleFlag(6)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q7 (text before + input + text after) -->
                                    <div
                                        id="question-7"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 7"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <span data-segment-id="q7-text-before" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q7-text-before')"></span>
                                        <input
                                            v-model="answers.q7"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="7"
                                            @focus="hoveredQuestion = 7"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span data-segment-id="q7-text-after" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q7-text-after')"></span>
                                        <button
                                            v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                            @click.stop="toggleFlag(7)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q8 (text before + input + text after) -->
                                    <div
                                        id="question-8"
                                        class="relative flex flex-wrap items-center gap-2  border-gray-200 bg-white p-3"
                                        @mouseenter="hoveredQuestion = 8"
                                        @mouseleave="hoveredQuestion = null"
                                    >

                                        <span data-segment-id="q8-text-before" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q8-text-before')"></span>
                                        <input
                                            v-model="answers.q8"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="border border-gray-900 px-3 py-1 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="8"
                                            @focus="hoveredQuestion = 8"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <span data-segment-id="q8-text-after" class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q8-text-after')"></span>
                                        <button
                                            v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                            @click.stop="toggleFlag(8)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- Questions 9–14: TRUE / FALSE / NOT GIVEN -->
                            <div class="mt-6 border-t-2 border-gray-300 p-6">
                                <div class="mb-6">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span data-segment-id="q9-14-title" class="text-gray-700" v-html="getHighlightedSegmentById('q9-14-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="statements-agree" v-html="getHighlightedSegmentById('statements-agree')"></span><br />
                                        <span data-segment-id="write-boxes" v-html="getHighlightedSegmentById('write-boxes')"></span>
                                    </p>
                                </div>

                                <!-- TRUE/FALSE/NOT GIVEN legend box -->
                                <div class="mb-6 border border-gray-900 p-4">
                                    <div class="grid grid-cols-1 gap-2 text-sm">
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="true-label" v-html="getHighlightedSegmentById('true-label')"></span>
                                            </span>
                                            <span data-segment-id="true-desc" class="text-gray-700" v-html="getHighlightedSegmentById('true-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="false-label" v-html="getHighlightedSegmentById('false-label')"></span>
                                            </span>
                                            <span data-segment-id="false-desc" class="text-gray-700" v-html="getHighlightedSegmentById('false-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900">
                                                <span data-segment-id="not-given-label" v-html="getHighlightedSegmentById('not-given-label')"></span>
                                            </span>
                                            <span data-segment-id="not-given-desc" class="text-gray-700" v-html="getHighlightedSegmentById('not-given-desc')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">

                                    <!-- Q9 -->
                                    <div
                                        id="question-9"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 9"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">

                                            <span data-segment-id="q9-statement" class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q9-statement')"></span>
                                        </div>
                                        <div class="ml-10 space-y-2">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="toggleFlag(9)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q10 -->
                                    <div
                                        id="question-10"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 10"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">

                                            <span data-segment-id="q10-statement" class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q10-statement')"></span>
                                        </div>
                                        <div class="ml-10 space-y-2">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q11 -->
                                    <div
                                        id="question-11"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 11"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">

                                            <span data-segment-id="q11-statement" class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q11-statement')"></span>
                                        </div>
                                        <div class="ml-10 space-y-2">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q12 -->
                                    <div
                                        id="question-12"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 12"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span data-segment-id="q12-statement" class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q12-statement')"></span>
                                        </div>
                                        <div class="ml-10 space-y-2">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q13 -->
                                    <div
                                        id="question-13"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">

                                            <span data-segment-id="q13-statement" class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q13-statement')"></span>
                                        </div>
                                        <div class="ml-10 space-y-2">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q13" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q13" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q13" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q14 -->
                                    <div
                                        id="question-14"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">

                                            <span data-segment-id="q14-statement" class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q14-statement')"></span>
                                        </div>
                                        <div class="ml-10 space-y-2">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q14" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q14" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q14" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Inline Teleport Highlight Tooltips ── -->
    <Teleport to="body">
        <!-- Context Menu (Note + Highlight) -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
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
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
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
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave"
                @click.stop
            >
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop
        >
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                <input
                    v-model="noteInputText"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote"
                    @keyup.escape="cancelNote"
                />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote" class="border border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.part-header-box {
    background-color: #F1F2EC;
}
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

/* Ensure marks are visible inside gradient text */
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
</style>
