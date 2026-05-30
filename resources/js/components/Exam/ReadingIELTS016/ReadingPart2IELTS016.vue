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

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

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

// Reading Part 2 component

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part2-panel-width';
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

// Text segments with their cumulative offsets in the full text
const passageText = `Section A
The probability that two right-handed people would have a left-handed child is only about 9.5 percent. The chance rises to 19.5 percent if one parent is a lefty and 26 percent if both parents are left-handed. The preference, however, could also stem from an infant&#39;s imitation of his parents. To test genetic influence, starting in the 1970s British biologist Marian Annett of the University of Leicester hypothesized that no single gene determines handedness. Rather, during fetal development, a certain molecular factor helps to strengthen the brain&#39;s left hemisphere, which increases the probability that the right hand will be dominant, because the left side of the brain controls the right side of the body, and vice versa. Among the minority of people who lack this factor, handedness develops entirely by chance. Research conducted on twins complicates the theory, however. One in five sets of identical twins involves one right-handed and one left-handed person, despite the fact that their genetic material is the same. Genes, therefore, are not solely responsible for handedness.

Section B
The genetic theory is also undermined by results from Peter Hepper and his team at Queen&#39;s University in Belfast, Ireland. In 2004 the psychologists used ultrasound to show that by the 15th week of pregnancy, fetuses already have a preference as to which thumb they suck. In most cases, the preference continued after birth. At 15 weeks, though, the brain does not yet have control over the body&#39;s limbs. Hepper speculates that fetuses tend to prefer whichever side of the body is developing quicker and that their movements, in turn, influence the brain&#39;s development. Whether this early preference is temporary or holds up throughout development and infancy is unknown. Genetic predetermination is also contradicted by the widespread observation that children do not settle on either their right or left hand until they are two or three years old.

Section C
But even if these correlations were true, they did not explain what actually causes left-handedness. Furthermore, specialization on either side of the body is common among animals. Cats will favor one paw over another when fishing toys out from under the couch. Horses stomp more frequently with one hoof than the other. Certain crabs motion predominantly with the left or right claw. In evolutionary terms, focusing power and dexterity in one limb is more efficient than having to train two, four or even eight limbs equally. Yet for most animals, the preference for one side or the other is seemingly random. The overwhelming dominance of the right hand is associated only with humans. That fact directs attention toward the brain&#39;s two hemispheres and perhaps toward language.

Section D
Interest in hemisphere dates back to at least 1836. That year, at a medical conference, French physician Marc Dax reported on an unusual commonality among his patients. During his many years as a country doctor, Dax had encountered more than 40 men and women for whom speech was difficult, the result of some kind of brain damage. What was unique was that every individual suffered damage to the left side of the brain. At the conference, Dax elaborated on his theory, stating that each half of the brain was responsible for certain functions and that the left hemisphere controlled speech. Other experts showed little interest in the Frenchman&#39;s ideas. Over time, however, scientists found more and more evidence of people experiencing speech difficulties following injury to the left brain. Patients with damage to the right hemisphere most often displayed disruptions in perception or concentration. Major advancements in understanding the brain&#39;s asymmetry were made in the 1960s as a result of so-called split-brain surgery, developed to help patients with epilepsy. During this operation, doctors severed the corpus callosum — the nerve bundle that connects the two hemispheres. The surgical cut also stopped almost all normal communication between the two hemispheres, which offered researchers the opportunity to investigate each side&#39;s activity.

Section E
In 1949 neurosurgeon Juhn Wada devised the first test to provide access to the brain&#39;s functional organization of language. By injecting an anaesthetic into the right or left carotid artery, Wada temporarily paralyzed one side of a healthy brain, enabling him to more closely study the other side&#39;s capabilities. Based on this approach, Brenda Milner and the late Theodore Rasmussen of the Montreal Neurological Institute published a major study in 1975 that confirmed the theory that country doctor Dax had formulated nearly 140 years earlier: in 96 percent of right-handed people, language is processed much more intensely in the left hemisphere. The correlation is not as clear in lefties, however. For two thirds of them, the left hemisphere is still the most active language processor. But for the remaining third, either the right side is dominant or both sides work equally, controlling different language functions. That last statistic has slowed acceptance of the notion that the predominance of right-handedness is driven by left-hemisphere dominance in language processing. It is not at all clear why language control should somehow have dragged the control of body movement with it. Some experts think one reason the left hemisphere reigns over language is because the organs of speech processing — the larynx and tongue — are positioned on the body&#39;s symmetry axis. Because these structures were centred, it may have been unclear, in evolutionary terms, which side of the brain should control them, and it seems unlikely that shared operation would result in smooth motor activity. Language and handedness could have developed preferentially for very different reasons as well. For example, some researchers, including evolutionary psychologist Michael C. Corballis of the University of Auckland in New Zealand, think that the origin of human speech lies in gestures. Gestures predated words and helped language emerge. If the left hemisphere began to dominate speech, it would have dominated gestures, too, and because the left brain controls the right side of the body, the right hand developed more strongly.

Section F
Perhaps we will know more soon. In the meantime, we can revel in what, if any, differences handedness brings to our human talents. Popular wisdom says right-handed, left-brained people excel at logical, analytical thinking. Left-handed, right-brained individuals are thought to possess more creative skills and may be better at combining the functional features emergent in both sides of the brain. Yet some neuroscientists see such claims as pure speculation. Fewer scientists are ready to claim that left-handedness means greater creative potential. Yet lefties are prevalent among artists, composers and the generally acknowledged great political thinkers. Possibly if these individuals are among the lefties whose language abilities are evenly distributed between hemispheres, the intense interplay required could lead to unusual mental capabilities.

Section G
Or perhaps some lefties become highly creative simply because they must be more clever to get by in our right-handed world. This battle, which begins during the very early stages of childhood, may lay the groundwork for exceptional achievements`;

const buildTextSegments = () => {
    const raw = [
        { id: 'part2-header', text: 'Reading Passage 2' },
        { id: 'part2-instruction', text: 'You should spend about 20 minutes on Questions 14–26, which are based on Reading Passage 2 below.' },
        { id: 'passage-label', text: 'READING PASSAGE 2' },
        { id: 'passage', text: passageText },
        { id: 'questions-title', text: 'QUESTIONS' },
        { id: 'questions-desc', text: 'Answer all questions based on Reading Passage 2' },
        { id: 'q14-18-title', text: 'Questions 14–18' },
        { id: 'q14-18-desc1', text: 'Reading Passage 2 has seven sections, A–G.' },
        { id: 'q14-18-desc2', text: 'Which section contains the following information?' },
        { id: 'q14-18-write1', text: 'Write the correct letter ' },
        { id: 'q14-18-write2', text: 'A–G' },
        { id: 'q14-18-write3', text: ' in boxes ' },
        { id: 'q14-18-write4', text: '14–18' },
        { id: 'q14-18-write5', text: ' on your answer sheet.' },
        { id: 'q14-num', text: '14.' },
        { id: 'q14', text: 'Preference of using one side of the body in animal species.' },
        { id: 'q15-num', text: '15.' },
        { id: 'q15', text: 'How likely one-handedness is born.' },
        { id: 'q16-num', text: '16.' },
        { id: 'q16', text: 'The age when the preference of using one hand is settled.' },
        { id: 'q17-num', text: '17.' },
        { id: 'q17', text: 'Occupations usually found in left-handed population.' },
        { id: 'q18-num', text: '18.' },
        { id: 'q18', text: "A reference to an early discovery of each hemisphere's function." },
        { id: 'q19-22-title', text: 'Questions 19–22' },
        { id: 'q19-22-desc1', text: 'Look at the following researchers ' },
        { id: 'q19-22-desc2', text: '(Questions 19–22)' },
        { id: 'q19-22-desc3', text: ' and the list of findings below.' },
        { id: 'q19-22-desc4', text: 'Match each researcher with the correct finding.' },
        { id: 'q19-22-write1', text: 'Write the correct letter ' },
        { id: 'q19-22-write2', text: 'A–G' },
        { id: 'q19-22-write3', text: ' in boxes ' },
        { id: 'q19-22-write4', text: '19–22' },
        { id: 'q19-22-write5', text: ' on your answer sheet.' },
        { id: 'findings-title', text: 'List of Findings' },
        { id: 'finding-a-label', text: 'A' },
        { id: 'finding-a', text: 'Early language evolution is correlated to body movement and thus affecting the preference of use of one hand.' },
        { id: 'finding-b-label', text: 'B' },
        { id: 'finding-b', text: 'No single biological component determines the handedness of a child.' },
        { id: 'finding-c-label', text: 'C' },
        { id: 'finding-c', text: 'Each hemisphere of the brain is in charge of different body functions.' },
        { id: 'finding-d-label', text: 'D' },
        { id: 'finding-d', text: 'Language process is mainly centred in the left hemisphere of the brain.' },
        { id: 'finding-e-label', text: 'E' },
        { id: 'finding-e', text: 'Speech difficulties are often caused by brain damage.' },
        { id: 'finding-f-label', text: 'F' },
        { id: 'finding-f', text: 'The rate of development of one side of the body has influence on hemisphere preference in fetus.' },
        { id: 'finding-g-label', text: 'G' },
        { id: 'finding-g', text: 'Brain function already matures by the end of the fetal stage.' },
        { id: 'researcher-annet', text: 'Marian Annett' },
        { id: 'researcher-hepper', text: 'Peter Hepper' },
        { id: 'researcher-milner', text: 'Brenda Milner & Theodore Rasmussen' },
        { id: 'researcher-corballis', text: 'Michael Corballis' },
        { id: 'q23-26-title', text: 'Questions 23–26' },
        { id: 'q23-26-desc', text: 'Do the following statements agree with the information given in Reading Passage 2?' },
        { id: 'q23-26-write1', text: 'In boxes ' },
        { id: 'q23-26-write2', text: '23–26' },
        { id: 'q23-26-write3', text: ' on your answer sheet write:' },
        { id: 'yes-label', text: 'YES' },
        { id: 'yes-desc', text: 'if the statement agrees with the information' },
        { id: 'no-label', text: 'NO' },
        { id: 'no-desc', text: 'if the statement contradicts the information' },
        { id: 'notgiven-label', text: 'NOT GIVEN' },
        { id: 'notgiven-desc', text: 'if there is no information on this' },
        { id: 'q23', text: 'The study of twins shows that genetic determination is not the only factor for left-handedness.' },
        { id: 'q24', text: "Marc Dax's report was widely accepted in his time." },
        { id: 'q25', text: 'Juhn Wada based his findings on his research of people with language problems.' },
        { id: 'q26', text: 'There tend to be more men with left-handedness than women.' },
    ];
    let offset = 0;
    return raw.map(seg => {
        // For the passage segment, use plain text length for offset calculation
        const plainText = seg.text.replace(/<[^>]*>/g, '');
        const result = { id: seg.id, text: seg.text, plainText, offset };
        offset += plainText.length + 1;
        return result;
    });
};

const textSegments = ref(buildTextSegments());

/**
 * Build a list of {htmlIndex, plainIndex} checkpoints by walking
 * the HTML string once. This lets us convert any plain-text offset
 * to its exact HTML-string position in O(n) — and we only do it
 * once per render rather than calling the old O(n²) helper per annotation.
 */
const buildHtmlCheckpoints = (html: string) => {
    const checkpoints: Array<{ htmlIndex: number; plainIndex: number }> = [];
    let plainIndex = 0;
    let htmlIndex = 0;

    while (htmlIndex < html.length) {
        // Record a checkpoint at every plain character boundary
        checkpoints.push({ htmlIndex, plainIndex });

        if (html[htmlIndex] === '<') {
            // Skip the entire HTML tag without advancing plainIndex
            while (htmlIndex < html.length && html[htmlIndex] !== '>') {
                htmlIndex++;
            }
            htmlIndex++; // consume '>'
        } else if (html[htmlIndex] === '&') {
            // HTML entity — counts as ONE plain character
            const semiIdx = html.indexOf(';', htmlIndex);
            if (semiIdx !== -1) {
                htmlIndex = semiIdx + 1;
            } else {
                htmlIndex++;
            }
            plainIndex++;
        } else {
            htmlIndex++;
            plainIndex++;
        }
    }

    // Final sentinel
    checkpoints.push({ htmlIndex, plainIndex });
    return checkpoints;
};

/**
 * Binary-search the checkpoint array to find the HTML index
 * corresponding to a given plain-text offset.
 */
const plainOffsetToHtml = (
    checkpoints: Array<{ htmlIndex: number; plainIndex: number }>,
    plainOffset: number,
): number => {
    let lo = 0;
    let hi = checkpoints.length - 1;
    while (lo < hi) {
        const mid = (lo + hi + 1) >> 1;
        if (checkpoints[mid].plainIndex <= plainOffset) {
            lo = mid;
        } else {
            hi = mid - 1;
        }
    }
    // lo is now the last checkpoint whose plainIndex <= plainOffset
    // advance htmlIndex by the remaining plain characters
    const { htmlIndex, plainIndex } = checkpoints[lo];
    let extra = plainOffset - plainIndex;
    let idx = htmlIndex;
    while (extra > 0 && idx < checkpoints[checkpoints.length - 1].htmlIndex) {
        if (checkpoints.find(c => c.htmlIndex === idx)) {
            // skip tags that start here
        }
        idx++;
        extra--;
    }
    return idx;
};

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const isPassage = segmentId === 'passage';
    const renderText = isPassage ? passageText : segment.text;
    // Use pre-computed plainText length for non-passage segments
    const plainLen = isPassage
        ? passageText.replace(/<[^>]*>/g, '').replace(/&[^;]+;/g, ' ').length
        : segment.plainText.length;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainLen;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset,
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset,
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return renderText;
    }

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: Math.max(0, h.start_offset - segmentOffset),
            end: Math.min(plainLen, h.end_offset - segmentOffset),
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: Math.max(0, n.start - segmentOffset),
            end: Math.min(plainLen, n.end - segmentOffset),
            type: 'note' as const,
            color: 'blue',
            id: n.id,
        })),
    ].filter((a) => a.start < a.end);

    if (annotations.length === 0) return renderText;

    if (isPassage) {
        // ── PASSAGE: work in HTML-index space ──────────────────────────
        // Build checkpoints once for this render
        const checkpoints = buildHtmlCheckpoints(renderText);

        // Sort annotations left-to-right so we can do a single forward pass
        const sorted = [...annotations].sort((a, b) => a.start - b.start);

        let result = '';
        let htmlCursor = 0;

        for (const ann of sorted) {
            const htmlStart = plainOffsetToHtml(checkpoints, ann.start);
            const htmlEnd = plainOffsetToHtml(checkpoints, ann.end);

            if (htmlStart < htmlCursor) continue; // skip overlapping annotations

            // Copy everything up to the annotation start
            result += renderText.substring(htmlCursor, htmlStart);

            const annotated = renderText.substring(htmlStart, htmlEnd);

            if (ann.type === 'note') {
                result += `<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${annotated}</mark>`;
            } else {
                result += `<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${annotated}</mark>`;
            }

            htmlCursor = htmlEnd;
        }

        result += renderText.substring(htmlCursor);
        return result;
    }

    // ── Non-passage segments: plain text, left-to-right single pass ──
    annotations.sort((a, b) => a.start - b.start);

    let result = '';
    let cursor = 0;
    const plainText = segment.plainText;

    for (const annotation of annotations) {
        if (annotation.start < cursor) continue;
        result += plainText.substring(cursor, annotation.start);
        const annotated = plainText.substring(annotation.start, annotation.end);
        result += annotation.type === 'note'
            ? `<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>`
            : `<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>`;
        cursor = annotation.end;
    }
    result += plainText.substring(cursor);
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

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

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

            if (selection && range) {
                // Walk up DOM to find element with data-segment-id
                let targetEl = range.startContainer as HTMLElement;
                while (targetEl && targetEl.nodeType !== Node.ELEMENT_NODE) {
                    targetEl = targetEl.parentNode as HTMLElement;
                }

                // If landed inside a <mark> (existing highlight/note), go up to parent
                if ((targetEl as HTMLElement).tagName === 'MARK') {
                    targetEl = targetEl.parentNode as HTMLElement;
                }

                // Find ancestor with data-segment-id attribute
                let segmentEl = targetEl;
                while (
                    segmentEl &&
                    !segmentEl.hasAttribute?.('data-segment-id') &&
                    segmentEl !== contentTextRef.value
                ) {
                    segmentEl = segmentEl.parentNode as HTMLElement;
                }

                if (!segmentEl?.hasAttribute('data-segment-id')) {
                    showContextMenu.value = false;
                    return;
                }

                const segmentId = segmentEl.getAttribute('data-segment-id')!;
                const segment = textSegments.value.find((s) => s.id === segmentId);

                if (!segment) {
                    showContextMenu.value = false;
                    return;
                }

                // Calculate plain text offset within the segment element
                const preSelectionRange = document.createRange();
                preSelectionRange.selectNodeContents(segmentEl);
                preSelectionRange.setEnd(range.startContainer, range.startOffset);
                const plainTextOffset = preSelectionRange.toString().length;

                const startOffset = segment.offset + plainTextOffset;
                const endOffset = startOffset + selected.length;

                selectedText.value = selected;
                selectionRange.value = { start: startOffset, end: endOffset };

                showContextMenu.value = true;
            }
        }
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

// Delete highlight
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
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

// Handle mouse leave from noted text
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

    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 2 Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 part-header-box px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part2-header"
                v-html="getHighlightedSegment('part2-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part2-instruction"
                v-html="getHighlightedSegment('part2-instruction')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" data-segment-id="passage-label"
                                v-html="getHighlightedSegment('passage-label')"></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm whitespace-pre-line leading-relaxed select-text">
                            <div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="text-lg text-gray-700 select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment('passage')"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 14–18 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q14-18-title"
                                                v-html="getHighlightedSegment('q14-18-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" data-segment-id="q14-18-desc1"
                                            v-html="getHighlightedSegment('q14-18-desc1')"></span>
                                    </p>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" data-segment-id="q14-18-desc2"
                                            v-html="getHighlightedSegment('q14-18-desc2')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" data-segment-id="q14-18-write1"
                                            v-html="getHighlightedSegment('q14-18-write1')"></span>
                                        <span class="font-bold text-gray-900 select-text"
                                            data-segment-id="q14-18-write2"
                                            v-html="getHighlightedSegment('q14-18-write2')"></span>
                                        <span class="text-gray-700 select-text" data-segment-id="q14-18-write3"
                                            v-html="getHighlightedSegment('q14-18-write3')"></span>
                                        <span class="font-bold text-gray-900 select-text"
                                            data-segment-id="q14-18-write4"
                                            v-html="getHighlightedSegment('q14-18-write4')"></span>
                                        <span class="text-gray-700 select-text" data-segment-id="q14-18-write5"
                                            v-html="getHighlightedSegment('q14-18-write5')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <!-- Question 14 -->
                                    <div id="question-14" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 pr-8">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0"
                                                data-segment-id="q14-num"
                                                v-html="getHighlightedSegment('q14-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1" data-segment-id="q14"
                                                v-html="getHighlightedSegment('q14')"></span>
                                            <select v-model="answers.q14"
                                                class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 15 -->
                                    <div id="question-15" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 pr-8">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0"
                                                data-segment-id="q15-num"
                                                v-html="getHighlightedSegment('q15-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1" data-segment-id="q15"
                                                v-html="getHighlightedSegment('q15')"></span>
                                            <select v-model="answers.q15"
                                                class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 16 -->
                                    <div id="question-16" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 pr-8">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0"
                                                data-segment-id="q16-num"
                                                v-html="getHighlightedSegment('q16-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1" data-segment-id="q16"
                                                v-html="getHighlightedSegment('q16')"></span>
                                            <select v-model="answers.q16"
                                                class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 17 -->
                                    <div id="question-17" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 pr-8">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0"
                                                data-segment-id="q17-num"
                                                v-html="getHighlightedSegment('q17-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1" data-segment-id="q17"
                                                v-html="getHighlightedSegment('q17')"></span>
                                            <select v-model="answers.q17"
                                                class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 18 -->
                                    <div id="question-18" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3 pr-8">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0"
                                                data-segment-id="q18-num"
                                                v-html="getHighlightedSegment('q18-num')"></span>
                                            <span class="text-sm text-gray-700 select-text flex-1" data-segment-id="q18"
                                                v-html="getHighlightedSegment('q18')"></span>
                                            <select v-model="answers.q18"
                                                class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                                <option value="E">E</option>
                                                <option value="F">F</option>
                                                <option value="G">G</option>
                                            </select>
                                        </div>
                                        <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="toggleFlag(18)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 19-22: Match Researchers with Findings -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q19-22-title"
                                                v-html="getHighlightedSegment('q19-22-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" data-segment-id="q19-22-desc1"
                                            v-html="getHighlightedSegment('q19-22-desc1')"></span>
                                        <span class="font-bold text-gray-900 select-text" data-segment-id="q19-22-desc2"
                                            v-html="getHighlightedSegment('q19-22-desc2')"></span>
                                        <span class="text-gray-700 select-text" data-segment-id="q19-22-desc3"
                                            v-html="getHighlightedSegment('q19-22-desc3')"></span>
                                    </p>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" data-segment-id="q19-22-desc4"
                                            v-html="getHighlightedSegment('q19-22-desc4')"></span>
                                    </p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" data-segment-id="q19-22-write1"
                                            v-html="getHighlightedSegment('q19-22-write1')"></span>
                                        <span class="font-bold text-gray-900 select-text" data-segment-id="q19-22-write2"
                                            v-html="getHighlightedSegment('q19-22-write2')"></span>
                                        <span class="text-gray-700 select-text" data-segment-id="q19-22-write3"
                                            v-html="getHighlightedSegment('q19-22-write3')"></span>
                                        <span class="font-bold text-gray-900 select-text" data-segment-id="q19-22-write4"
                                            v-html="getHighlightedSegment('q19-22-write4')"></span>
                                        <span class="text-gray-700 select-text" data-segment-id="q19-22-write5"
                                            v-html="getHighlightedSegment('q19-22-write5')"></span>
                                    </p>
                                </div>

                                <!-- List of Findings Box -->
                                <div class="mb-6  p-4">
                                    <h4 class="mb-3 text-sm font-bold text-gray-900 select-text" data-segment-id="findings-title"
                                        v-html="getHighlightedSegment('findings-title')"></h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text w-6 shrink-0" data-segment-id="finding-a-label"
                                                v-html="getHighlightedSegment('finding-a-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="finding-a"
                                                v-html="getHighlightedSegment('finding-a')"></span>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text w-6 shrink-0" data-segment-id="finding-b-label"
                                                v-html="getHighlightedSegment('finding-b-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="finding-b"
                                                v-html="getHighlightedSegment('finding-b')"></span>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text w-6 shrink-0" data-segment-id="finding-c-label"
                                                v-html="getHighlightedSegment('finding-c-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="finding-c"
                                                v-html="getHighlightedSegment('finding-c')"></span>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text w-6 shrink-0" data-segment-id="finding-d-label"
                                                v-html="getHighlightedSegment('finding-d-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="finding-d"
                                                v-html="getHighlightedSegment('finding-d')"></span>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text w-6 shrink-0" data-segment-id="finding-e-label"
                                                v-html="getHighlightedSegment('finding-e-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="finding-e"
                                                v-html="getHighlightedSegment('finding-e')"></span>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text w-6 shrink-0" data-segment-id="finding-f-label"
                                                v-html="getHighlightedSegment('finding-f-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="finding-f"
                                                v-html="getHighlightedSegment('finding-f')"></span>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text w-6 shrink-0" data-segment-id="finding-g-label"
                                                v-html="getHighlightedSegment('finding-g-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="finding-g"
                                                v-html="getHighlightedSegment('finding-g')"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Researcher Questions -->
                                <div class="space-y-4">
                                    <!-- Question 19 -->
                                <div id="question-19" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 19"
                                    @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-3 pr-8">
                                        <span class="font-bold text-gray-900 select-none flex-shrink-0">19.</span>
                                        <span class="font-semibold text-gray-900 select-text flex-1"
                                            data-segment-id="researcher-annet"
                                            v-html="getHighlightedSegment('researcher-annet')"></span>
                                        <select v-model="answers.q19"
                                            class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                    </div>
                                    <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                        @click.stop="toggleFlag(19)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Question 20 -->
                                <div id="question-20" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 20"
                                    @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-3 pr-8">
                                        <span class="font-bold text-gray-900 select-none flex-shrink-0">20.</span>
                                        <span class="font-semibold text-gray-900 select-text flex-1"
                                            data-segment-id="researcher-hepper"
                                            v-html="getHighlightedSegment('researcher-hepper')"></span>
                                        <select v-model="answers.q20"
                                            class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                    </div>
                                    <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                        @click.stop="toggleFlag(20)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Question 21 -->
                                <div id="question-21" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 21"
                                    @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-3 pr-8">
                                        <span class="font-bold text-gray-900 select-none flex-shrink-0">21.</span>
                                        <span class="font-semibold text-gray-900 select-text flex-1"
                                            data-segment-id="researcher-milner"
                                            v-html="getHighlightedSegment('researcher-milner')"></span>
                                        <select v-model="answers.q21"
                                            class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                    </div>
                                    <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                        @click.stop="toggleFlag(21)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Question 22 -->
                                <div id="question-22" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 22"
                                    @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-3 pr-8">
                                        <span class="font-bold text-gray-900 select-none flex-shrink-0">22.</span>
                                        <span class="font-semibold text-gray-900 select-text flex-1"
                                            data-segment-id="researcher-corballis"
                                            v-html="getHighlightedSegment('researcher-corballis')"></span>
                                        <select v-model="answers.q22"
                                            class="w-24 text-center shrink-0 border-2 border-gray-900 bg-white px-2 py-1 text-sm focus:border-black focus:outline-none">
                                            <option value="">Select</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                        </select>
                                    </div>
                                    <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                        @click.stop="toggleFlag(22)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                </div>
                            </div>

                            <!-- Questions 23–26 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" data-segment-id="q23-26-title"
                                                v-html="getHighlightedSegment('q23-26-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span class="text-gray-700 select-text" data-segment-id="q23-26-desc"
                                            v-html="getHighlightedSegment('q23-26-desc')"></span>
                                        <br />
                                        <span class="text-gray-700 select-text" data-segment-id="q23-26-write1"
                                            v-html="getHighlightedSegment('q23-26-write1')"></span>
                                        <span class="font-bold text-gray-900 select-text"
                                            data-segment-id="q23-26-write2"
                                            v-html="getHighlightedSegment('q23-26-write2')"></span>
                                        <span class="text-gray-700 select-text" data-segment-id="q23-26-write3"
                                            v-html="getHighlightedSegment('q23-26-write3')"></span>
                                    </p>
                                </div>

                                <!-- YES / NO / NOT GIVEN Legend -->
                                <div class="mb-6  p-4">
                                    <div class="grid grid-cols-1 gap-2 text-sm">
                                        <div class="flex items-center gap-4">
                                            <span
                                                class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900 select-text"
                                                data-segment-id="yes-label"
                                                v-html="getHighlightedSegment('yes-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="yes-desc"
                                                v-html="getHighlightedSegment('yes-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span
                                                class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900 select-text"
                                                data-segment-id="no-label"
                                                v-html="getHighlightedSegment('no-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="no-desc"
                                                v-html="getHighlightedSegment('no-desc')"></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span
                                                class="w-24 border border-gray-900 px-2 py-1 font-bold text-gray-900 select-text"
                                                data-segment-id="notgiven-label"
                                                v-html="getHighlightedSegment('notgiven-label')"></span>
                                            <span class="text-gray-700 select-text" data-segment-id="notgiven-desc"
                                                v-html="getHighlightedSegment('notgiven-desc')"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statements Q23–26 with radio buttons -->
                                <div class="space-y-5">

                                    <!-- Question 23 -->
                                    <div id="question-23" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="text-base font-bold text-gray-900 select-none flex-shrink-0 pt-1">23.</span>
                                            <div class="flex-1">
                                                <div class="mb-2">
                                                    <span class="text-sm text-gray-700 select-text"
                                                        data-segment-id="q23"
                                                        v-html="getHighlightedSegment('q23')"></span>
                                                </div>
                                                <div class="flex items-center gap-6">
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q23" value="YES"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">YES</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q23" value="NO"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NO</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q23" value="NOT GIVEN"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-7 h-7 shrink-0 absolute top-2 right-2">
                                            <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                                @click.stop="toggleFlag(23)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 24 -->
                                    <div id="question-24" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="text-base font-bold text-gray-900 select-none flex-shrink-0 pt-1">24.</span>
                                            <div class="flex-1">
                                                <div class="mb-2">
                                                    <span class="text-sm text-gray-700 select-text"
                                                        data-segment-id="q24"
                                                        v-html="getHighlightedSegment('q24')"></span>
                                                </div>
                                                <div class="flex items-center gap-6">
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q24" value="YES"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">YES</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q24" value="NO"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NO</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q24" value="NOT GIVEN"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-7 h-7 shrink-0 absolute top-2 right-2">
                                            <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                                @click.stop="toggleFlag(24)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 25 -->
                                    <div id="question-25" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="text-base font-bold text-gray-900 select-none flex-shrink-0 pt-1">25.</span>
                                            <div class="flex-1">
                                                <div class="mb-2">
                                                    <span class="text-sm text-gray-700 select-text"
                                                        data-segment-id="q25"
                                                        v-html="getHighlightedSegment('q25')"></span>
                                                </div>
                                                <div class="flex items-center gap-6">
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q25" value="YES"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">YES</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q25" value="NO"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NO</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q25" value="NOT GIVEN"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-7 h-7 shrink-0 absolute top-2 right-2">
                                            <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                                @click.stop="toggleFlag(25)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 26 -->
                                    <div id="question-26" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="text-base font-bold text-gray-900 select-none flex-shrink-0 pt-1">26.</span>
                                            <div class="flex-1">
                                                <div class="mb-2">
                                                    <span class="text-sm text-gray-700 select-text"
                                                        data-segment-id="q26"
                                                        v-html="getHighlightedSegment('q26')"></span>
                                                </div>
                                                <div class="flex items-center gap-6">
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q26" value="YES"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">YES</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q26" value="NO"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NO</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" v-model="answers.q26" value="NOT GIVEN"
                                                            class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                        <span class="text-sm font-medium text-gray-900">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-7 h-7 shrink-0 absolute top-2 right-2">
                                            <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                                @click.stop="toggleFlag(26)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
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
        </div>

        <!-- Teleported Tooltips -->
        <Teleport to="body">
            <!-- Context Menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
                <div class="highlight-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
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
                <div class="delete-highlight-tooltip fixed z-9999"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
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
                <div class="note-hover-tooltip pointer-events-auto fixed z-9999"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
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
                class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p
                        class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                        "{{ selectedText }}"</p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                        class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote"
                        class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save
                        Note</button>
                </div>
            </div>
        </Teleport>
    </div>
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

/* Radio button styles */
.radio-option {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.radio-option span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 4rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.025em;
    border: 2px solid #6b7280;
    color: #374151;
    background-color: white;
    transition: border-color 0.15s, background-color 0.15s, color 0.15s;
    border-radius: 2px;
}

.radio-option:hover span {
    border-color: #111827;
    background-color: #f9fafb;
}

.radio-option.active span {
    background-color: #111827;
    border-color: #111827;
    color: white;
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
</style>
