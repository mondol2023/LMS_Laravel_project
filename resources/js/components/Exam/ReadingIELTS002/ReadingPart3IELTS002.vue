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

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts002-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Delete highlight tooltip state
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note hover tooltip state
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const answers = ref({
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

// Drag and drop functionality for questions 28-33
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i', label: 'Amazing results from a project' },
    { value: 'ii', label: 'New religious ceremonies' },
    { value: 'iii', label: 'Community art centres' },
    { value: 'iv', label: 'Early painting techniques and marketing systems' },
    { value: 'v', label: 'Mythology and history combined' },
    { value: 'vi', label: 'The increasing acclaim for Aboriginal art' },
    { value: 'vii', label: 'Belief in continuity' },
    { value: 'viii', label: 'Oppression of a minority people' },
];

// Filter out used heading options
//const availableHeadingOptions = computed(() => {
    //const usedOptions = [answers.value.q28, answers.value.q29, answers.value.q30, answers.value.q31, answers.value.q32, answers.value.q33].filter(Boolean);
   // return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
//});

const usedHeadings = computed(() => {
    return new Set([answers.value.q28, answers.value.q29, answers.value.q30, answers.value.q31, answers.value.q32, answers.value.q33].filter(Boolean));
});

const isHeadingUsed = (value: string): boolean => usedHeadings.value.has(value);

const handleHeadingDragStart = (e: DragEvent, option: string) => {
    draggedHeading.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleHeadingDragEnd = () => {
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};

const handleHeadingDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
    dragOverQuestion.value = questionNum;
};

const handleHeadingDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleHeadingDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedHeading.value;
    if (option) {
        // If this option is already used in another zone, clear that zone first
        const keys = ['q28', 'q29', 'q30', 'q31', 'q32', 'q33'] as const;
        for (const key of keys) {
            if (answers.value[key] === option) {
                answers.value[key] = '';
            }
        }
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};

const clearHeadingAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// Get label from heading option value for display
const getHeadingLabel = (value: string): string => {
    const option = headingOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Paragraph texts for A-I
const paragraphA = `The works of Aboriginal artists are now much in demand throughout the world, and not just in Australia, where they are already fully recognised: the National Museum of Australia, which opened in Canberra in 2001, designated 40% of its exhibition space to works by Aborigines. In Europe their art is being exhibited at a museum in Lyon. France, while the future Quai Branly museum in Paris - which will be devoted to arts and civilisations of Africa. Asia, Oceania and the Americas - plans to commission frescoes by artists from Australia.`;
const paragraphB = `Their artistic movement began about 30 years ago. but its roots go back to time immemorial. All the works refer to the founding myth of the Aboriginal culture, 'the Dreaming'. That internal geography, which is rendered with a brush and colours, is also the expression of the Aborigines' long quest to regain the land which was stolen from them when Europeans arrived in the nineteenth century. 'Painting is nothing without history.' says one such artist. Michael Nelson Tjakamarra.`;
const paragraphC = `There arc now fewer than 400.000 Aborigines living in Australia. They have been swamped by the country's 17.5 million immigrants. These original 'natives' have been living in Australia for 50.000 years, but they were undoubtedly maltreated by the newcomers. Driven back to the most barren lands or crammed into slums on the outskirts of cities, the Aborigines were subjected to a policy of 'assimilation', which involved kidnapping children to make them better 'integrated' into European society, and herding the nomadic Aborigines by force into settled communities.`;
const paragraphD = `It was in one such community, Papunya, near Alice Springs, in the central desert, that Aboriginal painting first came into its own. In 1971, a white schoolteacher. Geoffrey Bardon, suggested to a group of Aborigines that they should decorate the school walls with ritual motifs. so as to pass on to the younger generation the myths that were starting to fade from their collective memory, lie gave them brushes. colours and surfaces to paint on cardboard and canvases. He was astounded by the result. But their art did not come like a bolt from the blue: for thousands of years Aborigines had been 'painting' on the ground using sands of different colours, and on rock faces. They had also been decorating their bodies for ceremonial purposes. So there existed a formal vocabulary.`;
const paragraphE = `This had already been noted by Europeans. In the early twentieth century. Aboriginal communities brought together by missionaries in northern Australia had been encouraged to reproduce on tree bark the motifs found on rock faces. Artists turned out a steady stream of works, supported by the churches, which helped to sell them to the public, and between 1950 and I960 Aboriginal paintings began to reach overseas museums. Painting on bark persisted in the north, whereas the communities in the central desert increasingly used acrylic paint, and elsewhere in Western Australia women explored the possibilities of wax painting and dyeing processes, known as 'batik'.`;
const paragraphF = `What Aborigines depict are always elements of the Dreaming, the collective history that each community is both part of and guardian of. I he Dreaming is the story of their origins, of their 'Great Ancestors', who passed on their knowledge, their art and their skills (hunting, medicine, painting, music and dance) to man. 'The Dreaming is not synonymous with the moment when the world was created.' says Stephane Jacob, one of the organisers of the Lyon exhibition. 'For Aborigines, that moment has never ceased to exist. It is perpetuated by the cycle of the seasons and the religious ceremonies which the Aborigines organise. Indeed the aim of those ceremonies is also to ensure the permanence of that golden age. The central function of Aboriginal painting, even in its contemporary manifestations, is to guarantee the survival of this world. The Dreaming is both past, present and future.'`;
const paragraphG = `Each work is created individually, with a form peculiar to each artist, but it is created within and on behalf of a community who must approve it. An artist cannot use a 'dream' that does not belong to his or her community, since each community is the owner of its dreams, just as it is anchored to a territory marked out by its ancestors, so each painting can be interpreted as a kind of spiritual road map for that community.`;
const paragraphH = `Nowadays, each community is organised as a cooperative and draws on the services of an art adviser, a government-employed agent who provides the artists with materials, deals with galleries and museums and redistributes the proceeds from sales among the artists. Today, Aboriginal painting has become a great success. Some works sell for more than $25,000, and exceptional items may fetch as much as $180,000 in Australia.`;
const paragraphI = `'By exporting their paintings as though they were surfaces of their territory, by accompanying them to the temples of western art. the Aborigines have redrawn the map of their country, into whose depths they were exiled,* says Yves Le Fur. of the Quai Branlv museum. 'Masterpieces have been created. Their undeniable power prompts a dialogue that has proved all too rare in the history of contacts between the two cultures'.`;

// ─── FIX: All offsets recalculated to be strictly sequential with GAP=10 ───────
// Root cause of paragraph bleed-through and Q38-40 extra highlights was
// overlapping offsets: para-f bled into para-g, para-h bled into para-i,
// and several Q38-40 segments overlapped their neighbours.
const textSegments = ref([
    { id: 'part3-header', text: 'Part 3', offset: 100 },
    { id: 'part3-instruction', text: 'Read the text and answer questions 28-40.', offset: 116 },
    { id: 'para-intro', text: `The world's fascination with the mystique of Australian Aboriginal art.' Emmanuel de Roux`, offset: 167 },
    { id: 'para-a', text: paragraphA, offset: 266 },
    { id: 'para-b', text: paragraphB, offset: 814 },
    { id: 'para-c', text: paragraphC, offset: 1305 },
    { id: 'para-d', text: paragraphD, offset: 1881 },
    { id: 'para-e', text: paragraphE, offset: 2672 },
    { id: 'para-f', text: paragraphF, offset: 3348 },
    { id: 'para-g', text: paragraphG, offset: 4251 },
    { id: 'para-h', text: paragraphH, offset: 4688 },
    { id: 'para-i', text: paragraphI, offset: 5120 },
    { id: 'q28-33-title', text: 'Questions 28-33', offset: 5554 },
    { id: 'q28-33-instructions', text: 'Reading Passage 3 has nine paragraphs A-l. Choose the most suitable heading for paragraphs A-F from the list of headings below. Write the correct number (i-viii) in boxes 1-6 on your answer sheet.', offset: 5579 },
    { id: 'headings-title', text: 'List of headings:', offset: 5785 },
    { id: 'heading-i', text: 'Amazing results from a project', offset: 5812 },
    { id: 'heading-ii', text: 'New religious ceremonies', offset: 5852 },
    { id: 'heading-iii', text: 'Community art centres', offset: 5886 },
    { id: 'heading-iv', text: 'Early painting techniques and marketing systems', offset: 5917 },
    { id: 'heading-v', text: 'Mythology and history combined', offset: 5974 },
    { id: 'heading-vi', text: 'The increasing acclaim for Aboriginal art', offset: 6014 },
    { id: 'heading-vii', text: 'Belief in continuity', offset: 6065 },
    { id: 'heading-viii', text: 'Oppression of a minority people', offset: 6095 },
    { id: 'q28-text', text: 'Paragraph A', offset: 6136 },
    { id: 'q29-text', text: 'Paragraph B', offset: 6157 },
    { id: 'q30-text', text: 'Paragraph C', offset: 6178 },
    { id: 'q31-text', text: 'Paragraph D', offset: 6199 },
    { id: 'q32-text', text: 'Paragraph E', offset: 6220 },
    { id: 'q33-text', text: 'Paragraph F', offset: 6241 },
    { id: 'q34-37-title', text: 'Questions 34-37', offset: 6262 },
    { id: 'q34-37-instructions', text: 'Complete the flowchart below. Choose No More Than Three Words from the passage for each answer.', offset: 6287 },
    { id: 'flowchart-1', text: 'For', offset: 6392 },
    { id: 'flowchart-2', text: ',Aborigines produced ground and rock paintings. Early 20th century, churches first encouraged the use of', offset: 6405 },
    { id: 'flowchart-3', text: 'for paintings. Early 20th century, churches first encouraged the use of', offset: 6519 },
    { id: 'flowchart-4', text: 'Early 1970s, Aboriginal painted traditional patterns on', offset: 6600 },
    { id: 'flowchart-5', text: 'in one community.', offset: 6665 },
    { id: 'q38-40-title', text: 'Questions 38-40', offset: 6692 },
    { id: 'q38-40-instructions', text: 'Choose the correct letter, A, B, C or D. Write your answers in boxes 38-40 on your answer sheet.', offset: 6717 },
    { id: 'q38-num', text: '38.', offset: 6823 },
    { id: 'q38-q', text: 'In Paragraph G, the writer suggests that an important feature of aboriginal art is', offset: 6836 },
    { id: 'q38-a', text: 'Its historical context', offset: 6928 },
    { id: 'q38-b', text: 'Its significance to the group', offset: 6960 },
    { id: 'q38-c', text: 'Its religious content', offset: 6999 },
    { id: 'q38-d', text: 'Its message about the environment', offset: 7030 },
    { id: 'q39-num', text: '39.', offset: 7073 },
    { id: 'q39-q', text: 'In Aboriginal beliefs, there is a significant relationship between', offset: 7086 },
    { id: 'q39-a', text: 'Communities and lifestyles', offset: 7162 },
    { id: 'q39-b', text: 'Images and techniques', offset: 7198 },
    { id: 'q39-c', text: 'Culture and form', offset: 7229 },
    { id: 'q39-d', text: 'Ancestors and territory', offset: 7255 },
    { id: 'q40-num', text: '40.', offset: 7288 },
    { id: 'q40-q', text: 'In Paragraph I, the writer suggests that Aboriginal art invites Westerners to engage with', offset: 7301 },
    { id: 'q40-a', text: 'The Australian land', offset: 7400 },
    { id: 'q40-b', text: 'Their own art', offset: 7429 },
    { id: 'q40-c', text: 'Aboriginal culture', offset: 7452 },
    { id: 'q40-d', text: 'Their own history', offset: 7480 },
]);

// Convert plain text offset to HTML offset (skips over HTML tags)
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

// Helper to get a highlighted version of a specific text segment by segment ID
// FIX: look up by segment ID, not by text content (avoids collisions when same text appears in multiple segments)
const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);

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

// Keep backward-compat alias (used in some template calls that pass text directly)
const getHighlightedSegment = (segmentText: string): string => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;
    return getHighlightedSegmentById(segment.id as string);
};

// Expose methods for parent component
const getAnswers = () => answers.value;

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

// ─── FIX: Improved text selection handler ────────────────────────────────────
// Old code called removeAllRanges() on no-selection, killing in-progress drags.
// New code only clears the menu, never the selection mid-drag.
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
            if (!segmentEl) return null;

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find((s) => String(s.id) === segmentIdAttr);
            if (!segment) return null;

            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let offsetInSegment = 0;
            let currentNode: Node | null;
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

    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
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

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
        part: 'Part 3',
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
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = note.id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
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
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
        }
    } else {
        closeDeleteTooltip();
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

const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, (newWidth) => { localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString()); });

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

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
         class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part3-header"
               v-html="getHighlightedSegmentById('part3-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part3-instruction"
               v-html="getHighlightedSegmentById('part3-instruction')"></p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                 :class="{ 'select-none': isResizing }">

                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                     :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-shrink-0 border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900">Painters of time</h2>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text sm:text-base">

                            <!-- Introduction -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700 italic select-text">
                                <span data-segment-id="para-intro"
                                      v-html="getHighlightedSegmentById('para-intro')"></span>
                            </div>

                            <!-- Paragraph A with drop zone -->
                            <div class="px-4">
                                <!-- FIX 1: Flag button uses group + group-hover so it's invisible until hover -->
                                <div id="question-28" class="group mb-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 28 ? 'border-blue-500 bg-blue-50' : answers.q28 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 28)"
                                        @dragleave="handleHeadingDragLeave" @drop="handleHeadingDrop($event, 28)"
                                        @click="clearHeadingAnswer(28)"
                                        :title="answers.q28 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q28">{{ getHeadingLabel(answers.q28) }}</span>
                                        <span v-else class="font-bold text-gray-500">28</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 28)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(28) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                            :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">A. </span><span class="select-text" data-segment-id="para-a"
                                                                            v-html="getHighlightedSegmentById('para-a')"></span>
                                </div>
                            </div>

                            <!-- Paragraph B with drop zone -->
                            <div class="px-4">
                                <div id="question-29" class="group mb-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 29 ? 'border-blue-500 bg-blue-50' : answers.q29 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 29)"
                                        @dragleave="handleHeadingDragLeave" @drop="handleHeadingDrop($event, 29)"
                                        @click="clearHeadingAnswer(29)"
                                        :title="answers.q29 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q29">{{ getHeadingLabel(answers.q29) }}</span>
                                        <span v-else class="font-bold text-gray-500">29</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 29)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(29) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                            :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">B. </span><span class="select-text" data-segment-id="para-b"
                                                                            v-html="getHighlightedSegmentById('para-b')"></span>
                                </div>
                            </div>

                            <!-- Paragraph C with drop zone -->
                            <div class="px-4">
                                <div id="question-30" class="group mb-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 30 ? 'border-blue-500 bg-blue-50' : answers.q30 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 30)"
                                        @dragleave="handleHeadingDragLeave" @drop="handleHeadingDrop($event, 30)"
                                        @click="clearHeadingAnswer(30)"
                                        :title="answers.q30 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q30">{{ getHeadingLabel(answers.q30) }}</span>
                                        <span v-else class="font-bold text-gray-500">30</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 30)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(30) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                            :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">C. </span><span class="select-text" data-segment-id="para-c"
                                                                            v-html="getHighlightedSegmentById('para-c')"></span>
                                </div>
                            </div>

                            <!-- Paragraph D with drop zone -->
                            <div class="px-4">
                                <div id="question-31" class="group mb-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 31 ? 'border-blue-500 bg-blue-50' : answers.q31 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 31)"
                                        @dragleave="handleHeadingDragLeave" @drop="handleHeadingDrop($event, 31)"
                                        @click="clearHeadingAnswer(31)"
                                        :title="answers.q31 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q31">{{ getHeadingLabel(answers.q31) }}</span>
                                        <span v-else class="font-bold text-gray-500">31</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 31)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(31) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                            :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">D. </span><span class="select-text" data-segment-id="para-d"
                                                                            v-html="getHighlightedSegmentById('para-d')"></span>
                                </div>
                            </div>

                            <!-- Paragraph E with drop zone -->
                            <div class="px-4">
                                <div id="question-32" class="group mb-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 32 ? 'border-blue-500 bg-blue-50' : answers.q32 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 32)"
                                        @dragleave="handleHeadingDragLeave" @drop="handleHeadingDrop($event, 32)"
                                        @click="clearHeadingAnswer(32)"
                                        :title="answers.q32 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q32">{{ getHeadingLabel(answers.q32) }}</span>
                                        <span v-else class="font-bold text-gray-500">32</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 32)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(32) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">E. </span><span class="select-text" data-segment-id="para-e"
                                                                            v-html="getHighlightedSegmentById('para-e')"></span>
                                </div>
                            </div>

                            <!-- Paragraph F with drop zone -->
                            <div class="px-4">
                                <div id="question-33" class="group mb-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex min-h-8 min-w-48 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                        :class="dragOverQuestion === 33 ? 'border-blue-500 bg-blue-50' : answers.q33 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                        @dragover="handleHeadingDragOver($event, 33)"
                                        @dragleave="handleHeadingDragLeave" @drop="handleHeadingDrop($event, 33)"
                                        @click="clearHeadingAnswer(33)"
                                        :title="answers.q33 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q33">{{ getHeadingLabel(answers.q33) }}</span>
                                        <span v-else class="font-bold text-gray-500">33</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 33)"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                            :class="isQuestionFlagged(33) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                            :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">F. </span><span class="select-text" data-segment-id="para-f"
                                                                            v-html="getHighlightedSegmentById('para-f')"></span>
                                </div>
                            </div>

                            <!-- Paragraphs G, H, I (no drop zones) -->
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">G. </span><span class="select-text" data-segment-id="para-g"
                                                                        v-html="getHighlightedSegmentById('para-g')"></span>
                            </div>
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">H. </span><span class="select-text" data-segment-id="para-h"
                                                                        v-html="getHighlightedSegmentById('para-h')"></span>
                            </div>
                            <div class="px-4 text-justify leading-relaxed text-gray-700">
                                <span class="font-bold">I. </span><span class="select-text" data-segment-id="para-i"
                                                                        v-html="getHighlightedSegmentById('para-i')"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
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
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- Questions 28-33: List of Headings -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="q28-33-title"
                                                  v-html="getHighlightedSegmentById('q28-33-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                        <span data-segment-id="q28-33-instructions"
                                              v-html="getHighlightedSegmentById('q28-33-instructions')"></span>
                                    </p>
                                </div>

                                <!-- Draggable List of Headings -->
                                <div class="border border-gray-300 p-4">
                                    <h4 class="mb-3 text-base font-bold text-gray-900 sm:text-base">
                                        <span data-segment-id="headings-title"
                                              v-html="getHighlightedSegmentById('headings-title')"></span>
                                    </h4>
                                    <div class="space-y-1.5 text-sm">
                                        <div v-for="option in headingOptions" :key="option.value"
                                            class="flex cursor-grab items-center gap-2 rounded border px-3 py-2 transition-all active:cursor-grabbing"
                                            :class="[
                                                isHeadingUsed(option.value)
                                                    ? 'border-green-400 bg-green-50 opacity-60 hover:opacity-80'
                                                    : 'border-gray-300 bg-white hover:border-gray-500 hover:bg-gray-50',
                                                { 'opacity-30': draggedHeading === option.value }
                                            ]"
                                            draggable="true"ß
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd">
                                            <svg class="h-4 w-4 shrink-0"
                                                :class="isHeadingUsed(option.value) ? 'text-green-500' : 'text-gray-400'"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 8h16M4 16h16" />
                                            </svg>
                                            <span class="font-bold" :class="isHeadingUsed(option.value) ? 'text-green-700' : 'text-gray-900'">
                                                {{ option.value }}
                                            </span>
                                            <span :class="isHeadingUsed(option.value) ? 'text-green-700' : 'text-gray-700'">
                                                {{ option.label }}
                                            </span>
                                            <!-- Placed checkmark -->
                                            <svg v-if="isHeadingUsed(option.value)" class="ml-auto h-4 w-4 shrink-0 text-green-500"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 34-37 -->
                            <div class="space-y-4 p-4 text-base leading-relaxed text-gray-800 sm:text-base">
                                <p class="leading-loose">
                                    <span data-segment-id="flowchart-1"
                                          v-html="getHighlightedSegmentById('flowchart-1')"></span>

                                    <span class="group inline-flex items-center gap-1 align-middle">
                                        <input id="question-34" v-model="answers.q34" type="text" placeholder="34"
                                               class="mx-2 w-40 border border-gray-300 px-3 py-0.5 text-center text-base font-medium uppercase transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                                               spellcheck="false" autocomplete="off" />
                                        <button @click.stop="emit('toggleFlag', 34)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                                :class="isQuestionFlagged(34) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>

                                    <span data-segment-id="flowchart-2"
                                          v-html="getHighlightedSegmentById('flowchart-2')"></span>

                                    <span class="group inline-flex items-center gap-1 align-middle">
                                        <input id="question-35" v-model="answers.q35" type="text" placeholder="35"
                                               class="mx-2 my-1 w-40 border border-gray-300 px-3 py-0.5 text-center text-base font-medium uppercase transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                                               spellcheck="false" autocomplete="off" />
                                        <button @click.stop="emit('toggleFlag', 35)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                                :class="isQuestionFlagged(35) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>

                                    <span data-segment-id="flowchart-3"
                                          v-html="getHighlightedSegmentById('flowchart-3')"></span>

                                    <span class="group inline-flex items-center gap-1 align-middle">
                                        <input id="question-36" v-model="answers.q36" type="text" placeholder="36"
                                               class="mx-2 w-40 border border-gray-300 px-3 py-0.5 text-center text-base font-medium uppercase transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                                               spellcheck="false" autocomplete="off" />
                                        <button @click.stop="emit('toggleFlag', 36)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                                :class="isQuestionFlagged(36) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>

                                    <span data-segment-id="flowchart-4"
                                          v-html="getHighlightedSegmentById('flowchart-4')"></span>

                                    <span class="group inline-flex items-center gap-1 align-middle">
                                        <input id="question-37" v-model="answers.q37" type="text" placeholder="37"
                                               class="mx-2 mt-2 w-40 border border-gray-300 px-3 py-0.5 text-center text-base font-medium uppercase transition-colors placeholder:font-bold placeholder:text-gray-500 focus:border-black focus:outline-none sm:text-base"
                                               spellcheck="false" autocomplete="off" />
                                        <button @click.stop="emit('toggleFlag', 37)"
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-200"
                                                :class="isQuestionFlagged(37) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-0 group-hover:opacity-100 hover:border-gray-400 hover:text-gray-500'"
                                                :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>

                                    <span data-segment-id="flowchart-5"
                                          v-html="getHighlightedSegmentById('flowchart-5')"></span>
                                </p>
                            </div>

                            <!-- Questions 38-40 MCQ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span data-segment-id="q38-40-title"
                                                  v-html="getHighlightedSegmentById('q38-40-title')"></span>
                                        </h3>
                                    </div>
                                    <div class="">
                                        <p
                                            class="mb-4 text-base leading-relaxed font-medium text-gray-800 sm:text-base">
                                            <span data-segment-id="q38-40-instructions"
                                                  v-html="getHighlightedSegmentById('q38-40-instructions')"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <!-- Q38 -->
                                    <div id="question-38" class="group/q38">
                                        <div class="text-base sm:text-base">
                                            <p class="mb-3 text-gray-700 flex items-start gap-2">
                                                <span class="flex-1">
                                                    <span class="font-bold select-text" data-segment-id="q38-num"
                                                          v-html="getHighlightedSegmentById('q38-num')"></span>
                                                    <span class="select-text" data-segment-id="q38-q"
                                                          v-html="getHighlightedSegmentById('q38-q')"></span>
                                                </span>
                                                <button @click.stop="emit('toggleFlag', 38)"
                                                        class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                            opacity-5 group-hover/q38:opacity-100"
                                                        :class="isQuestionFlagged(38)
                                                            ? 'border-red-400 text-red-500 opacity-100!'
                                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </p>
                                            <div class="space-y-3">
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q38" type="radio" value="A"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q38-a"
                                                          v-html="getHighlightedSegmentById('q38-a')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q38" type="radio" value="B"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q38-b"
                                                          v-html="getHighlightedSegmentById('q38-b')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q38" type="radio" value="C"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q38-c"
                                                          v-html="getHighlightedSegmentById('q38-c')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q38" type="radio" value="D"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q38-d"
                                                          v-html="getHighlightedSegmentById('q38-d')"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Q39 -->
                                    <div id="question-39" class="group/q39">
                                        <div class="text-base sm:text-base">
                                            <p class="mb-3 text-gray-700 flex items-start gap-2">
                                                <span class="flex-1">
                                                    <span class="font-bold select-text" data-segment-id="q39-num"
                                                          v-html="getHighlightedSegmentById('q39-num')"></span>
                                                    <span class="select-text" data-segment-id="q39-q"
                                                          v-html="getHighlightedSegmentById('q39-q')"></span>
                                                </span>
                                                <button @click.stop="emit('toggleFlag', 39)"
                                                        class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                            opacity-5 group-hover/q39:opacity-100"
                                                        :class="isQuestionFlagged(39)
                                                            ? 'border-red-400 text-red-500 opacity-100!'
                                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </p>
                                            <div class="space-y-3">
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q39" type="radio" value="A"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q39-a"
                                                          v-html="getHighlightedSegmentById('q39-a')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q39" type="radio" value="B"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q39-b"
                                                          v-html="getHighlightedSegmentById('q39-b')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q39" type="radio" value="C"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q39-c"
                                                          v-html="getHighlightedSegmentById('q39-c')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q39" type="radio" value="D"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q39-d"
                                                          v-html="getHighlightedSegmentById('q39-d')"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Q40 -->
                                    <div id="question-40" class="group/q40">
                                        <div class="text-base sm:text-base">
                                            <p class="mb-3 text-gray-700 flex items-start gap-2">
                                                <span class="flex-1">
                                                    <span class="font-bold select-text" data-segment-id="q40-num"
                                                          v-html="getHighlightedSegmentById('q40-num')"></span>
                                                    <span class="select-text" data-segment-id="q40-q"
                                                          v-html="getHighlightedSegmentById('q40-q')"></span>
                                                </span>
                                                <button @click.stop="emit('toggleFlag', 40)"
                                                        class="shrink-0 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                            opacity-5 group-hover/q40:opacity-100"
                                                        :class="isQuestionFlagged(40)
                                                            ? 'border-red-400 text-red-500 opacity-100!'
                                                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </p>
                                            <div class="space-y-3">
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q40" type="radio" value="A"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q40-a"
                                                          v-html="getHighlightedSegmentById('q40-a')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q40" type="radio" value="B"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q40-b"
                                                          v-html="getHighlightedSegmentById('q40-b')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q40" type="radio" value="C"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q40-c"
                                                          v-html="getHighlightedSegmentById('q40-c')"></span>
                                                </label>
                                                <label
                                                    class="flex cursor-pointer items-center space-x-3 hover:bg-gray-50 p-1 rounded">
                                                    <input v-model="answers.q40" type="radio" value="D"
                                                           class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text" data-segment-id="q40-d"
                                                          v-html="getHighlightedSegmentById('q40-d')"></span>
                                                </label>
                                            </div>
                                        </div>
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
        </Teleport>

        <!-- Delete Highlight Tooltip -->
        <Teleport to="body">
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
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path
                                    d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2">
                                </path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Note Hover Tooltip -->
        <Teleport to="body">
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
        </Teleport>

        <!-- Note Input Modal -->
        <Teleport to="body">
            <div v-if="showNoteInput"
                 class="fixed z-[9999] w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-xl"
                 :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                 @click.stop>
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{
                            selectedText }}"</p>
                    <input v-model="noteInputText" type="text" placeholder="Write your note here..."
                           class="note-input-field w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none"
                           @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote"
                            class="rounded border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</button>
                    <button @click="saveNote"
                            class="rounded bg-gray-900 px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save
                        Note</button>
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
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1);
    }

    50% {
        background-color: rgba(0, 0, 0, 0.2);
        transform: scale(1.02);
    }

    100% {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1);
    }
}

/* Scrollbar */
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

/* Tooltip arrows */
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

.note-hover-tooltip .note-tooltip-text {
    line-height: 1.4;
}
</style>

<!-- Non-scoped: v-html note indicators -->
<style>
.note-highlight {
    border-bottom: 2px solid rgba(0, 0, 0, 0.4);
    cursor: help;
    position: relative;
    display: inline;
}

.note-highlight::after {
    content: '📝';
    display: inline-block;
    margin-left: 2px;
    font-size: 0.7em;
    vertical-align: super;
    line-height: 0;
    opacity: 0.9;
    pointer-events: none;
}

.note-highlight:hover {
    border-bottom-color: #000;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
