<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
    fontSize?: number;
}

const props = withDefaults(defineProps<Props>(), {
    fontSize: 16,
});

const emit = defineEmits<{
    wordCountChange: [count: number];
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

const textStyle = computed(() => ({
    fontSize: `${props.fontSize}px`,
    lineHeight: '1.7',
}));

const answer = ref('');
const wordCount = ref(0);
const questionTextRef = ref<HTMLDivElement | null>(null);
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

const questionText = ref(`While some believe that technological advances in education will eliminate the need for physical classrooms, others argue that direct interaction between teachers and students is essential.


Discuss both views and give your opinion.
Give reasons for your answer and include any relevant examples from your own knowledge or experience
`);

// Resize functionality
const PANEL_WIDTH_KEY = 'writing-part2-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

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

// Expose methods for parent component
const getAnswers = () => {
    return {
        part2: answer.value || null,
    };
};

// Focus on a specific note's text
const focusNote = (noteData: { selectedText: string; start: number; end: number }) => {
    const marks = document.querySelectorAll('mark[data-highlight-id]');
    let foundMark: Element | null = null;

    for (const mark of marks) {
        if (mark.textContent?.trim() === noteData.selectedText.trim()) {
            foundMark = mark;
            break;
        }
    }

    if (foundMark) {
        foundMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
        foundMark.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2', 'transition-all');
        requestAnimationFrame(() => {
            setTimeout(() => {
                foundMark?.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2');
            }, 2000);
        });
        return;
    }

    if (!questionTextRef.value) return;

    const searchText = noteData.selectedText.trim();
    const walker = document.createTreeWalker(questionTextRef.value, NodeFilter.SHOW_TEXT, null);
    let node;

    while ((node = walker.nextNode())) {
        const text = node.textContent || '';
        const index = text.indexOf(searchText);

        if (index !== -1 && node.parentElement) {
            const range = document.createRange();
            range.setStart(node, index);
            range.setEnd(node, Math.min(index + searchText.length, text.length));

            const span = document.createElement('span');
            span.className = 'bg-gray-200 ring-4 ring-black ring-offset-2 transition-all';

            try {
                range.surroundContents(span);
                span.scrollIntoView({ behavior: 'smooth', block: 'center' });

                requestAnimationFrame(() => {
                    setTimeout(() => {
                        const parent = span.parentNode;
                        if (parent) {
                            parent.replaceChild(document.createTextNode(searchText), span);
                        }
                    }, 2000);
                });
                return;
            } catch (e) {
                console.warn('Could not surround contents:', e);
            }
        }
    }
};

defineExpose({
    getAnswers,
    deleteNote: (id: number) => {
        notes.value = notes.value.filter((note) => note.id !== id);
    },
    focusNote,
    notes,
});

const calculateWordCount = (text: string): number => {
    if (!text.trim()) return 0;
    return text.trim().split(/\s+/).length;
};

const highlightedQuestionText = computed(() => {
    let text = applyHighlightsToText(questionText.value);

    if (notes.value.length > 0) {
        for (const note of notes.value) {
            const escapedText = note.selectedText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

            const highlightPattern = new RegExp(`(<mark[^>]*>)(${escapedText})(</mark>)`, 'i');

            const beforeReplace = text;
            text = text.replace(highlightPattern, (match, openTag, content, closeTag) => {
                if (content.includes('note-highlight')) {
                    return match;
                }
                return `${openTag}<mark class="note-highlight" data-note-id="${note.id}">${content}</mark>${closeTag}`;
            });

            if (text === beforeReplace) {
                const plainPattern = new RegExp(`(?<!<[^>]*)(${escapedText})(?![^<]*>)`, '');

                let replaced = false;
                text = text.replace(plainPattern, (match) => {
                    if (replaced || match.includes('<')) {
                        return match;
                    }
                    replaced = true;
                    return `<mark class="note-highlight" data-note-id="${note.id}">${match}</mark>`;
                });
            }
        }
    }

    return text;
});

watch(answer, (newValue) => {
    const count = calculateWordCount(newValue);
    wordCount.value = count;
    emit('wordCountChange', count);
});

watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

const { selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights, applyHighlightsToText } = useHighlight();

// ✅ FIXED: Handles multi-line selection correctly
const handleQuestionTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        if (!range || !questionTextRef.value) return;

        // ✅ KEY FIX: Use getClientRects() to handle multi-line selections.
        // getBoundingClientRect() on a multi-line range can return a rect
        // that spans from the start of the first line to the end of the last,
        // making the calculated center wrong. getClientRects() returns one
        // rect per line, so we can precisely find the top of the first line
        // and the horizontal center of the last line for tooltip placement.
        const rects = Array.from(range.getClientRects());

        if (rects.length === 0) {
            showContextMenu.value = false;
            return;
        }

        const firstRect = rects[0];
        const lastRect = rects[rects.length - 1];

        // Center X: use the last line's rect center (where selection ends)
        // Top Y: use the very top of the first rect so tooltip appears above selection
        const menuX = lastRect.left + lastRect.width / 2;
        const menuHeight = 60; // Approximate tooltip height
        const menuY = firstRect.top - menuHeight - 8; // 8px gap above selection

        const viewportWidth = window.innerWidth;
        const menuWidth = 140;

        contextMenuPosition.value = {
            x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
            y: Math.max(menuY, 10),
        };
        showContextMenu.value = true;

        // Store selection info
        if (selection) {
            const preSelectionRange = range.cloneRange();
            const container = questionTextRef.value;
            preSelectionRange.selectNodeContents(container);
            preSelectionRange.setEnd(range.startContainer, range.startOffset);

            const plainTextBefore = preSelectionRange.toString();
            const startOffset = plainTextBefore.length;
            const endOffset = startOffset + selected.length;

            selectedText.value = selected;
            selectionRange.value = { start: startOffset, end: endOffset };
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    const overlappingNotes = notes.value.filter((note) => {
        const noteStart = note.start;
        const noteEnd = note.end;
        const selStart = selectionRange.value!.start;
        const selEnd = selectionRange.value!.end;
        return !(noteEnd <= selStart || noteStart >= selEnd);
    });

    if (overlappingNotes.length > 0) {
        notes.value = notes.value.filter((note) => !overlappingNotes.includes(note));
    }

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

        // ✅ Also use getClientRects() here for consistent positioning
        const rects = Array.from(range.getClientRects());
        if (rects.length > 0) {
            const lastRect = rects[rects.length - 1];
            x = lastRect.left + lastRect.width / 2;
            y = lastRect.bottom + 10;
        } else {
            const rect = range.getBoundingClientRect();
            x = rect.left + rect.width / 2;
            y = rect.bottom + 10;
        }
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
            const rects = Array.from(range.getClientRects());
            const firstRect = rects[0] ?? range.getBoundingClientRect();
            y = firstRect.top - modalHeight - 10;
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

    const overlapping = findOverlappingHighlights(selectionRange.value.start, selectionRange.value.end);
    overlapping.forEach((h) => deleteHighlight(h.id));

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

const closeContextMenu = () => {
    showContextMenu.value = false;
};

watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

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
            const note = notes.value.find((n) => n.id === parseInt(noteId, 10));
            if (note) {
                hoveredNoteId.value = note.id;
                hoveredNoteText.value = note.note;

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
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    if (target.closest('mark.note-highlight[data-note-id]')) {
        if (relatedTarget?.closest('.note-hover-tooltip')) {
            return;
        }
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
        notes.value = notes.value.filter((note) => note.id !== hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleClickOutside = () => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
};

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
    const count = calculateWordCount(answer.value);
    wordCount.value = count;
    emit('wordCountChange', count);

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
</script>

<template>
    <div class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Instruction Box - Full Width -->
        <div class="mx-4 mt-2 border-b-1 border-gray-400 bg-gray-100 px-4 py-3 sm:mx-6 lg:mx-8">
            <p class="text-sm font-bold text-gray-900 sm:text-base">Part 2</p>
            <p class="text-sm text-gray-700">You should spend about 40 minutes on this task. Write at least 250 words.</p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Question Section -->
                <div
                    class="question-panel mb-4 max-h-screen overflow-y-auto bg-white p-4 sm:p-6 lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div ref="questionTextRef" @mouseup="handleQuestionTextSelect" @click="handleContentClick" class="noted-content space-y-3 select-text sm:space-y-4">
                        <div class="whitespace-pre-wrap text-black" :style="textStyle" v-html="highlightedQuestionText"></div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4"
                                transform="rotate(90 12 12)"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Answer Section -->
                <div class="answer-panel max-h-screen overflow-y-auto bg-white px-4 py-2 sm:px-6" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <textarea
                        v-model="answer"
                        :style="textStyle"
                        class="h-[30vh] w-full resize-none border border-gray-400 bg-white p-3 text-black transition-all duration-200 focus:border-black focus:outline-none sm:h-[40vh] sm:p-4 lg:h-[55vh]"
                        placeholder="Write your answer here..."
                        spellcheck="false"
                    ></textarea>
                    <div class="mt-2 flex justify-end">
                        <div class="text-sm font-medium text-black">
                            Word count: <span class="font-bold">{{ wordCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu for Highlighting - Speech Bubble Style -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-9999"
                    :style="{
                        left: `${contextMenuPosition.x}px`,
                        top: `${contextMenuPosition.y}px`,
                        transform: 'translateX(-50%)',
                    }"
                    @click.stop
                >
                    <!-- Tooltip Content -->
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <!-- Note Button -->
                        <button
                            @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                            title="Add Note"
                        >
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"
                                />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <!-- Highlight Button -->
                        <button
                            @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                            title="Highlight"
                        >
                            <svg
                                class="h-5 w-5 text-gray-900"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                            >
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                        </button>
                    </div>
                    <!-- Arrow pointing down -->
                    <div class="tooltip-arrow"></div>
                </div>
            </div>

            <!-- Delete Highlight Tooltip -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
                <div
                    class="delete-highlight-tooltip fixed z-9999"
                    :style="{
                        left: `${deleteTooltipPosition.x}px`,
                        top: `${deleteTooltipPosition.y}px`,
                        transform: 'translateX(-50%)',
                    }"
                >
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button
                            @click="confirmDeleteHighlight"
                            type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                        >
                            <svg
                                class="h-5 w-5 text-gray-700"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
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
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-9999"
                    :style="{
                        left: `${noteTooltipPosition.x}px`,
                        top: `${noteTooltipPosition.y}px`,
                        transform: 'translateX(-50%)',
                    }"
                    @mouseleave="handleTooltipMouseLeave"
                    @click.stop
                >
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                ></path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button
                            @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                            title="Delete note"
                        >
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                ></path>
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input Modal -->
            <div
                v-if="showNoteInput"
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{
                    left: `${noteInputPosition.x}px`,
                    top: `${noteInputPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                        "{{ selectedText }}"
                    </p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        spellcheck="false"
                        autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        @click="cancelNote"
                        class="border-2 border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <button @click="saveNote" class="bg-black px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">
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
.question-panel {
    width: 100%;
}

.answer-panel {
    width: 100%;
}

@media (min-width: 1024px) {
    .question-panel {
        width: calc(var(--panel-width) - 0.5rem);
    }

    .answer-panel {
        width: calc(100% - var(--panel-width) - 0.5rem);
    }
}

mark[data-highlight-id] {
    padding: 2px 0;
}

.highlight-yellow {
    background-color: rgba(229, 229, 229, 0.6);
}

.highlight-green {
    background-color: rgba(204, 204, 204, 0.6);
}

.highlight-blue {
    background-color: rgba(178, 178, 178, 0.6);
}

.highlight-pink {
    background-color: rgba(153, 153, 153, 0.6);
}

.highlight-orange {
    background-color: rgba(127, 127, 127, 0.6);
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
</style>
