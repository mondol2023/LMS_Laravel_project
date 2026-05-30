import { ref, type Ref } from 'vue';

export interface Note {
    id: number;
    text: string;
    selectedText: string;
    note: string;
    start: number;
    end: number;
}

export interface TextSegment {
    id: number | string;
    text: string;
    offset: number;
}

export function useTooltip(textSegments: Ref<TextSegment[]>, notes: Ref<Note[]>) {
    // Context menu (highlight/note selection)
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

    // Note input modal
    const showNoteInput = ref(false);
    const noteInputText = ref('');
    const noteInputPosition = ref({ x: 0, y: 0 });

    // Selection state
    const selectedText = ref('');
    const selectionRange = ref<{ start: number; end: number } | null>(null);

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
                // Handle both string and number segment IDs
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
                // Position tooltip ABOVE the selection with arrow pointing down
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

                selectedText.value = selection.toString();
                selectionRange.value = { start: startAbsOffset, end: endAbsOffset };
            } else {
                showContextMenu.value = false;
            }
        }, 10);
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

    const cancelNote = () => {
        noteInputText.value = '';
        showNoteInput.value = false;
    };

    const closeDeleteTooltip = () => {
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
    };

    const handleClickOutside = (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        // Don't close context menu if clicking on highlight marks (delete tooltip will handle this)
        if (target.closest('mark[data-highlight-id]')) {
            return;
        }

        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
        // Note: Delete tooltip is now handled by backdrop in HighlightTooltips component
    };

    const handleKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            if (showContextMenu.value) {
                showContextMenu.value = false;
            }
            if (showDeleteTooltip.value) {
                closeDeleteTooltip();
            }
            if (showNoteInput.value) {
                cancelNote();
            }
        }
    };

    // Legacy document-level handler - kept for backwards compatibility but not recommended
    const handleHighlightClick = (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

        if (highlightMark && !target.closest('.note-highlight')) {
            event.preventDefault();
            event.stopPropagation();

            const highlightId = highlightMark.getAttribute('data-highlight-id');
            if (highlightId) {
                const rect = highlightMark.getBoundingClientRect();

                deleteTooltipPosition.value = {
                    x: rect.left + rect.width / 2,
                    y: rect.bottom + 8,
                };

                highlightToDelete.value = parseInt(highlightId, 10);
                showDeleteTooltip.value = true;
                showContextMenu.value = false;
            }
        }
    };

    // New content click handler - Use this on your content container instead of document listeners
    const handleContentClick = (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

        if (highlightMark && !target.closest('.note-highlight')) {
            event.stopPropagation();
            const highlightId = highlightMark.getAttribute('data-highlight-id');
            if (highlightId) {
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
            // Clicked outside of highlight - close context menu if open
            if (showContextMenu.value) {
                showContextMenu.value = false;
            }
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
        const relatedTarget = event.relatedTarget as HTMLElement;

        if (relatedTarget?.closest('.note-hover-tooltip')) {
            return;
        }

        const target = event.target as HTMLElement;
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

    const setupEventListeners = () => {
        document.addEventListener('click', handleClickOutside);
        // Note: handleHighlightClick removed - use handleContentClick on content container instead
        document.addEventListener('keydown', handleKeyDown);
        document.addEventListener('mouseover', handleNoteMouseEnter);
        document.addEventListener('mouseout', handleNoteMouseLeave);
    };

    const cleanupEventListeners = () => {
        document.removeEventListener('click', handleClickOutside);
        document.removeEventListener('keydown', handleKeyDown);
        document.removeEventListener('mouseover', handleNoteMouseEnter);
        document.removeEventListener('mouseout', handleNoteMouseLeave);
    };

    return {
        // Context menu
        contextMenuPosition,
        showContextMenu,
        // Delete highlight
        showDeleteTooltip,
        deleteTooltipPosition,
        highlightToDelete,
        closeDeleteTooltip,
        // Note tooltip
        showNoteTooltip,
        noteTooltipPosition,
        hoveredNoteId,
        hoveredNoteText,
        handleTooltipMouseLeave,
        // Note input
        showNoteInput,
        noteInputText,
        noteInputPosition,
        openNoteInput,
        cancelNote,
        // Selection
        selectedText,
        selectionRange,
        // Handlers
        handleContentTextSelect,
        handleContentClick,
        setupEventListeners,
        cleanupEventListeners,
    };
}
