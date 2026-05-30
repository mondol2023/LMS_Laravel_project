import { ref } from 'vue';

export interface Highlight {
    id: number;
    text: string;
    color: string;
    start_offset: number;
    end_offset: number;
    segment_id?: string;
}

export function useHighlight() {
    const highlights = ref<Highlight[]>([]);
    const selectedText = ref('');
    const selectionRange = ref<{ start: number; end: number } | null>(null);
    const focusedHighlightId = ref<number | null>(null); // New ref for focused highlight
    let nextId = 1;

    const colors = [
        { name: 'nocolor', class: 'bg-transparent hover:bg-gray-100' },
        { name: 'yellow', class: 'bg-yellow-200 hover:bg-yellow-300' },
    ];

    /**
     * Check if a highlight overlaps with an existing highlight in the same segment
     */
    const findOverlappingHighlights = (startOffset: number, endOffset: number, segmentId?: string): Highlight[] => {
        return highlights.value.filter((h) => {
            // Only check overlaps within the same segment
            if (segmentId && h.segment_id && h.segment_id !== segmentId) {
                return false;
            }
            // Two ranges overlap if one starts before the other ends
            return h.start_offset < endOffset && h.end_offset > startOffset;
        });
    };

    /**
     * Add a new highlight or remove existing highlights if 'nocolor' is selected
     */
    const addHighlight = (text: string, color: string, startOffset: number, endOffset: number, segmentId?: string) => {
        // Find overlapping highlights in the same segment
        const overlapping = findOverlappingHighlights(startOffset, endOffset, segmentId);

        if (color === 'nocolor') {
            // Remove all overlapping highlights
            if (overlapping.length > 0) {
                overlapping.forEach((h) => deleteHighlight(h.id));
            }
            return;
        }

        // Remove all overlapping highlights before adding new one
        if (overlapping.length > 0) {
            overlapping.forEach((h) => deleteHighlight(h.id));
        }

        // Add the new highlight with segment_id
        highlights.value.push({
            id: nextId++,
            text,
            color,
            start_offset: startOffset,
            end_offset: endOffset,
            segment_id: segmentId,
        });
    };

    const deleteHighlight = (id: number) => {
        highlights.value = highlights.value.filter((h) => h.id !== id);
    };

    const applyHighlightsToText = (text: string, segmentId?: string): string => {
        if (!text || highlights.value.length === 0) return text;

        // Filter highlights by segment_id if provided
        let filteredHighlights = highlights.value;
        if (segmentId) {
            filteredHighlights = highlights.value.filter(h => h.segment_id === segmentId);
        }

        if (filteredHighlights.length === 0) return text;

        // Sort highlights by start offset in descending order
        const sortedHighlights = [...filteredHighlights].sort((a, b) => b.start_offset - a.start_offset);

        let result = text;
        for (const highlight of sortedHighlights) {
            const before = result.substring(0, highlight.start_offset);
            const highlighted = result.substring(highlight.start_offset, highlight.end_offset);
            const after = result.substring(highlight.end_offset);

            // Add animation class if this highlight is the focused one
            const animationClass = highlight.id === focusedHighlightId.value ? 'focused-note-animation' : '';

            result = `${before}<mark class="highlight-${highlight.color} ${animationClass}" data-highlight-id="${highlight.id}" data-note-id="${highlight.id}">${highlighted}</mark>${after}`;
        }

        return result;
    };

    const setFocusedHighlightId = (id: number | null) => {
        focusedHighlightId.value = id;
    };

    return {
        highlights,
        selectedText,
        selectionRange,
        colors,
        addHighlight,
        deleteHighlight,
        applyHighlightsToText,
        findOverlappingHighlights,
        setFocusedHighlightId, // Expose the setter
    };
}
